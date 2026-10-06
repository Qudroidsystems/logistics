<?php

namespace App\Services\Payroll;

use App\Models\PayrollPeriod;
use App\Models\StaffDutyClaim;
use App\Support\FinanceSettings;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Links staff attendance and duty claims to pay:
 *  - absence on a working day with no approved leave (optional, off by default)
 *  - every N lates = one day's pay (optional)
 *  - approved extra lessons / overtime / weekend duty → earnings
 * Staff with no attendance records in the month are never docked (probably
 * not on the device); they get a warning instead.
 */
class AttendancePayService
{
    public function settings(): array
    {
        return FinanceSettings::get('attendance_pay');
    }

    /** Working dates in a range (weekdays that are not full-day holidays / device outages). */
    public function workingDates(string $from, string $to): array
    {
        $s = Carbon::parse($from)->startOfDay(); $e = Carbon::parse($to)->startOfDay();
        if ($e->lt($s)) return [];
        $off = [];
        if (Schema::hasTable('holidays')) {
            $q = DB::table('holidays')->whereBetween('date', [$s->toDateString(), $e->toDateString()]);
            if (Schema::hasColumn('holidays', 'is_full_day')) $q->where(fn ($w) => $w->where('is_full_day', true)->orWhereNull('is_full_day'));
            $off = $q->pluck('date')->map(fn ($d) => Carbon::parse($d)->toDateString())->all();
        }
        if (Schema::hasTable('attendance_holidays')) {
            foreach (DB::table('attendance_holidays')->where('holiday_date', '<=', $e->toDateString())
                ->where(fn ($w) => $w->where('holiday_end_date', '>=', $s->toDateString())->orWhere(fn ($x) => $x->whereNull('holiday_end_date')->where('holiday_date', '>=', $s->toDateString())))
                ->get(['holiday_date', 'holiday_end_date']) as $h) {
                foreach (CarbonPeriod::create($h->holiday_date, $h->holiday_end_date ?: $h->holiday_date) as $d) $off[] = $d->toDateString();
            }
        }
        if (Schema::hasTable('device_outage_dates')) {
            $off = array_merge($off, DB::table('device_outage_dates')->whereBetween('outage_date', [$s->toDateString(), $e->toDateString()])
                ->pluck('outage_date')->map(fn ($d) => Carbon::parse($d)->toDateString())->all());
        }
        $off = array_flip($off);
        $out = [];
        foreach (CarbonPeriod::create($s, $e) as $d) if (!$d->isWeekend() && !isset($off[$d->toDateString()])) $out[] = $d->toDateString();
        return $out;
    }

    /** Dates covered by any approved leave (paid or unpaid). */
    protected function leaveDates(int $staffId, string $from, string $to): array
    {
        if (!Schema::hasTable('leave_requests')) return [];
        $out = [];
        foreach (DB::table('leave_requests')->where('staff_id', $staffId)->whereIn('status', ['approved'])
            ->where('start_date', '<=', $to)->where('end_date', '>=', $from)->get(['start_date', 'end_date', 'resumed_at']) as $r) {
            $end = $r->resumed_at ? min(Carbon::parse($r->end_date), Carbon::parse($r->resumed_at)->subDay()) : Carbon::parse($r->end_date);
            foreach (CarbonPeriod::create(max(Carbon::parse($r->start_date), Carbon::parse($from)), min($end, Carbon::parse($to))) as $d) $out[$d->toDateString()] = true;
        }
        return $out;
    }

    /** Attendance summary for one staff member in a period. */
    public function summary(int $staffId, PayrollPeriod $period, ?string $employedFrom = null): array
    {
        $from = Carbon::parse($period->start_date)->toDateString();
        $to = min(Carbon::parse($period->end_date), now()->startOfDay())->toDateString();
        if ($employedFrom && $employedFrom > $from) $from = $employedFrom;
        $working = $to >= $from ? $this->workingDates($from, $to) : [];
        $fullMonth = $this->workingDates(Carbon::parse($period->start_date)->toDateString(), Carbon::parse($period->end_date)->toDateString());

        $att = Schema::hasTable('staff_attendance')
            ? DB::table('staff_attendance')->where('staff_id', $staffId)->whereBetween('attendance_date', [$from, $to])->get(['attendance_date', 'status'])
            : collect();
        $present = $att->pluck('attendance_date')->map(fn ($d) => Carbon::parse($d)->toDateString())->flip();
        $leave = $this->leaveDates($staffId, $from, $to);

        $absent = [];
        foreach ($working as $d) if (!isset($present[$d]) && !isset($leave[$d])) $absent[] = $d;

        return [
            'working_days' => count($fullMonth), 'counted_days' => count($working), 'present' => $att->count(),
            'late' => $att->where('status', 'late')->count(), 'leave' => count(array_intersect_key($leave, array_flip($working))),
            'absent' => count($absent), 'absent_dates' => $absent, 'has_data' => $att->isNotEmpty(),
        ];
    }

    /** Earnings lines (negative for deductions) for PayrollEngine. */
    public function adjustments(object $staff, PayrollPeriod $period, float $regularMonthly): array
    {
        $S = $this->settings();
        $lines = []; $warnings = [];

        // Duty claims (always on — they need approval anyway).
        foreach ($this->claimsFor((int) $staff->id, $period) as $c) {
            $lines[] = ['code' => 'OVERTIME', 'label' => $c->typeLabel() . ' · ' . $c->work_date->format('d M') . ($c->quantity != 1 ? " ({$c->quantity})" : ''),
                        'amount' => round($c->amount, 2), 'taxable' => (bool) $S['overtime_taxable'], 'pensionable' => false,
                        'no_proration' => true, 'meta' => ['claim_id' => $c->id]];
        }

        if (empty($S['enabled']) || in_array((int) $staff->id, array_map('intval', (array) $S['exempt_staff']), true)) {
            return ['lines' => $lines, 'warnings' => $warnings];
        }

        $sum = $this->summary((int) $staff->id, $period, $staff->date_of_employment ?? null);
        if (!$sum['has_data']) {
            $warnings[] = 'No attendance records this month — absence not deducted';
            return ['lines' => $lines, 'warnings' => $warnings];
        }
        $dayRate = $sum['working_days'] > 0 ? $regularMonthly / $sum['working_days'] : 0;

        $days = !empty($S['deduct_absence']) ? max(0, $sum['absent'] - (int) $S['grace_absences']) : 0;
        if ($days > 0 && $dayRate > 0) {
            $lines[] = ['code' => 'ABSENCE', 'label' => "Absent without leave ({$days} day" . ($days > 1 ? 's' : '') . ')',
                        'amount' => -round(min($regularMonthly, $dayRate * $days), 2), 'taxable' => true, 'pensionable' => false, 'no_proration' => true,
                        'meta' => ['dates' => array_slice($sum['absent_dates'], (int) $S['grace_absences'])]];
        }
        $per = (int) $S['lates_per_day'];
        if ($per > 0 && $sum['late'] >= $per && $dayRate > 0) {
            $ld = intdiv($sum['late'], $per);
            $lines[] = ['code' => 'LATENESS', 'label' => "Lateness ({$sum['late']} lates = {$ld} day" . ($ld > 1 ? 's' : '') . ')',
                        'amount' => -round($dayRate * $ld, 2), 'taxable' => true, 'pensionable' => false, 'no_proration' => true];
        }
        return ['lines' => $lines, 'warnings' => $warnings, 'summary' => $sum];
    }

    /** Approved, unpaid claims up to the end of this month (or already tied to it). */
    public function claimsFor(int $staffId, PayrollPeriod $period)
    {
        if (!Schema::hasTable('staff_duty_claims')) return collect();
        return StaffDutyClaim::where('staff_id', $staffId)
            ->where(fn ($q) => $q->where(fn ($a) => $a->where('status', 'approved')->whereNull('payroll_period_id'))
                                  ->orWhere('payroll_period_id', $period->id))
            ->whereIn('status', ['approved', 'paid'])
            ->whereDate('work_date', '<=', $period->end_date)->orderBy('work_date')->get();
    }

    /** Payroll locked: tie the claims to the month and mark them paid. */
    public function markClaimsPaid(PayrollPeriod $period): int
    {
        if (!Schema::hasTable('staff_duty_claims')) return 0;
        $ids = collect(DB::table('payroll_run_lines')->where('payroll_period_id', $period->id)->where('code', 'OVERTIME')->pluck('meta'))
            ->map(fn ($m) => is_string($m) ? (json_decode($m, true)['claim_id'] ?? null) : ($m['claim_id'] ?? null))->filter()->all();
        return $ids ? StaffDutyClaim::whereIn('id', $ids)->update(['status' => 'paid', 'payroll_period_id' => $period->id]) : 0;
    }

    public function rate(string $type): float
    {
        return (float) ($this->settings()['rates'][$type] ?? 0);
    }
}
