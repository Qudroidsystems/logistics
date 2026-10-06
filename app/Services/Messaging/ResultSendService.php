<?php

namespace App\Services\Messaging;

use App\Http\Controllers\ViewStudentReportController;
use App\Models\ResultSend;
use App\Models\ResultSendDelivery;
use App\Models\ResultSendItem;
use App\Models\SchoolInformation;
use App\Models\Schoolsession;
use App\Models\Schoolterm;
use App\Services\ResultAccessService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Sends each student's report card to their parents:
 *   email    → PDF attached
 *   WhatsApp → PDF as a document (approved document template) or a secure link
 *   SMS      → secure download link
 *
 * Readiness is checked per student (results entered, optionally all vetted,
 * result access rules for fees). PDFs are generated and sent in batches from
 * `results:send` (scheduler) or right after "Send" is clicked.
 */
class ResultSendService
{
    public const PLACEHOLDERS = [
        '{parent_name}'  => 'Parent name',
        '{student_name}' => 'Student full name',
        '{class}'        => 'Class and arm',
        '{term}'         => 'Term',
        '{session}'      => 'Session',
        '{link}'         => 'Secure download link',
        '{school_name}'  => 'School name',
    ];

    public const DEFAULT_MESSAGE = "Dear {parent_name},\n\nPlease find attached {student_name}'s {term} report card for the {session} session ({class}).\n\nYou can also download it here: {link}\n\nThank you.\n{school_name}";
    public const DEFAULT_SMS     = "Dear {parent_name}, {student_name}'s {term} report card is ready. Download: {link} - {school_name}";

    public function __construct(
        protected MessagingService $messaging,
        protected NoticeAudienceService $audience,
    ) {}

    // ─────────────────────────────────────────────────────────────────────
    // Candidates (the check table before sending)
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Students with results in the chosen classes, with readiness flags.
     * @return Collection<object{student_id, class_id, name, adm, class, subjects, vetted, ready, owing, blocked_reason, phones, emails, whatsapp}>
     */
    public function candidates(int $sessionId, int $termId, array $classIds, bool $requireVetted, array $studentIds = []): Collection
    {
        $rows = DB::table('broadsheet_records as br')
            ->join('broadsheets as b', 'b.broadsheet_record_id', '=', 'br.id')
            ->join('studentRegistration as s', 's.id', '=', 'br.student_id')
            ->leftJoin('schoolclass as c', 'c.id', '=', 'br.schoolclass_id')
            ->leftJoin('schoolarm as a', 'a.id', '=', 'c.arm')
            ->where('br.session_id', $sessionId)
            ->where('b.term_id', $termId)
            ->when($classIds, fn ($q) => $q->whereIn('br.schoolclass_id', $classIds))
            ->when($studentIds, fn ($q) => $q->whereIn('br.student_id', $studentIds))
            ->groupBy('br.student_id', 'br.schoolclass_id', 's.firstname', 's.lastname', 's.othername', 's.admissionNo', 'c.schoolclass', 'a.arm')
            ->orderBy('c.schoolclass')->orderBy('a.arm')->orderBy('s.lastname')->orderBy('s.firstname')
            ->get([
                'br.student_id', 'br.schoolclass_id as class_id',
                's.firstname', 's.lastname', 's.othername', 's.admissionNo',
                DB::raw("TRIM(CONCAT(COALESCE(c.schoolclass,''), ' ', COALESCE(a.arm,''))) as class_name"),
                DB::raw('COUNT(*) as subjects'),
                DB::raw('SUM(CASE WHEN b.vettedstatus = 1 THEN 1 ELSE 0 END) as vetted'),
            ]);

        if ($rows->isEmpty()) return collect();

        // Contacts per student (same rules as notices).
        $res = $this->audience->resolve(['scope' => 'students', 'student_ids' => $rows->pluck('student_id')->all()], ['sms', 'whatsapp', 'email']);
        $count = [];
        foreach ($res['contacts'] as $ch => $list) {
            foreach ($list as $c) {
                foreach ($c['students'] as $sid) {
                    $count[$sid][$ch] = ($count[$sid][$ch] ?? 0) + 1;
                }
            }
        }

        $access = class_exists(ResultAccessService::class) ? app(ResultAccessService::class) : null;

        return $rows->map(function ($r) use ($count, $access, $termId, $sessionId, $requireVetted) {
            $check = $access ? $access->check((int) $r->student_id, $termId, $sessionId, false) : ['allowed' => true, 'reason' => 'ok'];
            $complete = (int) $r->subjects > 0 && (!$requireVetted || (int) $r->vetted >= (int) $r->subjects);

            return (object) [
                'student_id' => (int) $r->student_id,
                'class_id'   => (int) $r->class_id,
                'name'       => trim($r->lastname . ' ' . $r->firstname . ' ' . ($r->othername ?? '')),
                'adm'        => $r->admissionNo,
                'class'      => $r->class_name,
                'subjects'   => (int) $r->subjects,
                'vetted'     => (int) $r->vetted,
                'complete'   => $complete,
                'blocked'    => empty($check['allowed']),
                'block_reason' => $check['reason'] ?? null,
                'owed'       => (float) ($check['owed'] ?? 0),
                'sms'        => $count[$r->student_id]['sms'] ?? 0,
                'whatsapp'   => $count[$r->student_id]['whatsapp'] ?? 0,
                'email'      => $count[$r->student_id]['email'] ?? 0,
            ];
        });
    }

    // ─────────────────────────────────────────────────────────────────────
    // Create a batch
    // ─────────────────────────────────────────────────────────────────────

    public function create(array $data, array $studentIds, int $userId): ResultSend
    {
        return DB::transaction(function () use ($data, $studentIds, $userId) {
            $send = ResultSend::create($data + ['created_by' => $userId, 'status' => 'queued']);

            $cands = $this->candidates($send->session_id, $send->term_id, $send->class_ids ?? [], $send->require_vetted, $studentIds)
                ->keyBy('student_id');

            $skipped = 0;
            foreach ($studentIds as $sid) {
                $c = $cands->get((int) $sid);
                if (!$c) continue;

                $reason = null;
                if (!$c->complete) {
                    $reason = $c->subjects === 0 ? 'No results entered' : 'Not all subjects vetted (' . $c->vetted . '/' . $c->subjects . ')';
                } elseif ($c->blocked && ($c->block_reason === 'manual_block' || !$send->include_owing)) {
                    $reason = $c->block_reason === 'manual_block' ? 'Result access blocked by the school' : 'Result on hold: fees owed';
                } elseif (($c->sms + $c->whatsapp + $c->email) === 0) {
                    $reason = 'No parent phone or email on record';
                }

                ResultSendItem::create([
                    'result_send_id' => $send->id,
                    'student_id'     => $c->student_id,
                    'class_id'       => $c->class_id,
                    'status'         => $reason ? 'skipped' : 'pending',
                    'skip_reason'    => $reason,
                    'token'          => $reason ? null : Str::random(40),
                    'link_expires_at'=> $reason ? null : now()->addDays(max(1, (int) $send->link_days)),
                ]);
                if ($reason) $skipped++;
            }

            $send->update(['students' => $send->items()->count(), 'skipped' => $skipped]);
            return $send;
        });
    }

    // ─────────────────────────────────────────────────────────────────────
    // Processing
    // ─────────────────────────────────────────────────────────────────────

    public function runDue(int $maxStudents = 60): int
    {
        $done = 0;
        foreach (ResultSend::whereIn('status', ['queued', 'sending'])->orderBy('id')->get() as $send) {
            $done += $this->process($send->id, $maxStudents - $done);
            if ($done >= $maxStudents) break;
        }
        return $done;
    }

    /** Generate + send for up to $limit students of a batch. Safe to call from several processes. */
    public function process(int $sendId, int $limit = 1000): int
    {
        @set_time_limit(0);
        $send = ResultSend::find($sendId);
        if (!$send || in_array($send->status, ['sent', 'cancelled'], true)) return 0;

        if ($send->status === 'queued') {
            $send->update(['status' => 'sending', 'started_at' => now()]);
        }

        // Items interrupted mid-way (> 15 min) go back to pending.
        ResultSendItem::where('result_send_id', $send->id)->whereIn('status', ['generating', 'sending'])
            ->where('updated_at', '<', now()->subMinutes(15))->update(['status' => 'pending']);

        $report  = app(ViewStudentReportController::class);
        $refreshed = [];
        $count = 0;

        while ($count < $limit) {
            $item = ResultSendItem::where('result_send_id', $send->id)->where('status', 'pending')->orderBy('id')->first();
            if (!$item) break;
            if (!ResultSendItem::whereKey($item->id)->where('status', 'pending')->update(['status' => 'generating', 'updated_at' => now()])) {
                continue; // taken by another run
            }
            $count++;

            try {
                // 1. PDF (positions are refreshed once per class per run)
                if (!$item->pdf_path || !Storage::disk('local')->exists($item->pdf_path)) {
                    $key = $item->class_id;
                    $pdf = $report->renderReportCardPdf($item->student_id, $item->class_id, $send->session_id, $send->term_id, !isset($refreshed[$key]));
                    $refreshed[$key] = true;
                    if (!$pdf) {
                        $item->update(['status' => 'failed', 'error' => 'The report card could not be generated.']);
                        continue;
                    }
                    $path = "result-reports/{$send->id}/{$item->id}-" . Str::random(8) . '.pdf';
                    Storage::disk('local')->put($path, $pdf);
                    $item->update(['pdf_path' => $path]);
                }

                // 2. Deliveries for every parent contact on the chosen channels
                $item->update(['status' => 'sending']);
                $this->buildDeliveries($send, $item);

                // 3. Send
                $this->sendItem($send, $item->fresh());
                $item->update(['status' => 'done']);
                $v = $this->vars($send, $item);
                PortalNotifier::toStudents([$item->student_id], $v['{term}'] . ' report card is ready',
                    'Your ' . $v['{term}'] . ' ' . $v['{session}'] . ' report card has been sent to your parents. You can also view your results in My Assessments.',
                    route('assessments'), 'result', 'result:' . $send->id . ':' . $item->student_id);
            } catch (\Throwable $e) {
                Log::error('Result send failed', ['send' => $send->id, 'item' => $item->id, 'error' => $e->getMessage()]);
                $item->update(['status' => 'failed', 'error' => mb_substr($e->getMessage(), 0, 490)]);
            }
        }

        $this->refresh($send);
        return $count;
    }

    protected function buildDeliveries(ResultSend $send, ResultSendItem $item): void
    {
        $res = $this->audience->resolve(['scope' => 'students', 'student_ids' => [$item->student_id]], $send->channels ?? []);
        $now = now();
        $rows = [];
        foreach ($res['contacts'] as $ch => $list) {
            foreach ($list as $c) {
                $rows[] = [
                    'result_send_item_id' => $item->id, 'result_send_id' => $send->id,
                    'channel' => $ch, 'recipient' => $c['to'], 'recipient_name' => mb_substr((string) $c['name'], 0, 190),
                    'status' => 'queued', 'created_at' => $now, 'updated_at' => $now,
                ];
            }
        }
        if ($rows) DB::table('result_send_deliveries')->insertOrIgnore($rows);
    }

    protected function sendItem(ResultSend $send, ResultSendItem $item): void
    {
        $vars     = $this->vars($send, $item);
        $filePath = Storage::disk('local')->path($item->pdf_path);
        $filename = preg_replace('/[^A-Za-z0-9_.-]+/', '_', 'Report_Card_' . $vars['{student_name}'] . '_' . $vars['{term}'] . '_' . $vars['{session}']) . '.pdf';

        $queued = ResultSendDelivery::where('result_send_item_id', $item->id)->where('status', 'queued')->get();
        foreach ($queued as $d) {
            if (!ResultSendDelivery::whereKey($d->id)->where('status', 'queued')->update(['status' => 'sending', 'updated_at' => now()])) {
                continue;
            }
            $tpl  = $d->channel === 'sms' && trim((string) $send->sms_text) !== '' ? $send->sms_text : $send->message;
            $body = strtr($tpl, $vars + ['{parent_name}' => $d->recipient_name ?: 'Parent']);

            $res = $this->messaging->send($d->channel, $d->recipient, $body, [
                'name'        => $d->recipient_name ?: 'Parent',
                'subject'     => $vars['{student_name}'] . ' — ' . $vars['{term}'] . ' report card',
                'attachment'  => $d->channel === 'sms' ? null : ['path' => $filePath, 'filename' => $filename, 'mime' => 'application/pdf'],
                'wa_media_id' => $item->wa_media_id,
            ]);

            if (!empty($res['media_id']) && $res['media_id'] !== $item->wa_media_id) {
                $item->update(['wa_media_id' => $res['media_id']]);
            }

            $attempts = $d->attempts + 1;
            $status   = $res['status'] === 'failed' && !empty($res['retryable']) && $attempts < 3 ? 'queued' : $res['status'];
            $d->update([
                'status' => $status, 'attempts' => $attempts,
                'error' => $res['error'] ? mb_substr($res['error'], 0, 490) : null,
                'provider_message_id' => $res['id'] ?: null,
                'sent_at' => $res['status'] === 'sent' ? now() : null,
            ]);
            if ($status === 'queued') {
                sleep(2);
                $this->sendItem($send, $item); // one retry pass
                return;
            }
        }
    }

    public function vars(ResultSend $send, ResultSendItem $item): array
    {
        static $cache = [];
        $key = $send->id;
        $cache[$key] ??= [
            'term'    => Schoolterm::where('id', $send->term_id)->value('term') ?? '',
            'session' => Schoolsession::where('id', $send->session_id)->value('session') ?? '',
            'school'  => (SchoolInformation::getActiveSchool() ?? SchoolInformation::first())->school_name ?? config('app.name'),
        ];
        $st = $item->student;
        $class = DB::table('schoolclass as c')->leftJoin('schoolarm as a', 'a.id', '=', 'c.arm')->where('c.id', $item->class_id)
            ->selectRaw("TRIM(CONCAT(COALESCE(c.schoolclass,''), ' ', COALESCE(a.arm,''))) as class_label")->value('class_label');

        return [
            '{student_name}' => $st ? trim($st->firstname . ' ' . $st->lastname) : 'your ward',
            '{class}'        => $class ?: '',
            '{term}'         => $cache[$key]['term'],
            '{session}'      => $cache[$key]['session'],
            '{link}'         => $item->token ? route('results.link', $item->token) : '',
            '{school_name}'  => $cache[$key]['school'],
        ];
    }

    public function resendFailed(ResultSend $send): int
    {
        $n = ResultSendDelivery::where('result_send_id', $send->id)->where('status', 'failed')
            ->update(['status' => 'queued', 'error' => null, 'attempts' => 0]);
        $items = ResultSendItem::where('result_send_id', $send->id)->where('status', 'failed')->whereNotNull('token')
            ->update(['status' => 'pending', 'error' => null]);
        ResultSendItem::where('result_send_id', $send->id)->where('status', 'done')
            ->whereHas('deliveries', fn ($q) => $q->where('status', 'queued'))
            ->update(['status' => 'pending']);
        if ($n || $items) $send->update(['status' => 'sending', 'finished_at' => null]);
        return $n + $items;
    }

    public function refresh(ResultSend $send): void
    {
        $c = ResultSendDelivery::where('result_send_id', $send->id)->selectRaw('status, COUNT(*) n')->groupBy('status')->pluck('n', 'status');
        $pending = ResultSendItem::where('result_send_id', $send->id)->whereIn('status', ['pending', 'generating', 'sending'])->exists();
        $send->update([
            'messages_sent'   => (int) ($c['sent'] ?? 0),
            'messages_failed' => (int) ($c['failed'] ?? 0),
            'skipped'         => ResultSendItem::where('result_send_id', $send->id)->where('status', 'skipped')->count(),
            'status'          => $send->status === 'cancelled' ? 'cancelled' : ($pending ? 'sending' : 'sent'),
            'finished_at'     => $pending ? null : ($send->finished_at ?? now()),
        ]);
    }
}
