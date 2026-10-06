<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\LoanAdvance;
use App\Models\LoanRepayment;
use App\Services\Loans\LoanService;
use App\Support\FinanceSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Bursary side of staff loans and salary advances. */
class LoanController extends Controller
{
    public function __construct(protected LoanService $svc)
    {
        $this->middleware('permission:Manage staff loans|Approve staff loans|View payroll')->only(['index', 'show']);
        $this->middleware('permission:Manage staff loans')->only(['store', 'disburse', 'repay', 'pause', 'cancel', 'quote']);
        $this->middleware('permission:Approve staff loans')->only(['approve', 'reject', 'writeOff']);
        $this->middleware('permission:Manage payroll settings|Approve staff loans')->only(['saveRules']);
    }

    protected function staffList()
    {
        return DB::table('staffbioinfo as s')->join('users as u', 'u.id', '=', 's.userid')->orderBy('u.name')->get(['s.id', 'u.name']);
    }

    public function index(Request $request)
    {
        $q = LoanAdvance::query()->leftJoin('staffbioinfo as s', 's.id', '=', 'loans_advances.staff_id')->leftJoin('users as u', 'u.id', '=', 's.userid')
            ->select('loans_advances.*', 'u.name as staff_name')
            ->when($request->filled('status'), fn ($x) => $request->status === 'open' ? $x->whereIn('loans_advances.status', ['pending', 'approved', 'disbursing', 'active']) : $x->where('loans_advances.status', $request->status))
            ->when($request->filled('type'), fn ($x) => $x->where('loans_advances.type', $request->type))
            ->when($request->filled('q'), fn ($x) => $x->where(fn ($w) => $w->where('u.name', 'like', '%' . $request->q . '%')->orWhere('loans_advances.reference_no', 'like', '%' . $request->q . '%')));
        $loans = $q->orderByRaw("FIELD(loans_advances.status,'pending','approved','disbursing','active') DESC")->latest('loans_advances.id')->paginate(25)->withQueryString();

        $all = LoanAdvance::query();
        return view('finance.loans.index', [
            'pagetitle' => 'Staff Loans & Advances', 'loans' => $loans, 'staff' => $this->staffList(), 'rules' => $this->svc->rules(),
            'stats' => [
                'outstanding' => (clone $all)->where('status', 'active')->sum('balance'),
                'pending' => (clone $all)->where('status', 'pending')->count(),
                'to_disburse' => (clone $all)->where('status', 'approved')->sum('amount'),
                'recovered_month' => LoanRepayment::where('paid_on', '>=', now()->startOfMonth())->where('source', '!=', 'waiver')->sum('amount'),
                'active' => (clone $all)->where('status', 'active')->count(),
            ],
        ]);
    }

    public function quote(Request $request)
    {
        $d = $request->validate(['staff_id' => 'required|integer', 'type' => 'required|in:loan,advance,cooperative', 'amount' => 'required|numeric|min:1', 'months' => 'required|integer|min:1|max:60']);
        return response()->json($this->svc->eligibility((int) $d['staff_id'], $d['type'], (float) $d['amount'], (int) $d['months']));
    }

    public function store(Request $request)
    {
        $d = $request->validate([
            'staff_id' => 'required|integer|exists:staffbioinfo,id', 'type' => 'required|in:loan,advance,cooperative',
            'amount' => 'required|numeric|min:1', 'months' => 'required|integer|min:1|max:60', 'purpose' => 'nullable|string|max:500',
            'guarantor_staff_id' => 'nullable|integer|different:staff_id', 'override' => 'nullable|boolean',
        ]);
        try {
            $loan = $this->svc->apply((int) $d['staff_id'], $d, (int) auth()->id(), (bool) ($d['override'] ?? false));
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
        return redirect()->route('payroll.loans.show', $loan)->with('success', 'Application recorded. It now needs approval.');
    }

    public function show(LoanAdvance $loan)
    {
        $loan->load(['repaymentSchedule', 'repayments.recorder:id,name', 'approvedBy:id,name', 'createdBy:id,name']);
        $name = fn ($id) => $id ? DB::table('staffbioinfo as s')->join('users as u', 'u.id', '=', 's.userid')->where('s.id', $id)->value('u.name') : null;
        return view('finance.loans.show', [
            'pagetitle' => $loan->reference_no, 'loan' => $loan, 'staffName' => $name($loan->staff_id), 'guarantorName' => $name($loan->guarantor_staff_id),
            'check' => $loan->status === 'pending' ? $this->svc->eligibility((int) $loan->staff_id, $loan->type, (float) $loan->amount, (int) $loan->repayment_months, $loan->id) : null,
            'payout' => $loan->disbursement_method === 'paystack' && $loan->disbursement_reference ? \App\Models\PayoutBatch::where('reference', $loan->disbursement_reference)->first() : null,
        ]);
    }

    public function approve(Request $request, LoanAdvance $loan)
    {
        $d = $request->validate(['amount' => 'nullable|numeric|min:1', 'months' => 'nullable|integer|min:1|max:60', 'rate' => 'nullable|numeric|min:0|max:100', 'first_month' => 'nullable|date_format:Y-m']);
        return $this->run(fn () => $this->svc->approve($loan, (int) auth()->id(), array_filter($d, fn ($v) => $v !== null && $v !== '')), 'Approved.');
    }

    public function reject(Request $request, LoanAdvance $loan)
    {
        $d = $request->validate(['reason' => 'required|string|max:255']);
        return $this->run(fn () => $this->svc->reject($loan, (int) auth()->id(), $d['reason']), 'Declined.');
    }

    public function disburse(Request $request, LoanAdvance $loan)
    {
        $d = $request->validate(['method' => 'required|in:paystack,transfer,cash', 'reference' => 'nullable|string|max:80']);
        try {
            $batch = $this->svc->disburse($loan, $d['method'], (int) auth()->id(), $d['reference'] ?? null);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
        return $batch
            ? redirect()->route('payroll.payouts.show', $batch)->with('success', 'Payout prepared. Another authorised person must release it.')
            : back()->with('success', 'Recorded as paid. Repayments start next month.');
    }

    public function repay(Request $request, LoanAdvance $loan)
    {
        $d = $request->validate(['amount' => 'required|numeric|min:1', 'source' => 'required|in:cash,transfer', 'reference' => 'nullable|string|max:80', 'paid_on' => 'nullable|date|before_or_equal:today', 'note' => 'nullable|string|max:255']);
        if ($loan->status !== 'active') return back()->with('error', 'Only an active loan can take repayments.');
        $this->svc->recordRepayment($loan, (float) $d['amount'], $d['source'], (int) auth()->id(), null, $d['reference'] ?? null, $d['paid_on'] ?? null, $d['note'] ?? null);
        return back()->with('success', 'Repayment recorded.');
    }

    public function pause(Request $request, LoanAdvance $loan)
    {
        $d = $request->validate(['until' => 'nullable|date_format:Y-m']);
        $this->svc->pause($loan, $d['until'] ?? null, (int) auth()->id());
        return back()->with('success', ($d['until'] ?? null) ? 'Deductions paused.' : 'Deductions resumed.');
    }

    public function writeOff(Request $request, LoanAdvance $loan)
    {
        $d = $request->validate(['reason' => 'required|string|max:255']);
        return $this->run(fn () => $this->svc->writeOff($loan, (int) auth()->id(), $d['reason']), 'Balance written off.');
    }

    public function cancel(LoanAdvance $loan)
    {
        return $this->run(fn () => $this->svc->cancel($loan), 'Cancelled.');
    }

    public function saveRules(Request $request)
    {
        $d = $request->validate([
            'max_loan_months' => 'required|integer|min:1|max:60', 'max_loan_multiple' => 'required|numeric|min:0',
            'max_advance_percent' => 'required|numeric|min:0|max:100', 'max_advance_months' => 'required|integer|min:1|max:12',
            'max_deduction_percent' => 'required|numeric|min:0|max:100', 'default_interest_rate' => 'required|numeric|min:0|max:100',
            'min_service_months' => 'required|integer|min:0', 'guarantor_above' => 'required|numeric|min:0', 'coop_loan_multiple' => 'required|numeric|min:0',
        ]);
        $d['enabled'] = $request->boolean('enabled');
        $d['one_active_per_type'] = $request->boolean('one_active_per_type');
        FinanceSettings::put('loans', $d, (int) auth()->id());
        return back()->with('success', 'Loan rules saved.');
    }

    protected function run(callable $fn, string $ok)
    {
        try { $fn(); } catch (\Throwable $e) { return back()->with('error', $e->getMessage()); }
        return back()->with('success', $ok);
    }
}
