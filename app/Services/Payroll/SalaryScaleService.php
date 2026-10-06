<?php

namespace App\Services\Payroll;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Salary scales (grade + step, versioned by date), staff placements and the
 * pay items given to staff.
 */
class SalaryScaleService
{
    /** salary_grade_steps column → [payslip code, label, pensionable, is_basic] */
    public const COLUMNS = [
        'basic'     => ['BASIC', 'Basic salary', true, true],
        'housing'   => ['HOUSING', 'Housing allowance', true, false],
        'transport' => ['TRANSPORT', 'Transport allowance', true, false],
        'meal'      => ['MEAL', 'Meal allowance', false, false],
        'medical'   => ['MEDICAL', 'Medical allowance', false, false],
        'utility'   => ['UTILITY', 'Utility allowance', false, false],
        'other'     => ['OTHER', 'Other allowances', false, false],
    ];

    public static function available(): bool
    {
        static $ok = null;
        return $ok ??= Schema::hasTable('salary_grades');
    }

    /** Where a staff member sits on the scale on a date. */
    public function placement(int $staffId, $date): ?object
    {
        if (!self::available()) return null;
        return DB::table('staff_grade_history as h')->join('salary_grades as g', 'g.id', '=', 'h.grade_id')
            ->where('h.staff_id', $staffId)->where('h.effective_from', '<=', Carbon::parse($date)->toDateString())
            ->orderByDesc('h.effective_from')->orderByDesc('h.id')
            ->first(['h.*', 'g.code as grade_code', 'g.name as grade_name', 'g.max_step']);
    }

    /** Scale amounts for a grade/step on a date. */
    public function stepAmounts(int $gradeId, int $step, $date): ?object
    {
        return DB::table('salary_grade_steps')->where('grade_id', $gradeId)->where('step', $step)
            ->where('effective_from', '<=', Carbon::parse($date)->toDateString())
            ->orderByDesc('effective_from')->first();
    }

    /** The scale in force on a date: [grade_id => [step => row]] */
    public function scaleOn($date, ?array $gradeIds = null): array
    {
        $d = Carbon::parse($date)->toDateString();
        $latest = DB::table('salary_grade_steps')->where('effective_from', '<=', $d)
            ->when($gradeIds, fn ($q) => $q->whereIn('grade_id', $gradeIds))
            ->selectRaw('grade_id, step, MAX(effective_from) as ef')->groupBy('grade_id', 'step');
        $rows = DB::table('salary_grade_steps as s')->joinSub($latest, 'l', fn ($j) => $j->on('l.grade_id', '=', 's.grade_id')->on('l.step', '=', 's.step')->on('l.ef', '=', 's.effective_from'))
            ->orderBy('s.grade_id')->orderBy('s.step')->get(['s.*']);
        $out = [];
        foreach ($rows as $r) $out[$r->grade_id][$r->step] = $r;
        return $out;
    }

    /** Earnings lines from the scale for a staff member on a date. */
    public function earnings(int $staffId, $date): ?array
    {
        $p = $this->placement($staffId, $date);
        if (!$p) return null;
        $row = $this->stepAmounts((int) $p->grade_id, (int) $p->step, $date);
        if (!$row) return null;
        return ['placement' => $p, 'lines' => $this->linesFromRow($row)];
    }

    public function linesFromRow(object $row): array
    {
        $lines = [];
        foreach (self::COLUMNS as $col => [$code, $label, $pensionable, $basic]) {
            $amt = (float) ($row->$col ?? 0);
            if ($amt > 0) $lines[] = ['code' => $code, 'label' => $label, 'amount' => $amt, 'taxable' => true, 'pensionable' => $pensionable, 'basic' => $basic];
        }
        return $lines;
    }

    /**
     * Pay items given to a staff member that apply in the month.
     * @return array{earnings: array, deductions: array}
     */
    public function staffItems(int $staffId, $monthStart, float $proration, array $earnings): array
    {
        if (!Schema::hasTable('staff_pay_items')) return ['earnings' => [], 'deductions' => []];
        $m = Carbon::parse($monthStart)->startOfMonth()->toDateString();

        $rows = DB::table('staff_pay_items as s')->join('pay_items as i', 'i.id', '=', 's.pay_item_id')
            ->where('s.staff_id', $staffId)->where('i.is_active', true)
            ->where('s.from_month', '<=', $m)->where(fn ($q) => $q->whereNull('s.to_month')->orWhere('s.to_month', '>=', $m))
            ->orderBy('i.type')->orderBy('i.name')
            ->get(['s.id', 's.amount', 's.rate', 's.note', 's.from_month', 's.to_month', 'i.code', 'i.name', 'i.type', 'i.calc',
                   'i.default_amount', 'i.default_rate', 'i.taxable', 'i.pensionable', 'i.one_off']);

        // Bases for % items: this month's regular earnings (already part-month adjusted).
        $basic = 0.0; $gross = 0.0;
        foreach ($earnings as $e) {
            $amt = (float) $e['amount'] * (!empty($e['one_off']) ? 1 : $proration);
            $gross += $amt;
            if (!empty($e['basic'])) $basic += $amt;
        }

        $out = ['earnings' => [], 'deductions' => []];
        foreach ($rows as $r) {
            $amount = match ($r->calc) {
                'percent_basic' => round($basic * (float) ($r->rate ?? $r->default_rate) / 100, 2),
                'percent_gross' => round($gross * (float) ($r->rate ?? $r->default_rate) / 100, 2),
                default         => (float) ($r->amount ?? $r->default_amount),
            };
            if ($amount <= 0) continue;
            $oneMonth = $r->to_month && $r->to_month === $r->from_month;
            $label = $r->name . ($r->note ? ' — ' . $r->note : '');
            if ($r->type === 'earning') {
                $out['earnings'][] = ['code' => $r->code, 'label' => $label, 'amount' => $amount, 'taxable' => (bool) $r->taxable,
                    'pensionable' => (bool) $r->pensionable, 'one_off' => (bool) $r->one_off || $oneMonth,
                    // Fixed recurring items follow part months; % items are already based on part-month pay; one-offs are paid in full.
                    'no_proration' => $r->calc !== 'fixed', 'meta' => ['staff_pay_item_id' => $r->id]];
            } else {
                $out['deductions'][] = ['code' => $r->code, 'label' => $label, 'amount' => $amount, 'meta' => ['staff_pay_item_id' => $r->id]];
            }
        }
        return $out;
    }

    /** Record a staff member's grade/step from a date. */
    public function place(int $staffId, int $gradeId, int $step, $from, string $reason = 'placement', ?int $by = null, ?int $reviewId = null): void
    {
        DB::table('staff_grade_history')->insert([
            'staff_id' => $staffId, 'grade_id' => $gradeId, 'step' => $step, 'effective_from' => Carbon::parse($from)->toDateString(),
            'reason' => $reason, 'review_id' => $reviewId, 'created_by' => $by, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
