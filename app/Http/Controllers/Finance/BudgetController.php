<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Budget;
use App\Models\BudgetLine;
use App\Models\ExpenseCategory;
use App\Services\Finance\BudgetService;
use Illuminate\Http\Request;

/** Budgets per school year / term, with budget vs actual. */
class BudgetController extends Controller
{
    public function __construct(protected BudgetService $svc)
    {
        $this->middleware('permission:Manage budgets|View financial reports')->only(['index', 'show', 'export']);
        $this->middleware('permission:Manage budgets')->except(['index', 'show', 'export']);
    }

    public function index()
    {
        $budgets = Budget::withSum('lines', 'amount')->orderByDesc('start_date')->get();
        $active = $budgets->firstWhere('status', 'active');
        return view('finance.budgets.index', ['pagetitle' => 'Budgets', 'budgets' => $budgets, 'active' => $active, 'report' => $active ? $this->svc->report($active) : null]);
    }

    public function store(Request $request)
    {
        $d = $request->validate(['name' => 'required|string|max:150', 'start_date' => 'required|date', 'end_date' => 'required|date|after:start_date',
                                 'copy_from' => 'nullable|integer|exists:budgets,id', 'uplift' => 'nullable|numeric|min:-50|max:200']);
        $b = Budget::create(['name' => $d['name'], 'start_date' => $d['start_date'], 'end_date' => $d['end_date'], 'status' => 'draft', 'created_by' => auth()->id()]);
        if (!empty($d['copy_from'])) {
            $f = 1 + (float) ($d['uplift'] ?? 0) / 100;
            foreach (BudgetLine::where('budget_id', $d['copy_from'])->get() as $l) {
                BudgetLine::create(['budget_id' => $b->id, 'line_type' => $l->line_type, 'expense_category_id' => $l->expense_category_id, 'label' => $l->label, 'amount' => round($l->amount * $f, 2)]);
            }
        }
        return redirect()->route('finance.budgets.show', $b)->with('success', 'Budget created. Add or adjust the lines, then activate it.');
    }

    public function show(Budget $budget)
    {
        return view('finance.budgets.show', [
            'pagetitle' => $budget->name, 'budget' => $budget, 'report' => $this->svc->report($budget),
            'categories' => ExpenseCategory::active()->orderBy('name')->get(),
        ]);
    }

    public function saveLines(Request $request, Budget $budget)
    {
        if ($budget->status === 'closed') return back()->with('error', 'A closed budget cannot be changed.');
        $d = $request->validate(['lines' => 'array', 'lines.*' => 'nullable|numeric|min:0', 'payroll' => 'nullable|numeric|min:0']);
        foreach ((array) ($d['lines'] ?? []) as $catId => $amount) {
            if ($amount === null || $amount === '') { BudgetLine::where('budget_id', $budget->id)->where('expense_category_id', $catId)->delete(); continue; }
            BudgetLine::updateOrCreate(['budget_id' => $budget->id, 'line_type' => 'category', 'expense_category_id' => (int) $catId], ['amount' => (float) $amount]);
        }
        if (($d['payroll'] ?? null) !== null && $d['payroll'] !== '') {
            BudgetLine::updateOrCreate(['budget_id' => $budget->id, 'line_type' => 'payroll'], ['amount' => (float) $d['payroll'], 'label' => 'Staff salaries (payroll)']);
        } else {
            BudgetLine::where('budget_id', $budget->id)->where('line_type', 'payroll')->delete();
        }
        return back()->with('success', 'Budget lines saved.');
    }

    public function status(Request $request, Budget $budget)
    {
        $d = $request->validate(['status' => 'required|in:draft,active,closed']);
        if ($d['status'] === 'active') {
            Budget::where('id', '!=', $budget->id)->where('status', 'active')
                ->where('start_date', '<=', $budget->end_date)->where('end_date', '>=', $budget->start_date)->update(['status' => 'closed']);
            $budget->update(['status' => 'active', 'approved_by' => auth()->id(), 'approved_at' => now()]);
        } else {
            $budget->update(['status' => $d['status']]);
        }
        return back()->with('success', 'Budget ' . $d['status'] . '.');
    }

    public function export(Budget $budget)
    {
        $r = $this->svc->report($budget);
        return response()->streamDownload(function () use ($r) {
            $o = fopen('php://output', 'w');
            fputcsv($o, ['Line', 'Budget', 'Actual', 'Committed', 'Remaining', 'Used %']);
            foreach ($r['rows'] as $x) fputcsv($o, [$x['name'], $x['budget'], $x['actual'], $x['committed'], $x['remaining'], $x['used']]);
            fputcsv($o, ['Total', $r['total_budget'], $r['total_actual'], $r['total_committed'], $r['total_budget'] - $r['total_actual'] - $r['total_committed'], '']);
            fputcsv($o, ['Spent outside budget lines', '', $r['unbudgeted']]);
            fclose($o);
        }, 'budget-' . $budget->id . '.csv', ['Content-Type' => 'text/csv']);
    }
}
