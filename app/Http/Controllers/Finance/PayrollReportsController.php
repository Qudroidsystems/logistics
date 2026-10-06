<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\PayrollPeriod;
use App\Support\PayrollDocs;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Payroll months list, yearly summary and statutory summary (CB design). */
class PayrollReportsController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View payroll')->only(['periods', 'summary', 'statutory']);
        $this->middleware('permission:Process payroll')->only(['store']);
    }

    protected function years(): array
    {
        $ys = PayrollPeriod::select('year')->distinct()->orderByDesc('year')->pluck('year')->map(fn ($y) => (int) $y)->all();
        return array_values(array_unique(array_merge([now()->year], $ys)));
    }

    public function periods(Request $request)
    {
        $year = (int) ($request->get('year') ?: now()->year);
        $periods = PayrollPeriod::where('year', $year)->orderByDesc('month')->get();
        $users = DB::table('users')->whereIn('id', $periods->pluck('approved_by')->merge($periods->pluck('processed_by'))->filter()->unique())->pluck('name', 'id');
        $next = PayrollPeriod::orderByDesc('year')->orderByDesc('month')->first();
        $suggest = $next ? Carbon::create($next->year, $next->month, 1)->addMonth() : now()->startOfMonth();

        return view('finance.payroll.periods', [
            'pagetitle' => 'Payroll Months', 'periods' => $periods, 'year' => $year, 'years' => $this->years(), 'users' => $users, 'suggest' => $suggest,
            'ytd' => ['gross' => $periods->whereIn('status', PayrollDocs::PUBLISHED)->sum('total_gross_pay'), 'net' => $periods->whereIn('status', PayrollDocs::PUBLISHED)->sum('total_net_pay'),
                      'paye' => $periods->whereIn('status', PayrollDocs::PUBLISHED)->sum('total_tax'), 'open' => $periods->whereIn('status', ['draft', 'processing'])->count()],
        ]);
    }

    public function store(Request $request)
    {
        $d = $request->validate(['month' => 'required|date_format:Y-m', 'payment_date' => 'required|date']);
        $start = Carbon::parse($d['month'] . '-01');
        if (PayrollPeriod::where('month', $start->month)->where('year', $start->year)->exists()) {
            return back()->with('error', $start->format('F Y') . ' already exists.');
        }
        $p = PayrollPeriod::create([
            'period_name' => $start->format('F Y'), 'month' => $start->month, 'year' => $start->year,
            'start_date' => $start->toDateString(), 'end_date' => $start->copy()->endOfMonth()->toDateString(),
            'payment_date' => $d['payment_date'], 'status' => 'draft',
        ]);
        return redirect()->route('payroll.month.show', $p)->with('success', $p->period_name . ' created. Click Calculate when salaries and allowances are up to date.');
    }

    public function summary(Request $request)
    {
        $year = (int) ($request->get('year') ?: now()->year);
        $rows = PayrollPeriod::where('year', $year)->whereIn('status', PayrollDocs::PUBLISHED)->orderBy('month')->get();
        $byDept = Schema::hasColumn('staffbioinfo', 'department')
            ? DB::table('payroll_runs as r')->join('payroll_periods as p', 'p.id', '=', 'r.payroll_period_id')->join('staffbioinfo as s', 's.id', '=', 'r.staff_id')
                ->where('p.year', $year)->whereIn('p.status', PayrollDocs::PUBLISHED)
                ->groupBy('dept')->selectRaw("COALESCE(NULLIF(TRIM(s.department),''), 'No department') as dept, SUM(r.total_earnings) as gross, SUM(r.net_pay) as net, SUM(COALESCE(r.employer_cost, r.total_earnings)) as cost, COUNT(DISTINCT r.staff_id) as staff")
                ->orderByDesc('cost')->get()
            : collect();
        $prev = PayrollPeriod::where('year', $year - 1)->whereIn('status', PayrollDocs::PUBLISHED)->get();

        return view('finance.payroll.summary', [
            'pagetitle' => 'Payroll Summary', 'rows' => $rows, 'year' => $year, 'years' => $this->years(), 'byDept' => $byDept,
            'totals' => [
                'gross' => $rows->sum('total_gross_pay'), 'net' => $rows->sum('total_net_pay'), 'paye' => $rows->sum('total_tax'),
                'pension_ee' => $rows->sum('total_employee_pension'), 'pension_er' => $rows->sum('total_employer_pension'), 'nhf' => $rows->sum('total_nhf'),
                'loans' => $rows->sum('total_loan_deductions'), 'cost' => $rows->sum(fn ($r) => (float) ($r->total_employer_cost ?: $r->total_gross_pay)),
            ],
            'prevCost' => $prev->sum(fn ($r) => (float) ($r->total_employer_cost ?: $r->total_gross_pay)),
        ]);
    }

    public function statutory(Request $request)
    {
        $year = (int) ($request->get('year') ?: now()->year);
        $rows = PayrollPeriod::where('year', $year)->whereIn('status', PayrollDocs::PUBLISHED)->orderBy('month')->get();
        $remit = Schema::hasTable('statutory_remittances')
            ? DB::table('statutory_remittances')->whereIn('payroll_period_id', $rows->pluck('id'))
                ->groupBy('payroll_period_id', 'type')->selectRaw('payroll_period_id, type, SUM(amount_due) due, SUM(amount_paid) paid')->get()
                ->groupBy('payroll_period_id')->map(fn ($g) => $g->keyBy('type'))
            : collect();

        return view('finance.payroll.statutory', ['pagetitle' => 'Statutory Summary', 'rows' => $rows, 'year' => $year, 'years' => $this->years(), 'remit' => $remit]);
    }
}
