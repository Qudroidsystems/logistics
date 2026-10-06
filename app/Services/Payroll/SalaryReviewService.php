<?php

namespace App\Services\Payroll;

use App\Models\PayrollPeriod;
use App\Models\SalaryReview;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Salary reviews: step increments or % raises from a date. If the date is in
 * the past, the difference for months already paid becomes arrears, paid as a
 * one-off item in a chosen payroll month (taxed as a lump sum).
 */
class SalaryReviewService
{
    public function __construct(protected SalaryScaleService $scale) {}

    /** Staff on the scale on the review date, with old → new monthly pay. */
    public function preview(SalaryReview $r): array
    {
        $date = $r->effective_from->toDateString();
        $gradeIds = $r->grade_ids ?: null;
        $cols = $r->components ?: array_keys(SalaryScaleService::COLUMNS);
        $pct = (float) $r->percent / 100;

        // Current placement per staff member on the date.
        $latest = DB::table('staff_grade_history')->where('effective_from', '<=', $date)
            ->selectRaw('staff_id, MAX(CONCAT(effective_from, LPAD(id, 10, "0"))) as k')->groupBy('staff_id');
        $placed = DB::table('staff_grade_history as h')
            ->joinSub($latest, 'l', fn ($j) => $j->on('l.staff_id', '=', 'h.staff_id')->whereRaw('CONCAT(h.effective_from, LPAD(h.id, 10, "0")) = l.k'))
            ->join('salary_grades as g', 'g.id', '=', 'h.grade_id')
            ->join('staffbioinfo as s', 's.id', '=', 'h.staff_id')->leftJoin('users as u', 'u.id', '=', 's.userid')
            ->join('staff_pay_profiles as p', 'p.staff_id', '=', 'h.staff_id')->where('p.salary_source', 'grade')->where('p.pay_status', '!=', 'exited')
            ->when($gradeIds, fn ($q) => $q->whereIn('h.grade_id', $gradeIds))
            ->orderBy('g.sort')->orderBy('g.code')->orderBy('u.name')
            ->get(['h.staff_id', 'h.grade_id', 'h.step', 'g.code', 'g.max_step', 'u.name']);

        $scaleNow = $this->scale->scaleOn(Carbon::parse($date)->subDay(), $gradeIds);
        $rows = []; $oldTotal = 0; $newTotal = 0;
        foreach ($placed as $p) {
            $old = $scaleNow[$p->grade_id][$p->step] ?? null;
            if (!$old) continue;
            $newStep = $p->step; $new = clone $old;
            if ($r->type === 'step_increment') {
                if ($p->step >= $p->max_step) { $rows[] = ['staff_id' => $p->staff_id, 'name' => $p->name, 'grade' => $p->code, 'from' => $p->step, 'to' => $p->step,
                    'old' => $this->total($old), 'new' => $this->total($old), 'note' => 'Already on the top step']; continue; }
                $newStep = $p->step + 1;
                $new = $scaleNow[$p->grade_id][$newStep] ?? null;
                if (!$new) { $rows[] = ['staff_id' => $p->staff_id, 'name' => $p->name, 'grade' => $p->code, 'from' => $p->step, 'to' => $p->step,
                    'old' => $this->total($old), 'new' => $this->total($old), 'note' => "No amounts set for step {$newStep}"]; continue; }
            } else {
                foreach ($cols as $c) $new->$c = round((float) $old->$c * (1 + $pct), 2);
            }
            $o = $this->total($old); $n = $this->total($new);
            $rows[] = ['staff_id' => $p->staff_id, 'name' => $p->name, 'grade' => $p->code, 'from' => $p->step, 'to' => $newStep, 'old' => $o, 'new' => $n, 'note' => null];
            $oldTotal += $o; $newTotal += $n;
        }

        $past = PayrollPeriod::whereIn('status', ['approved', 'paid', 'locked'])->where('end_date', '>=', $date)->orderBy('start_date')->get(['id', 'period_name', 'start_date']);
        return ['rows' => $rows, 'old_total' => $oldTotal, 'new_total' => $newTotal, 'past_periods' => $past];
    }

    protected function total(object $row): float
    {
        return array_sum(array_map(fn ($c) => (float) ($row->$c ?? 0), array_keys(SalaryScaleService::COLUMNS)));
    }

    /** Apply: new scale rows / new steps from the date, then arrears for months already paid. */
    public function apply(SalaryReview $r, int $userId): array
    {
        if ($r->status !== 'approved') throw new \RuntimeException('Approve the review before applying it.');
        $date = $r->effective_from->toDateString();
        $preview = $this->preview($r);

        $needsArrears = $preview['past_periods']->isNotEmpty();
        $target = $r->arrears_period_id ? PayrollPeriod::find($r->arrears_period_id) : null;
        if ($needsArrears && (!$target || !in_array($target->status, ['draft', 'processing'], true))) {
            throw new \RuntimeException('Months already paid are affected. Choose a draft payroll month to pay the arrears in.');
        }

        $arrears = [];
        DB::transaction(function () use ($r, $userId, $date, $preview, $target, &$arrears) {
            if ($r->type === 'percent_raise') {
                $pct = (float) $r->percent / 100;
                $cols = $r->components ?: array_keys(SalaryScaleService::COLUMNS);
                foreach ($this->scale->scaleOn(Carbon::parse($date)->subDay(), $r->grade_ids ?: null) as $gradeId => $steps) {
                    foreach ($steps as $step => $row) {
                        $new = ['grade_id' => $gradeId, 'step' => $step, 'effective_from' => $date, 'review_id' => $r->id, 'created_at' => now(), 'updated_at' => now()];
                        foreach (array_keys(SalaryScaleService::COLUMNS) as $c) $new[$c] = in_array($c, $cols, true) ? round((float) $row->$c * (1 + $pct), 2) : $row->$c;
                        DB::table('salary_grade_steps')->updateOrInsert(['grade_id' => $gradeId, 'step' => $step, 'effective_from' => $date], $new);
                    }
                }
            } else {
                foreach ($preview['rows'] as $row) {
                    if ($row['to'] > $row['from']) {
                        $this->scale->place($row['staff_id'], (int) DB::table('salary_grades')->where('code', $row['grade'])->value('id'), $row['to'], $date, 'increment', $userId, $r->id);
                    }
                }
            }

            // Arrears: for each month already paid from the date, new scale pay − what was paid.
            if ($target) {
                $itemId = DB::table('pay_items')->where('code', 'ARREARS')->value('id');
                $codes = array_column(SalaryScaleService::COLUMNS, 0);
                foreach ($preview['past_periods'] as $period) {
                    $pEnd = Carbon::parse(DB::table('payroll_periods')->where('id', $period->id)->value('end_date'));
                    foreach ($preview['rows'] as $row) {
                        $run = DB::table('payroll_runs')->where('payroll_period_id', $period->id)->where('staff_id', $row['staff_id'])->first(['id', 'proration']);
                        if (!$run) continue;
                        $paid = (float) DB::table('payroll_run_lines')->where('payroll_run_id', $run->id)->where('type', 'earning')->whereIn('code', $codes)->sum('amount');
                        $e = $this->scale->earnings($row['staff_id'], $pEnd);
                        if (!$e) continue;
                        $due = round(array_sum(array_column($e['lines'], 'amount')) * (float) ($run->proration ?: 1), 2);
                        $diff = round($due - $paid, 2);
                        if ($diff > 0.009) {
                            $arrears[$row['staff_id']]['amount'] = round(($arrears[$row['staff_id']]['amount'] ?? 0) + $diff, 2);
                            $arrears[$row['staff_id']]['months'][] = Carbon::parse($period->start_date)->format('M Y');
                        }
                    }
                }
                $month = Carbon::parse($target->start_date)->startOfMonth()->toDateString();
                foreach ($arrears as $staffId => $a) {
                    DB::table('staff_pay_items')->insert([
                        'staff_id' => $staffId, 'pay_item_id' => $itemId, 'amount' => $a['amount'], 'from_month' => $month, 'to_month' => $month,
                        'note' => $r->name . ': ' . implode(', ', $a['months']), 'review_id' => $r->id, 'created_by' => $userId,
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
            }

            $r->update(['status' => 'applied', 'applied_at' => now(), 'summary' => [
                'staff' => count(array_filter($preview['rows'], fn ($x) => !$x['note'])),
                'monthly_increase' => round($preview['new_total'] - $preview['old_total'], 2),
                'arrears_total' => round(array_sum(array_column($arrears, 'amount')), 2),
                'arrears_staff' => count($arrears),
            ]]);
        });

        return $r->summary;
    }
}
