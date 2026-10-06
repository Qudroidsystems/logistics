<?php

namespace App\Services\Finance;

use App\Models\Budget;
use App\Models\BudgetLine;
use App\Models\ExpenseVoucher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Budgets with budget-vs-actual (paid vouchers by category; payroll cost for the payroll line). */
class BudgetService
{
    /** Actual spend per budget line. Committed = approved but not yet paid. */
    public function report(Budget $budget): array
    {
        $from = $budget->start_date->toDateString(); $to = $budget->end_date->toDateString();
        $paid = ExpenseVoucher::where('status', 'paid')->whereBetween('expense_date', [$from, $to])->groupBy('expense_category_id')->selectRaw('expense_category_id, SUM(amount) amt')->pluck('amt', 'expense_category_id');
        $committed = ExpenseVoucher::whereIn('status', ['submitted', 'approved', 'paying'])->whereBetween('expense_date', [$from, $to])->groupBy('expense_category_id')->selectRaw('expense_category_id, SUM(amount) amt')->pluck('amt', 'expense_category_id');
        $payroll = (float) DB::table('payroll_periods')->whereIn('status', ['approved', 'paid', 'locked'])->whereBetween('start_date', [$from, $to])
            ->sum(Schema::hasColumn('payroll_periods', 'total_employer_cost') ? 'total_employer_cost' : 'total_gross_pay');

        $elapsed = max(0, min(1, $budget->start_date->diffInDays(now()) / max(1, $budget->start_date->diffInDays($budget->end_date))));
        $rows = [];
        foreach ($budget->lines()->with('category')->get() as $l) {
            $actual = $l->line_type === 'payroll' ? $payroll : (float) ($paid[$l->expense_category_id] ?? 0);
            $comm = $l->line_type === 'payroll' ? 0 : (float) ($committed[$l->expense_category_id] ?? 0);
            $rows[] = ['line' => $l, 'name' => $l->name(), 'budget' => $l->amount, 'actual' => $actual, 'committed' => $comm,
                       'remaining' => round($l->amount - $actual - $comm, 2), 'used' => $l->amount > 0 ? round(($actual + $comm) / $l->amount * 100) : ($actual > 0 ? 100 : 0)];
        }
        $unbudgeted = ExpenseVoucher::where('status', 'paid')->whereBetween('expense_date', [$from, $to])
            ->whereNotIn('expense_category_id', $budget->lines()->whereNotNull('expense_category_id')->pluck('expense_category_id'))->sum('amount');

        return ['rows' => $rows, 'total_budget' => array_sum(array_column($rows, 'budget')), 'total_actual' => array_sum(array_column($rows, 'actual')),
                'total_committed' => array_sum(array_column($rows, 'committed')), 'unbudgeted' => (float) $unbudgeted, 'elapsed' => round($elapsed * 100)];
    }

    /** Would this voucher push its category over the active budget? */
    public function checkVoucher(ExpenseVoucher $v): array
    {
        if (!Schema::hasTable('budgets') || !$v->expense_category_id) return ['ok' => true];
        $budget = Budget::where('status', 'active')->whereDate('start_date', '<=', $v->expense_date)->whereDate('end_date', '>=', $v->expense_date)->first();
        if (!$budget) return ['ok' => true];
        $line = BudgetLine::where('budget_id', $budget->id)->where('expense_category_id', $v->expense_category_id)->first();
        if (!$line) return ['ok' => false, 'message' => 'This category is not in the budget "' . $budget->name . '".', 'budget' => $budget];
        $spent = (float) ExpenseVoucher::whereIn('status', ['submitted', 'approved', 'paying', 'paid'])->where('id', '!=', $v->id)
            ->where('expense_category_id', $v->expense_category_id)->whereBetween('expense_date', [$budget->start_date, $budget->end_date])->sum('amount');
        $left = round($line->amount - $spent, 2);
        return $v->amount > $left
            ? ['ok' => false, 'message' => 'Over budget: only ₦' . number_format(max(0, $left), 2) . ' left on "' . $line->name() . '".', 'left' => $left]
            : ['ok' => true, 'left' => $left, 'line' => $line];
    }
}
