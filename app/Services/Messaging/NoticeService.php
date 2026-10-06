<?php

namespace App\Services\Messaging;

use App\Models\NoticeDelivery;
use App\Models\NoticeDispatch;
use App\Models\SchoolInformation;
use App\Models\SchoolNotice;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Notice lifecycle: templates → preview → schedule (initial send + reminders)
 * → build deliveries from the audience at send time → send → report.
 *
 * Sending runs inline (after the HTTP response for "send now", or from the
 * `notices:dispatch` scheduler command), so it works on shared hosting
 * without a long-running queue worker. Each delivery is claimed atomically,
 * so two runs can never send the same message twice.
 */
class NoticeService
{
    public const PLACEHOLDERS = [
        '{parent_name}'    => 'Parent / staff name',
        '{student_name}'   => 'Child\'s first name(s)',
        '{class}'          => 'Class and arm',
        '{title}'          => 'Notice title',
        '{event_date}'     => 'Event date',
        '{event_end_date}' => 'End date',
        '{event_time}'     => 'Time',
        '{school_name}'    => 'School name',
    ];

    public function __construct(
        protected NoticeAudienceService $audience,
        protected MessagingService $messaging,
    ) {}

    // ─────────────────────────────────────────────────────────────────────
    // Templates & rendering
    // ─────────────────────────────────────────────────────────────────────

    public static function templates(): array
    {
        return [
            'ca_test'    => ['title' => 'Continuous Assessment Test',
                'message' => "Dear {parent_name},\n\nThis is to inform you that the continuous assessment (CA) test for {student_name} ({class}) begins on {event_date}. Please ensure your ward prepares well and arrives early.\n\nThank you.\n{school_name}",
                'sms' => "Dear {parent_name}, {student_name}'s CA test ({class}) begins {event_date}. Please ensure adequate preparation. - {school_name}"],
            'exam'       => ['title' => 'Examinations',
                'message' => "Dear {parent_name},\n\nThe examinations for {student_name} ({class}) start on {event_date} and end on {event_end_date}. Please ensure your ward is punctual and fully prepared, and that all outstanding fees are cleared.\n\nThank you.\n{school_name}",
                'sms' => "Dear {parent_name}, exams for {student_name} ({class}) run {event_date} - {event_end_date}. Please ensure punctuality. - {school_name}"],
            'mock'       => ['title' => 'Mock Examinations',
                'message' => "Dear {parent_name},\n\nThe mock examinations for {student_name} ({class}) begin on {event_date}. Kindly encourage your ward to prepare well.\n\nThank you.\n{school_name}",
                'sms' => "Dear {parent_name}, mock exams for {student_name} ({class}) begin {event_date}. - {school_name}"],
            'midterm'    => ['title' => 'Midterm Break',
                'message' => "Dear {parent_name},\n\nThe school will go on midterm break from {event_date} to {event_end_date}. Classes resume the next school day after the break.\n\nThank you.\n{school_name}",
                'sms' => "Dear {parent_name}, midterm break runs {event_date} - {event_end_date}. - {school_name}"],
            'holiday'    => ['title' => 'Public Holiday',
                'message' => "Dear {parent_name},\n\nPlease note that the school will be closed on {event_date} for the public holiday ({title}).\n\nThank you.\n{school_name}",
                'sms' => "Dear {parent_name}, school is closed on {event_date} for {title}. - {school_name}"],
            'resumption' => ['title' => 'Resumption',
                'message' => "Dear {parent_name},\n\nSchool resumes on {event_date}. Please ensure {student_name} resumes promptly with all required materials.\n\nThank you.\n{school_name}",
                'sms' => "Dear {parent_name}, school resumes {event_date}. Please ensure {student_name} resumes promptly. - {school_name}"],
            'vacation'   => ['title' => 'End of Term',
                'message' => "Dear {parent_name},\n\nThe term ends on {event_date}. Report cards will be available on the portal. We wish you a restful holiday.\n\n{school_name}",
                'sms' => "Dear {parent_name}, term ends {event_date}. Results will be on the portal. - {school_name}"],
            'meeting'    => ['title' => 'PTA Meeting',
                'message' => "Dear {parent_name},\n\nYou are invited to the {title} on {event_date} at {event_time}. Your presence is highly valued.\n\nThank you.\n{school_name}",
                'sms' => "Dear {parent_name}, you are invited to {title} on {event_date}, {event_time}. - {school_name}"],
            'fees'       => ['title' => 'School Fees Reminder',
                'message' => "Dear {parent_name},\n\nThis is a reminder that school fees for {student_name} ({class}) are due by {event_date}. You can pay online through the student portal.\n\nThank you.\n{school_name}",
                'sms' => "Dear {parent_name}, fees for {student_name} are due by {event_date}. Pay online via the portal. - {school_name}"],
            'event'      => ['title' => 'School Event',
                'message' => "Dear {parent_name},\n\nWe are pleased to invite you to {title} on {event_date} at {event_time}.\n\nThank you.\n{school_name}",
                'sms' => "Dear {parent_name}, you're invited to {title} on {event_date}, {event_time}. - {school_name}"],
            'general'    => ['title' => '',
                'message' => "Dear {parent_name},\n\n\n\nThank you.\n{school_name}",
                'sms' => "Dear {parent_name}, - {school_name}"],
        ];
    }

    public function schoolName(): string
    {
        $s = SchoolInformation::getActiveSchool() ?? SchoolInformation::first();
        return $s->school_name ?? config('app.name', 'School');
    }

    public function render(string $tpl, SchoolNotice $n, array $contact): string
    {
        $staff = ($contact['type'] ?? 'parent') === 'staff';
        $names = array_values(array_unique($contact['student_names'] ?? []));
        $join  = fn (array $xs) => count($xs) <= 1 ? ($xs[0] ?? '') : implode(', ', array_slice($xs, 0, -1)) . ' and ' . end($xs);
        $date  = fn ($d) => $d ? Carbon::parse($d)->format('D, j M Y') : '';

        $vars = [
            '{parent_name}'    => $contact['name'] ?: ($staff ? 'Colleague' : 'Parent'),
            '{student_name}'   => $staff ? 'our students' : ($join($names) ?: 'your ward'),
            '{class}'          => $staff ? 'all classes' : ($join($contact['classes'] ?? []) ?: 'their class'),
            '{title}'          => $n->title,
            '{event_date}'     => $date($n->event_date),
            '{event_end_date}' => $date($n->event_end_date ?: $n->event_date),
            '{event_time}'     => (string) $n->event_time,
            '{school_name}'    => $this->schoolName(),
        ];

        $out = strtr($tpl, $vars);
        return trim(preg_replace("/[ \t]+\n/", "\n", preg_replace('/\(\s*\)/', '', $out)));
    }

    /** The text used on a channel: SMS uses the short version when present. */
    public function bodyFor(SchoolNotice $n, string $channel, array $contact): string
    {
        $tpl = $channel === 'sms' && trim((string) $n->sms_text) !== '' ? $n->sms_text : $n->message;
        return $this->render($tpl, $n, $contact);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Preview
    // ─────────────────────────────────────────────────────────────────────

    public function preview(SchoolNotice $n): array
    {
        $r = $this->audience->resolve($n->audience ?? [], $n->channels ?? []);
        $out = ['students' => $r['students'], 'staff' => $r['staff'], 'channels' => []];

        if (in_array('portal', $n->channels ?? [], true)) {
            $ids = $this->audience->studentIds($n->audience ?? []);
            $accounts = $ids ? \App\Models\User::whereIn('student_id', $ids)->count() : 0;
            $staff = !empty($n->audience['include_staff']) ? count(PortalNotifier::staffIds()) : 0;
            $out['channels']['portal'] = [
                'recipients' => $accounts + $staff, 'enabled' => PortalNotifier::available(), 'live' => true,
                'sample' => $this->render($n->message, $n, ['name' => 'Parent/Student', 'type' => 'parent', 'student_names' => [], 'classes' => []]),
                'sms' => null, 'sms_total' => null, 'estimate' => null,
                'missing' => max(0, count($ids) - $accounts), 'missing_list' => [],
            ];
        }

        foreach ($r['contacts'] as $ch => $list) {
            $first  = reset($list) ?: ['name' => 'Mrs Adeyemi', 'type' => 'parent', 'student_names' => ['Tolu'], 'classes' => ['JSS 1 A']];
            $sample = $this->bodyFor($n, $ch, $first);
            $pages  = $ch === 'sms' ? MessagingService::smsPages($sample) : null;
            $out['channels'][$ch] = [
                'recipients' => count($list),
                'enabled'    => $this->messaging->enabled($ch),
                'live'       => $this->messaging->setting($ch)->isLive(),
                'sample'     => $sample,
                'sms'        => $pages,
                'sms_total'  => $pages ? $pages['pages'] * count($list) : null,
                'estimate'   => $pages ? $this->messaging->smsEstimate($pages['pages'] * count($list)) : null,
                'missing'    => count($r['missing'][$ch] ?? []),
                'missing_list' => array_slice($r['missing'][$ch] ?? [], 0, 50),
            ];
        }
        return $out;
    }

    // ─────────────────────────────────────────────────────────────────────
    // Scheduling
    // ─────────────────────────────────────────────────────────────────────

    /** Create the dispatches (initial + reminders) for a notice being sent or scheduled. */
    public function schedule(SchoolNotice $n, ?Carbon $sendAt): array
    {
        return DB::transaction(function () use ($n, $sendAt) {
            $n->dispatches()->where('status', 'pending')->update(['status' => 'cancelled']);

            $now  = now();
            $when = $sendAt && $sendAt->gt($now) ? $sendAt : $now;
            $created = [NoticeDispatch::create([
                'school_notice_id' => $n->id, 'kind' => 'initial',
                'label' => $when->equalTo($now) ? 'Sent immediately' : 'Scheduled send', 'run_at' => $when,
            ])];

            if ($n->event_date) {
                foreach ($n->reminders ?? [] as $r) {
                    $days = (int) ($r['days'] ?? 0);
                    $time = preg_match('/^\d{2}:\d{2}$/', (string) ($r['time'] ?? '')) ? $r['time'] : '08:00';
                    $at   = Carbon::parse($n->event_date->format('Y-m-d') . ' ' . $time)->subDays($days);
                    if ($at->lte($when->copy()->addMinutes(5))) continue; // would clash with / precede the first send
                    $created[] = NoticeDispatch::create([
                        'school_notice_id' => $n->id, 'kind' => 'reminder', 'run_at' => $at,
                        'label' => $days === 0 ? 'Reminder on the day' : "Reminder {$days} day" . ($days > 1 ? 's' : '') . ' before',
                    ]);
                }
            }

            $n->update([
                'status'  => $when->equalTo($now) ? 'sending' : 'scheduled',
                'send_at' => $when,
            ]);

            return $created;
        });
    }

    public function cancel(SchoolNotice $n): void
    {
        $n->dispatches()->where('status', 'pending')->update(['status' => 'cancelled']);
        $n->update(['status' => $n->dispatches()->where('status', 'done')->exists() ? 'sent' : 'cancelled']);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Processing
    // ─────────────────────────────────────────────────────────────────────

    /** Run every due dispatch, and retry messages that failed with a temporary error. */
    public function runDue(int $limit = 20): int
    {
        $ran = 0;
        NoticeDispatch::where('status', 'pending')->where('run_at', '<=', now())
            ->orderBy('run_at')->limit($limit)->pluck('id')
            ->each(function ($id) use (&$ran) { if ($this->process((int) $id)) $ran++; });

        // Picks up messages left behind by an interrupted run.
        NoticeDispatch::where('status', 'processing')->where('started_at', '<', now()->subMinutes(10))
            ->pluck('id')->each(fn ($id) => $this->sendQueued((int) $id));

        return $ran;
    }

    public function process(int $dispatchId): bool
    {
        @set_time_limit(0);

        // Claim atomically: only one process can move pending → processing.
        $claimed = NoticeDispatch::whereKey($dispatchId)->where('status', 'pending')
            ->update(['status' => 'processing', 'started_at' => now()]);
        if (!$claimed) return false;

        $d = NoticeDispatch::with('notice')->find($dispatchId);
        $n = $d->notice;

        if (!$n || $n->status === 'cancelled') {
            $d->update(['status' => 'cancelled', 'finished_at' => now()]);
            return false;
        }
        if ($n->status === 'scheduled') {
            $n->update(['status' => 'sending']);
        }

        try {
            $this->buildDeliveries($d, $n);
            if ($d->kind === 'initial') {
                $this->notifyPortal($n);
            }
            $this->sendQueued($d->id);
        } catch (\Throwable $e) {
            Log::error('Notice dispatch failed', ['dispatch' => $d->id, 'error' => $e->getMessage()]);
            $d->update(['status' => 'failed', 'error' => mb_substr($e->getMessage(), 0, 250), 'finished_at' => now()]);
        }
        $this->refreshNoticeStatus($n->fresh());
        return true;
    }

    /** In-portal bell for students (and staff) in the audience — once per notice. */
    public function notifyPortal(SchoolNotice $n): void
    {
        if (!in_array('portal', $n->channels ?? [], true) || !PortalNotifier::available()) return;

        $when  = $n->event_date ? ' · ' . $n->event_date->format('D j M Y') . ($n->event_end_date && !$n->event_end_date->eq($n->event_date) ? ' – ' . $n->event_end_date->format('D j M Y') : '') : '';
        $title = $n->title . $when;
        $key   = 'notice:' . $n->id;

        $ids = $this->audience->studentIds($n->audience ?? []);
        if ($ids) {
            $body = $this->render($n->message, $n, ['name' => 'Parent/Student', 'type' => 'parent', 'student_names' => [], 'classes' => []]);
            PortalNotifier::toStudents($ids, $title, $body, null, 'notice', $key);
        }
        if (!empty($n->audience['include_staff'])) {
            $body = $this->render($n->message, $n, ['name' => 'Colleague', 'type' => 'staff']);
            PortalNotifier::toUsers(PortalNotifier::staffIds(), $title, $body, null, 'notice', $key);
        }
    }

    /** Resolve the audience now (fresh contacts) and create one queued row per contact/channel. */
    public function buildDeliveries(NoticeDispatch $d, SchoolNotice $n): void
    {
        $r = $this->audience->resolve($n->audience ?? [], $n->channels ?? []);
        $now = now();

        foreach ($r['contacts'] as $ch => $list) {
            foreach (array_chunk($list, 200, true) as $chunk) {
                $rows = [];
                foreach ($chunk as $c) {
                    $rows[] = [
                        'notice_dispatch_id' => $d->id, 'school_notice_id' => $n->id,
                        'channel' => $ch, 'recipient' => $c['to'], 'recipient_name' => mb_substr((string) $c['name'], 0, 190),
                        'audience_type' => $c['type'], 'student_ids' => json_encode($c['students']),
                        'body' => $this->bodyFor($n, $ch, $c), 'status' => 'queued',
                        'created_at' => $now, 'updated_at' => $now,
                    ];
                }
                DB::table('notice_deliveries')->insertOrIgnore($rows);
            }
        }
        $this->refreshCounts($d);
    }

    /** Send every queued delivery of a dispatch. */
    public function sendQueued(int $dispatchId): void
    {
        @set_time_limit(0);
        $d = NoticeDispatch::with('notice')->find($dispatchId);
        if (!$d) return;
        $subject = $d->notice?->title ?: 'School notice';

        while (true) {
            $ids = NoticeDelivery::where('notice_dispatch_id', $d->id)->where('status', 'queued')
                ->orderBy('id')->limit(100)->pluck('id');
            if ($ids->isEmpty()) break;

            foreach ($ids as $id) {
                // Claim the row; skip if another run took it.
                if (!NoticeDelivery::whereKey($id)->where('status', 'queued')->update(['status' => 'sending', 'updated_at' => now()])) {
                    continue;
                }
                $del = NoticeDelivery::find($id);
                $res = $this->messaging->send($del->channel, $del->recipient, $del->body, [
                    'name' => $del->recipient_name, 'subject' => $subject,
                ]);
                $attempts = $del->attempts + 1;
                $status   = $res['status'] === 'failed' && $res['retryable'] && $attempts < 3 ? 'queued' : $res['status'];

                $del->update([
                    'status' => $status, 'attempts' => $attempts,
                    'error' => $res['error'] ? mb_substr($res['error'], 0, 490) : null,
                    'provider_message_id' => $res['id'] ?: $del->provider_message_id,
                    'sent_at' => $res['status'] === 'sent' ? now() : $del->sent_at,
                ]);
                if ($status === 'queued') {
                    sleep(2); // brief back-off before the retry
                }
            }
        }

        $this->refreshCounts($d);
        if (!NoticeDelivery::where('notice_dispatch_id', $d->id)->whereIn('status', ['queued', 'sending'])->exists()) {
            $d->update(['status' => 'done', 'finished_at' => now()]);
        }
        if ($d->notice) $this->refreshNoticeStatus($d->notice->fresh());
    }

    /** Put failed messages of a notice back in the queue as a new "resend" dispatch. */
    public function resendFailed(SchoolNotice $n): ?NoticeDispatch
    {
        $failed = NoticeDelivery::where('school_notice_id', $n->id)->where('status', 'failed')->get();
        if ($failed->isEmpty()) return null;

        $d = NoticeDispatch::create([
            'school_notice_id' => $n->id, 'kind' => 'resend', 'label' => 'Resend of failed messages',
            'run_at' => now(), 'status' => 'processing', 'started_at' => now(),
        ]);
        foreach ($failed as $f) {
            DB::table('notice_deliveries')->insertOrIgnore([
                'notice_dispatch_id' => $d->id, 'school_notice_id' => $n->id, 'channel' => $f->channel,
                'recipient' => $f->recipient, 'recipient_name' => $f->recipient_name, 'audience_type' => $f->audience_type,
                'student_ids' => json_encode($f->student_ids), 'body' => $f->body, 'status' => 'queued',
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        $failed->each->update(['status' => 'skipped', 'error' => 'Retried in resend #' . $d->id]);
        foreach (NoticeDispatch::where('school_notice_id', $n->id)->where('id', '!=', $d->id)->get() as $old) {
            $this->refreshCounts($old);
        }
        $this->refreshCounts($d);
        return $d;
    }

    /** Send the notice to the admin only (own phone / email), rendered as for a parent. */
    public function sendTest(SchoolNotice $n, User $user): array
    {
        $contact = ['name' => $user->name, 'type' => 'parent', 'student_names' => ['Tolu'], 'classes' => ['JSS 1 A']];
        $results = [];
        foreach (array_diff($n->channels ?? [], ['portal']) as $ch) {
            $to = $ch === 'email' ? $user->email : MessagingService::normalizePhone($user->phone_number ?? null);
            if (!$to) { $results[$ch] = ['status' => 'skipped', 'error' => $ch === 'email' ? 'Your account has no email address.' : 'Your account has no phone number.']; continue; }
            $results[$ch] = $this->messaging->send($ch, $to, '[TEST] ' . $this->bodyFor($n, $ch, $contact), ['name' => $user->name, 'subject' => '[TEST] ' . $n->title]) + ['to' => $to];
        }
        return $results;
    }

    // ── counters ────────────────────────────────────────────────────────

    public function refreshCounts(NoticeDispatch $d): void
    {
        $c = NoticeDelivery::where('notice_dispatch_id', $d->id)->selectRaw('status, COUNT(*) as n')->groupBy('status')->pluck('n', 'status');
        $d->update([
            'total'   => (int) $c->sum(),
            'sent'    => (int) ($c['sent'] ?? 0),
            'failed'  => (int) ($c['failed'] ?? 0),
            'skipped' => (int) ($c['skipped'] ?? 0),
        ]);
    }

    public function refreshNoticeStatus(SchoolNotice $n): void
    {
        if ($n->status === 'cancelled') return;
        $initialDone = $n->dispatches()->where('kind', 'initial')->whereIn('status', ['done', 'failed'])->exists();
        $busy        = $n->dispatches()->where('status', 'processing')->exists();
        $pending     = $n->dispatches()->where('status', 'pending')->exists();

        $status = $busy ? 'sending' : ($initialDone ? 'sent' : ($pending ? 'scheduled' : $n->status));
        $n->update(['status' => $status, 'sent_at' => $initialDone ? ($n->sent_at ?? now()) : $n->sent_at]);
    }
}
