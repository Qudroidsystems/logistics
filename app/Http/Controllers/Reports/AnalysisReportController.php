<?php
// app/Http/Controllers/Reports/AnalysisReportController.php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\DiscountAssignment;
use App\Models\ScholarshipAssignment;
use App\Models\SchoolInformation;
use App\Services\Billing\BillAdjustmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class AnalysisReportController extends Controller
{
    protected BillAdjustmentService $billAdjustment;

    public function __construct(BillAdjustmentService $billAdjustment)
    {
        $this->billAdjustment = $billAdjustment;
    }

    /**
     * Display the class analysis page with filters
     */
    public function index()
    {
        $pagetitle  = 'Class Financial Analysis';
        $schoolInfo = SchoolInformation::getActiveSchool();

        $classes = DB::table('schoolclass')
            ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->select(
                'schoolclass.id',
                DB::raw("CONCAT(schoolclass.schoolclass, ' ', COALESCE(schoolarm.arm, '')) as display_name")
            )
            ->orderBy('schoolclass.schoolclass')
            ->get();

        $terms    = DB::table('schoolterm')->orderBy('id')->get();
        $sessions = DB::table('schoolsession')->orderBy('session', 'desc')->get();

        return view('reports.analysis.class-analysis', compact(
            'pagetitle', 'classes', 'terms', 'sessions', 'schoolInfo'
        ));
    }

    /**
     * Core data endpoint used by both Class Analysis and School-wide Analysis.
     *
     * ENROLLMENT ROSTER FIX (2026-09):
     * `studentclass.termid` / `studentclass.sessionid` are NOT reliable
     * history — they get overwritten as a student is promoted/moved, so
     * filtering the roster by the selected term/session silently excluded
     * (or mismatched) students and produced an empty report for any
     * term/session other than the one `studentclass` currently points to.
     *
     * The roster is now pulled by CLASS only (current enrollment).
     * Bills (`school_bill_class_term_session`) and payments
     * (`student_bill_payment_book`) are still correctly scoped to the
     * selected term/session, since those tables are written at
     * billing/payment time and do carry accurate historical data.
     * This mirrors the same pattern already used in the payment flow
     * (see the "current active enrollment" fallback note there).
     *
     * KEY FIX vs. the old version: every eligible bill for every student is
     * run through BillAdjustmentService::buildBillAdjustment() regardless of
     * whether a student_bill_payment_book row exists yet. Previously, a
     * student with an active scholarship/discount but zero payments made
     * showed the FULL undiscounted bill here, because the old code only
     * read scholarship_deduction/discount_deduction off the payment book.
     */
    public function getClassAnalysisData(Request $request)
    {
        $classId   = $request->input('class_id');
        $termId    = $request->input('term_id');
        $sessionId = $request->input('session_id');

        if (!$termId || !$sessionId) {
            return response()->json([
                'data'            => [],
                'recordsTotal'    => 0,
                'recordsFiltered' => 0,
                'message'         => 'Term and Session are required',
            ]);
        }

        try {
            $studentsQuery = DB::table('studentRegistration as s')
                ->join('studentclass as sc', 'sc.studentId', '=', 's.id')
                ->leftJoin('studentpicture as sp', 'sp.studentid', '=', 's.id')
                ->leftJoin('schoolclass as scl', 'scl.id', '=', 'sc.schoolclassid')
                ->leftJoin('schoolarm as sar', 'sar.id', '=', 'scl.arm');
            // NOTE: intentionally NOT filtering by sc.termid / sc.sessionid —
            // see the roster-fix comment above. Roster = current enrollment;
            // bills/payments below remain scoped to the selected term/session.

            if ($classId && $classId !== '') {
                $studentsQuery->where('sc.schoolclassid', $classId);
            }

            $students = $studentsQuery->select(
                's.id as student_id',
                's.firstname',
                's.lastname',
                's.othername',
                's.admissionNo',
                's.gender',
                's.statusId',
                'sp.picture as avatar',
                'sc.schoolclassid as class_id',
                DB::raw("CONCAT(scl.schoolclass, ' ', COALESCE(sar.arm, '')) as class_display_name")
            )->get();

            if ($students->isEmpty()) {
                return response()->json([
                    'data'            => [],
                    'recordsTotal'    => 0,
                    'recordsFiltered' => 0,
                ]);
            }

            $studentIds = $students->pluck('student_id')->toArray();
            $now        = now();

            // Pre-load scholarship/discount assignments once (avoids N+1)
            $allScholarships = ScholarshipAssignment::whereIn('student_id', $studentIds)
                ->where('status', 'active')
                ->where('effective_from', '<=', $now)
                ->where(function ($q) use ($now) {
                    $q->whereNull('effective_to')->orWhere('effective_to', '>=', $now);
                })
                ->with('scholarship')
                ->get()
                ->keyBy('student_id');

            $allDiscounts = DiscountAssignment::whereIn('student_id', $studentIds)
                ->where('status', 'active')
                ->where('effective_from', '<=', $now)
                ->where(function ($q) use ($now) {
                    $q->whereNull('effective_to')->orWhere('effective_to', '>=', $now);
                })
                ->with('discount')
                ->get()
                ->groupBy('student_id');

            // Bills assigned to each class for this term/session
            $billsQuery = DB::table('school_bill_class_term_session as sbcts')
                ->join('school_bill as sb', 'sb.id', '=', 'sbcts.bill_id')
                ->where('sbcts.termid_id', $termId)
                ->where('sbcts.session_id', $sessionId)
                ->whereNull('sbcts.deleted_at')
                ->select(
                    'sbcts.class_id',
                    'sb.id as bill_id',
                    'sb.title',
                    'sb.bill_amount',
                    'sb.statusId as bill_status_id',
                    'sb.category'
                );

            if ($classId && $classId !== '') {
                $billsQuery->where('sbcts.class_id', $classId);
            }

            $allBillsByClass = $billsQuery->get()->groupBy('class_id');

            // Payment books, keyed for O(1) lookup per student+bill
            $paymentBooks = DB::table('student_bill_payment_book')
                ->whereIn('student_id', $studentIds)
                ->where('term_id', $termId)
                ->where('session_id', $sessionId)
                ->when($classId && $classId !== '', fn ($q) => $q->where('class_id', $classId))
                ->get()
                ->keyBy(fn ($row) => $row->student_id . '_' . $row->school_bill_id);

            $data = [];

            foreach ($students as $student) {
                $classBills = $allBillsByClass->get($student->class_id, collect());

                $eligibleBills = $this->billAdjustment->filterBillsByStudentStatus(
                    $classBills,
                    $student->statusId
                );

                $scholarship = $allScholarships->get($student->student_id);
                $discounts   = $allDiscounts->get($student->student_id, collect());

                $adjustedTotal = 0.0;
                $totalSavings  = 0.0;
                $totalPaid     = 0.0;
                $studentBills  = [];

                foreach ($eligibleBills as $bill) {
                    // Recompute the adjustment for THIS bill regardless of
                    // whether a payment book row exists — this is what makes
                    // discounts/scholarships show up even before any payment.
                    $adj = $this->billAdjustment->buildBillAdjustment(
                        $student->student_id,
                        $bill->bill_id,
                        (float) $bill->bill_amount,
                        $scholarship,
                        $discounts
                    );

                    $adjustedTotal += $adj['adjusted_amount'];
                    $totalSavings  += $adj['total_savings'];

                    $bookKey  = $student->student_id . '_' . $bill->bill_id;
                    $billBook = $paymentBooks->get($bookKey);
                    $paidHere = $billBook ? (float) $billBook->amount_paid : 0.0;
                    $totalPaid += $paidHere;

                    $studentBills[] = [
                        'title'                 => $bill->title,
                        'amount'                => $adj['adjusted_amount'],
                        'original_amount'       => $adj['original_amount'],
                        'scholarship_deduction' => $adj['scholarship_deduction'],
                        'discount_deduction'    => $adj['discount_deduction'],
                        'paid'                  => $paidHere,
                        'balance'               => max(0, $adj['adjusted_amount'] - $paidHere),
                    ];
                }

                $outstanding = max(0.0, $adjustedTotal - $totalPaid);
                $completion  = $adjustedTotal > 0
                    ? round(($totalPaid / $adjustedTotal) * 100, 1)
                    : 0;

                $status = ($outstanding <= 0 && $totalPaid > 0)
                    ? 'Fully Paid'
                    : ($totalPaid > 0 ? 'Partial' : 'Unpaid');

                $studentName = trim($student->firstname . ' ' . $student->lastname);
                if (!empty($student->othername)) {
                    $studentName .= ' (' . $student->othername . ')';
                }

                $data[] = [
                    'student_id'      => $student->student_id,
                    'student_name'    => $studentName,
                    'admission_no'    => $student->admissionNo ?? 'N/A',
                    'gender'          => $student->gender ?? 'N/A',
                    'class_id'        => $student->class_id,
                    'class_name'      => trim($student->class_display_name ?? ''),
                    'total_billed'    => round($adjustedTotal, 2),
                    'total_paid'      => round($totalPaid, 2),
                    'outstanding'     => round($outstanding, 2),
                    'completion'      => $completion,
                    'status'          => $status,
                    'total_savings'   => round($totalSavings, 2),
                    'has_scholarship' => $scholarship !== null,
                    'has_discount'    => $discounts->isNotEmpty(),
                    'avatar'          => $student->avatar,
                    'bills'           => $studentBills,
                ];
            }

            return response()->json([
                'data'            => $data,
                'recordsTotal'    => count($data),
                'recordsFiltered' => count($data),
            ]);

        } catch (\Exception $e) {
            Log::error('Class Analysis Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error'   => true,
                'message' => $e->getMessage(),
                'data'    => [],
            ], 500);
        }
    }

    public function schoolWideAnalysis(Request $request)
    {
        $pagetitle  = 'School-Wide Fee Collection Analysis';
        $schoolInfo = SchoolInformation::getActiveSchool();
        $terms      = DB::table('schoolterm')->orderBy('id')->get();
        $sessions   = DB::table('schoolsession')->orderBy('session', 'desc')->get();

        if ($request->ajax()) {
            $request->merge(['class_id' => null]);
            return $this->getClassAnalysisData($request);
        }

        return view('reports.analysis.school-wide', compact(
            'pagetitle', 'terms', 'sessions', 'schoolInfo'
        ));
    }

    /**
     * Scholarship & Discount Impact — driven off the same computed per-bill
     * adjustments instead of only summing whatever is already stored in
     * student_bill_payment_book (which under-counts unpaid bills).
     */
    public function scholarshipImpactAnalysis(Request $request)
    {
        $pagetitle  = 'Scholarship & Discount Impact Report';
        $schoolInfo = SchoolInformation::getActiveSchool();

        if (!$request->ajax()) {
            $terms    = DB::table('schoolterm')->orderBy('id')->get();
            $sessions = DB::table('schoolsession')->orderBy('session', 'desc')->get();
            return view('reports.financial.scholarship-impact', compact('pagetitle', 'schoolInfo', 'terms', 'sessions'));
        }

        $termId    = $request->input('term_id');
        $sessionId = $request->input('session_id');
        $now       = now();

        $scholarships = DB::table('scholarship_assignments as sa')
            ->join('scholarships as s', 's.id', '=', 'sa.scholarship_id')
            ->where('sa.status', 'active')
            ->where('sa.effective_from', '<=', $now)
            ->where(function ($q) use ($now) {
                $q->whereNull('sa.effective_to')->orWhere('sa.effective_to', '>=', $now);
            })
            ->select('s.title as scholarship_name', 'sa.student_id')
            ->get();

        $discounts = DB::table('discount_assignments as da')
            ->join('discounts as d', 'd.id', '=', 'da.discount_id')
            ->where('da.status', 'active')
            ->where('da.effective_from', '<=', $now)
            ->where(function ($q) use ($now) {
                $q->whereNull('da.effective_to')->orWhere('da.effective_to', '>=', $now);
            })
            ->select('d.title as discount_name', 'da.student_id')
            ->get();

        $beneficiaryIds = $scholarships->pluck('student_id')
            ->merge($discounts->pluck('student_id'))
            ->unique()
            ->values();

        // Real total savings across every bill for every beneficiary
        $billsQuery = DB::table('school_bill_class_term_session as sbcts')
            ->join('school_bill as sb', 'sb.id', '=', 'sbcts.bill_id');
        if ($termId)    $billsQuery->where('sbcts.termid_id', $termId);
        if ($sessionId) $billsQuery->where('sbcts.session_id', $sessionId);
        $bills = $billsQuery->select('sbcts.class_id', 'sb.id as bill_id', 'sb.bill_amount')->get();

        $totalSavings = 0.0;
        foreach ($beneficiaryIds as $studentId) {
            foreach ($bills as $bill) {
                $adj = $this->billAdjustment->buildBillAdjustment($studentId, $bill->bill_id, (float) $bill->bill_amount);
                $totalSavings += $adj['total_savings'];
            }
        }

        // Impact by class (still useful for the chart breakdown)
        $impactByClass = DB::table('student_bill_payment_book as sbpb')
            ->join('schoolclass as sc', 'sc.id', '=', 'sbpb.class_id')
            ->leftJoin('schoolarm as sa', 'sa.id', '=', 'sc.arm')
            ->select(
                DB::raw("CONCAT(sc.schoolclass, ' ', COALESCE(sa.arm, '')) as class_name"),
                DB::raw('SUM(sbpb.scholarship_deduction) as scholarship_value'),
                DB::raw('SUM(sbpb.discount_deduction) as discount_value'),
                DB::raw('COUNT(DISTINCT CASE WHEN sbpb.scholarship_deduction > 0 THEN sbpb.student_id END) as scholarship_students'),
                DB::raw('COUNT(DISTINCT CASE WHEN sbpb.discount_deduction > 0 THEN sbpb.student_id END) as discount_students')
            )
            ->when($termId, fn ($q) => $q->where('sbpb.term_id', $termId))
            ->when($sessionId, fn ($q) => $q->where('sbpb.session_id', $sessionId))
            ->groupBy('sbpb.class_id', 'sc.schoolclass', 'sa.arm')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => [
                'total_scholarships'  => $scholarships->count(),
                'total_discounts'     => $discounts->count(),
                'total_beneficiaries' => $beneficiaryIds->count(),
                'total_savings'       => round($totalSavings, 2),
                'scholarship_by_type' => $scholarships->groupBy('scholarship_name')->map->count(),
                'discount_by_type'    => $discounts->groupBy('discount_name')->map->count(),
                'impact_by_class'     => $impactByClass,
            ],
        ]);
    }

    /**
     * Export Class Analysis (PDF or CSV)
     */
    public function exportClassAnalysis(Request $request)
    {
        $classId   = $request->input('class_id');
        $termId    = $request->input('term_id');
        $sessionId = $request->input('session_id');
        $format    = $request->input('format', 'pdf');

        if (!$termId || !$sessionId) {
            return redirect()->back()->with('error', 'Please select term and session');
        }

        if ($format === 'pdf') {
            return $this->exportPDF($classId, $termId, $sessionId, 'download');
        }

        return $this->exportCSV($classId, $termId, $sessionId);
    }
/**
 * Encode a student's avatar as a base64 data URI for embedding in PDFs.
 * Same reasoning as SchoolInformation::getLogoBase64Attribute() — DomPDF
 * can't reliably fetch images over HTTP mid-render, so we read the file
 * straight off the public disk and inline it.
 */
private function encodeStudentAvatar(?string $picture): ?string
{
    if (!$picture || $picture === 'unnamed.jpg') {
        return null;
    }

    $relativePath = 'images/student_avatars/' . $picture;

    if (!Storage::disk('public')->exists($relativePath)) {
        return null;
    }

    $fullPath = Storage::disk('public')->path($relativePath);
    $mime     = mime_content_type($fullPath) ?: 'image/jpeg';
    $data     = base64_encode(file_get_contents($fullPath));

    return "data:{$mime};base64,{$data}";
}
  
    /**
 * Export PDF — per-student totals computed via buildBillAdjustment() for
 * every eligible bill, so scholarships/discounts show correctly even for
 * students who have not yet made a payment. Roster is pulled by CLASS
 * only (current enrollment) — see the fix note on getClassAnalysisData()
 * above; bills/payments stay scoped to the selected term/session.
 *
 * ADDED for the redesigned PDF:
 * - $studentBillDetails: per-student, per-bill adjusted amount / paid /
 *   balance, so the report can show a real Paid+Balance pair per bill
 *   instead of just an aggregate total.
 * - $billSummary: per-bill totals (expected/collected/outstanding),
 *   summed only across students the bill actually applies to (respects
 *   filterBillsByStudentStatus and discount/scholarship adjustments) —
 *   this replaces the old "count($students) * $bill->amount" math, which
 *   overstated what was expected for anyone on a scholarship/discount.
 */
public function exportPDF($class_id, $termid_id, $session_id, $action = 'view')
{
    $schoolInfo = SchoolInformation::getActiveSchool();

    $studentsQuery = DB::table('studentclass');
    // NOTE: intentionally NOT filtering by termid / sessionid — roster
    // fix (see getClassAnalysisData). Class filter only.

    if ($class_id && $class_id !== '') {
        $studentsQuery->where('schoolclassid', $class_id);
    }

    $students = $studentsQuery->leftJoin('studentRegistration', 'studentRegistration.id', '=', 'studentclass.studentId')
        ->leftJoin('studentpicture', 'studentpicture.studentid', '=', 'studentRegistration.id')
        ->select([
            'studentRegistration.admissionNo as admissionno',
            'studentRegistration.firstname as firstname',
            'studentRegistration.lastname as lastname',
            'studentRegistration.id as stid',
            'studentRegistration.othername as othername',
            'studentRegistration.gender as gender',
            'studentRegistration.statusId as statusId',
            'studentpicture.picture as picture',
            'studentclass.schoolclassid as schoolclassid',
        ])
        ->get();
        

    if ($students->isEmpty()) {
        return redirect()->route('reports.analysis.index')->with('error', 'No students found.');
    }

      // ADDED: embed each student's photo as base64 so DomPDF can render it
        foreach ($students as $std) {
            $std->avatar_base64 = $this->encodeStudentAvatar($std->picture);
        }
    $billQuery = DB::table('school_bill_class_term_session')
        ->where('school_bill_class_term_session.termid_id', $termid_id)
        ->where('school_bill_class_term_session.session_id', $session_id)
        ->whereNull('school_bill_class_term_session.deleted_at');

    if ($class_id && $class_id !== '') {
        $billQuery->where('school_bill_class_term_session.class_id', $class_id);
    }

    $studentBillInfo = $billQuery->leftJoin('school_bill', 'school_bill.id', '=', 'school_bill_class_term_session.bill_id')
        ->select([
            'school_bill.id as schoolbillid',
            'school_bill.title as title',
            'school_bill.description as description',
            'school_bill.bill_amount as amount',
            'school_bill.statusId as billStatusId',
        ])
        ->get();

    $studentIds = $students->pluck('stid')->toArray();
    $now        = now();

    $scholarshipAssignments = ScholarshipAssignment::whereIn('student_id', $studentIds)
        ->where('status', 'active')
        ->where('effective_from', '<=', $now)
        ->where(function ($q) use ($now) {
            $q->whereNull('effective_to')->orWhere('effective_to', '>=', $now);
        })
        ->with('scholarship')
        ->get()
        ->keyBy('student_id');

    $discountAssignments = DiscountAssignment::whereIn('student_id', $studentIds)
        ->where('status', 'active')
        ->where('effective_from', '<=', $now)
        ->where(function ($q) use ($now) {
            $q->whereNull('effective_to')->orWhere('effective_to', '>=', $now);
        })
        ->with('discount')
        ->get()
        ->groupBy('student_id');

    $paymentBooks = DB::table('student_bill_payment_book')
        ->whereIn('student_id', $studentIds)
        ->where('term_id', $termid_id)
        ->where('session_id', $session_id)
        ->when($class_id && $class_id !== '', fn ($q) => $q->where('class_id', $class_id))
        ->get()
        ->keyBy(fn ($row) => $row->student_id . '_' . $row->school_bill_id);

    if ($class_id && $class_id !== '') {
        $schoolClass = DB::table('schoolclass')
            ->where('schoolclass.id', $class_id)
            ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->first();
    } else {
        $schoolClass = (object) ['schoolclass' => 'All Classes', 'arm' => ''];
    }

    $schoolTerm    = DB::table('schoolterm')->where('id', $termid_id)->value('term');
    $schoolSession = DB::table('schoolsession')->where('id', $session_id)->value('session');

    $studentTotals       = [];
    $studentBillDetails  = [];   // stid => [ billId => ['adjusted'=>, 'paid'=>, 'balance'=>] ]
    $billSummary         = [];   // billId => ['title'=>, 'expected'=>, 'collected'=>, 'students'=>]
    $totalPaidSum        = 0;
    $totalBalanceSum     = 0;
    $totalSavingsSum     = 0;

    foreach ($students as $student) {
        $scholarship = $scholarshipAssignments->get($student->stid);
        $discounts   = $discountAssignments->get($student->stid, collect());

        $eligibleBills = $this->billAdjustment->filterBillsByStudentStatus($studentBillInfo, $student->statusId);

        $adjustedTotal = 0.0;
        $totalPaid     = 0.0;
        $totalSavings  = 0.0;

        foreach ($eligibleBills as $bill) {
            $adj = $this->billAdjustment->buildBillAdjustment(
                $student->stid, $bill->schoolbillid, (float) $bill->amount, $scholarship, $discounts
            );
            $adjustedTotal += $adj['adjusted_amount'];
            $totalSavings  += $adj['total_savings'];

            $book     = $paymentBooks->get($student->stid . '_' . $bill->schoolbillid);
            $paidHere = $book ? (float) $book->amount_paid : 0.0;
            $totalPaid += $paidHere;

            $studentBillDetails[$student->stid][$bill->schoolbillid] = [
                'adjusted' => $adj['adjusted_amount'],
                'paid'     => $paidHere,
                'balance'  => max(0, $adj['adjusted_amount'] - $paidHere),
            ];

            if (!isset($billSummary[$bill->schoolbillid])) {
                $billSummary[$bill->schoolbillid] = [
                    'title'     => $bill->title,
                    'unit_amount' => (float) $bill->amount,
                    'expected'  => 0.0,
                    'collected' => 0.0,
                    'students'  => 0,
                ];
            }
            $billSummary[$bill->schoolbillid]['expected']  += $adj['adjusted_amount'];
            $billSummary[$bill->schoolbillid]['collected'] += $paidHere;
            $billSummary[$bill->schoolbillid]['students']  += 1;
        }

        $totalBalance = max(0, $adjustedTotal - $totalPaid);

        $totalPaidSum    += $totalPaid;
        $totalBalanceSum += $totalBalance;
        $totalSavingsSum += $totalSavings;

        $studentTotals[$student->stid] = [
            'totalPaid'    => $totalPaid,
            'totalBilled'  => $adjustedTotal,
            'totalBalance' => $totalBalance,
            'totalSavings' => $totalSavings,
            'status'       => $totalPaid > 0 ? ($totalBalance > 0 ? 'partial' : 'paid') : 'unpaid',
        ];
    }

    $collectionRate = ($totalPaidSum + $totalBalanceSum) > 0
        ? round(($totalPaidSum / ($totalPaidSum + $totalBalanceSum)) * 100, 1)
        : 0;

    $data = [
        'schoolInfo'              => $schoolInfo,
        'students'                => $students,
        'studentBillInfo'         => $studentBillInfo,
        'studentTotals'           => $studentTotals,
        'studentBillDetails'      => $studentBillDetails,
        'billSummary'             => $billSummary,
        'paymentBooks'            => $paymentBooks,
        'schoolClass'             => $schoolClass,
        'schoolTerm'              => $schoolTerm,
        'schoolSession'           => $schoolSession,
        'scholarshipAssignments'  => $scholarshipAssignments,
        'discountAssignments'     => $discountAssignments,
        'totalPaidSum'            => $totalPaidSum,
        'totalBalanceSum'         => $totalBalanceSum,
        'totalSavingsSum'         => $totalSavingsSum,
        'collectionRate'          => $collectionRate,
        'generatedAt'             => now()->format('d F, Y H:i:s'),
    ];

    $pdf = PDF::loadView('reports.analysis.pdf.class-analysis', $data);
    $pdf->setPaper('a3', 'landscape');

    $className   = $class_id && $class_id !== ''
        ? str_replace(['/', '\\'], '_', ($schoolClass->schoolclass ?? '') . ' ' . ($schoolClass->arm ?? ''))
        : 'All_Classes';
    $termName    = str_replace(['/', '\\'], '_', $schoolTerm ?? 'N/A');
    $sessionName = str_replace(['/', '\\'], '_', $schoolSession ?? 'N/A');

    $filename = "Payment_Analysis_{$className}_{$termName}_{$sessionName}.pdf";

    if ($action === 'download') {
        return $pdf->download($filename);
    }
    return $pdf->stream($filename);
}

    /**
     * Export CSV — same per-bill adjustment logic as exportPDF(), same
     * roster-by-class fix.
     */
    private function exportCSV($classId, $termId, $sessionId)
    {
        if ($classId && $classId !== '') {
            $classInfo = DB::table('schoolclass')
                ->leftJoin('schoolarm', 'schoolclass.arm', '=', 'schoolarm.id')
                ->where('schoolclass.id', $classId)
                ->first();
            $className = ($classInfo->schoolclass ?? '') . ' ' . ($classInfo->arm ?? '');
        } else {
            $className = 'All_Classes';
        }

        $termName    = DB::table('schoolterm')->where('id', $termId)->value('term');
        $sessionName = DB::table('schoolsession')->where('id', $sessionId)->value('session');

        $studentsQuery = DB::table('studentclass');
        // NOTE: intentionally NOT filtering by termid / sessionid — roster
        // fix (see getClassAnalysisData). Class filter only.

        if ($classId && $classId !== '') {
            $studentsQuery->where('schoolclassid', $classId);
        }

        $students = $studentsQuery->leftJoin('studentRegistration', 'studentRegistration.id', '=', 'studentclass.studentId')
            ->select(
                'studentRegistration.admissionNo',
                'studentRegistration.firstname',
                'studentRegistration.lastname',
                'studentRegistration.othername',
                'studentRegistration.id as stid',
                'studentRegistration.gender',
                'studentRegistration.statusId',
                'studentclass.schoolclassid'
            )
            ->get();

        $billQuery = DB::table('school_bill_class_term_session')
            ->where('termid_id', $termId)
            ->where('session_id', $sessionId)
            ->whereNull('deleted_at');

        if ($classId && $classId !== '') {
            $billQuery->where('class_id', $classId);
        }

        $bills = $billQuery->join('school_bill', 'school_bill.id', '=', 'school_bill_class_term_session.bill_id')
            ->select('school_bill.id as schoolbillid', 'school_bill.bill_amount as amount', 'school_bill.statusId as billStatusId')
            ->get();

        $studentIds = $students->pluck('stid')->toArray();
        $now        = now();

        $scholarshipAssignments = ScholarshipAssignment::whereIn('student_id', $studentIds)
            ->where('status', 'active')
            ->where('effective_from', '<=', $now)
            ->where(function ($q) use ($now) { $q->whereNull('effective_to')->orWhere('effective_to', '>=', $now); })
            ->with('scholarship')
            ->get()
            ->keyBy('student_id');

        $discountAssignments = DiscountAssignment::whereIn('student_id', $studentIds)
            ->where('status', 'active')
            ->where('effective_from', '<=', $now)
            ->where(function ($q) use ($now) { $q->whereNull('effective_to')->orWhere('effective_to', '>=', $now); })
            ->with('discount')
            ->get()
            ->groupBy('student_id');

        $paymentBooks = DB::table('student_bill_payment_book')
            ->whereIn('student_id', $studentIds)
            ->where('term_id', $termId)
            ->where('session_id', $sessionId)
            ->when($classId && $classId !== '', fn ($q) => $q->where('class_id', $classId))
            ->get()
            ->keyBy(fn ($row) => $row->student_id . '_' . $row->school_bill_id);

        $reportData = [];

        foreach ($students as $student) {
            $scholarship   = $scholarshipAssignments->get($student->stid);
            $discounts     = $discountAssignments->get($student->stid, collect());
            $eligibleBills = $this->billAdjustment->filterBillsByStudentStatus($bills, $student->statusId);

            $adjustedTotal = 0.0;
            $totalPaid     = 0.0;

            foreach ($eligibleBills as $bill) {
                $adj = $this->billAdjustment->buildBillAdjustment(
                    $student->stid, $bill->schoolbillid, (float) $bill->amount, $scholarship, $discounts
                );
                $adjustedTotal += $adj['adjusted_amount'];

                $book = $paymentBooks->get($student->stid . '_' . $bill->schoolbillid);
                $totalPaid += $book ? (float) $book->amount_paid : 0.0;
            }

            $outstanding = max(0, $adjustedTotal - $totalPaid);

            $studentName = trim($student->firstname . ' ' . $student->lastname);
            if (!empty($student->othername)) {
                $studentName .= ' (' . $student->othername . ')';
            }

            $reportData[] = [
                'student_name' => $studentName,
                'admission_no' => $student->admissionNo ?? 'N/A',
                'gender'       => $student->gender ?? 'N/A',
                'total_billed' => number_format($adjustedTotal, 2),
                'total_paid'   => number_format($totalPaid, 2),
                'outstanding'  => number_format($outstanding, 2),
            ];
        }

        $filename = "class_analysis_" . str_replace(['/', '\\'], '_', $className) . "_"
            . str_replace(['/', '\\'], '_', $termName) . "_"
            . str_replace(['/', '\\'], '_', $sessionName) . "_" . date('Y-m-d') . ".csv";

        $handle = fopen('php://output', 'w');
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        fputcsv($handle, ['Student Name', 'Admission No', 'Gender', 'Total Billed (₦)', 'Total Paid (₦)', 'Outstanding (₦)']);
        foreach ($reportData as $row) {
            fputcsv($handle, [
                $row['student_name'], $row['admission_no'], $row['gender'],
                $row['total_billed'], $row['total_paid'], $row['outstanding'],
            ]);
        }
        fclose($handle);
        exit;
    }

    /**
     * Student Payment Details
     *
     * ADDED: $allTermsOutstanding — a full history of this student's billed
     * / paid / outstanding totals across EVERY term & session they have a
     * payment-book record for (not just the one currently selected in the
     * URL). This is built straight off student_bill_payment_book, which is
     * always correctly scoped per term/session (unlike studentclass), so it
     * gives an accurate picture even when the roster/class link is stale.
     */
    public function studentPaymentDetails($studentId, $classId, $termId, $sessionId)
    {
        $pagetitle  = 'Student Payment Details';
        $schoolInfo = SchoolInformation::getActiveSchool();

        $student = DB::table('studentRegistration as s')
            ->leftJoin('studentpicture as sp', 'sp.studentid', '=', 's.id')
            ->where('s.id', $studentId)
            ->select('s.*', 'sp.picture as avatar')
            ->first();

        if (!$student) {
            abort(404, 'Student not found');
        }

        $studentName = trim($student->firstname . ' ' . $student->lastname);
        if (!empty($student->othername)) {
            $studentName .= ' (' . $student->othername . ')';
        }

        $classInfo   = DB::table('schoolclass')
            ->leftJoin('schoolarm', 'schoolclass.arm', '=', 'schoolarm.id')
            ->where('schoolclass.id', $classId)
            ->first();

        $termInfo    = DB::table('schoolterm')->where('id', $termId)->first();
        $sessionInfo = DB::table('schoolsession')->where('id', $sessionId)->first();

        // Aggregate across all bills for the student (multiple bill rows summed)
        $paymentBook = DB::table('student_bill_payment_book')
            ->where('student_id', $studentId)
            ->where('term_id', $termId)
            ->where('session_id', $sessionId)
            ->when($classId, fn ($q) => $q->where('class_id', $classId))
            ->selectRaw('
                SUM(amount_paid) as amount_paid,
                SUM(amount_owed) as amount_owed,
                SUM(COALESCE(scholarship_deduction,0)) as scholarship_deduction,
                SUM(COALESCE(discount_deduction,0)) as discount_deduction,
                SUM(COALESCE(adjusted_amount, original_amount, 0)) as adjusted_amount
            ')
            ->first();

        $bills = DB::table('school_bill_class_term_session as sbcts')
            ->where('sbcts.termid_id', $termId)
            ->where('sbcts.session_id', $sessionId)
            ->where('sbcts.class_id', $classId)
            ->join('school_bill', 'school_bill.id', '=', 'sbcts.bill_id')
            ->select('school_bill.*')
            ->get();

        $paymentRecords = DB::table('student_bill_payment as sbp')
            ->join('student_bill_payment_record as sbpr', 'sbpr.student_bill_payment_id', '=', 'sbp.id')
            ->leftJoin('users as u', 'u.id', '=', 'sbp.generated_by')
            ->where('sbp.student_id', $studentId)
            ->where('sbp.class_id', $classId)
            ->where('sbp.termid_id', $termId)
            ->where('sbp.session_id', $sessionId)
            ->select(
                'sbp.created_at as payment_date',
                'sbp.payment_method',
                'sbp.status',
                'sbpr.amount_paid',
                'sbpr.amount_owed as balance',
                DB::raw("COALESCE(u.name, 'System') as received_by")
            )
            ->orderBy('sbpr.created_at', 'desc')
            ->get();

        // Full multi-term / multi-session outstanding history for this
        // student, independent of whatever class/term/session is in the URL.
        $allTermsOutstanding = DB::table('student_bill_payment_book as spb')
            ->join('schoolterm as st', 'st.id', '=', 'spb.term_id')
            ->join('schoolsession as ss', 'ss.id', '=', 'spb.session_id')
            ->leftJoin('schoolclass as scl', 'scl.id', '=', 'spb.class_id')
            ->leftJoin('schoolarm as sar', 'sar.id', '=', 'scl.arm')
            ->where('spb.student_id', $studentId)
            ->groupBy(
                'spb.term_id', 'spb.session_id', 'spb.class_id',
                'st.term', 'ss.session', 'scl.schoolclass', 'sar.arm'
            )
            ->select(
                'spb.term_id',
                'spb.session_id',
                'spb.class_id',
                'st.term as term_name',
                'ss.session as session_name',
                DB::raw("CONCAT(scl.schoolclass, ' ', COALESCE(sar.arm, '')) as class_name"),
                DB::raw('SUM(spb.amount_paid) as amount_paid'),
                DB::raw('SUM(spb.amount_owed) as amount_owed'),
                DB::raw('SUM(COALESCE(spb.adjusted_amount, spb.original_amount, 0)) as total_billed')
            )
            ->orderByDesc('spb.session_id')
            ->orderByDesc('spb.term_id')
            ->get();

        return view('reports.analysis.student-payment-details', compact(
            'pagetitle', 'schoolInfo', 'student', 'studentName',
            'classInfo', 'termInfo', 'sessionInfo', 'paymentBook',
            'bills', 'paymentRecords', 'studentId', 'classId', 'termId', 'sessionId',
            'allTermsOutstanding'
        ));
    }
}