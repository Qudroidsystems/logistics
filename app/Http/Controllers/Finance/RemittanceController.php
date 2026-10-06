<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\PayrollPeriod;
use App\Models\StatutoryRemittance;
use App\Services\Payroll\StatutoryRemittanceService;
use App\Support\PayrollDocs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/** Government remittances: what's owed per month, schedules, payments, receipts. */
class RemittanceController extends Controller
{
    public function __construct(protected StatutoryRemittanceService $svc)
    {
        $this->middleware('permission:View payroll|Manage remittances')->only(['index', 'show', 'schedule', 'evidence']);
        $this->middleware('permission:Manage remittances')->only(['generate', 'pay']);
    }

    public function index(Request $request)
    {
        $year = (int) ($request->get('year') ?: now()->year);
        $q = StatutoryRemittance::with('period:id,period_name,start_date')
            ->whereHas('period', fn ($p) => $p->whereYear('start_date', $year))
            ->when($request->filled('type'), fn ($x) => $x->where('type', $request->type))
            ->when($request->get('status') === 'unpaid', fn ($x) => $x->where('status', '!=', 'paid'))
            ->when($request->get('status') === 'overdue', fn ($x) => $x->where('status', '!=', 'paid')->whereDate('due_date', '<', now()->toDateString()));
        $rows = $q->get()->sortBy([fn ($a, $b) => strcmp((string) $b->period?->start_date, (string) $a->period?->start_date), ['type', 'asc'], ['authority', 'asc']])->values();

        $all = StatutoryRemittance::whereHas('period', fn ($p) => $p->whereYear('start_date', $year))->get();
        return view('finance.payroll.remittances', [
            'pagetitle' => 'Government Remittances', 'rows' => $rows, 'year' => $year,
            'stats' => [
                'due' => $all->sum('amount_due'), 'paid' => $all->sum('amount_paid'),
                'outstanding' => $all->sum(fn ($r) => $r->balance()),
                'overdue' => $all->filter(fn ($r) => $r->isOverdue())->sum(fn ($r) => $r->balance()),
                'overdue_count' => $all->filter(fn ($r) => $r->isOverdue())->count(),
            ],
            'byType' => $all->groupBy('type')->map(fn ($g) => ['due' => $g->sum('amount_due'), 'paid' => $g->sum('amount_paid')]),
            'periods' => PayrollPeriod::whereIn('status', PayrollDocs::PUBLISHED)->orderByDesc('start_date')->limit(24)->get(['id', 'period_name']),
            'years' => DB::table('payroll_periods')->selectRaw('DISTINCT YEAR(start_date) y')->orderByDesc('y')->pluck('y')->push(now()->year)->unique()->values(),
        ]);
    }

    public function generate(Request $request)
    {
        $period = PayrollPeriod::findOrFail((int) $request->input('payroll_period_id'));
        try {
            $r = $this->svc->generate($period, $request->user()->id);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
        return back()->with('success', "{$period->period_name}: {$r['created']} remittance(s) prepared." . ($r['flagged'] ? " {$r['flagged']} already-paid item(s) no longer match the payroll — check them." : ''));
    }

    public function show(StatutoryRemittance $remittance)
    {
        [$head, $rows] = $this->svc->schedule($remittance);
        return view('finance.payroll.remittance-show', [
            'pagetitle' => StatutoryRemittance::TYPES[$remittance->type][0] . ' — ' . $remittance->authority, 'r' => $remittance->load('period'),
            'head' => $head, 'rows' => $rows, 'recorder' => DB::table('users')->where('id', $remittance->recorded_by)->value('name'),
        ]);
    }

    public function schedule(StatutoryRemittance $remittance)
    {
        [$head, $rows] = $this->svc->schedule($remittance);
        $name = strtoupper($remittance->type) . '_' . preg_replace('/[^A-Za-z0-9]+/', '_', $remittance->authority . '_' . ($remittance->period->period_name ?? '')) . '.csv';
        return response()->streamDownload(function () use ($head, $rows, $remittance) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $head);
            foreach ($rows as $r) fputcsv($out, $r);
            fputcsv($out, array_merge(['', 'TOTAL'], array_fill(0, max(0, count($head) - 3), ''), [$remittance->amount_due]));
            fclose($out);
        }, $name, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function pay(Request $request, StatutoryRemittance $remittance)
    {
        $d = $request->validate([
            'amount' => 'required|numeric|min:0.01|max:' . max(0.01, $remittance->balance() + 0.01), 'paid_at' => 'required|date|before_or_equal:today',
            'reference' => 'required|string|max:120', 'payment_method' => 'nullable|string|max:30', 'notes' => 'nullable|string|max:1000',
            'evidence' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);
        if ($request->hasFile('evidence')) {
            $d['evidence'] = $request->file('evidence')->store('remittances/' . $remittance->payroll_period_id, 'local');
        }
        $this->svc->recordPayment($remittance, $d, $request->user()->id, $request->boolean('notify', true));
        return back()->with('success', 'Payment recorded.' . ($remittance->fresh()->status === 'paid' ? ' Marked as fully paid.' : ' Part payment — balance still owed.'));
    }

    public function evidence(StatutoryRemittance $remittance)
    {
        abort_unless($remittance->evidence && Storage::disk('local')->exists($remittance->evidence), 404);
        return Storage::disk('local')->response($remittance->evidence);
    }
}
