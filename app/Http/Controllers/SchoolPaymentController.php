<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Discount;
use App\Models\DiscountAssignment;
use App\Models\Scholarship;
use App\Models\ScholarshipAssignment;
use App\Models\SchoolBillModel;
use App\Models\SchoolBillTermSession;
use App\Models\Schoolclass;
use App\Models\SchoolInformation;
use App\Models\Schoolsession;
use App\Models\Schoolterm;
use App\Models\Student;
use App\Models\StudentBillPayment;
use App\Models\StudentBillPaymentBook;
use App\Models\StudentBillPaymentRecord;
use App\Models\Studentpicture;
use App\Services\Billing\ArrearsService;
use App\Services\Billing\BillAdjustmentService;
use App\Services\Billing\PaymentAuditService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PDF;
use Yajra\DataTables\Facades\DataTables;

class SchoolPaymentController extends Controller
{
    protected BillAdjustmentService $billAdjustment;
    protected PaymentAuditService $audit;
    protected ArrearsService $arrears;

    public function __construct(
        BillAdjustmentService $billAdjustment,
        PaymentAuditService $audit,
        ArrearsService $arrears
    ) {
        $this->billAdjustment = $billAdjustment;
        $this->audit          = $audit;
        $this->arrears        = $arrears;
    }

    /**
     * Display student list for payment selection (initial page shell only;
     * the table itself is populated by data() via server-side DataTables).
     */
    public function index(Request $request)
    {
        $pagetitle      = 'Student Payments';
        $schoolterms    = Schoolterm::all();
        $schoolsessions = Schoolsession::orderByDesc('id')->get();

        // One option per class + arm (e.g. "JSS 1 A"), keyed by schoolclass.id
        $classOptions = Schoolclass::leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->select(['schoolclass.id', 'schoolclass.schoolclass', 'schoolarm.arm as arm_name'])
            ->orderBy('schoolclass.schoolclass')
            ->orderBy('schoolarm.arm')
            ->get()
            ->map(fn ($c) => (object) [
                'id'    => $c->id,
                'label' => trim($c->schoolclass . ' ' . ($c->arm_name ?? '')),
            ])
            ->filter(fn ($c) => $c->label !== '')
            ->values();

        $defaultSessionId = $this->defaultListSessionId();

        $statusOptions = Student::whereNotNull('student_status')
            ->where('student_status', '!=', '')
            ->distinct()
            ->orderBy('student_status')
            ->pluck('student_status');

        return view('schoolpayment.index', compact(
            'pagetitle', 'schoolterms', 'schoolsessions', 'classOptions', 'statusOptions', 'defaultSessionId'
        ));
    }

    /**
     * Session the student list opens on: the Current session, or — when no
     * student has been placed in it yet (e.g. a new session was just set
     * Current) — the latest session that has class placements.
     */
    protected function defaultListSessionId(): ?int
    {
        $current = Schoolsession::where('status', 'Current')->value('id');

        if ($current && DB::table('studentclass')->where('sessionid', $current)->exists()) {
            return (int) $current;
        }

        $latest = DB::table('studentclass')->max('sessionid');

        return $latest ? (int) $latest : ($current ? (int) $current : null);
    }

    /** session_filter from the request: '' / 'all' = every session, missing = default. */
    protected function requestedListSession(Request $request): ?int
    {
        if (!$request->has('session_filter')) {
            return $this->defaultListSessionId();
        }
        $v = $request->input('session_filter');

        return ($v === null || $v === '' || $v === 'all') ? null : (int) $v;
    }

    /**
     * Stat card counts for the student payments index (AJAX).
     */
    public function stats(Request $request)
    {
        $now       = now();
        $sessionId = $this->requestedListSession($request);

        $base = Student::join('studentclass', 'studentclass.studentId', '=', 'studentRegistration.id')
            ->when($sessionId, fn ($q) => $q->where('studentclass.sessionid', $sessionId))
            ->when($request->filled('class_filter'), fn ($q) => $q->where('studentclass.schoolclassid', (int) $request->input('class_filter')));

        $total  = (clone $base)->distinct()->count('studentRegistration.id');
        $active = (clone $base)->where('studentRegistration.student_status', 'Active')->distinct()->count('studentRegistration.id');

        $studentIds = (clone $base)->distinct()->pluck('studentRegistration.id');

        $scholarshipCount = ScholarshipAssignment::whereIn('student_id', $studentIds)
            ->where('status', 'active')
            ->where('effective_from', '<=', $now)
            ->where(function ($q) use ($now) {
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', $now);
            })
            ->distinct('student_id')
            ->count('student_id');

        $discountCount = DiscountAssignment::whereIn('student_id', $studentIds)
            ->where('status', 'active')
            ->where('effective_from', '<=', $now)
            ->where(function ($q) use ($now) {
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', $now);
            })
            ->distinct('student_id')
            ->count('student_id');

        return response()->json([
            'stats' => [
                'total'       => $total,
                'active'      => $active,
                'scholarship' => $scholarshipCount,
                'discount'    => $discountCount,
            ],
        ]);
    }

    /**
     * Server-side DataTables endpoint for the student payments index.
     */
    public function data(Request $request)
    {
        $now = now();

        $query = Student::leftJoin('studentclass', 'studentclass.studentId', '=', 'studentRegistration.id')
            ->leftJoin('schoolclass', 'schoolclass.id', '=', 'studentclass.schoolclassid')
            ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->leftJoin('schoolterm', 'schoolterm.id', '=', 'studentclass.termid')
            ->leftJoin('schoolsession', 'schoolsession.id', '=', 'studentclass.sessionid')
            ->leftJoin('studentpicture', 'studentpicture.studentid', '=', 'studentRegistration.id')
            ->whereNotNull('studentclass.id')
            ->when($this->requestedListSession($request), fn ($q, $sid) => $q->where('studentclass.sessionid', $sid))
            ->select([
                'studentRegistration.id as id',
                'studentRegistration.admissionNo as admissionNo',
                'studentRegistration.firstname as firstname',
                'studentRegistration.lastname as lastname',
                'studentRegistration.othername as othername',
                'studentRegistration.gender as gender',
                'studentRegistration.student_status as student_status',
                'schoolclass.schoolclass as schoolclass',
                'schoolarm.arm as arm',
                'schoolterm.term as term',
                'schoolsession.session as session',
                'studentpicture.picture as picture',
            ]);

        $scholarshipIds = ScholarshipAssignment::where('status', 'active')
            ->where('effective_from', '<=', $now)
            ->where(function ($q) use ($now) {
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', $now);
            })
            ->pluck('student_id')
            ->flip();

        $discountIds = DiscountAssignment::where('status', 'active')
            ->where('effective_from', '<=', $now)
            ->where(function ($q) use ($now) {
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', $now);
            })
            ->pluck('student_id')
            ->flip();

        return DataTables::of($query)
            ->filter(function ($q) use ($request) {
                if ($search = $request->input('search.value')) {
                    $q->where(function ($qq) use ($search) {
                        $qq->where('studentRegistration.firstname', 'like', "%{$search}%")
                            ->orWhere('studentRegistration.lastname', 'like', "%{$search}%")
                            ->orWhere('studentRegistration.admissionNo', 'like', "%{$search}%")
                            ->orWhere('studentRegistration.othername', 'like', "%{$search}%")
                            ->orWhere('schoolclass.schoolclass', 'like', "%{$search}%")
                            ->orWhere(DB::raw("CONCAT(schoolclass.schoolclass, ' ', COALESCE(schoolarm.arm, ''))"), 'like', "%{$search}%");
                    });
                }
                if ($class = $request->input('class_filter')) {
                    // class id (class + arm); fall back to a class name for old links
                    ctype_digit((string) $class)
                        ? $q->where('schoolclass.id', (int) $class)
                        : $q->where('schoolclass.schoolclass', $class);
                }
                if ($status = $request->input('status_filter')) {
                    $q->where('studentRegistration.student_status', $status);
                }
            })
            ->addColumn('avatar', function ($s) {
                $initials = strtoupper(substr($s->firstname ?? '', 0, 1) . substr($s->lastname ?? '', 0, 1)) ?: 'ST';
                if ($s->picture && $s->picture !== 'unnamed.jpg' && $s->picture !== '') {
                    $url = asset('storage/images/student_avatars/' . $s->picture);
                    return '<div class="p-avatar"><img src="' . $url . '" alt="" onerror="this.remove()"></div>';
                }
                return '<div class="p-avatar">' . e($initials) . '</div>';
            })
            ->addColumn('full_name', function ($s) {
                $name = trim(($s->firstname ?? '') . ' ' . ($s->lastname ?? ''));
                return '<div class="fw-semibold" style="color:var(--p-primary)">' . e($name) . '</div>'
                    . '<div class="text-muted small">' . e($s->gender) . '</div>';
            })
            ->addColumn('class_display', function ($s) {
                $val = trim(($s->schoolclass ?? '') . ' ' . ($s->arm ?? ''));
                return $val !== '' ? e($val) : '—';
            })
            ->addColumn('term_session_display', function ($s) {
                return e(($s->term ?: '—') . ' · ' . ($s->session ?: '—'));
            })
            ->addColumn('status_badge', function ($s) {
                if (!$s->student_status) {
                    return '<span class="p-pill status-default"><i class="bi bi-dash"></i>Unknown</span>';
                }
                $cls = strtolower($s->student_status) === 'active' ? 'status-active' : 'status-inactive';
                return '<span class="p-pill ' . $cls . '"><i class="bi bi-circle-fill" style="font-size:6px"></i>' . e($s->student_status) . '</span>';
            })
            ->addColumn('adjustments', function ($s) use ($scholarshipIds, $discountIds) {
                $out = '';
                if (isset($scholarshipIds[$s->id])) {
                    $out .= '<span class="p-pill scholarship"><i class="bi bi-award-fill"></i>Scholarship</span> ';
                }
                if (isset($discountIds[$s->id])) {
                    $out .= '<span class="p-pill discount"><i class="bi bi-tag-fill"></i>Discount</span>';
                }
                return $out !== '' ? $out : '<span class="p-pill none"><i class="bi bi-dash"></i>None</span>';
            })
            ->addColumn('action', function ($s) {
                $name = trim(($s->firstname ?? '') . ' ' . ($s->lastname ?? ''));
                return '<button type="button" class="p-action-btn pay select-term-session-btn" '
                    . 'data-student-id="' . $s->id . '" data-student-name="' . e($name) . '" title="Manage Payment">'
                    . '<i class="bi bi-cash-coin"></i></button>';
            })
            ->rawColumns(['avatar', 'full_name', 'class_display', 'term_session_display', 'status_badge', 'adjustments', 'action'])
            ->make(true);
    }

    /**
     * Show payment details page (called from debtors list)
     */
    public function showPaymentDetails($studentId, $classId, $termId, $sessionId)
    {
        $pagetitle = 'Payment Details';
        $student   = Student::findOrFail($studentId);

        return view('payment.payment-details', compact(
            'pagetitle', 'studentId', 'classId', 'termId', 'sessionId'
        ));
    }

    /**
     * Termsession payments page (alternative entry point)
     */
    public function termsessionpayments(Request $request)
    {
        $studentId = (int) $request->get('studentId');
        $termid    = (int) $request->get('termid');
        $sessionid = (int) $request->get('sessionid');

        if (!$studentId || !$termid || !$sessionid) {
            return redirect()->route('schoolpayment.index')
                ->with('error', 'Invalid student, term, or session selected.');
        }

        $pagetitle = 'Student Payment Details';

        return view('schoolpayment.studentpayment', compact('pagetitle', 'studentId', 'termid', 'sessionid'));
    }

    /**
     * Term/Session selector page (legacy fallback — the index now uses a modal instead)
     */
    public function termSession(string $id)
    {
        $pagetitle      = 'Student Payments';
        $schoolterms    = Schoolterm::all();
        $schoolsessions = Schoolsession::all();

        $studentDetails = Student::leftJoin('studentpicture', 'studentpicture.studentid', '=', 'studentRegistration.id')
            ->where('studentRegistration.id', $id)
            ->select('studentRegistration.*', 'studentpicture.picture as avatar')
            ->first();

        return view('schoolpayment.termSession', compact('pagetitle', 'schoolterms', 'schoolsessions', 'id', 'studentDetails'));
    }

    /**
     * Comprehensive arrears / outstanding details page for a student.
     */
    public function arrearsDetails(Request $request, $studentId)
    {
        $studentId = (int) $studentId;

        $student = Student::leftJoin('studentpicture', 'studentpicture.studentid', '=', 'studentRegistration.id')
            ->where('studentRegistration.id', $studentId)
            ->select('studentRegistration.*', 'studentpicture.picture as avatar')
            ->firstOrFail();

        $excludeTermId    = (int) $request->get('exclude_term', 0);
        $excludeSessionId = (int) $request->get('exclude_session', 0);

        $arrears = $this->arrears->getStudentArrears(
            $studentId,
            $excludeTermId ?: null,
            $excludeSessionId ?: null
        );

        $pagetitle = 'Outstanding Arrears — ' . trim($student->firstname . ' ' . $student->lastname);
        $fullName  = $this->getFullNameWithOther(
            $student->firstname,
            $student->lastname,
            $student->othername ?? ''
        );

        return view('schoolpayment.arrears', compact(
            'pagetitle',
            'student',
            'fullName',
            'arrears',
            'studentId'
        ));
    }

    // ── Private helpers ───────────────────────────────────────────────────

    private function getStudentAvatarUrl($picture): ?string
    {
        if (!$picture || $picture === 'unnamed.jpg' || $picture === '') {
            return null;
        }
        return asset('storage/images/student_avatars/' . $picture);
    }

    private function getFullNameWithOther($firstname, $lastname, $othername = ''): string
    {
        $fullName = trim($firstname . ' ' . $lastname);
        if (!empty($othername)) {
            $fullName .= ' (' . $othername . ')';
        }
        return $fullName;
    }

    private function fetchStudentData(int $studentId, int $termid, int $sessionid): ?object
    {
        return Student::where('studentRegistration.id', $studentId)
            ->leftJoin('studentclass', 'studentclass.studentId', '=', 'studentRegistration.id')
            ->leftJoin('parentRegistration', 'parentRegistration.id', '=', 'studentRegistration.id')
            ->leftJoin('studentpicture', 'studentpicture.studentid', '=', 'studentRegistration.id')
            ->leftJoin('schoolclass', 'schoolclass.id', '=', 'studentclass.schoolclassid')
            ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->select([
                'studentRegistration.id as id',
                'studentRegistration.admissionNo as admissionNo',
                'studentRegistration.firstname as firstname',
                'studentRegistration.lastname as lastname',
                'studentRegistration.othername as othername',
                'studentRegistration.home_address2 as homeadd',
                'parentRegistration.father_phone as phone',
                'studentRegistration.statusId as statusId',
                'studentRegistration.student_status as student_status',
                'studentpicture.picture as avatar',
                'schoolclass.schoolclass as schoolclass',
                'schoolarm.arm as arm',
                'studentclass.schoolclassid as schoolclassId',
            ])
            ->first();
    }

    /**
     * Helper method to prepare school info with base64 images for PDF
     */
    private function prepareSchoolInfoForPdf($schoolInfo)
    {
        if (!$schoolInfo) {
            return null;
        }

        if ($schoolInfo->school_logo && Storage::disk('public')->exists($schoolInfo->school_logo)) {
            $path = Storage::disk('public')->path($schoolInfo->school_logo);
            $mime = mime_content_type($path) ?: 'image/png';
            $data = base64_encode(file_get_contents($path));
            $schoolInfo->logo_base64 = "data:{$mime};base64,{$data}";
        } else {
            $schoolInfo->logo_base64 = null;
        }

        if ($schoolInfo->school_stamp && Storage::disk('public')->exists($schoolInfo->school_stamp)) {
            $path = Storage::disk('public')->path($schoolInfo->school_stamp);
            $mime = mime_content_type($path) ?: 'image/png';
            $data = base64_encode(file_get_contents($path));
            $schoolInfo->stamp_base64 = "data:{$mime};base64,{$data}";
        } else {
            $schoolInfo->stamp_base64 = null;
        }

        if (empty($schoolInfo->formatted_phones) && !empty($schoolInfo->school_phones)) {
            $schoolInfo->formatted_phones = implode(', ', $schoolInfo->school_phones);
        }

        return $schoolInfo;
    }

    // ── Source-of-truth payment ledger helpers ────────────────────────────
    //
    // These two helpers are the fix for the "amount paid / progress"
    // inconsistencies and the post-delete calculation drift: instead of
    // incrementing/decrementing a counter column on student_bill_payment_book
    // (which can drift out of sync, especially around deletes and class
    // changes), every write recomputes amount_paid/amount_owed directly from
    // the SUM of student_bill_payment_record rows. That sum is always correct
    // by construction, regardless of what happened before it.

    /**
     * Authoritative "amount paid so far" for one student/bill/class/term/session,
     * computed straight from the payment ledger. Soft-deleted records are
     * explicitly excluded — this query is a raw join, so it does not benefit
     * from Eloquent's automatic SoftDeletes scope the way a plain Eloquent
     * query on StudentBillPaymentRecord would.
     */
    private function getTotalPaidForBill(int $studentId, int $schoolBillId, int $classId, int $termId, int $sessionId): float
    {
        return (float) DB::table('student_bill_payment_record')
            ->join('student_bill_payment', 'student_bill_payment.id', '=', 'student_bill_payment_record.student_bill_payment_id')
            ->where('student_bill_payment.student_id', $studentId)
            ->where('student_bill_payment.school_bill_id', $schoolBillId)
            ->where('student_bill_payment.class_id', $classId)
            ->where('student_bill_payment.termid_id', $termId)
            ->where('student_bill_payment.session_id', $sessionId)
            ->whereNull('student_bill_payment.deleted_at')
            ->whereNull('student_bill_payment_record.deleted_at')
            ->sum('student_bill_payment_record.amount_paid');
    }

    /**
     * Batch version of getTotalPaidForBill for an entire student/class/term/session —
     * used when rendering the bill list so we don't run one query per bill.
     * Returns [school_bill_id => total_paid].
     */
    private function getTotalPaidByBillMap(int $studentId, int $classId, int $termId, int $sessionId)
    {
        return DB::table('student_bill_payment_record')
            ->join('student_bill_payment', 'student_bill_payment.id', '=', 'student_bill_payment_record.student_bill_payment_id')
            ->where('student_bill_payment.student_id', $studentId)
            ->where('student_bill_payment.class_id', $classId)
            ->where('student_bill_payment.termid_id', $termId)
            ->where('student_bill_payment.session_id', $sessionId)
            ->whereNull('student_bill_payment.deleted_at')
            ->whereNull('student_bill_payment_record.deleted_at')
            ->groupBy('student_bill_payment.school_bill_id')
            ->select('student_bill_payment.school_bill_id', DB::raw('SUM(student_bill_payment_record.amount_paid) as total_paid'))
            ->pluck('total_paid', 'school_bill_id');
    }

    /**
     * Recompute and persist the student_bill_payment_book row for one bill,
     * from the ledger (never by adding/subtracting a delta). Creates the book
     * row if it doesn't exist yet. Always call this after any write to
     * student_bill_payment_record (create OR delete) for that bill.
     */
    private function syncPaymentBook(
        int $studentId,
        int $schoolBillId,
        int $classId,
        int $termId,
        int $sessionId,
        float $adjustedAmount,
        float $originalAmount,
        float $scholarshipDeduction,
        float $discountDeduction,
        ?int $generatedBy
    ): array {
        $totalPaid = $this->getTotalPaidForBill($studentId, $schoolBillId, $classId, $termId, $sessionId);
        $newBalance = max(0, $adjustedAmount - $totalPaid);
        $status = ($newBalance <= 0 && $totalPaid > 0) ? 'Completed' : 'Pending';

        $book = StudentBillPaymentBook::where([
            'student_id'     => $studentId,
            'school_bill_id' => $schoolBillId,
            'class_id'       => $classId,
            'term_id'        => $termId,
            'session_id'     => $sessionId,
        ])->first();

        $payload = [
            'amount_paid'           => $totalPaid,
            'amount_owed'           => $newBalance,
            'payment_status'        => $status,
            'scholarship_deduction' => $scholarshipDeduction,
            'discount_deduction'    => $discountDeduction,
            'adjusted_amount'       => $adjustedAmount,
            'updated_at'            => now(),
        ];

        if ($book) {
            DB::table('student_bill_payment_book')->where('id', $book->id)->update($payload);
        } else {
            StudentBillPaymentBook::create(array_merge($payload, [
                'student_id'      => $studentId,
                'school_bill_id'  => $schoolBillId,
                'class_id'        => $classId,
                'term_id'         => $termId,
                'session_id'      => $sessionId,
                'original_amount' => $originalAmount,
                'generated_by'    => $generatedBy,
            ]));
        }

        return ['total_paid' => $totalPaid, 'balance' => $newBalance, 'status' => $status];
    }

    // ── AJAX: Get payment details ─────────────────────────────────────────

    public function getPaymentDetailsAjax(Request $request)
    {
        try {
            $studentId = (int) $request->studentId;
            $termid    = (int) $request->termid;
            $sessionid = (int) $request->sessionid;

            if (!$studentId || !$termid || !$sessionid) {
                return response()->json(['success' => false, 'message' => 'Invalid parameters'], 400);
            }

            $studentdata = $this->fetchStudentData($studentId, $termid, $sessionid);

            if (!$studentdata) {
                return response()->json(['success' => false, 'message' => 'Student not found'], 404);
            }

            $schoolclassId = $studentdata->schoolclassId;

            $rawBills = DB::table('school_bill_class_term_session')
                ->where('school_bill_class_term_session.class_id', $schoolclassId)
                ->where('school_bill_class_term_session.termid_id', $termid)
                ->where('school_bill_class_term_session.session_id', $sessionid)
                ->whereNull('school_bill_class_term_session.deleted_at')
                ->join('school_bill', 'school_bill.id', '=', 'school_bill_class_term_session.bill_id')
                ->where(function ($q) use ($studentdata) {
                    $q->whereNull('school_bill.statusId')
                      ->orWhere('school_bill.statusId', '')
                      ->orWhere('school_bill.statusId', 0)
                      ->orWhere('school_bill.statusId', $studentdata->statusId);
                })
                ->select([
                    'school_bill_class_term_session.id as id',
                    'school_bill.id as schoolbillid',
                    'school_bill.title as title',
                    'school_bill.description as description',
                    'school_bill.bill_amount as amount',
                    'school_bill.statusId as billStatusId',
                ])
                ->get();

            $now = now();
            $scholarshipAssignment = ScholarshipAssignment::where('student_id', $studentId)
                ->where('status', 'active')
                ->where('effective_from', '<=', $now)
                ->where(function ($q) use ($now) {
                    $q->whereNull('effective_to')->orWhere('effective_to', '>=', $now);
                })
                ->with('scholarship')
                ->first();

            $discountAssignments = DiscountAssignment::where('student_id', $studentId)
                ->where('status', 'active')
                ->where('effective_from', '<=', $now)
                ->where(function ($q) use ($now) {
                    $q->whereNull('effective_to')->orWhere('effective_to', '>=', $now);
                })
                ->with('discount')
                ->get();

            $student_bill_info = $rawBills->map(function ($bill) use ($studentId, $scholarshipAssignment, $discountAssignments) {
                $adj = $this->billAdjustment->buildBillAdjustment(
                    $studentId,
                    $bill->schoolbillid,
                    (float) $bill->amount,
                    $scholarshipAssignment,
                    $discountAssignments
                );
                $bill->original_amount       = $adj['original_amount'];
                $bill->scholarship_deduction = $adj['scholarship_deduction'];
                $bill->scholarship_label     = $adj['scholarship_label'];
                $bill->discount_deduction    = $adj['discount_deduction'];
                $bill->discount_labels       = $adj['discount_labels'];
                $bill->total_savings         = $adj['total_savings'];
                $bill->adjusted_amount       = $adj['adjusted_amount'];
                return $bill;
            });

            // FIX: filtered by class_id too, so a book row from a different
            // class/enrollment can never be mismatched against this student's
            // current bills.
            $studentpaymentbillbook = StudentBillPaymentBook::where('student_id', $studentId)
                ->where('class_id', $schoolclassId)
                ->where('term_id', $termid)
                ->where('session_id', $sessionid)
                ->get();

            // FIX: authoritative amount-paid-per-bill, computed straight from
            // the ledger instead of trusting the book's running counter.
            $paymentSumsByBill = $this->getTotalPaidByBillMap($studentId, $schoolclassId, $termid, $sessionid);

            $billsData     = [];
            $totalAdjusted = 0;
            $totalPaid     = 0;

            foreach ($student_bill_info as $bill) {
                $bookEntry   = $studentpaymentbillbook->where('school_bill_id', $bill->schoolbillid)->first();
                $amountPaid  = isset($paymentSumsByBill[$bill->schoolbillid])
                    ? (float) $paymentSumsByBill[$bill->schoolbillid]
                    : ($bookEntry ? (float) $bookEntry->amount_paid : 0); // fallback only for legacy rows with no ledger history
                $adjustedAmt = (float) $bill->adjusted_amount;
                $balance     = max(0, $adjustedAmt - $amountPaid);
                $progress    = $adjustedAmt > 0 ? min(100, ($amountPaid / $adjustedAmt) * 100) : 0;

                $totalAdjusted += $adjustedAmt;
                $totalPaid     += $amountPaid;

                $pendingPayment = StudentBillPayment::where('student_id', $studentId)
                    ->where('school_bill_id', $bill->schoolbillid)
                    ->where('termid_id', $termid)
                    ->where('session_id', $sessionid)
                    ->where('delete_status', '1')
                    ->first();

                $billsData[] = [
                    'id'                    => $bill->schoolbillid,
                    'class_id'              => $schoolclassId,
                    'title'                 => $bill->title,
                    'description'           => $bill->description,
                    'original_amount'       => $bill->original_amount,
                    'adjusted_amount'       => $adjustedAmt,
                    'amount_paid'           => $amountPaid,
                    'balance'               => $balance,
                    'progress'              => $progress,
                    'scholarship_deduction' => $bill->scholarship_deduction,
                    'discount_deduction'    => $bill->discount_deduction,
                    'total_savings'         => $bill->total_savings,
                    'scholarship_label'     => $bill->scholarship_label,
                    'discount_labels'       => is_array($bill->discount_labels)
                                                ? implode(', ', $bill->discount_labels)
                                                : ($bill->discount_labels ?? ''),
                    'has_pending_invoice'   => !is_null($pendingPayment),
                    'is_paid'               => $balance <= 0 && $amountPaid > 0,
                    'is_partial'            => $amountPaid > 0 && $balance > 0,
                ];
            }

            // Payment records (pending / not yet invoiced).
            // FIX: the join to student_bill_payment_record now explicitly
            // excludes soft-deleted rows AND the MAX(id) subquery excludes
            // them too — otherwise a "deleted" record could keep winning the
            // MAX(id) and reappear here even after being removed.
            $paymentRecords = StudentBillPayment::where('student_bill_payment.student_id', $studentId)
                ->where('student_bill_payment.termid_id', $termid)
                ->where('student_bill_payment.session_id', $sessionid)
                ->where('student_bill_payment.delete_status', '1')
                ->leftJoin('student_bill_payment_record', function ($join) {
                    $join->on('student_bill_payment_record.student_bill_payment_id', '=', 'student_bill_payment.id')
                        ->whereNull('student_bill_payment_record.deleted_at')
                        ->whereRaw('student_bill_payment_record.id = (
                            SELECT MAX(id) FROM student_bill_payment_record spr
                            WHERE spr.student_bill_payment_id = student_bill_payment.id
                            AND spr.deleted_at IS NULL
                        )');
                })
                ->leftJoin('school_bill', 'school_bill.id', '=', 'student_bill_payment.school_bill_id')
                ->leftJoin('users', 'users.id', '=', 'student_bill_payment.generated_by')
                ->select([
                    'student_bill_payment.id as paymentId',
                    'student_bill_payment.school_bill_id as school_bill_id',
                    'student_bill_payment_record.id as recordId',
                    'student_bill_payment.created_at as receivedDate',
                    'student_bill_payment.payment_method as paymentMethod',
                    'student_bill_payment.status as paymentStatus',
                    'school_bill.title as title',
                    'school_bill.description as description',
                    'school_bill.bill_amount as billAmount',
                    'student_bill_payment_record.amount_paid as totalAmountPaid',
                    'student_bill_payment_record.amount_owed as balance',
                    DB::raw('COALESCE(users.name, "Unknown") as receivedBy'),
                ])
                ->get();

            // Payment history (invoiced / completed).
            // FIX: same soft-delete exclusion on the joined record table.
            $paymentHistory = StudentBillPayment::where('student_bill_payment.student_id', $studentId)
                ->where('student_bill_payment.termid_id', $termid)
                ->where('student_bill_payment.session_id', $sessionid)
                ->where('student_bill_payment.delete_status', '0')
                ->leftJoin('student_bill_payment_record', function ($join) {
                    $join->on('student_bill_payment_record.student_bill_payment_id', '=', 'student_bill_payment.id')
                        ->whereNull('student_bill_payment_record.deleted_at');
                })
                ->leftJoin('school_bill', 'school_bill.id', '=', 'student_bill_payment.school_bill_id')
                ->leftJoin('users', 'users.id', '=', 'student_bill_payment.generated_by')
                ->select([
                    'student_bill_payment.id as paymentId',
                    'student_bill_payment.school_bill_id as school_bill_id',
                    'student_bill_payment.class_id as classId',
                    'student_bill_payment.termid_id as termId',
                    'student_bill_payment.session_id as sessionId',
                    'student_bill_payment_record.id as recordId',
                    'student_bill_payment_record.created_at as receivedDate',
                    'student_bill_payment.payment_method as paymentMethod',
                    DB::raw('CASE WHEN student_bill_payment_record.complete_payment = 1 THEN "Completed" ELSE "Pending" END as paymentStatus'),
                    'school_bill.title as title',
                    'school_bill.description as description',
                    'school_bill.bill_amount as billAmount',
                    'student_bill_payment_record.amount_paid as totalAmountPaid',
                    'student_bill_payment_record.amount_owed as balance',
                    DB::raw('COALESCE(users.name, "Unknown") as receivedBy'),
                    'student_bill_payment_record.complete_payment as completePayment',
                ])
                ->orderBy('student_bill_payment_record.created_at', 'desc')
                ->get();

            $totalOutstanding = max(0, $totalAdjusted - $totalPaid);
            $totalSavings     = $student_bill_info->sum('total_savings');
            $totalOriginal    = $student_bill_info->sum('original_amount');

            $fullName = $this->getFullNameWithOther($studentdata->firstname, $studentdata->lastname, $studentdata->othername ?? '');

            // Past-term/session arrears (excludes the currently selected term+session)
            $arrears = $this->arrears->getStudentArrears($studentId, $termid, $sessionid);

            return response()->json([
                'success' => true,
                'data'    => [
                    'student' => [
                        'id'             => $studentdata->id,
                        'name'           => $fullName,
                        'firstname'      => $studentdata->firstname,
                        'lastname'       => $studentdata->lastname,
                        'othername'      => $studentdata->othername ?? '',
                        'admissionNo'    => $studentdata->admissionNo,
                        'avatar'         => $studentdata->avatar,
                        'schoolclass'    => $studentdata->schoolclass,
                        'schoolclassId'  => $schoolclassId,
                        'arm'            => $studentdata->arm,
                        'student_status' => $studentdata->student_status,
                        'statusId'       => $studentdata->statusId,
                    ],
                    'bills'           => $billsData,
                    'payment_records' => $paymentRecords,
                    'payment_history' => $paymentHistory,
                    'scholarship'     => $scholarshipAssignment ? [
                        'title'        => $scholarshipAssignment->scholarship->title ?? 'Scholarship',
                        'value'        => $scholarshipAssignment->value,
                        'value_type'   => $scholarshipAssignment->value_type,
                        'effective_to' => $scholarshipAssignment->effective_to,
                    ] : null,
                    'discounts' => $discountAssignments->map(function ($da) {
                        return $da->discount ? [
                            'title'      => $da->discount->title,
                            'value'      => $da->value,
                            'value_type' => $da->value_type,
                        ] : null;
                    })->filter()->values(),
                    'totals' => [
                        'original'    => $totalOriginal,
                        'adjusted'    => $totalAdjusted,
                        'paid'        => $totalPaid,
                        'outstanding' => $totalOutstanding,
                        'savings'     => $totalSavings,
                    ],
                    'term'    => optional(Schoolterm::find($termid))->term,
                    'session' => optional(Schoolsession::find($sessionid))->session,
                    'arrears' => $arrears,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('getPaymentDetailsAjax error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ── Store individual payment ──────────────────────────────────────────

  
        // ── Store individual payment ──────────────────────────────────────────

    public function store(Request $request)
    {
        try {
            // Normalize ids and amount before validation (avoid commas / empty strings)
            $paymentAmountRaw = (float) str_replace(',', '', (string) $request->input('payment_amount', 0));

            $request->merge([
                'student_id'     => (int) $request->input('student_id'),
                'class_id'       => (int) $request->input('class_id'),
                'term_id'        => (int) $request->input('term_id'),
                'session_id'     => (int) $request->input('session_id'),
                'school_bill_id' => (int) $request->input('school_bill_id'),
                'payment_amount' => $paymentAmountRaw,
            ]);

            $request->validate([
                'student_id'            => 'required|integer|exists:studentRegistration,id',
                'class_id'              => 'required|integer|exists:schoolclass,id',
                'term_id'               => 'required|integer|exists:schoolterm,id',
                'session_id'            => 'required|integer|exists:schoolsession,id',
                'school_bill_id'        => 'required|integer|exists:school_bill,id',
                'actual_amount'         => 'required|numeric|min:0',
                'adjusted_amount'       => 'nullable|numeric|min:0',
                'balance2'              => 'required|numeric|min:0',
                'last_amount_paid'      => 'required|numeric|min:0',
                'payment_amount'        => 'required|numeric|min:0.01',
                'payment_amount2'       => 'nullable|numeric|min:0',
                'payment_method2'       => 'required|string|in:Bank Deposit,School POS,Bank Transfer,Cheque',
                'scholarship_deduction' => 'nullable|numeric|min:0',
                'discount_deduction'    => 'nullable|numeric|min:0',
            ]);

            if (!Auth::check()) {
                return response()->json(['success' => false, 'message' => 'Not authenticated. Please login again.'], 401);
            }

            DB::beginTransaction();

            $studentId    = (int) $request->student_id;
            $classId      = (int) $request->class_id;
            $termId       = (int) $request->term_id;
            $sessionId    = (int) $request->session_id;
            $schoolBillId = (int) $request->school_bill_id;

            $studentPayment = StudentBillPayment::where([
                'student_id'     => $studentId,
                'school_bill_id' => $schoolBillId,
                'class_id'       => $classId,
                'termid_id'      => $termId,
                'session_id'     => $sessionId,
            ])->first();

            // delete_status = '1' → payment recorded but not yet invoiced (pending invoice).
            // Block a second payment until the user generates the invoice.
            // delete_status = '0' → already invoiced. Allow a NEW payment cycle by
            // starting a fresh shell (do not mix into the old invoice batch).
            if ($studentPayment && (string) $studentPayment->delete_status === '1') {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'There is already a payment recorded that has not been invoiced yet. Generate the invoice first, then you can record another payment.',
                ], 422);
            }

            if ($studentPayment && (string) $studentPayment->delete_status === '0') {
                // Previous cycle was invoiced — force create of a new shell below.
                $studentPayment = null;
            }

            $bill = SchoolBillModel::findOrFail($schoolBillId);

            // ALWAYS recompute server-side — never trust client-submitted
            // adjusted_amount / scholarship_deduction / discount_deduction.
            $adj = $this->billAdjustment->buildBillAdjustment(
                $studentId,
                $schoolBillId,
                (float) $bill->bill_amount
            );
            $finalAdjustedAmount = $adj['adjusted_amount'];

            // Authoritative amount already paid from the ledger.
            $currentPaid    = $this->getTotalPaidForBill($studentId, $schoolBillId, $classId, $termId, $sessionId);
            $currentBalance = max(0, $finalAdjustedAmount - $currentPaid);

            if ($currentBalance <= 0) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'This bill is already fully paid.'], 422);
            }

            $paymentAmount = (float) $request->payment_amount;

            if ($paymentAmount > $currentBalance + 0.01) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Payment amount (₦' . number_format($paymentAmount, 2) . ') exceeds the outstanding balance (₦' . number_format($currentBalance, 2) . ').',
                ], 422);
            }

            $balance         = round($currentBalance - $paymentAmount, 2);
            $completePayment = $balance <= 0 ? 1 : 0;
            $status          = $completePayment ? 'Completed' : 'Pending';
            $generatedBy     = Auth::id();

            if ($studentPayment) {
                DB::table('student_bill_payment')
                    ->where('id', $studentPayment->id)
                    ->update([
                        'payment_method' => $request->payment_method2,
                        'status'         => $status,
                        'generated_by'   => $generatedBy,
                        'delete_status'  => '1',
                        'updated_at'     => now(),
                    ]);
            } else {
                $studentPayment = StudentBillPayment::create([
                    'student_id'     => $studentId,
                    'school_bill_id' => $schoolBillId,
                    'class_id'       => $classId,
                    'termid_id'      => $termId,
                    'session_id'     => $sessionId,
                    'payment_method' => $request->payment_method2,
                    'status'         => $status,
                    'generated_by'   => $generatedBy,
                    'delete_status'  => '1',
                ]);
            }

            StudentBillPaymentRecord::create([
                'student_bill_payment_id' => $studentPayment->id,
                'class_id'                => $classId,
                'termid_id'               => $termId,
                'session_id'              => $sessionId,
                'amount_paid'             => $paymentAmount,
                'last_payment'            => $paymentAmount,
                'amount_owed'             => $balance,
                'total_bill'              => $finalAdjustedAmount,
                'complete_payment'        => $completePayment,
                'generated_by'            => $generatedBy,
            ]);

            // Recompute the book row from the ledger (SUM).
            $this->syncPaymentBook(
                $studentId,
                $schoolBillId,
                $classId,
                $termId,
                $sessionId,
                $finalAdjustedAmount,
                (float) $bill->bill_amount,
                $adj['scholarship_deduction'],
                $adj['discount_deduction'],
                $generatedBy
            );

            DB::commit();

            $this->audit->log('recorded', [
                'student_id'              => $studentId,
                'school_bill_id'          => $schoolBillId,
                'student_bill_payment_id' => $studentPayment->id,
                'class_id'                => $classId,
                'term_id'                 => $termId,
                'session_id'              => $sessionId,
                'amount'                  => $paymentAmount,
                'payment_method'          => $request->payment_method2,
                'entity_type'             => 'payment',
            ], null, [
                'amount'                => $paymentAmount,
                'adjusted_amount'       => $finalAdjustedAmount,
                'scholarship_deduction' => $adj['scholarship_deduction'],
                'discount_deduction'    => $adj['discount_deduction'],
                'new_balance'           => $balance,
            ], 'Individual payment recorded');

            // Receipt to parents (SMS / WhatsApp / email) — sent after the response.
            \App\Services\Messaging\PaymentReceiptNotifier::queue(
                $studentId, (float) $paymentAmount, (string) $request->payment_method2,
                'SP-' . $studentPayment->id . '-' . now()->format('ymdHis'), $termId, $sessionId
            );

            return response()->json(['success' => true, 'message' => 'Payment recorded successfully.']);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payment store error: ', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to record payment: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ── Bulk store payment ────────────────────────────────────────────────

    public function bulkStore(Request $request)
    {
        try {
            $paymentAmountRaw = (float) str_replace(',', '', (string) $request->input('payment_amount', 0));

            $request->merge([
                'student_id'     => (int) $request->input('student_id'),
                'class_id'       => (int) $request->input('class_id'),
                'term_id'        => (int) $request->input('term_id'),
                'session_id'     => (int) $request->input('session_id'),
                'payment_amount' => $paymentAmountRaw,
            ]);

            $request->validate([
                'student_id'                            => 'required|integer|exists:studentRegistration,id',
                'class_id'                              => 'required|integer|exists:schoolclass,id',
                'term_id'                               => 'required|integer|exists:schoolterm,id',
                'session_id'                            => 'required|integer|exists:schoolsession,id',
                'payment_amount'                        => 'required|numeric|min:0.01',
                'payment_method'                        => 'required|string|in:Bank Deposit,School POS,Bank Transfer,Cheque',
                'bill_payments'                         => 'required|array|min:1',
                'bill_payments.*.school_bill_id'        => 'required|integer|exists:school_bill,id',
                'bill_payments.*.title'                 => 'nullable|string|max:255',
                // Client amounts are ignored — kept only so old front-end payloads don't fail validation.
                'bill_payments.*.adjusted_amount'       => 'nullable|numeric|min:0',
                'bill_payments.*.balance'               => 'nullable|numeric|min:0',
                'bill_payments.*.scholarship_deduction' => 'nullable|numeric|min:0',
                'bill_payments.*.discount_deduction'    => 'nullable|numeric|min:0',
            ]);

            if (!Auth::check()) {
                return response()->json(['success' => false, 'message' => 'Not authenticated. Please login again.'], 401);
            }

            DB::beginTransaction();

            $studentId          = (int) $request->student_id;
            $classId            = (int) $request->class_id;
            $termId             = (int) $request->term_id;
            $sessionId          = (int) $request->session_id;
            $generatedBy        = Auth::id();
            $totalPaymentAmount = (float) $request->payment_amount;
            $remainingAmount    = $totalPaymentAmount;
            $paymentsProcessed  = [];

            foreach ($request->bill_payments as $billPayment) {
                if ($remainingAmount <= 0) {
                    break;
                }

                $schoolBillId = (int) $billPayment['school_bill_id'];
                $bill         = SchoolBillModel::findOrFail($schoolBillId);

                // SERVER-SIDE RECOMPUTE — never trust client adjusted amounts.
                $adj = $this->billAdjustment->buildBillAdjustment(
                    $studentId,
                    $schoolBillId,
                    (float) $bill->bill_amount
                );
                $adjustedAmount       = $adj['adjusted_amount'];
                $scholarshipDeduction = $adj['scholarship_deduction'];
                $discountDeduction    = $adj['discount_deduction'];

                $alreadyPaid    = $this->getTotalPaidForBill($studentId, $schoolBillId, $classId, $termId, $sessionId);
                $currentBalance = max(0, $adjustedAmount - $alreadyPaid);

                if ($currentBalance <= 0) {
                    continue;
                }

                $paymentForThisBill = min($remainingAmount, $currentBalance);
                if ($paymentForThisBill <= 0) {
                    continue;
                }

                $remainingAmount -= $paymentForThisBill;
                $newTotalPaid     = $alreadyPaid + $paymentForThisBill;
                $newBalance       = max(0, round($adjustedAmount - $newTotalPaid, 2));
                $completePayment  = $newBalance <= 0 ? 1 : 0;
                $status           = $completePayment ? 'Completed' : 'Pending';

                $studentPayment = StudentBillPayment::where([
                    'student_id'     => $studentId,
                    'school_bill_id' => $schoolBillId,
                    'class_id'       => $classId,
                    'termid_id'      => $termId,
                    'session_id'     => $sessionId,
                ])->first();

                // Same rule as individual store:
                // '1' = pending invoice → block
                // '0' = already invoiced → start a new shell
                if ($studentPayment && (string) $studentPayment->delete_status === '1') {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Cannot make payment — a pending (uninvoiced) payment already exists for one of the selected bills. Generate the invoice first.',
                    ], 422);
                }

                if ($studentPayment && (string) $studentPayment->delete_status === '0') {
                    $studentPayment = null;
                }

                if ($studentPayment) {
                    DB::table('student_bill_payment')
                        ->where('id', $studentPayment->id)
                        ->update([
                            'payment_method' => $request->payment_method,
                            'status'         => $status,
                            'generated_by'   => $generatedBy,
                            'delete_status'  => '1',
                            'updated_at'     => now(),
                        ]);
                } else {
                    $studentPayment = StudentBillPayment::create([
                        'student_id'     => $studentId,
                        'school_bill_id' => $schoolBillId,
                        'class_id'       => $classId,
                        'termid_id'      => $termId,
                        'session_id'     => $sessionId,
                        'payment_method' => $request->payment_method,
                        'status'         => $status,
                        'generated_by'   => $generatedBy,
                        'delete_status'  => '1',
                    ]);
                }

                StudentBillPaymentRecord::create([
                    'student_bill_payment_id' => $studentPayment->id,
                    'class_id'                => $classId,
                    'termid_id'               => $termId,
                    'session_id'              => $sessionId,
                    'amount_paid'             => $paymentForThisBill,
                    'last_payment'            => $paymentForThisBill,
                    'amount_owed'             => $newBalance,
                    'total_bill'              => $adjustedAmount,
                    'complete_payment'        => $completePayment,
                    'generated_by'            => $generatedBy,
                ]);

                $this->syncPaymentBook(
                    $studentId,
                    $schoolBillId,
                    $classId,
                    $termId,
                    $sessionId,
                    $adjustedAmount,
                    (float) $bill->bill_amount,
                    $scholarshipDeduction,
                    $discountDeduction,
                    $generatedBy
                );

                $paymentsProcessed[] = [
                    'school_bill_id'        => $schoolBillId,
                    'bill_title'            => $billPayment['title'] ?? $bill->title,
                    'amount_paid'           => $paymentForThisBill,
                    'balance'               => $newBalance,
                    'scholarship_deduction' => $scholarshipDeduction,
                    'discount_deduction'    => $discountDeduction,
                ];
            }

            if (count($paymentsProcessed) === 0) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'No payments were applied. Selected bills may already be fully paid.',
                ], 422);
            }

            DB::commit();

            $this->audit->log('bulk_recorded', [
                'student_id'     => $studentId,
                'class_id'       => $classId,
                'term_id'        => $termId,
                'session_id'     => $sessionId,
                'amount'         => $totalPaymentAmount - $remainingAmount,
                'payment_method' => $request->payment_method,
                'entity_type'    => 'bulk_payment',
            ], null, ['bills' => $paymentsProcessed], 'Bulk payment recorded (server-recomputed adjustments)');

            // One receipt for the whole bulk payment.
            \App\Services\Messaging\PaymentReceiptNotifier::queue(
                $studentId, (float) ($totalPaymentAmount - $remainingAmount), (string) $request->payment_method,
                'BP-' . $studentId . '-' . now()->format('ymdHis'), $termId, $sessionId
            );

            return response()->json([
                'success' => true,
                'message' => 'Bulk payment recorded successfully.',
                'data'    => [
                    'payments_processed' => $paymentsProcessed,
                    'total_paid'         => $totalPaymentAmount - $remainingAmount,
                    'remaining_amount'   => $remainingAmount,
                ],
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk payment store error: ', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to record payment: ' . $e->getMessage(),
            ], 500);
        }
    }

  

    // ── Delete payment record ─────────────────────────────────────────────

    public function deletestudentpayment($recordId)
    {
        try {
            DB::beginTransaction();

            $paymentRecord = StudentBillPaymentRecord::find($recordId);
            if (!$paymentRecord) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'Payment record not found.'], 404);
            }

            $studentPayment = StudentBillPayment::find($paymentRecord->student_bill_payment_id);
            if (!$studentPayment) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'Parent payment record not found.'], 404);
            }

            if ($studentPayment->delete_status == '0') {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete payment record after invoice is generated.',
                ], 403);
            }

            $oldValues = [
                'amount_paid' => $paymentRecord->amount_paid,
                'amount_owed' => $paymentRecord->amount_owed,
            ];

            $recordIdToCheck      = $paymentRecord->id;
            $studentBillPaymentId = $studentPayment->id;
            $studentId    = $studentPayment->student_id;
            $schoolBillId = $studentPayment->school_bill_id;
            $classId      = $studentPayment->class_id;
            $termId       = $studentPayment->termid_id;
            $sessionId    = $studentPayment->session_id;

            // FIX: forceDelete() — both models use SoftDeletes, so a plain
            // delete() only set deleted_at and left the row in place. Since
            // several read queries elsewhere join this table with raw SQL
            // (bypassing Eloquent's SoftDeletes scope), a soft-deleted row
            // kept reappearing as if the delete never happened.
            $paymentRecord->forceDelete();

            // Belt-and-braces: prove the row is actually gone at the DB level.
            $stillExists = DB::table('student_bill_payment_record')
                ->where('id', $recordIdToCheck)
                ->exists();

            if ($stillExists) {
                DB::rollBack();
                Log::error('deletestudentpayment: row still present after forceDelete()', [
                    'recordId' => $recordIdToCheck,
                ]);
                return response()->json(['success' => false, 'message' => 'Delete did not take effect. Please contact support.'], 500);
            }

            // Recompute the book row from what's left in the ledger — never
            // by subtracting the deleted amount from a stored counter.
            $paymentBook = StudentBillPaymentBook::where([
                'student_id'     => $studentId,
                'school_bill_id' => $schoolBillId,
                'class_id'       => $classId,
                'term_id'        => $termId,
                'session_id'     => $sessionId,
            ])->first();

            if ($paymentBook) {
                $adjustedTotal = (float) ($paymentBook->adjusted_amount ?: $paymentBook->original_amount);
                $newAmountPaid = $this->getTotalPaidForBill($studentId, $schoolBillId, $classId, $termId, $sessionId);
                $newAmountOwed = max(0, $adjustedTotal - $newAmountPaid);

                DB::table('student_bill_payment_book')
                    ->where('id', $paymentBook->id)
                    ->update([
                        'amount_paid'    => $newAmountPaid,
                        'amount_owed'    => $newAmountOwed,
                        'payment_status' => ($newAmountOwed <= 0 && $newAmountPaid > 0) ? 'Completed' : 'Pending',
                        'updated_at'     => now(),
                    ]);
            }

            // Clean up the parent payment shell if no records remain — force
            // delete here too, for the same reason as above.
            $remaining = StudentBillPaymentRecord::where('student_bill_payment_id', $studentBillPaymentId)->count();
            if ($remaining == 0) {
                $studentPayment->forceDelete();
            }

            DB::commit();

            $this->audit->log('deleted', [
                'student_id'                     => $studentId,
                'school_bill_id'                 => $schoolBillId,
                'student_bill_payment_id'        => $studentBillPaymentId,
                'student_bill_payment_record_id' => $recordIdToCheck,
                'class_id'                       => $classId,
                'term_id'                        => $termId,
                'session_id'                     => $sessionId,
                'amount'                         => $oldValues['amount_paid'],
                'entity_type'                    => 'payment',
            ], $oldValues, null, 'Payment record deleted');

            return response()->json(['success' => true, 'message' => 'Payment deleted successfully.']);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Delete payment error: ', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to delete: ' . $e->getMessage()], 500);
        }
    }

    // ── Invoice ───────────────────────────────────────────────────────────

    public function invoice(Request $request, $studentId, $schoolclassid, $termid, $sessionid)
    {
        $pagetitle = 'Student Payment Invoice';

        $student = $this->fetchStudentData((int) $studentId, (int) $termid, (int) $sessionid);

        if (!$student) {
            return redirect()->route('payment.index')->with('error', 'Student not found or not enrolled.');
        }

        if (!$schoolclassid && $student->schoolclassId) {
            $schoolclassid = $student->schoolclassId;
        }

        if (!$schoolclassid) {
            return redirect()->route('payment.index')->with('error', 'Could not resolve student class for invoice.');
        }

        $safeAdmission = preg_replace('/[\/\\\\]/', '-', $student->admissionNo ?? $studentId);

        // ================================================================
        // INVOICE NUMBER GENERATION - UNIQUE WITH TIMESTAMP
        // ================================================================
        // Format: INV-[ADMISSION]-[YYYYMMDDHHMMSS]-[UNIQUE_ID]
        // Example: INV-STU123-20260304143215-A7B3
        // ================================================================
        $timestamp = date('YmdHis');
        $uniqueId = strtoupper(substr(uniqid(), -4));
        $invoiceNumber = 'INV-' . $safeAdmission . '-' . $timestamp . '-' . $uniqueId;

        try {
            $allClassBills = DB::table('school_bill_class_term_session')
                ->where('school_bill_class_term_session.class_id', $schoolclassid)
                ->where('school_bill_class_term_session.termid_id', $termid)
                ->where('school_bill_class_term_session.session_id', $sessionid)
                ->whereNull('school_bill_class_term_session.deleted_at')
                ->join('school_bill', 'school_bill.id', '=', 'school_bill_class_term_session.bill_id')
                ->where(function ($q) use ($student) {
                    $q->whereNull('school_bill.statusId')
                      ->orWhere('school_bill.statusId', '')
                      ->orWhere('school_bill.statusId', 0)
                      ->orWhere('school_bill.statusId', $student->statusId);
                })
                ->select([
                    'school_bill.id as school_bill_id',
                    'school_bill.title as title',
                    'school_bill.description as description',
                    'school_bill.bill_amount as amount',
                ])
                ->get();
        } catch (\Exception $e) {
            Log::error('Invoice bill query failed: ' . $e->getMessage());
            $allClassBills = collect();
        }

        // NOTE: this query only joins school_bill / users — it never joins
        // student_bill_payment_record directly, so it isn't affected by the
        // soft-delete issue. Per-bill amounts paid are computed just below
        // using an Eloquent query on StudentBillPaymentRecord, which already
        // applies the SoftDeletes scope automatically.
        $paidBills = StudentBillPayment::where('student_bill_payment.student_id', $studentId)
            ->where('student_bill_payment.class_id', $schoolclassid)
            ->where('student_bill_payment.termid_id', $termid)
            ->where('student_bill_payment.session_id', $sessionid)
            ->leftJoin('school_bill', 'school_bill.id', '=', 'student_bill_payment.school_bill_id')
            ->leftJoin('users', 'users.id', '=', 'student_bill_payment.generated_by')
            ->select([
                'student_bill_payment.id as paymentid',
                'student_bill_payment.school_bill_id',
                'student_bill_payment.created_at as payment_date',
                'student_bill_payment.payment_method as payment_method',
                'school_bill.title as title',
                'school_bill.description as description',
                'school_bill.bill_amount as amount',
                DB::raw('COALESCE(users.name, "Unknown") as receivedBy'),
            ])
            ->groupBy([
                'student_bill_payment.school_bill_id', 'student_bill_payment.id',
                'student_bill_payment.created_at', 'student_bill_payment.payment_method',
                'school_bill.title', 'school_bill.description', 'school_bill.bill_amount', 'users.name',
            ])
            ->get()
            ->keyBy('school_bill_id');

        $now = now();
        $scholarshipAssignment = ScholarshipAssignment::where('student_id', $studentId)
            ->where('status', 'active')
            ->where('effective_from', '<=', $now)
            ->where(function ($q) use ($now) {
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', $now);
            })
            ->with('scholarship')
            ->first();

        $discountAssignments = DiscountAssignment::where('student_id', $studentId)
            ->where('status', 'active')
            ->where('effective_from', '<=', $now)
            ->where(function ($q) use ($now) {
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', $now);
            })
            ->with('discount')
            ->get();

        $payments = $allClassBills->map(function ($bill) use ($paidBills, $studentId, $invoiceNumber, $scholarshipAssignment, $discountAssignments) {
            $adj      = $this->billAdjustment->buildBillAdjustment(
                $studentId,
                $bill->school_bill_id,
                (float) $bill->amount,
                $scholarshipAssignment,
                $discountAssignments
            );
            $paidBill = $paidBills->get($bill->school_bill_id);

            if ($paidBill) {
                // Eloquent query — SoftDeletes scope applies automatically.
                $paymentRecords       = StudentBillPaymentRecord::where('student_bill_payment_id', $paidBill->paymentid)->orderBy('created_at')->get();
                $totalPaidForThisBill = $paymentRecords->sum('amount_paid');
                $lastRecord           = $paymentRecords->sortByDesc('created_at')->first();
                $lastPaymentAmount    = $lastRecord ? $lastRecord->amount_paid : 0;
                $previousPaid         = $totalPaidForThisBill - $lastPaymentAmount;
                $currentBalance       = max(0, $adj['adjusted_amount'] - $totalPaidForThisBill);

                if ($lastRecord && !$lastRecord->invoiceNo) {
                    StudentBillPaymentRecord::where('id', $lastRecord->id)
                        ->update(['invoiceNo' => $invoiceNumber]);
                }

                return (object) [
                    'school_bill_id'        => $bill->school_bill_id,
                    'title'                 => $bill->title,
                    'description'           => $bill->description,
                    'amount'                => $adj['adjusted_amount'],
                    'original_amount'       => $adj['original_amount'],
                    'scholarship_deduction' => $adj['scholarship_deduction'],
                    'discount_deduction'    => $adj['discount_deduction'],
                    'total_savings'         => $adj['total_savings'],
                    'previousPaid'          => $previousPaid,
                    'todayPaid'             => $lastPaymentAmount,
                    'amountPaid'            => $totalPaidForThisBill,
                    'balance'               => $currentBalance,
                    'paymentMethod'         => $paidBill->payment_method ?? 'N/A',
                    'receivedBy'            => $paidBill->receivedBy ?? 'Unknown',
                    'paymentDate'           => $lastRecord ? $lastRecord->created_at : $paidBill->payment_date,
                    'complete_payment'      => $currentBalance == 0 ? 1 : 0,
                    'invoiceNo'             => $lastRecord->invoiceNo ?? $invoiceNumber,
                ];
            }

            return (object) [
                'school_bill_id'        => $bill->school_bill_id,
                'title'                 => $bill->title,
                'description'           => $bill->description,
                'amount'                => $adj['adjusted_amount'],
                'original_amount'       => $adj['original_amount'],
                'scholarship_deduction' => $adj['scholarship_deduction'],
                'discount_deduction'    => $adj['discount_deduction'],
                'total_savings'         => $adj['total_savings'],
                'previousPaid'          => 0,
                'todayPaid'             => 0,
                'amountPaid'            => 0,
                'balance'               => $adj['adjusted_amount'],
                'paymentMethod'         => 'N/A',
                'receivedBy'            => 'N/A',
                'paymentDate'           => null,
                'complete_payment'      => 0,
                'invoiceNo'             => null,
            ];
        });

        $payments = $payments->groupBy('school_bill_id')
            ->map(fn($g) => $g->sortByDesc('paymentDate')->first())
            ->values()
            ->sortByDesc('paymentDate');

        $totalBillAmount   = $payments->sum('amount');
        $totalPreviousPaid = $payments->sum('previousPaid');
        $totalTodayPaid    = $payments->sum('todayPaid');
        $totalPaid         = $payments->sum('amountPaid');
        $totalOutstanding  = $payments->sum('balance');
        $totalSavings      = $payments->sum('total_savings');

        $schoolInfo = SchoolInformation::first();
        $schoolInfo = $this->prepareSchoolInfoForPdf($schoolInfo);

        $schoolterm    = optional(Schoolterm::find($termid))->term    ?? 'N/A';
        $schoolsession = optional(Schoolsession::find($sessionid))->session ?? 'N/A';

        $fullName = $this->getFullNameWithOther($student->firstname, $student->lastname, $student->othername ?? '');

        $data = [
            'pagetitle'          => $pagetitle,
            'invoiceNumber'      => $invoiceNumber,
            'schoolterm'         => $schoolterm,
            'schoolsession'      => $schoolsession,
            'studentId'          => $studentId,
            'termid'             => $termid,
            'sessionid'          => $sessionid,
            'schoolclassid'      => $schoolclassid,
            'totalBillAmount'    => $totalBillAmount,
            'totalPreviousPaid'  => $totalPreviousPaid,
            'totalTodayPaid'     => $totalTodayPaid,
            'totalSavings'       => $totalSavings,
            'totalPaid'          => $totalPaid,
            'totalOutstanding'   => $totalOutstanding,
            'schoolInfo'         => $schoolInfo,
            'studentdata'        => $student ? collect([$student]) : collect([]),
            'studentpaymentbill' => $payments,
            'studentFullName'    => $fullName,
        ];

        if ($request->has('download_pdf')) {
            StudentBillPayment::where('student_id', $studentId)
                ->where('class_id', $schoolclassid)
                ->where('termid_id', $termid)
                ->where('session_id', $sessionid)
                ->where('delete_status', '1')
                ->update(['delete_status' => '0']);

            $this->audit->log('invoice_confirmed', [
                'student_id' => $studentId, 'class_id' => $schoolclassid,
                'term_id'    => $termid,    'session_id' => $sessionid,
                'entity_type'=> 'invoice',
            ], null, ['invoice_number' => $invoiceNumber], 'Invoice generated via PDF download');

            $safeFilename = 'invoice_' . preg_replace('/[\/\\\\]/', '-', $student->admissionNo ?? $studentId) . '_' . date('Ymd_His') . '.pdf';

            $pdf = PDF::loadView('schoolpayment.studentinvoicepdf', $data)
                ->setOptions([
                    'defaultFont' => 'DejaVu Sans',
                    'isRemoteEnabled' => false,
                    'isHtml5ParserEnabled' => true,
                    'isPhpEnabled' => false,
                ]);
            return $pdf->download($safeFilename);
        }

        return view('schoolpayment.studentinvoice', $data);
    }

    /**
     * Explicit POST action to finalise / confirm an invoice from the web view
     * (moves delete_status 1 → 0 for this student/class/term/session). This
     * is the only other place — besides the PDF download above — that flips
     * this flag, so a page refresh of the invoice view alone can never do it.
     */
    public function confirmInvoice(Request $request, $studentId, $schoolclassid, $termid, $sessionid)
    {
        if (!Auth::check()) {
            return response()->json(['success' => false, 'message' => 'Not authenticated.'], 401);
        }

        $updated = StudentBillPayment::where('student_id', $studentId)
            ->where('class_id', $schoolclassid)
            ->where('termid_id', $termid)
            ->where('session_id', $sessionid)
            ->where('delete_status', '1')
            ->update(['delete_status' => '0']);

        $this->audit->log('invoice_confirmed', [
            'student_id' => $studentId, 'class_id' => $schoolclassid,
            'term_id'    => $termid,    'session_id' => $sessionid,
            'entity_type'=> 'invoice',
        ], null, ['records_finalized' => $updated], 'Invoice confirmed from web view');

        return response()->json([
            'success' => true,
            'message' => 'Invoice confirmed. ' . $updated . ' payment record(s) finalised.',
        ]);
    }

    // ── Statement ─────────────────────────────────────────────────────────

    public function statement(Request $request, $studentId, $schoolclassid, $termid, $sessionid)
    {
        $pagetitle = 'Student Payment Statement';

        $student = $this->fetchStudentData((int) $studentId, (int) $termid, (int) $sessionid);

        if (!$student) {
            return redirect()->route('payment.index')->with('error', 'Student not found.');
        }

        $resolvedClassId = $schoolclassid ?: $student->schoolclassId;

        $student_bill_info = DB::table('school_bill_class_term_session')
            ->where('school_bill_class_term_session.class_id', $resolvedClassId)
            ->where('school_bill_class_term_session.termid_id', $termid)
            ->where('school_bill_class_term_session.session_id', $sessionid)
            ->whereNull('school_bill_class_term_session.deleted_at')
            ->join('school_bill', 'school_bill.id', '=', 'school_bill_class_term_session.bill_id')
            ->where(function ($q) use ($student) {
                $q->whereNull('school_bill.statusId')
                  ->orWhere('school_bill.statusId', '')
                  ->orWhere('school_bill.statusId', 0)
                  ->orWhere('school_bill.statusId', $student->statusId);
            })
            ->select([
                DB::raw('school_bill.id as schoolbillid'),
                'school_bill.title',
                'school_bill.description',
                DB::raw('school_bill.bill_amount as amount'),
            ])
            ->get();

        $studentpaymentbillbook = StudentBillPaymentBook::where('student_id', $studentId)
            ->where('class_id', $resolvedClassId)
            ->where('term_id', $termid)
            ->where('session_id', $sessionid)
            ->get();

        // FIX: the join to student_bill_payment_record is now scoped to
        // exclude soft-deleted rows, so a deleted payment can never show up
        // (or be summed into totals) on the printed statement.
        $studentpaymentbill = StudentBillPayment::where('student_bill_payment.student_id', $studentId)
            ->where('student_bill_payment.class_id', $resolvedClassId)
            ->where('student_bill_payment.termid_id', $termid)
            ->where('student_bill_payment.session_id', $sessionid)
            ->leftJoin('student_bill_payment_record', function ($join) {
                $join->on('student_bill_payment_record.student_bill_payment_id', '=', 'student_bill_payment.id')
                    ->whereNull('student_bill_payment_record.deleted_at');
            })
            ->leftJoin('school_bill', 'school_bill.id', '=', 'student_bill_payment.school_bill_id')
            ->leftJoin('users', 'users.id', '=', 'student_bill_payment.generated_by')
            ->select([
                'student_bill_payment_record.created_at as payment_date',
                'student_bill_payment.payment_method as payment_method',
                'school_bill.title as title',
                'school_bill.description as description',
                'school_bill.bill_amount as amount',
                'student_bill_payment_record.amount_paid as amount_paid',
                'student_bill_payment_record.amount_owed as balance',
                DB::raw('CASE WHEN student_bill_payment_record.complete_payment = 1 THEN "Completed" ELSE "Pending" END as payment_status'),
                DB::raw('COALESCE(users.name, "Unknown") as received_by'),
            ])
            ->orderBy('student_bill_payment_record.created_at', 'desc')
            ->get();

        $now = now();
        $scholarshipAssignment = ScholarshipAssignment::where('student_id', $studentId)
            ->where('status', 'active')
            ->where('effective_from', '<=', $now)
            ->where(function ($q) use ($now) {
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', $now);
            })
            ->with('scholarship')
            ->first();

        $discountAssignments = DiscountAssignment::where('student_id', $studentId)
            ->where('status', 'active')
            ->where('effective_from', '<=', $now)
            ->where(function ($q) use ($now) {
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', $now);
            })
            ->with('discount')
            ->get();

        $totalSchoolBill = $student_bill_info->sum(function ($bill) use ($studentId, $scholarshipAssignment, $discountAssignments) {
            $adj = $this->billAdjustment->buildBillAdjustment(
                $studentId,
                $bill->schoolbillid,
                (float) $bill->amount,
                $scholarshipAssignment,
                $discountAssignments
            );
            return $adj['adjusted_amount'];
        });

        // FIX: sum straight from the ledger rather than the book counter, so
        // this always matches the amounts shown on the live payment page.
        $totalPaid        = $studentpaymentbillbook->sum('amount_paid');
        $totalOutstanding = max(0, $totalSchoolBill - $totalPaid);

        $schoolInfo = SchoolInformation::first();
        $schoolInfo = $this->prepareSchoolInfoForPdf($schoolInfo);

        $safeAdmission   = preg_replace('/[\/\\\\]/', '-', $student->admissionNo ?? $studentId);
        $statementNumber = 'STMT-' . $safeAdmission . '-' . date('YmdHis') . '-' . strtoupper(substr(uniqid(), -4));

        $schoolterm    = optional(Schoolterm::find($termid))->term    ?? 'N/A';
        $schoolsession = optional(Schoolsession::find($sessionid))->session ?? 'N/A';

        $fullName = $this->getFullNameWithOther($student->firstname, $student->lastname, $student->othername ?? '');

        $data = [
            'pagetitle'          => $pagetitle,
            'studentpaymentbill' => $studentpaymentbill,
            'totalSchoolBill'    => $totalSchoolBill,
            'totalPaid'          => $totalPaid,
            'totalOutstanding'   => $totalOutstanding,
            'schoolInfo'         => $schoolInfo,
            'statementNumber'    => $statementNumber,
            'schoolterm'         => $schoolterm,
            'schoolsession'      => $schoolsession,
            'studentId'          => $studentId,
            'termid'             => $termid,
            'sessionid'          => $sessionid,
            'studentdata'        => $student ? collect([$student]) : collect([]),
            'studentFullName'    => $fullName,
        ];

        $safeFilename = 'statement_' . $safeAdmission . '_' . date('Ymd_His') . '.pdf';
        $pdf = PDF::loadView('schoolpayment.studentstatement', $data)
            ->setOptions([
                'defaultFont' => 'DejaVu Sans',
                'isRemoteEnabled' => false,
                'isHtml5ParserEnabled' => true,
                'isPhpEnabled' => false,
            ]);
        return $pdf->download($safeFilename);
    }
}