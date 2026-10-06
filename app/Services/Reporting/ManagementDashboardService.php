<?php

namespace App\Services\Reporting;

use App\Models\Student;
use App\Services\Billing\InstalmentPlanService;
use App\Services\Billing\StudentFeeStatementService;
use App\Services\Messaging\MessagingService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Figures for the management (principal / proprietor) dashboard.
 * Fee totals use the same statement as My Payments and the bursar, so they
 * match to the kobo; they are worked out in the background and cached.
 */
class ManagementDashboardService
{
    public const FEE_TTL_MINUTES = 30;

    public function __construct(protected StudentFeeStatementService $statements) {}

    protected function has(string $table): bool
    {
        static $c = [];
        return $c[$table] ??= Schema::hasTable($table);
    }

    // ── Enrolment ───────────────────────────────────────────────────────

    public function enrolment(int $sessionId): array
    {
        $active = DB::table('studentRegistration')->whereRaw("LOWER(COALESCE(student_status, 'active')) = 'active'");
        $placed = DB::table('studentclass as sc')->join('studentRegistration as s', 's.id', '=', 'sc.studentId')
            ->where('sc.sessionid', $sessionId)->whereRaw("LOWER(COALESCE(s.student_status, 'active')) = 'active'");

        $gender = (clone $active)->selectRaw("LOWER(COALESCE(gender,'')) as g, COUNT(*) as n")->groupBy('g')->pluck('n', 'g');

        return [
            'active'  => (clone $active)->count(),
            'placed'  => (clone $placed)->distinct()->count('sc.studentId'),
            'male'    => (int) ($gender['male'] ?? 0) + (int) ($gender['m'] ?? 0),
            'female'  => (int) ($gender['female'] ?? 0) + (int) ($gender['f'] ?? 0),
            'new30'   => (clone $active)->where('created_at', '>=', now()->subDays(30))->count(),
            'staff'   => DB::table('users')->whereNull('student_id')
                ->whereNotExists(fn ($q) => $q->from('model_has_roles as mr')->join('roles as r', 'r.id', '=', 'mr.role_id')
                    ->whereColumn('mr.model_id', 'users.id')->where('mr.model_type', \App\Models\User::class)->whereIn('r.name', ['Student', 'Parent']))
                ->count(),
        ];
    }

    // ── Attendance today ────────────────────────────────────────────────

    public function attendanceToday(int $sessionId, int $termId): array
    {
        $today = now()->toDateString();
        $out = ['marked_classes' => 0, 'classes' => 0, 'present' => 0, 'absent' => 0, 'late' => 0, 'rate' => null,
                'unmarked' => [], 'staff_present' => null, 'staff_total' => null, 'is_school_day' => !now()->isWeekend()];

        if ($this->has('student_attendance')) {
            $rows = DB::table('student_attendance')->where('attendance_date', $today)->where('session_id', $sessionId)
                ->when(Schema::hasColumn('student_attendance', 'period'), fn ($q) => $q->where(fn ($w) => $w->where('period', 'morning')->orWhereNull('period')))
                ->selectRaw('status, COUNT(DISTINCT student_id) as n')->groupBy('status')->pluck('n', 'status');
            $out['present'] = (int) ($rows['present'] ?? 0);
            $out['late']    = (int) ($rows['late'] ?? 0);
            $out['absent']  = (int) ($rows['absent'] ?? 0);
            $total = $rows->sum();
            $out['rate'] = $total ? round(($out['present'] + $out['late']) / $total * 100) : null;

            $classIds = DB::table('studentclass')->where('sessionid', $sessionId)->distinct()->pluck('schoolclassid')->map(fn ($v) => (int) $v);
            $marked   = DB::table('student_attendance')->where('attendance_date', $today)->where('session_id', $sessionId)
                ->distinct()->pluck('schoolclass_id')->map(fn ($v) => (int) $v);
            $out['classes'] = $classIds->count();
            $out['marked_classes'] = $classIds->intersect($marked)->count();
            $unmarked = $classIds->diff($marked)->values()->all();
            $out['unmarked'] = $unmarked ? DB::table('schoolclass as c')->leftJoin('schoolarm as a', 'a.id', '=', 'c.arm')->whereIn('c.id', $unmarked)
                ->orderBy('c.schoolclass')->selectRaw("TRIM(CONCAT(COALESCE(c.schoolclass,''), ' ', COALESCE(a.arm,''))) as class_label")->pluck('class_label')->all() : [];
        }

        if ($this->has('staff_attendance')) {
            $out['staff_present'] = DB::table('staff_attendance')->where('attendance_date', $today)->whereIn('status', ['present', 'late'])->distinct()->count('staff_id');
        }
        return $out;
    }

    // ── Money ───────────────────────────────────────────────────────────

    /** Cached fee snapshot for the term, or null while it's being worked out. */
    public function feeSnapshot(int $termId, int $sessionId, bool $refresh = false): ?array
    {
        $key = "mgmt:fees:$termId:$sessionId";
        if ($refresh) Cache::forget($key);
        $snap = Cache::get($key);
        if ($snap) return $snap;

        if (Cache::add("$key:lock", 1, now()->addMinutes(10))) {
            dispatch(function () use ($termId, $sessionId, $key) {
                try {
                    Cache::put($key, app(self::class)->computeFees($termId, $sessionId), now()->addMinutes(self::FEE_TTL_MINUTES));
                } catch (\Throwable $e) {
                    Log::error('Management fee snapshot failed', ['error' => $e->getMessage()]);
                } finally {
                    Cache::forget("$key:lock");
                }
            })->afterResponse();
        }
        return null;
    }

    public function computeFees(int $termId, int $sessionId): array
    {
        @set_time_limit(600);
        $placements = DB::table('studentclass as sc')->join('studentRegistration as s', 's.id', '=', 'sc.studentId')
            ->where('sc.sessionid', $sessionId)->whereRaw("LOWER(COALESCE(s.student_status, 'active')) = 'active'")
            ->groupBy('sc.studentId')->select('sc.studentId as id', DB::raw('MAX(sc.schoolclassid) as class_id'))->get();

        $plans = InstalmentPlanService::available() ? app(InstalmentPlanService::class) : null;
        $t = ['payable' => 0, 'paid' => 0, 'outstanding' => 0, 'arrears' => 0, 'savings' => 0, 'debtors' => 0, 'students' => 0,
              'fully_paid' => 0, 'plan_students' => 0, 'plan_behind' => 0, 'plan_overdue' => 0];
        $byClass = [];

        foreach ($placements as $p) {
            $student = Student::find($p->id);
            if (!$student) continue;
            try { $st = $this->statements->buildStatement($student, $termId, $sessionId); } catch (\Throwable $e) { continue; }
            $tot = $st['totals'] ?? [];
            $payable = (float) ($tot['adjusted'] ?? 0);
            if ($payable <= 0 && empty($st['arrears']['has_arrears'])) continue;

            $paid = (float) ($tot['paid'] ?? 0); $owed = (float) ($tot['outstanding'] ?? 0); $arr = (float) ($st['arrears']['total_arrears'] ?? 0);
            $t['students']++; $t['payable'] += $payable; $t['paid'] += $paid; $t['outstanding'] += $owed; $t['arrears'] += $arr;
            $t['savings'] += (float) ($tot['savings'] ?? 0);
            if ($owed + $arr > 0.009) $t['debtors']++; elseif ($payable > 0) $t['fully_paid']++;

            if ($plans && ($sched = $plans->schedule((int) $p->id, $termId, $sessionId, $payable, $paid, (int) $p->class_id))) {
                $t['plan_students']++;
                if (!$sched['on_track']) { $t['plan_behind']++; $t['plan_overdue'] += $sched['overdue']; }
            }

            $cid = (int) ($st['class']->id ?? $p->class_id);
            $c = $byClass[$cid] ?? ['id' => $cid, 'name' => $st['class']->schoolclass ?? '—', 'students' => 0, 'payable' => 0, 'paid' => 0, 'outstanding' => 0, 'debtors' => 0];
            $c['students']++; $c['payable'] += $payable; $c['paid'] += $paid; $c['outstanding'] += $owed;
            if ($owed > 0.009) $c['debtors']++;
            $byClass[$cid] = $c;
        }

        $t = array_map(fn ($v) => is_float($v) ? round($v, 2) : $v, $t);
        $t['rate'] = $t['payable'] > 0 ? round($t['paid'] / $t['payable'] * 100, 1) : 0;
        foreach ($byClass as &$c) { $c['rate'] = $c['payable'] > 0 ? round($c['paid'] / $c['payable'] * 100, 1) : 100; }
        unset($c);
        usort($byClass, fn ($a, $b) => $a['rate'] <=> $b['rate']);

        return ['totals' => $t, 'classes' => array_values($byClass), 'at' => now()->toDateTimeString()];
    }

    /** Money received per day (ledger) for the last $days days. */
    public function collections(int $days = 14): array
    {
        $from = now()->subDays($days - 1)->startOfDay();
        $q = DB::table('student_bill_payment_record as r')->where('r.created_at', '>=', $from);
        if (Schema::hasColumn('student_bill_payment_record', 'deleted_at')) $q->whereNull('r.deleted_at');
        $rows = $q->selectRaw('DATE(r.created_at) as d, SUM(r.amount_paid) as amt')->groupBy('d')->pluck('amt', 'd');

        $online = $this->has('online_fee_payments')
            ? DB::table('online_fee_payments')->where('status', 'success')->where('paid_at', '>=', $from)
                ->selectRaw('DATE(paid_at) as d, SUM(paid_kobo) as k')->groupBy('d')->pluck('k', 'd')
            : collect();

        $out = [];
        for ($i = 0; $i < $days; $i++) {
            $d = $from->copy()->addDays($i)->toDateString();
            $out[] = ['date' => $d, 'label' => Carbon::parse($d)->format('D j'), 'amount' => round((float) ($rows[$d] ?? 0), 2), 'online' => round(((int) ($online[$d] ?? 0)) / 100, 2)];
        }
        return $out;
    }

    // ── Results ─────────────────────────────────────────────────────────

    public function results(int $termId, int $sessionId): array
    {
        $v = DB::table('broadsheet_records as br')->join('broadsheets as b', 'b.broadsheet_record_id', '=', 'br.id')
            ->where('br.session_id', $sessionId)->where('b.term_id', $termId)
            ->selectRaw('COUNT(*) as entries, SUM(b.vettedstatus = 1) as vetted, COUNT(DISTINCT br.schoolclass_id) as classes')->first();

        $approvals = ReportApprovalService::available()
            ? DB::table('report_approvals')->where('term_id', $termId)->where('session_id', $sessionId)->selectRaw('status, COUNT(*) as n')->groupBy('status')->pluck('n', 'status')
            : collect();

        return [
            'entries'  => (int) ($v->entries ?? 0),
            'vetted'   => (int) ($v->vetted ?? 0),
            'pct'      => ($v->entries ?? 0) ? (int) floor($v->vetted / $v->entries * 100) : 0,
            'classes'  => (int) ($v->classes ?? 0),
            'approved' => (int) ($approvals['approved'] ?? 0),
            'submitted'=> (int) ($approvals['submitted'] ?? 0),
            'returned' => (int) ($approvals['returned'] ?? 0),
            'enforced' => ReportApprovalService::enforced(),
        ];
    }

    // ── Communication ───────────────────────────────────────────────────

    public function messaging(int $days = 7): array
    {
        $from = now()->subDays($days);
        $count = function (string $table) use ($from) {
            if (!$this->has($table)) return collect();
            return DB::table($table)->where('created_at', '>=', $from)->selectRaw('status, COUNT(*) as n')->groupBy('status')->pluck('n', 'status');
        };
        $sum = collect();
        foreach (['notice_deliveries', 'result_send_deliveries', 'auto_messages', 'payment_receipt_messages'] as $tbl) {
            foreach ($count($tbl) as $s => $n) $sum[$s] = ($sum[$s] ?? 0) + $n;
        }
        $balance = null;
        try { $balance = app(MessagingService::class)->smsBalance(); } catch (\Throwable $e) {}

        $parents = null;
        if (Schema::hasTable('parent_student')) {
            $pu = DB::table('users')->whereIn('id', DB::table('parent_student')->select('user_id'));
            $parents = ['accounts' => (clone $pu)->count(), 'active' => Schema::hasColumn('users', 'last_login_at') ? (clone $pu)->whereNotNull('last_login_at')->count() : null];
        }

        return ['sent' => (int) ($sum['sent'] ?? 0), 'failed' => (int) ($sum['failed'] ?? 0), 'queued' => (int) ($sum['queued'] ?? 0),
                'sms_balance' => $balance, 'parents' => $parents];
    }

    // ── To-do list ──────────────────────────────────────────────────────

    public function actions(array $att, ?array $fees, array $res, array $msg): array
    {
        $a = [];
        if ($att['is_school_day'] && now()->hour >= 10 && count($att['unmarked'])) {
            $a[] = ['warning', 'ri-calendar-close-line', count($att['unmarked']) . ' class(es) have not marked attendance today', null];
        }
        if ($res['submitted']) $a[] = ['info', 'ri-shield-check-line', $res['submitted'] . ' class(es) waiting for report card approval', route('report-approvals.index', ['status' => 'submitted'])];
        if ($res['returned'])  $a[] = ['warning', 'ri-arrow-go-back-line', $res['returned'] . ' class(es) returned to class teachers', route('report-approvals.index', ['status' => 'returned'])];
        if ($msg['failed'])    $a[] = ['danger', 'ri-error-warning-line', $msg['failed'] . ' message(s) failed to send this week', route('notices.index')];
        if ($msg['sms_balance'] && $msg['sms_balance']['balance'] < 2000) $a[] = ['danger', 'ri-sim-card-line', 'SMS balance is low: ₦' . number_format($msg['sms_balance']['balance'], 2), route('notices.settings')];
        if ($fees && $fees['totals']['plan_behind']) $a[] = ['warning', 'ri-calendar-todo-line', $fees['totals']['plan_behind'] . ' student(s) are behind on their instalment plan', route('instalment-plans.index')];
        if ($this->has('online_fee_payments')) {
            $review = DB::table('online_fee_payments')->where('needs_review', true)->count();
            if ($review) $a[] = ['danger', 'ri-bank-card-line', $review . ' online payment(s) need checking', route('online-fees.index')];
        }
        if (\Illuminate\Support\Facades\Schema::hasColumn('leave_requests', 'resumed_at')) {
            $notBack = DB::table('leave_requests')->where('status', 'approved')->whereNull('resumed_at')
                ->where('end_date', '<', now()->subDay()->toDateString())->where('end_date', '>=', now()->subDays(30)->toDateString())->count();
            if ($notBack) $a[] = ['warning', 'ri-user-unfollow-line', $notBack . ' staff not confirmed back from leave', route('leave.records')];
            $away = DB::table('leave_requests')->where('status', 'approved')->where('start_date', '<=', now()->toDateString())->where('end_date', '>=', now()->toDateString())->count();
            if ($away) $a[] = ['info', 'ri-calendar-event-line', $away . ' staff on leave today', route('leave.records')];
        }
        if (\Illuminate\Support\Facades\Schema::hasTable('statutory_remittances')) {
            $late = \App\Models\StatutoryRemittance::where('status', '!=', 'paid')->whereDate('due_date', '<', now()->toDateString())->get();
            if ($late->count()) $a[] = ['danger', 'ri-government-line', $late->count() . ' government payment(s) overdue (₦' . number_format($late->sum(fn ($r) => $r->balance()), 2) . ')', route('payroll.remittances', ['status' => 'overdue'])];
        }
        return $a;
    }
}
