<?php

namespace App\Http\Controllers\Payment;

use App\Services\Parents\ParentAccountService;

use App\Http\Controllers\Controller;
use App\Models\OnlineFeePayment;
use App\Models\Schoolsession;
use App\Models\Schoolterm;
use App\Models\Student;
use App\Services\Payment\OnlineFeeCheckoutService;
use App\Services\Payment\PaystackGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Online school-fee payments with Paystack — student portal and bursary.
 */
class OnlineFeeController extends Controller
{
    public function __construct(protected OnlineFeeCheckoutService $checkout)
    {
        $this->middleware('permission:View student payments')->only(['myFees']);
        $this->middleware('permission:View online-fee-payments')->only(['index']);
        $this->middleware('permission:Create online-fee-payments')->only(['payFor', 'searchStudents']);
        $this->middleware('permission:Update online-fee-payments')->only(['verify']);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Checkout pages
    // ─────────────────────────────────────────────────────────────────────

    /** Student portal: pay my own fees. */
    public function myFees(Request $request)
    {
        $student = Student::find(auth()->user()->student_id);
        if (!$student) {
            return redirect()->route('dashboard')->with('error', 'Student profile not found.');
        }

        return $this->checkoutPage($request, $student, 'student');
    }

    /** Parent portal: pay for one of my children. */
    public function parentPay(Request $request, int $student)
    {
        abort_unless(ParentAccountService::isParentOf($request->user(), $student), 403, 'This student is not linked to your account.');

        return $this->checkoutPage($request, Student::findOrFail($student), 'parent');
    }

    /** Bursary: pay on behalf of a student. */
    public function payFor(Request $request, int $student)
    {
        $student = Student::findOrFail($student);

        return $this->checkoutPage($request, $student, 'staff');
    }

    protected function checkoutPage(Request $request, Student $student, string $mode)
    {
        [$sessions, $terms] = $this->periodOptions((int) $student->id);
        [$sessionId, $termId] = $this->resolvePeriod($request, (int) $student->id, $sessions);

        $quote = $this->checkout->quote($student, $termId, $sessionId);

        $history = OnlineFeePayment::where('student_id', $student->id)
            ->whereIn('status', ['success', 'pending', 'amount_mismatch'])
            ->latest()->limit(8)->get();

        return view('online-fees.checkout', [
            'pagetitle'         => $mode === 'staff' ? 'Online Payment for Student' : 'Pay School Fees',
            'mode'              => $mode,
            'student'           => $student,
            'quote'             => $quote,
            'sessions'          => $sessions,
            'terms'             => $terms,
            'selectedSessionId' => $sessionId,
            'selectedTermId'    => $termId,
            'history'           => $history,
            'gatewayReady'      => $this->checkout->gatewayReady(),
            'gateways'          => $this->checkout->readyGateways(),
            'minTotalKobo'      => OnlineFeeCheckoutService::MIN_TOTAL_KOBO,
        ]);
    }

    /** Start a Paystack checkout. Returns the Paystack URL to redirect to. */
    public function initialize(Request $request)
    {
        $data = $request->validate([
            'student_id' => 'required|integer',
            'term_id'    => 'required|integer|exists:schoolterm,id',
            'session_id' => 'required|integer|exists:schoolsession,id',
            'items'      => 'required|array|min:1',
            'items.*'    => 'nullable|string|max:20',
            'gateway'    => 'nullable|in:paystack,opay',
        ]);
        $gatewayKey = $data['gateway'] ?? (array_key_first($this->checkout->readyGateways()) ?: 'paystack');
        if (!array_key_exists($gatewayKey, $this->checkout->readyGateways())) {
            return response()->json(['success' => false, 'message' => 'That payment option is not available right now.'], 422);
        }

        $user    = $request->user();
        $student = Student::find((int) $data['student_id']);
        if (!$student) {
            return response()->json(['success' => false, 'message' => 'Student not found.'], 404);
        }

        $isSelf = (int) ($user->student_id ?? 0) === (int) $student->id;
        $allowed = $isSelf ? $user->can('View student payments')
            : (ParentAccountService::isParentOf($user, (int) $student->id) || $user->can('Create online-fee-payments'));
        if (!$allowed) {
            return response()->json(['success' => false, 'message' => 'You are not allowed to pay for this student.'], 403);
        }

        try {
            $payment = $this->checkout->start(
                $student, $user, (int) $data['term_id'], (int) $data['session_id'],
                array_filter($data['items'], fn ($v) => $v !== null && $v !== ''),
                route('online-fees.callback'),
                $gatewayKey
            );
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first(),
                'errors'  => collect($e->errors())->flatten()->values(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Online fee checkout failed', ['student' => $student->id, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Could not start the payment. Please try again.'], 500);
        }

        return response()->json([
            'success'           => true,
            'reference'         => $payment->reference,
            'authorization_url' => $payment->authorization_url,
        ]);
    }

    /** Paystack sends the payer back here. */
    public function callback(Request $request)
    {
        $reference = (string) ($request->query('reference') ?: $request->query('trxref'));
        if ($reference === '') {
            return redirect()->route('dashboard')->with('error', 'Missing payment reference.');
        }

        $this->checkout->finalize($reference, null, 'callback');

        return redirect()->route('online-fees.show', $reference);
    }

    /** Status / receipt page. */
    public function show(Request $request, string $reference)
    {
        $payment = OnlineFeePayment::with('items')->where('reference', $reference)->firstOrFail();
        $this->authorizeView($payment);

        // Still waiting? Ask Paystack once more.
        if ($payment->status === 'pending' && !$payment->posted_at
            && (!$payment->last_verified_at || $payment->last_verified_at->lt(now()->subSeconds(10)))) {
            $payment = $this->checkout->finalize($reference, null, 'status_check')->load('items');
        }

        $student = Student::find($payment->student_id);
        $school  = \App\Models\SchoolInformation::getActiveSchool() ?? \App\Models\SchoolInformation::first();
        $labels  = [
            'terms'    => Schoolterm::pluck('term', 'id'),
            'sessions' => Schoolsession::pluck('session', 'id'),
            'classes'  => DB::table('schoolclass')->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
                ->select('schoolclass.id', DB::raw("TRIM(CONCAT(schoolclass.schoolclass, ' ', COALESCE(schoolarm.arm, ''))) as name"))
                ->pluck('name', 'id'),
        ];

        return view('online-fees.transaction', [
            'pagetitle' => 'Payment ' . $payment->reference,
            'payment'   => $payment,
            'student'   => $student,
            'school'    => $school,
            'labels'    => $labels,
            'isStaff'   => auth()->user()->can('View online-fee-payments'),
        ]);
    }

    /** JSON status for the waiting screen. */
    public function status(string $reference)
    {
        $payment = OnlineFeePayment::where('reference', $reference)->firstOrFail();
        $this->authorizeView($payment);

        if ($payment->status === 'pending' && !$payment->posted_at) {
            $payment = $this->checkout->finalize($reference, null, 'status_check');
        }

        return response()->json(['status' => $payment->status, 'posted' => (bool) $payment->posted_at]);
    }

    protected function authorizeView(OnlineFeePayment $payment): void
    {
        $user = auth()->user();
        $own  = (int) ($user->student_id ?? 0) === (int) $payment->student_id
             || (int) $payment->payer_user_id === (int) $user->id
             || ParentAccountService::isParentOf($user, (int) $payment->student_id);

        abort_unless($own || $user->can('View online-fee-payments'), 403);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Paystack webhook (outside auth + CSRF)
    // ─────────────────────────────────────────────────────────────────────

    public function webhook(Request $request, PaystackGateway $paystack)
    {
        $payload = $request->getContent();

        if (!$paystack->validSignature($payload, $request->header('x-paystack-signature'))) {
            Log::warning('Paystack webhook: invalid signature', ['ip' => $request->ip()]);
            return response()->json(['message' => 'invalid signature'], 401);
        }

        $event = json_decode($payload, true) ?: [];
        $ref   = (string) ($event['data']['reference'] ?? '');

        // Salary / loan / expense payouts (Paystack Transfers).
        if (str_starts_with((string) ($event['event'] ?? ''), 'transfer.') && class_exists(\App\Services\Payroll\PayoutService::class)) {
            try {
                app(\App\Services\Payroll\PayoutService::class)->handleEvent((string) $event['event'], (array) ($event['data'] ?? []));
            } catch (\Throwable $e) {
                Log::error('Paystack transfer webhook failed', ['reference' => $ref, 'error' => $e->getMessage()]);
                return response()->json(['message' => 'retry'], 500);
            }
            return response()->json(['message' => 'ok']);
        }

        if (in_array($event['event'] ?? '', ['charge.success', 'charge.failed'], true) && str_starts_with($ref, 'CSK-')) {
            try {
                $this->checkout->finalize($ref, $event['data'], 'webhook');
            } catch (\Throwable $e) {
                Log::error('Paystack webhook processing failed', ['reference' => $ref, 'error' => $e->getMessage()]);
                return response()->json(['message' => 'retry'], 500); // Paystack retries
            }
        }

        return response()->json(['message' => 'ok']);
    }

    /**
     * OPay callback (outside auth + CSRF). The body is only a hint: the payment
     * is always re-checked with OPay's status API before anything is posted.
     */
    public function opayWebhook(Request $request, \App\Services\Payment\OpayGateway $opay)
    {
        $body = $request->json()->all() ?: (json_decode($request->getContent(), true) ?: []);
        $ref  = (string) ($body['payload']['reference'] ?? '');

        if (!$opay->validCallback($body)) {
            Log::notice('OPay callback signature not matched; re-checking with OPay anyway', ['reference' => $ref, 'ip' => $request->ip()]);
        }
        if ($ref !== '' && str_starts_with($ref, 'CSK-')) {
            $payment = OnlineFeePayment::where('reference', $ref)->where('gateway', 'opay')->first();
            if ($payment) {
                try {
                    $this->checkout->finalize($ref, null, 'webhook');
                } catch (\Throwable $e) {
                    Log::error('OPay callback processing failed', ['reference' => $ref, 'error' => $e->getMessage()]);
                    return response()->json(['message' => 'retry'], 500); // OPay retries for 72h
                }
            }
        }
        return response()->json(['message' => 'ok']);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Bursary: transactions list
    // ─────────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $q = OnlineFeePayment::query()
            ->leftJoin('studentRegistration as s', 's.id', '=', 'online_fee_payments.student_id')
            ->select('online_fee_payments.*', 's.firstname', 's.lastname', 's.othername', 's.admissionNo');

        if ($status = $request->get('status')) {
            $status === 'review'
                ? $q->where('online_fee_payments.needs_review', true)
                : $q->where('online_fee_payments.status', $status);
        }
        if ($search = trim((string) $request->get('q'))) {
            $q->where(function ($w) use ($search) {
                $w->where('online_fee_payments.reference', 'like', "%{$search}%")
                  ->orWhere('s.admissionNo', 'like', "%{$search}%")
                  ->orWhere('s.firstname', 'like', "%{$search}%")
                  ->orWhere('s.lastname', 'like', "%{$search}%");
            });
        }
        if ($from = $request->get('from')) $q->whereDate('online_fee_payments.created_at', '>=', $from);
        if ($to = $request->get('to'))     $q->whereDate('online_fee_payments.created_at', '<=', $to);

        $payments = $q->orderByDesc('online_fee_payments.id')->paginate(25)->withQueryString();

        $success = OnlineFeePayment::where('status', 'success');
        $stats = [
            'today'   => (int) (clone $success)->whereDate('paid_at', today())->sum('paid_kobo'),
            'month'   => (int) (clone $success)->whereBetween('paid_at', [now()->startOfMonth(), now()])->sum('paid_kobo'),
            'count'   => (int) (clone $success)->whereBetween('paid_at', [now()->startOfMonth(), now()])->count(),
            'pending' => OnlineFeePayment::where('status', 'pending')->count(),
            'review'  => OnlineFeePayment::where('needs_review', true)->count(),
        ];

        return view('online-fees.index', [
            'pagetitle'    => 'Online Fee Payments',
            'payments'     => $payments,
            'stats'        => $stats,
            'gatewayReady' => $this->checkout->gatewayReady(),
            'gatewayProblem' => $this->checkout->gatewayProblem(),
        ]);
    }

    /** Re-check a transaction with Paystack (e.g. a payer closed the tab). */
    public function verify(string $reference)
    {
        $payment = $this->checkout->finalize($reference, null, 'admin_verify');
        abort_unless($payment, 404);

        return back()->with(
            $payment->status === 'success' ? 'success' : 'error',
            $payment->reference . ': ' . $payment->statusLabel()
                . ($payment->posted_at ? ' — posted to the student\'s bills.' : '.')
        );
    }

    /** Student lookup for "pay on behalf of". */
    public function searchStudents(Request $request)
    {
        $term = trim((string) $request->get('q'));
        if (mb_strlen($term) < 2) {
            return response()->json([]);
        }

        $rows = Student::leftJoin('studentclass', 'studentclass.studentId', '=', 'studentRegistration.id')
            ->leftJoin('schoolclass', 'schoolclass.id', '=', 'studentclass.schoolclassid')
            ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->where(function ($w) use ($term) {
                $w->where('studentRegistration.admissionNo', 'like', "%{$term}%")
                  ->orWhere('studentRegistration.firstname', 'like', "%{$term}%")
                  ->orWhere('studentRegistration.lastname', 'like', "%{$term}%")
                  ->orWhere(DB::raw("CONCAT(studentRegistration.firstname, ' ', studentRegistration.lastname)"), 'like', "%{$term}%");
            })
            ->orderBy('studentRegistration.lastname')
            ->limit(15)
            ->get([
                'studentRegistration.id', 'studentRegistration.firstname', 'studentRegistration.lastname',
                'studentRegistration.admissionNo', 'schoolclass.schoolclass', 'schoolarm.arm',
            ])
            ->unique('id')
            ->map(fn ($s) => [
                'id'    => $s->id,
                'name'  => trim($s->lastname . ' ' . $s->firstname),
                'adm'   => $s->admissionNo,
                'class' => trim(($s->schoolclass ?? '') . ' ' . ($s->arm ?? '')),
                'url'   => route('online-fees.pay-for', $s->id),
            ])->values();

        return response()->json($rows);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Period helpers (same rules as the student's My Payments page)
    // ─────────────────────────────────────────────────────────────────────

    protected function periodOptions(int $studentId): array
    {
        $ids = collect()
            ->merge(DB::table('studentclass')->where('studentId', $studentId)->pluck('sessionid'))
            ->merge(DB::table('student_bill_payment_book')->where('student_id', $studentId)->pluck('session_id'))
            ->merge(DB::table('student_bill_payment')->where('student_id', $studentId)->pluck('session_id'))
            ->merge(Schoolsession::where('status', 'Current')->pluck('id'))
            ->map(fn ($v) => (int) $v)->filter()->unique();

        return [
            Schoolsession::whereIn('id', $ids)->orderByDesc('id')->get(['id', 'session', 'status']),
            Schoolterm::orderBy('id')->get(['id', 'term']),
        ];
    }

    protected function resolvePeriod(Request $request, int $studentId, Collection $sessions): array
    {
        $sessionId = (int) $request->get('session_id');
        if (!$sessionId || !$sessions->contains('id', $sessionId)) {
            $sessionId = (int) ($sessions->firstWhere('status', 'Current')->id ?? $sessions->first()->id ?? 0);
        }

        $termId = (int) $request->get('term_id');
        if (!$termId) {
            $termId = (int) (
                DB::table('studentclass')->where('studentId', $studentId)->where('sessionid', $sessionId)->max('termid')
                ?: DB::table('student_bill_payment_book')->where('student_id', $studentId)->where('session_id', $sessionId)->max('term_id')
                ?: Schoolterm::min('id')
            );
        }

        return [$sessionId ?: null, $termId ?: null];
    }
}
