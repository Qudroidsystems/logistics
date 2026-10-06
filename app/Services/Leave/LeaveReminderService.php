<?php

namespace App\Services\Leave;

use App\Models\MessagingSetting;
use App\Services\Messaging\MessagingService;
use App\Services\Messaging\PortalNotifier;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Daily leave reminders by SMS / WhatsApp / email (+ portal):
 *  - the day before leave starts (to the staff member and the colleague covering)
 *  - countdown while on leave ("3 working days left — you resume Mon 12 Oct")
 *  - last day ("you resume tomorrow")
 *  - resumption day (to HOD / principal)
 *  - not back yet (to the principal and the staff member)
 * Each message goes once per leave request (logged).
 */
class LeaveReminderService
{
    public const DEFAULTS = [
        'channels' => ['sms', 'whatsapp', 'email'],
        'countdown' => [5, 3],             // working days left (the last day is always sent)
        'notify_cover' => true,
        'notify_managers_on_resume' => true,
        'overdue_after_days' => 1,          // working days after the resume date
        'auto_resume_on_login' => true,
    ];

    public function __construct(protected LeaveService $leave, protected MessagingService $messaging) {}

    public static function settings(): MessagingSetting
    {
        $s = MessagingSetting::firstOrCreate(['channel' => 'leave_reminders'], ['driver' => 'auto', 'is_active' => true, 'config' => self::DEFAULTS]);
        $s->config = array_merge(self::DEFAULTS, $s->config ?? []);
        return $s;
    }

    /** First working day after the leave ends. */
    public function resumeDate($end): Carbon
    {
        $d = Carbon::parse($end)->addDay();
        for ($i = 0; $i < 30 && $this->leave->workingDays($d, $d) == 0; $i++) $d->addDay();
        return $d;
    }

    public function run(?Carbon $today = null): array
    {
        $cfg = self::settings();
        $today = ($today ?? now())->copy()->startOfDay();
        $stats = ['sent' => 0, 'resumed' => 0];
        if (!$cfg->is_active) return $stats;
        $c = $cfg->config;

        $reqs = DB::table('leave_requests as r')->join('leave_types as t', 't.id', '=', 'r.leave_type_id')->join('users as u', 'u.id', '=', 'r.user_id')
            ->leftJoin('staffbioinfo as s', 's.id', '=', 'r.staff_id')->leftJoin('users as rl', 'rl.id', '=', 'r.relief_staff_id')
            ->where('r.status', 'approved')->whereNull('r.resumed_at')
            ->where('r.start_date', '<=', $today->copy()->addDays(3)->toDateString())
            ->where('r.end_date', '>=', $today->copy()->subDays(14)->toDateString())
            ->get(['r.*', 't.name as type', 'u.name', 'u.email', 'u.phone_number', 'u.last_login_at', 's.phonenumber', 's.department', 'rl.name as relief_name']);

        foreach ($reqs as $r) {
            $start = Carbon::parse($r->start_date); $end = Carbon::parse($r->end_date);
            $resume = $this->resumeDate($end);
            $resumeTxt = $resume->format('l j M Y');
            $first = explode(' ', trim($r->name))[0] ?: $r->name;

            // Starts on the next working day
            if ($start->gt($today) && $this->leave->workingDays($today->copy()->addDay(), $start->copy()->subDay()) == 0) {
                $stats['sent'] += $this->toStaff($r, 'starting', "Hello {$first}, your {$r->type} starts " . $start->format('l j M') . " and ends " . $end->format('j M') . ". You resume on {$resumeTxt}." . ($r->relief_name ? " {$r->relief_name} is covering for you." : '') . ' Enjoy your leave.', $c);
                if (!empty($c['notify_cover']) && $r->relief_staff_id) {
                    $stats['sent'] += $this->toUser((int) $r->relief_staff_id, $r->id, 'cover', "Reminder: you are covering for {$r->name} from " . $start->format('l j M') . ' to ' . $end->format('j M') . '. They resume on ' . $resume->format('j M') . '.', $c);
                }
            }

            // On leave: countdown and last day
            if ($start->lte($today) && $end->gte($today)) {
                $left = $this->leave->workingDays($today, $end);
                if ($left <= 1) {
                    $stats['sent'] += $this->toStaff($r, 'last_day', "Hello {$first}, today is the last day of your {$r->type}. We look forward to seeing you back on {$resumeTxt}.", $c);
                } elseif (in_array((int) $left, array_map('intval', (array) $c['countdown']), true)) {
                    $stats['sent'] += $this->toStaff($r, 'countdown_' . (int) $left, "Hello {$first}, you have {$left} working days of {$r->type} left. You resume on {$resumeTxt}.", $c);
                }
            }

            // Back already? (signed in on/after the resume date)
            if ($today->gte($resume) && !empty($c['auto_resume_on_login']) && $r->last_login_at && Carbon::parse($r->last_login_at)->gte($resume)) {
                DB::table('leave_requests')->where('id', $r->id)->update(['resumed_at' => $r->last_login_at, 'resume_source' => 'login', 'updated_at' => now()]);
                $stats['resumed']++;
                continue;
            }

            // Resumption day → managers
            if ($today->isSameDay($resume) && !empty($c['notify_managers_on_resume'])) {
                $this->portal($this->managers($r), "{$r->name} resumes today", "Back from {$r->type} (" . $start->format('j M') . ' – ' . $end->format('j M') . ').', 'leave:resume:' . $r->id);
                $this->log($r->id, 'resumes_today', 'portal', null);
            }

            // Not back yet
            $grace = (int) ($c['overdue_after_days'] ?? 1);
            if ($today->gt($resume) && $this->leave->workingDays($resume, $today->copy()->subDay()) >= $grace) {
                if ($this->log($r->id, 'overdue', 'portal', null)) {
                    $this->portal($this->managers($r), "{$r->name} has not resumed", "Expected back on {$resumeTxt} after {$r->type}. Not yet confirmed.", 'leave:overdue:' . $r->id);
                }
                $stats['sent'] += $this->toStaff($r, 'overdue', "Hello {$first}, we expected you back on {$resumeTxt}. Please resume or contact the school if you need an extension.", $c);
            }
        }
        return $stats;
    }

    protected function managers(object $r): array
    {
        $staff = (object) ['department' => $r->department, 'userid' => $r->user_id];
        return array_values(array_unique(array_merge($this->leave->hodsFor($staff), $this->leave->approvers())));
    }

    protected function toStaff(object $r, string $kind, string $text, array $c): int
    {
        return $this->deliver($r->id, $kind, (int) $r->user_id, $r->name, MessagingService::normalizePhone($r->phonenumber ?: $r->phone_number), $r->email, $text, $c);
    }

    protected function toUser(int $userId, int $reqId, string $kind, string $text, array $c): int
    {
        $u = DB::table('users as u')->leftJoin('staffbioinfo as s', 's.userid', '=', 'u.id')->where('u.id', $userId)->first(['u.id', 'u.name', 'u.email', 'u.phone_number', 's.phonenumber']);
        return $u ? $this->deliver($reqId, $kind, $userId, $u->name, MessagingService::normalizePhone($u->phonenumber ?: $u->phone_number), $u->email, $text, $c) : 0;
    }

    protected function deliver(int $reqId, string $kind, int $userId, string $name, ?string $phone, ?string $email, string $text, array $c): int
    {
        $n = 0;
        if ($this->log($reqId, $kind, 'portal', (string) $userId)) {
            $this->portal([$userId], 'Leave reminder', $text, "leave:$kind:$reqId");
            $n++;
        }
        foreach ((array) $c['channels'] as $ch) {
            if (!$this->messaging->enabled($ch)) continue;
            $to = $ch === 'email' ? (filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null) : $phone;
            if (!$to || !$this->log($reqId, $kind, $ch, $to)) continue;
            try {
                $res = $this->messaging->send($ch, $to, $text, ['name' => $name, 'subject' => 'Leave reminder']);
                if (($res['status'] ?? '') !== 'sent') DB::table('leave_reminder_logs')->where(['leave_request_id' => $reqId, 'kind' => $kind, 'channel' => $ch])->update(['status' => $res['status'] ?? 'failed', 'error' => mb_substr((string) ($res['error'] ?? ''), 0, 250)]);
                else $n++;
            } catch (\Throwable $e) {
                Log::warning('Leave reminder failed', ['req' => $reqId, 'channel' => $ch, 'error' => $e->getMessage()]);
            }
        }
        return $n;
    }

    /** Claims the (request, kind, channel) slot; false if already sent. */
    protected function log(int $reqId, string $kind, string $channel, ?string $to): bool
    {
        return DB::table('leave_reminder_logs')->insertOrIgnore(['leave_request_id' => $reqId, 'kind' => $kind, 'channel' => $channel, 'recipient' => $to, 'created_at' => now()]) > 0;
    }

    protected function portal(array $userIds, string $title, string $body, string $key): void
    {
        if (!$userIds || !class_exists(PortalNotifier::class)) return;
        try { PortalNotifier::toUsers($userIds, $title, $body, route('leave.index'), 'system', $key); } catch (\Throwable $e) {}
    }

    /** Classes a teacher normally takes, by weekday (for the cover plan). */
    public function affectedClasses(int $userId, $start, $end): array
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('timetable_slots')) return [];
        $days = [];
        foreach (\Carbon\CarbonPeriod::create(Carbon::parse($start), Carbon::parse($end)) as $d) if (!$d->isWeekend()) $days[$d->format('l')] = true;
        if (!$days) return [];
        $rows = DB::table('timetable_slots as ts')->join('timetable_settings as st', 'st.id', '=', 'ts.setting_id')
            ->leftJoin('timetable_periods as p', 'p.id', '=', 'ts.period_id')->leftJoin('subject as sj', 'sj.id', '=', 'ts.subject_id')
            ->leftJoin('schoolclass as c', 'c.id', '=', 'st.schoolclass_id')->leftJoin('schoolarm as a', 'a.id', '=', 'c.arm')
            ->where('ts.teacher_id', $userId)->where('st.is_active', 1)->whereIn('ts.day', array_keys($days))
            ->orderByRaw("FIELD(ts.day,'Monday','Tuesday','Wednesday','Thursday','Friday')")->orderBy('p.order')
            ->get(['ts.day', 'p.name as period', 'p.start_time', 'sj.subject', DB::raw("TRIM(CONCAT(COALESCE(c.schoolclass,''),' ',COALESCE(a.arm,''))) as class")]);
        $out = [];
        foreach ($rows as $r) $out[$r->day][] = trim(($r->class ?: '') . ' ' . ($r->subject ?: '') . ($r->period ? " ({$r->period})" : ''));
        return $out;
    }
}
