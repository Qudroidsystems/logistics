<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\LoanAdvance;
use App\Models\StaffDutyClaim;
use App\Services\Loans\CoopService;
use App\Services\Loans\LoanService;
use App\Services\Payroll\AttendancePayService;
use App\Support\PayrollDocs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Staff self-service: loans & advances, cooperative savings, extra-duty claims, attendance. */
class MyFinanceController extends Controller
{
    public function __construct(protected LoanService $loans, protected CoopService $coop) {}

    protected function me(Request $request): object
    {
        $s = PayrollDocs::staffForUser($request->user()->id);
        abort_unless($s, 404, 'No staff record is linked to your account. Please contact the bursar.');
        $s->viewing_other = false;
        return $s;
    }

    protected function base(object $s, string $title, string $section): array
    {
        return ['s' => $s, 'pagetitle' => $title, 'section' => $section, 'q' => [], 'years' => [now()->year]];
    }

    // ── Loans ─────────────────────────────────────────────────────
    public function loans(Request $request)
    {
        $s = $this->me($request);
        $colleagues = DB::table('staffbioinfo as st')->join('users as u', 'u.id', '=', 'st.userid')->where('st.id', '!=', $s->id)->orderBy('u.name')->get(['st.id', 'u.name']);
        return view('finance.my-pay.loans', $this->base($s, 'My Loans', 'loans') + [
            'myLoans' => LoanAdvance::with('repaymentSchedule')->where('staff_id', $s->id)->latest()->get(),
            'guaranteeRequests' => LoanAdvance::where('guarantor_staff_id', $s->id)->where('guarantor_status', 'pending')->where('status', 'pending')->get(),
            'rules' => $this->loans->rules(), 'pay' => $this->loans->payFor((int) $s->id), 'savings' => $this->coop->balance((int) $s->id),
            'colleagues' => $colleagues,
        ]);
    }

    public function quote(Request $request)
    {
        $s = $this->me($request);
        $d = $request->validate(['type' => 'required|in:loan,advance,cooperative', 'amount' => 'required|numeric|min:1', 'months' => 'required|integer|min:1|max:60']);
        $e = $this->loans->eligibility((int) $s->id, $d['type'], (float) $d['amount'], (int) $d['months']);
        unset($e['pay']);
        return response()->json($e);
    }

    public function apply(Request $request)
    {
        $s = $this->me($request);
        $d = $request->validate([
            'type' => 'required|in:loan,advance,cooperative', 'amount' => 'required|numeric|min:1', 'months' => 'required|integer|min:1|max:60',
            'purpose' => 'required|string|max:500', 'guarantor_staff_id' => 'nullable|integer|exists:staffbioinfo,id', 'agree' => 'accepted',
        ]);
        try {
            $this->loans->apply((int) $s->id, $d, (int) $request->user()->id);
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
        return back()->with('success', 'Application sent. You will be notified when it is decided.');
    }

    public function guarantee(Request $request, LoanAdvance $loan)
    {
        $s = $this->me($request);
        abort_unless((int) $loan->guarantor_staff_id === (int) $s->id && $loan->guarantor_status === 'pending', 403);
        $this->loans->guarantorRespond($loan, $request->input('answer') === 'accept');
        return back()->with('success', 'Thank you — your answer was recorded.');
    }

    public function cancelLoan(Request $request, LoanAdvance $loan)
    {
        $s = $this->me($request);
        abort_unless((int) $loan->staff_id === (int) $s->id && $loan->status === 'pending', 403);
        $this->loans->cancel($loan);
        return back()->with('success', 'Application withdrawn.');
    }

    // ── Cooperative ───────────────────────────────────────────────
    public function cooperative(Request $request)
    {
        $s = $this->me($request);
        return view('finance.my-pay.cooperative', $this->base($s, 'My Cooperative Savings', 'cooperative') + $this->coop->statement((int) $s->id));
    }

    // ── Extra duty claims & attendance ────────────────────────────
    public function claims(Request $request)
    {
        $s = $this->me($request);
        $att = app(AttendancePayService::class);
        $period = \App\Models\PayrollPeriod::where('start_date', '<=', now())->where('end_date', '>=', now())->first();
        return view('finance.my-pay.claims', $this->base($s, 'Extra Duty & Attendance', 'claims') + [
            'claims' => StaffDutyClaim::where('staff_id', $s->id)->latest('work_date')->limit(60)->get(),
            'rates' => $att->settings()['rates'], 'attendanceOn' => (bool) $att->settings()['enabled'],
            'summary' => $period ? $att->summary((int) $s->id, $period, $s->date_of_employment ?? null) : null, 'period' => $period,
        ]);
    }

    public function claim(Request $request)
    {
        $s = $this->me($request);
        $d = $request->validate([
            'work_date' => 'required|date|before_or_equal:today|after:' . now()->subDays(60)->toDateString(),
            'type' => 'required|in:' . implode(',', array_keys(StaffDutyClaim::TYPES)), 'quantity' => 'required|numeric|min:0.5|max:50',
            'description' => 'required|string|max:255', 'amount' => 'nullable|numeric|min:0',
        ]);
        $rate = app(AttendancePayService::class)->rate($d['type']);
        $amount = $d['type'] === 'other' ? (float) ($d['amount'] ?? 0) : round($rate * (float) $d['quantity'], 2);
        if ($amount <= 0) return back()->withInput()->with('error', 'Enter an amount for this claim.');
        StaffDutyClaim::create($d + ['staff_id' => $s->id, 'rate' => $d['type'] === 'other' ? $amount : $rate, 'amount' => $amount, 'status' => 'pending', 'submitted_by' => $request->user()->id]);
        return back()->with('success', 'Claim submitted for approval.');
    }

    public function withdrawClaim(Request $request, StaffDutyClaim $claim)
    {
        $s = $this->me($request);
        abort_unless((int) $claim->staff_id === (int) $s->id && $claim->status === 'pending', 403);
        $claim->delete();
        return back()->with('success', 'Claim withdrawn.');
    }
}
