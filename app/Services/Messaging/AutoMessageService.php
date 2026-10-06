<?php

namespace App\Services\Messaging;

use App\Models\MessagingSetting;
use App\Models\SchoolInformation;
use App\Models\Schoolsession;
use App\Models\Schoolterm;
use App\Models\Student;
use App\Services\Billing\StudentFeeStatementService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Automatic messages to parents:
 *   absence  — after attendance is taken, parents of students marked absent
 *   fees     — on dates the bursar sets, debtors with their balance
 *   birthday — on the student's birthday
 *
 * Settings: Notices › Automatic messages (messaging_settings rows auto_*).
 * Runs from `messages:auto` every few minutes; each message is logged in
 * auto_messages and can never be sent twice (type + key + channel + contact).
 */
class AutoMessageService
{
    public const TYPES = ['absence', 'fees', 'birthday'];

    public const PLACEHOLDERS = [
        'absence'  => ['{parent_name}', '{student_name}', '{class}', '{date}', '{status}', '{school_name}'],
        'fees'     => ['{parent_name}', '{student_name}', '{class}', '{balance}', '{term_balance}', '{arrears}', '{term}', '{session}', '{pay_link}', '{next_due}', '{school_name}'],
        'birthday' => ['{parent_name}', '{student_name}', '{first_name}', '{age}', '{school_name}'],
    ];

    public const DEFAULTS = [
        'absence' => [
            'channels' => ['sms'], 'time' => '10:00', 'days' => [1, 2, 3, 4, 5], 'statuses' => ['absent'], 'period' => 'morning',
            'message'  => "Dear {parent_name},\n\n{student_name} ({class}) was marked {status} at school today, {date}. If you were not aware, please contact the school.\n\nThank you.\n{school_name}",
            'sms_text' => "Dear {parent_name}, {student_name} was marked {status} at school today ({date}). Please contact the school if unaware. - {school_name}",
        ],
        'fees' => [
            'channels' => ['sms'], 'dates' => [], 'min_balance' => 1000, 'include_arrears' => true,
            'message'  => "Dear {parent_name},\n\nThis is a reminder that {student_name} ({class}) has an outstanding balance of {balance} for {term}, {session}.\n\nYou can pay online through the student portal: {pay_link}\n\nPlease ignore this message if you have paid recently.\n{school_name}",
            'sms_text' => "Dear {parent_name}, {student_name} has an outstanding fee balance of {balance} ({term}). Pay online: {pay_link} - {school_name}",
        ],
        'birthday' => [
            'channels' => ['sms'], 'time' => '07:00', 'audience' => 'parents',
            'message'  => "Dear {parent_name},\n\nHappy birthday to {first_name}! Everyone at {school_name} wishes {first_name} a wonderful day and a year full of success.\n\nWarm regards,\n{school_name}",
            'sms_text' => "Happy birthday to {first_name} from all of us at {school_name}! Wishing a wonderful year ahead.",
        ],
    ];

    public function __construct(
        protected MessagingService $messaging,
        protected NoticeAudienceService $audience,
        protected StudentFeeStatementService $statements,
    ) {}

    public static function settings(string $type): MessagingSetting
    {
        $s = MessagingSetting::firstOrCreate(
            ['channel' => 'auto_' . $type],
            ['driver' => 'auto', 'is_active' => false, 'config' => self::DEFAULTS[$type]]
        );
        $s->config = array_merge(self::DEFAULTS[$type], $s->config ?? []);
        return $s;
    }

    // ─────────────────────────────────────────────────────────────────────
    // Scheduler entry point
    // ─────────────────────────────────────────────────────────────────────

    public function runDue(): array
    {
        $done = [];
        $now  = now();

        $a = self::settings('absence');
        if ($a->is_active && in_array((int) $now->isoWeekday(), array_map('intval', $a->config['days'] ?? []), true)
            && $now->format('H:i') >= ($a->config['time'] ?? '10:00') && ($a->config['last_run'] ?? null) !== $now->toDateString()) {
            $done['absence'] = $this->send('absence', ['date' => $now->toDateString()]);
            $this->mark($a, ['last_run' => $now->toDateString()]);
        }

        $f = self::settings('fees');
        if ($f->is_active) {
            foreach ($f->config['dates'] ?? [] as $d) {
                $key = ($d['date'] ?? '') . ' ' . ($d['time'] ?? '08:00');
                if (($d['date'] ?? '') === $now->toDateString() && $now->format('H:i') >= ($d['time'] ?? '08:00')
                    && !in_array($key, $f->config['done'] ?? [], true)) {
                    $done['fees'] = $this->send('fees', ['run' => $key]);
                    $this->mark($f, ['done' => array_values(array_unique(array_merge(self::settings('fees')->config['done'] ?? [], [$key])))]);
                }
            }
        }

        $b = self::settings('birthday');
        if ($b->is_active && $now->format('H:i') >= ($b->config['time'] ?? '07:00') && ($b->config['last_run'] ?? null) !== $now->toDateString()) {
            $done['birthday'] = $this->send('birthday', ['date' => $now->toDateString()]);
            $this->mark($b, ['last_run' => $now->toDateString()]);
        }

        return $done;
    }

    protected function mark(MessagingSetting $s, array $values): void
    {
        $fresh = MessagingSetting::find($s->id);
        $fresh->config = array_merge($fresh->config ?? [], $values);
        $fresh->save();
    }

    // ─────────────────────────────────────────────────────────────────────
    // Build + preview + send
    // ─────────────────────────────────────────────────────────────────────

    /** Targets: [student_id => ['vars' => [...], 'key' => dedupe key]] */
    public function targets(string $type, array $ctx = []): array
    {
        return match ($type) {
            'absence'  => $this->absenceTargets($ctx['date'] ?? now()->toDateString()),
            'fees'     => $this->feeTargets($ctx['run'] ?? now()->format('Y-m-d H:i')),
            'birthday' => $this->birthdayTargets($ctx['date'] ?? now()->toDateString()),
            default    => [],
        };
    }

    public function preview(string $type, array $ctx = []): array
    {
        $cfg      = self::settings($type)->config;
        $channels = array_values(array_intersect($cfg['channels'] ?? [], ['sms', 'whatsapp', 'email']));
        $targets  = $this->targets($type, $ctx);
        $messages = $this->messages($type, $targets, $channels, $cfg);

        $byChannel = collect($messages)->groupBy('channel')->map->count();
        $smsUnits  = collect($messages)->where('channel', 'sms')->sum(fn ($m) => MessagingService::smsPages($m['body'])['pages']);

        return [
            'students' => count($targets),
            'messages' => $byChannel->all(),
            'sample'   => collect($messages)->first()['body'] ?? null,
            'sms'      => $smsUnits ? $this->messaging->smsEstimate($smsUnits) : null,
            'list'     => collect($targets)->take(30)->map(fn ($t) => $t['label'])->values(),
            'enabled'  => collect($channels)->mapWithKeys(fn ($c) => [$c => $this->messaging->enabled($c)]),
        ];
    }

    public function send(string $type, array $ctx = []): array
    {
        @set_time_limit(0);
        $cfg      = self::settings($type)->config;
        $channels = array_values(array_filter(array_intersect($cfg['channels'] ?? [], ['sms', 'whatsapp', 'email']), fn ($c) => $this->messaging->enabled($c)));
        if (!$channels) return ['sent' => 0, 'failed' => 0, 'skipped' => 0];

        $targets  = $this->targets($type, $ctx);
        $messages = $this->messages($type, $targets, $channels, $cfg);
        $stats    = ['sent' => 0, 'failed' => 0, 'skipped' => 0];

        // In-portal bell for the student as well (free; once per key).
        foreach ($targets as $sid => $t) {
            [$title, $url] = match ($type) {
                'absence'  => ['Marked ' . ($t['vars']['{status}'] ?? 'absent') . ' today', null],
                'fees'     => ['Fee reminder: ' . ($t['vars']['{balance}'] ?? '') . ' outstanding', route('student.fees.pay')],
                default    => ['Happy birthday, ' . ($t['vars']['{first_name}'] ?? '') . '! 🎉', null],
            };
            $body = trim(strtr($cfg['message'], $t['vars'] + ['{parent_name}' => 'Parent', '{school_name}' => $this->schoolName()]));
            PortalNotifier::toStudents([$sid], $title, $body, $url, $type, $t['key']);
        }

        foreach ($messages as $m) {
            $claimed = DB::table('auto_messages')->insertOrIgnore([
                'type' => $type, 'dedupe_key' => mb_substr($m['key'], 0, 120), 'student_id' => $m['student_id'],
                'channel' => $m['channel'], 'recipient' => $m['to'], 'recipient_name' => mb_substr((string) $m['name'], 0, 190),
                'body' => $m['body'], 'status' => 'queued', 'created_at' => now(), 'updated_at' => now(),
            ]);
            if (!$claimed) { $stats['skipped']++; continue; } // already sent before

            $r = $this->messaging->send($m['channel'], $m['to'], $m['body'], ['name' => $m['name'], 'subject' => $m['subject']]);
            DB::table('auto_messages')->where(['type' => $type, 'dedupe_key' => mb_substr($m['key'], 0, 120), 'channel' => $m['channel'], 'recipient' => $m['to']])
                ->update(['status' => $r['status'], 'error' => $r['error'] ? mb_substr($r['error'], 0, 490) : null,
                          'provider_message_id' => $r['id'] ?: null, 'updated_at' => now()]);
            $stats[$r['status'] === 'sent' ? 'sent' : ($r['status'] === 'failed' ? 'failed' : 'skipped')]++;
        }

        Log::info("Auto messages ({$type})", $stats + ['students' => count($targets)]);
        return $stats;
    }

    /** One message per student × contact × channel. */
    protected function messages(string $type, array $targets, array $channels, array $cfg): array
    {
        if (!$targets || !$channels) return [];

        $school = $this->schoolName();
        $out = [];

        $wantParents  = $type !== 'birthday' || in_array($cfg['audience'] ?? 'parents', ['parents', 'both'], true);
        $wantStudents = $type === 'birthday' && in_array($cfg['audience'] ?? 'parents', ['students', 'both'], true);

        $contacts = [];
        if ($wantParents) {
            $res = $this->audience->resolve(['scope' => 'students', 'student_ids' => array_keys($targets)], $channels);
            foreach ($res['contacts'] as $ch => $list) {
                foreach ($list as $c) {
                    foreach ($c['students'] as $sid) {
                        $contacts[$sid][$ch][$c['to']] = $c['name'] ?: 'Parent';
                    }
                }
            }
        }
        if ($wantStudents) {
            foreach (DB::table('studentRegistration')->whereIn('id', array_keys($targets))->get(['id', 'firstname', 'phone_number', 'email']) as $s) {
                foreach ($channels as $ch) {
                    $to = $ch === 'email'
                        ? (filter_var($s->email, FILTER_VALIDATE_EMAIL) ? strtolower($s->email) : null)
                        : MessagingService::normalizePhone($s->phone_number);
                    if ($to) $contacts[$s->id][$ch][$to] = $contacts[$s->id][$ch][$to] ?? $s->firstname;
                }
            }
        }

        foreach ($targets as $sid => $t) {
            foreach ($contacts[$sid] ?? [] as $ch => $list) {
                foreach ($list as $to => $name) {
                    $tpl = $ch === 'sms' && trim((string) ($cfg['sms_text'] ?? '')) !== '' ? $cfg['sms_text'] : $cfg['message'];
                    $out[] = [
                        'student_id' => $sid, 'channel' => $ch, 'to' => $to, 'name' => $name, 'key' => $t['key'],
                        'subject' => $t['subject'],
                        'body' => trim(strtr($tpl, $t['vars'] + ['{parent_name}' => $name, '{school_name}' => $school])),
                    ];
                }
            }
        }
        return $out;
    }

    // ── targets ─────────────────────────────────────────────────────────

    protected function absenceTargets(string $date): array
    {
        $cfg = self::settings('absence')->config;
        $statuses = array_values(array_intersect($cfg['statuses'] ?? ['absent'], ['absent', 'late', 'sick_leave', 'excused']));

        $rows = DB::table('student_attendance as sa')
            ->join('studentRegistration as s', 's.id', '=', 'sa.student_id')
            ->leftJoin('schoolclass as c', 'c.id', '=', 'sa.schoolclass_id')
            ->leftJoin('schoolarm as a', 'a.id', '=', 'c.arm')
            ->whereDate('sa.attendance_date', $date)
            ->whereIn('sa.status', $statuses ?: ['absent'])
            ->when(($cfg['period'] ?? 'morning') === 'morning', fn ($q) => $q->where('sa.period', 'morning'))
            ->get(['s.id', 's.firstname', 's.lastname', 'sa.status', DB::raw("TRIM(CONCAT(COALESCE(c.schoolclass,''), ' ', COALESCE(a.arm,''))) as class_name")]);

        $labels = ['absent' => 'absent', 'late' => 'late', 'sick_leave' => 'on sick leave', 'excused' => 'excused'];
        $out = [];
        foreach ($rows as $r) {
            $out[$r->id] = [
                'key'     => "absence:{$date}:{$r->id}",
                'label'   => trim($r->lastname . ' ' . $r->firstname) . ' — ' . ($labels[$r->status] ?? $r->status),
                'subject' => trim($r->firstname . ' ' . $r->lastname) . ' — attendance ' . Carbon::parse($date)->format('j M Y'),
                'vars'    => [
                    '{student_name}' => trim($r->firstname . ' ' . $r->lastname),
                    '{class}'        => $r->class_name,
                    '{date}'         => Carbon::parse($date)->format('D, j M Y'),
                    '{status}'       => $labels[$r->status] ?? $r->status,
                ],
            ];
        }
        return $out;
    }

    protected function feeTargets(string $runKey): array
    {
        $cfg     = self::settings('fees')->config;
        $session = Schoolsession::where('status', 'Current')->first(['id', 'session']);
        if (!$session) return [];
        $min     = (float) ($cfg['min_balance'] ?? 0);
        $arrearsOn = !empty($cfg['include_arrears']);

        $placements = DB::table('studentclass as sc')
            ->join('studentRegistration as s', 's.id', '=', 'sc.studentId')
            ->where('sc.sessionid', $session->id)
            ->whereRaw("LOWER(COALESCE(s.student_status, 'active')) = 'active'")
            ->groupBy('sc.studentId')
            ->select('sc.studentId as id', DB::raw('MAX(sc.termid) as term_id'))
            ->get();

        $terms = Schoolterm::pluck('term', 'id');
        $payLink = route('student.fees.pay');
        $money = fn ($v) => '₦' . number_format((float) $v, 2);
        $out = [];

        foreach ($placements as $p) {
            $student = Student::find($p->id);
            if (!$student) continue;
            try {
                $st = $this->statements->buildStatement($student, (int) $p->term_id, (int) $session->id);
            } catch (\Throwable $e) {
                continue;
            }
            $termOwed = (float) ($st['totals']['outstanding'] ?? 0);
            $arrears  = $arrearsOn ? (float) ($st['arrears']['total_arrears'] ?? 0) : 0.0;

            // On an instalment plan: only the overdue part of this term counts.
            $plan = \App\Services\Billing\InstalmentPlanService::available()
                ? app(\App\Services\Billing\InstalmentPlanService::class)->schedule(
                    (int) $student->id, (int) $p->term_id, (int) $session->id,
                    (float) ($st['totals']['adjusted'] ?? 0), (float) ($st['totals']['paid'] ?? 0), (int) ($st['class']->id ?? 0) ?: null)
                : null;
            if ($plan) $termOwed = (float) $plan['overdue'];
            $total    = round($termOwed + $arrears, 2);
            if ($total <= 0.009 || $total < $min) continue;

            $out[$student->id] = [
                'key'     => "fees:{$runKey}:{$student->id}",
                'label'   => trim($student->lastname . ' ' . $student->firstname) . ' — ' . $money($total),
                'subject' => 'Fee reminder — ' . trim($student->firstname . ' ' . $student->lastname),
                'vars'    => [
                    '{student_name}' => trim($student->firstname . ' ' . $student->lastname),
                    '{class}'        => $st['class']->schoolclass ?? '',
                    '{balance}'      => $money($total),
                    '{term_balance}' => $money($termOwed),
                    '{arrears}'      => $money($arrears),
                    '{term}'         => $terms[$p->term_id] ?? '',
                    '{session}'      => $session->session,
                    '{pay_link}'     => $payLink,
                    '{next_due}'     => $plan && $plan['next'] ? $money($plan['next']['amount']) . ' by ' . $plan['next']['due_date'] : '',
                ],
            ];
        }
        return $out;
    }

    protected function birthdayTargets(string $date): array
    {
        $today = Carbon::parse($date);
        $out = [];
        $rows = DB::table('studentRegistration')
            ->whereRaw("LOWER(COALESCE(student_status, 'active')) = 'active'")
            ->whereNotNull('dateofbirth')->where('dateofbirth', '!=', '')
            ->get(['id', 'firstname', 'lastname', 'dateofbirth']);

        foreach ($rows as $r) {
            $dob = $this->parseDate($r->dateofbirth);
            if (!$dob) continue;
            $md = $dob->format('m-d');
            $match = $md === $today->format('m-d')
                || ($md === '02-29' && !$today->isLeapYear() && $today->format('m-d') === '02-28');
            if (!$match) continue;

            $age = $dob->year > 1900 && $dob->year < $today->year ? $today->year - $dob->year : null;
            $out[$r->id] = [
                'key'     => 'birthday:' . $today->year . ':' . $r->id,
                'label'   => trim($r->lastname . ' ' . $r->firstname) . ($age ? " — turns {$age}" : ''),
                'subject' => 'Happy birthday, ' . $r->firstname . '!',
                'vars'    => [
                    '{student_name}' => trim($r->firstname . ' ' . $r->lastname),
                    '{first_name}'   => $r->firstname,
                    '{age}'          => $age ? (string) $age : '',
                ],
            ];
        }
        return $out;
    }

    protected function parseDate(?string $v): ?Carbon
    {
        $v = trim((string) $v);
        if ($v === '') return null;
        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'm/d/Y', 'd.m.Y', 'Y/m/d', 'd M Y', 'j M Y', 'd F Y'] as $fmt) {
            try {
                $d = Carbon::createFromFormat('!' . $fmt, $v);
                if ($d && $d->format($fmt) === $v) return $d;
            } catch (\Throwable $e) {
            }
        }
        try {
            return Carbon::parse($v);
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function schoolName(): string
    {
        $s = SchoolInformation::getActiveSchool() ?? SchoolInformation::first();
        return $s->school_name ?? config('app.name', 'School');
    }
}
