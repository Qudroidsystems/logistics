<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Services\Reporting\FinancialReportService;
use App\Services\Accounting\AccountingService;
use App\Models\SchoolInformation;
use App\Models\ScholarshipAssignment;
use App\Models\DiscountAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use Yajra\DataTables\Facades\DataTables;

class FinancialReportController extends Controller
{
    protected $financialService;
    protected $accountingService;

    public function __construct(
        FinancialReportService $financialService,
        AccountingService $accountingService
    ) {
        $this->financialService = $financialService;
        $this->accountingService = $accountingService;

        $this->middleware('permission:View financial reports')->only([
            'debtorsList',
            'balanceSheet',
            'incomeStatement',
            'trialBalance',
            'cashFlow',
            'collectionSummary',
            'scholarshipImpact',
        ]);

        $this->middleware('permission:Export financial reports')->only([
            'exportDebtors',
            'exportBalanceSheet',
            'exportIncomeStatement',
            'exportCashFlow',
        ]);
    }

    /**
     * Debtors List Report
     */
    public function debtorsList(Request $request)
    {
        $pagetitle  = 'Student Debtors List';
        $schoolInfo = SchoolInformation::getActiveSchool();

        $wantsJson = $request->ajax()
            || $request->wantsJson()
            || $request->boolean('ajax')
            || str_contains((string) $request->header('Accept'), 'application/json');

        if ($wantsJson) {
            try {
                $data = $this->buildDebtorsDataset($request)->values();

                return response()->json([
                    'success' => true,
                    'data'    => $data,
                ]);
            } catch (\Throwable $e) {
                Log::error('Debtors list AJAX error: ' . $e->getMessage(), [
                    'trace' => $e->getTraceAsString(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to load debtors: ' . $e->getMessage(),
                    'data'    => [],
                ], 500);
            }
        }

        $classes = DB::table('schoolclass')
            ->leftJoin('schoolarm', 'schoolclass.arm', '=', 'schoolarm.id')
            ->select(
                'schoolclass.id',
                DB::raw("CONCAT(schoolclass.schoolclass, ' ', COALESCE(schoolarm.arm, '')) as display_name")
            )
            ->orderBy('schoolclass.schoolclass')
            ->get();

        $terms    = DB::table('schoolterm')->orderBy('id')->get();
        $sessions = DB::table('schoolsession')->orderBy('session', 'desc')->get();

        return view('reports.financial.debtors-list', compact(
            'pagetitle',
            'classes',
            'terms',
            'sessions',
            'schoolInfo'
        ));
    }

    /**
     * Build grouped debtors dataset. Now joins parentRegistration so each
     * row carries enough info to compute has_contact (does this debtor
     * have ANY reachable parent/student email or phone on file).
     */
    private function buildDebtorsDataset(Request $request): Collection
    {
        // roster-based debtors: start from EVERY student in the selected
        // class/term/session, then compute owed = billed − paid. Students who
        // never paid (no payment-book row) are included, owing the full bill.
        $classId   = $request->filled('class_id') ? (int) $request->class_id : null;
        $termId    = $request->filled('term_id') ? (int) $request->term_id : null;
        $sessionId = $request->filled('session_id') ? (int) $request->session_id : null;

        // Keep the roster bounded: default to the current session when nothing is chosen.
        if (!$sessionId && !$classId && !$termId) {
            $sessionId = (int) (DB::table('schoolsession')->where('status', 'Current')->value('id') ?? 0) ?: null;
        }

        // 1) Bills assigned to each class/term/session group.
        $sbcts = DB::table('school_bill_class_term_session as sbcts')
            ->join('school_bill as sb', 'sb.id', '=', 'sbcts.bill_id')
            ->when($classId, fn ($q) => $q->where('sbcts.class_id', $classId))
            ->when($termId, fn ($q) => $q->where('sbcts.termid_id', $termId))
            ->when($sessionId, fn ($q) => $q->where('sbcts.session_id', $sessionId));
        if (Schema::hasColumn('school_bill_class_term_session', 'deleted_at')) $sbcts->whereNull('sbcts.deleted_at');
        if (Schema::hasColumn('school_bill_class_term_session', 'is_active')) $sbcts->where('sbcts.is_active', 1);
        $billRows = $sbcts->select(
            'sbcts.class_id', 'sbcts.termid_id as term_id', 'sbcts.session_id',
            'sb.id as bill_id', 'sb.title as bill_title', 'sb.bill_amount'
        )->get();

        if ($billRows->isEmpty()) {
            return collect();
        }

        $gk = fn ($c, $t, $se) => ((int) $c) . '_' . ((int) $t) . '_' . ((int) $se);
        $billsByGroup = $billRows->groupBy(fn ($b) => $gk($b->class_id, $b->term_id, $b->session_id));

        // 2) Roster: students placed in those class/term/session groups.
        $roster = DB::table('studentclass as sc')
            ->join('studentRegistration as s', 's.id', '=', 'sc.studentId')
            ->leftJoin('schoolclass as scl', 'scl.id', '=', 'sc.schoolclassid')
            ->leftJoin('schoolarm as sa', 'sa.id', '=', 'scl.arm')
            ->leftJoin('schoolterm as t', 't.id', '=', 'sc.termid')
            ->leftJoin('schoolsession as ss', 'ss.id', '=', 'sc.sessionid')
            ->leftJoin('studentpicture as sp', 'sp.studentid', '=', 's.id')
            ->leftJoin('parentRegistration as pr', 'pr.studentId', '=', 's.id')
            ->when($classId, fn ($q) => $q->where('sc.schoolclassid', $classId))
            ->when($termId, fn ($q) => $q->where('sc.termid', $termId))
            ->when($sessionId, fn ($q) => $q->where('sc.sessionid', $sessionId))
            ->select(
                's.id as student_id',
                DB::raw("CONCAT(s.firstname, ' ', s.lastname) as student_name"),
                's.admissionNo as admission_no',
                's.student_status',
                's.gender',
                's.email as student_email',
                's.phone_number as student_phone',
                'pr.parent_email',
                'pr.father_phone',
                'pr.mother_phone',
                'sp.picture as avatar',
                'sc.schoolclassid as class_id',
                DB::raw("TRIM(CONCAT(scl.schoolclass, ' ', COALESCE(sa.arm, ''))) as class_name"),
                'sc.termid as term_id',
                't.term as term_name',
                'sc.sessionid as session_id',
                'ss.session as session_name'
            );

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $roster->where(function ($q) use ($search) {
                $q->where('s.firstname', 'like', "%{$search}%")
                  ->orWhere('s.lastname', 'like', "%{$search}%")
                  ->orWhere('s.admissionNo', 'like', "%{$search}%")
                  ->orWhereRaw("CONCAT(s.firstname, ' ', s.lastname) LIKE ?", ["%{$search}%"]);
            });
        }

        $rosterRows = $roster->get()->unique(fn ($r) => $r->student_id . '_' . $r->class_id . '_' . $r->term_id . '_' . $r->session_id)->values();
        if ($rosterRows->isEmpty()) {
            return collect();
        }

        $studentIds = $rosterRows->pluck('student_id')->unique()->values();

        // 3) Payment-book rows for those students, keyed per student+group+bill.
        $books = DB::table('student_bill_payment_book')
            ->whereIn('student_id', $studentIds)
            ->when($classId, fn ($q) => $q->where('class_id', $classId))
            ->when($termId, fn ($q) => $q->where('term_id', $termId))
            ->when($sessionId, fn ($q) => $q->where('session_id', $sessionId))
            ->get()
            ->keyBy(fn ($b) => ((int) $b->student_id) . '_' . ((int) $b->class_id) . '_' . ((int) $b->term_id) . '_' . ((int) $b->session_id) . '_' . ((int) $b->school_bill_id));

        // Live scholarship / discount flags (for the badges), same as before.
        $now = now();
        $scholarships = ScholarshipAssignment::whereIn('student_id', $studentIds)
            ->where('status', 'active')->where('effective_from', '<=', $now)
            ->where(fn ($q) => $q->whereNull('effective_to')->orWhere('effective_to', '>=', $now))
            ->with('scholarship')->get()->keyBy('student_id');
        $discounts = DiscountAssignment::whereIn('student_id', $studentIds)
            ->where('status', 'active')->where('effective_from', '<=', $now)
            ->where(fn ($q) => $q->whereNull('effective_to')->orWhere('effective_to', '>=', $now))
            ->with('discount')->get()->groupBy('student_id');

        $minOut = $request->filled('min_outstanding') ? (float) $request->min_outstanding : null;
        $maxOut = $request->filled('max_outstanding') ? (float) $request->max_outstanding : null;

        $out = collect();
        foreach ($rosterRows as $r) {
            $groupBills = $billsByGroup->get($gk($r->class_id, $r->term_id, $r->session_id));
            if (!$groupBills) continue; // no bills for this group → nothing owed

            $totalOriginal = 0.0; $totalPaid = 0.0; $totalOutstanding = 0.0; $totalSavings = 0.0;
            $billLines = [];
            foreach ($groupBills as $b) {
                $billAmt = (float) $b->bill_amount;
                $key = ((int) $r->student_id) . '_' . ((int) $r->class_id) . '_' . ((int) $r->term_id) . '_' . ((int) $r->session_id) . '_' . ((int) $b->bill_id);
                $book = $books->get($key);
                if ($book) {
                    $paid    = (float) $book->amount_paid;
                    $owed    = (float) $book->amount_owed;
                    $savings = (float) ($book->scholarship_deduction ?? 0) + (float) ($book->discount_deduction ?? 0);
                    $orig    = $book->original_amount !== null ? (float) $book->original_amount : $billAmt;
                } else {
                    $paid = 0.0; $owed = $billAmt; $savings = 0.0; $orig = $billAmt;
                }
                $totalOriginal += $orig; $totalPaid += $paid; $totalOutstanding += $owed; $totalSavings += $savings;
                $billLines[] = [
                    'bill_id' => $b->bill_id, 'title' => $b->bill_title,
                    'original_amount' => $orig, 'amount_paid' => $paid, 'outstanding' => $owed, 'savings' => $savings,
                ];
            }

            if ($totalOutstanding <= 0) continue;                     // fully paid → not a debtor
            if ($minOut !== null && $totalOutstanding < $minOut) continue;
            if ($maxOut !== null && $totalOutstanding > $maxOut) continue;

            $adjusted = max(0, $totalOriginal - $totalSavings);
            $rate = $adjusted > 0 ? round(($totalPaid / $adjusted) * 100, 1) : 0;

            $sch  = $scholarships->get($r->student_id);
            $disc = $discounts->get($r->student_id, collect());
            $hasContact = !empty($r->parent_email) || !empty($r->father_phone) || !empty($r->mother_phone)
                || !empty($r->student_email) || !empty($r->student_phone);

            $out->push([
                'student_id'      => $r->student_id,
                'student_name'    => $r->student_name,
                'admission_no'    => $r->admission_no,
                'student_status'  => $r->student_status ?? 'N/A',
                'gender'          => $r->gender ?? 'N/A',
                'avatar'          => $r->avatar,
                'has_contact'     => $hasContact,
                'class_id'        => $r->class_id,
                'class_name'      => $r->class_name,
                'term_id'         => $r->term_id,
                'term_name'       => $r->term_name,
                'session_id'      => $r->session_id,
                'session_name'    => $r->session_name,
                'original_amount' => $totalOriginal,
                'amount_paid'     => $totalPaid,
                'outstanding'     => $totalOutstanding,
                'savings'         => $totalSavings,
                'collection_rate' => $rate,
                'bill_count'      => count($billLines),
                'never_paid'      => $totalPaid <= 0,
                'has_scholarship' => $sch !== null,
                'has_discount'    => $disc->isNotEmpty(),
                'scholarship'     => $sch ? [
                    'title'      => $sch->scholarship->title ?? 'Scholarship',
                    'value'      => $sch->value,
                    'value_type' => $sch->value_type,
                ] : null,
                'discounts' => $disc->map(fn ($d) => [
                    'title'      => $d->discount->title ?? 'Discount',
                    'value'      => $d->value,
                    'value_type' => $d->value_type,
                ])->values(),
                'bills' => collect($billLines)->values(),
            ]);
        }

        return $out->sortByDesc('outstanding')->values();
    }

    /**
     * Export: reports/financial/export/{report}/{format}
     */
    public function exportDebtors($report, $format, Request $request)
    {
        $report = strtolower((string) $report);
        $format = strtolower((string) $format);

        if ($report !== 'debtors') {
            abort(404, 'Unknown report type: ' . $report);
        }

        if (!in_array($format, ['pdf', 'excel', 'csv'], true)) {
            abort(404, 'Unsupported export format: ' . $format);
        }

        $dataset  = $this->buildDebtorsDataset($request)->values();
        $filename = 'debtors_list_' . date('Y-m-d_H-i-s');

        if ($format === 'excel' || $format === 'csv') {
            return $this->exportDebtorsCSV($dataset, $filename);
        }

        return $this->exportDebtorsPDF($dataset, $filename, $request);
    }

    private function exportDebtorsCSV($dataset, $filename)
    {
        $fullFilename = $filename . '.csv';
        $handle = fopen('php://output', 'w');

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $fullFilename . '"');

        fputcsv($handle, [
            'Student Name',
            'Admission No',
            'Class',
            'Term',
            'Session',
            'Gender',
            'Status',
            'Scholarship',
            'Discounts',
            'Bills Owing',
            'Original (₦)',
            'Paid (₦)',
            'Outstanding (₦)',
            'Savings (₦)',
            'Collection Rate (%)',
        ]);

        foreach ($dataset as $row) {
            fputcsv($handle, [
                $row['student_name'],
                $row['admission_no'],
                $row['class_name'],
                $row['term_name'],
                $row['session_name'],
                $row['gender'],
                $row['student_status'],
                $row['scholarship']['title'] ?? '',
                collect($row['discounts'])->pluck('title')->implode('; '),
                collect($row['bills'])->pluck('title')->implode('; '),
                number_format($row['original_amount'], 2),
                number_format($row['amount_paid'], 2),
                number_format($row['outstanding'], 2),
                number_format($row['savings'], 2),
                $row['collection_rate'],
            ]);
        }

        fclose($handle);
        exit;
    }

    private function exportDebtorsPDF($dataset, $filename, $request)
    {
        $dataset = $dataset->map(function ($row) {
            $row['avatar_base64'] = $this->avatarBase64($row['avatar']);
            return $row;
        });

        $schoolInfo = $this->prepareSchoolInfoForPdf(SchoolInformation::getActiveSchool());

        $filters = [
            'class' => $request->filled('class_id')
                ? DB::table('schoolclass')
                    ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
                    ->where('schoolclass.id', $request->class_id)
                    ->selectRaw("TRIM(CONCAT(schoolclass.schoolclass, ' ', COALESCE(schoolarm.arm, ''))) as n")
                    ->value('n')
                : null,
            'term' => $request->filled('term_id')
                ? DB::table('schoolterm')->where('id', $request->term_id)->value('term')
                : null,
            'session' => $request->filled('session_id')
                ? DB::table('schoolsession')->where('id', $request->session_id)->value('session')
                : null,
            'search' => $request->get('search'),
        ];

        $totals = [
            'debtors'     => $dataset->count(),
            'original'    => $dataset->sum('original_amount'),
            'paid'        => $dataset->sum('amount_paid'),
            'outstanding' => $dataset->sum('outstanding'),
            'savings'     => $dataset->sum('savings'),
        ];

        $pdf = PDF::loadView(
            'reports.financial.pdf.debtors',
            compact('dataset', 'schoolInfo', 'filters', 'totals')
        )->setOptions([
            'defaultFont'         => 'DejaVu Sans',
            'isRemoteEnabled'     => false,
            'isHtml5ParserEnabled'=> true,
            'isPhpEnabled'        => false,
        ]);

        $pdf->setPaper('a4', 'landscape');

        return $pdf->download($filename . '.pdf');
    }

    public function balanceSheet(Request $request)
    {
        $pagetitle  = 'Balance Sheet';
        $asAtDate   = $request->get('as_at_date', now()->format('Y-m-d'));
        $schoolInfo = SchoolInformation::getActiveSchool();

        if ($request->ajax() || $request->wantsJson()) {
            $data = $this->financialService->generateBalanceSheet($asAtDate);
            return response()->json(['success' => true, 'data' => $data]);
        }

        if ($request->get('format') === 'pdf') {
            return $this->exportBalanceSheet($request);
        }

        return view('reports.financial.balance-sheet', compact('pagetitle', 'asAtDate', 'schoolInfo'));
    }

    public function exportBalanceSheet(Request $request)
    {
        $asAtDate   = $request->get('as_at_date', now()->format('Y-m-d'));
        $schoolInfo = $this->prepareSchoolInfoForPdf(SchoolInformation::getActiveSchool());
        $data       = $this->financialService->generateBalanceSheet($asAtDate);

        $pdf = PDF::loadView(
            'reports.financial.pdf.balance-sheet',
            compact('data', 'asAtDate', 'schoolInfo')
        )->setOptions([
            'defaultFont'         => 'DejaVu Sans',
            'isRemoteEnabled'     => false,
            'isHtml5ParserEnabled'=> true,
            'isPhpEnabled'        => false,
        ]);

        $pdf->setPaper('a4', 'portrait');

        return $pdf->download("balance_sheet_{$asAtDate}.pdf");
    }

    public function incomeStatement(Request $request)
    {
        $pagetitle  = 'Income Statement';
        $startDate  = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate    = $request->get('end_date', now()->format('Y-m-d'));
        $schoolInfo = SchoolInformation::getActiveSchool();

        if ($request->ajax() || $request->wantsJson()) {
            $data = $this->financialService->generateIncomeStatement($startDate, $endDate);
            return response()->json(['success' => true, 'data' => $data]);
        }

        if ($request->get('format') === 'pdf') {
            return $this->exportIncomeStatement($request);
        }

        return view('reports.financial.income-statement', compact(
            'pagetitle',
            'startDate',
            'endDate',
            'schoolInfo'
        ));
    }

    public function exportIncomeStatement(Request $request)
    {
        $startDate  = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate    = $request->get('end_date', now()->format('Y-m-d'));
        $schoolInfo = $this->prepareSchoolInfoForPdf(SchoolInformation::getActiveSchool());
        $data       = $this->financialService->generateIncomeStatement($startDate, $endDate);

        $pdf = PDF::loadView(
            'reports.financial.pdf.income-statement',
            compact('data', 'startDate', 'endDate', 'schoolInfo')
        )->setOptions([
            'defaultFont'         => 'DejaVu Sans',
            'isRemoteEnabled'     => false,
            'isHtml5ParserEnabled'=> true,
            'isPhpEnabled'        => false,
        ]);

        $pdf->setPaper('a4', 'portrait');

        return $pdf->download("income_statement_{$startDate}_to_{$endDate}.pdf");
    }

    public function trialBalance(Request $request)
    {
        $pagetitle  = 'Trial Balance';
        $asAtDate   = $request->get('as_at_date', now()->format('Y-m-d'));
        $schoolInfo = SchoolInformation::getActiveSchool();

        if ($request->ajax() || $request->wantsJson()) {
            $trialBalance = $this->accountingService->getTrialBalance($asAtDate);
            $totalDebit   = array_sum(array_column($trialBalance, 'debit'));
            $totalCredit  = array_sum(array_column($trialBalance, 'credit'));

            return response()->json([
                'success' => true,
                'data'    => compact('trialBalance', 'totalDebit', 'totalCredit'),
            ]);
        }

        if ($request->get('format') === 'excel') {
            return $this->exportTrialBalanceExcel($asAtDate);
        }

        return view('reports.financial.trial-balance', compact('pagetitle', 'asAtDate', 'schoolInfo'));
    }

    public function cashFlow(Request $request)
    {
        $pagetitle  = 'Cash Flow Statement';
        $startDate  = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate    = $request->get('end_date', now()->format('Y-m-d'));
        $schoolInfo = SchoolInformation::getActiveSchool();

        if ($request->ajax() || $request->wantsJson()) {
            $data = $this->financialService->generateCashFlow($startDate, $endDate);
            return response()->json(['success' => true, 'data' => $data]);
        }

        if ($request->get('format') === 'pdf') {
            return $this->exportCashFlow($request);
        }

        return view('reports.financial.cash-flow', compact(
            'pagetitle',
            'startDate',
            'endDate',
            'schoolInfo'
        ));
    }

    public function exportCashFlow(Request $request)
    {
        $startDate  = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate    = $request->get('end_date', now()->format('Y-m-d'));
        $schoolInfo = $this->prepareSchoolInfoForPdf(SchoolInformation::getActiveSchool());
        $data       = $this->financialService->generateCashFlow($startDate, $endDate);

        $pdf = PDF::loadView(
            'reports.financial.pdf.cash-flow',
            compact('data', 'startDate', 'endDate', 'schoolInfo')
        )->setOptions([
            'defaultFont'         => 'DejaVu Sans',
            'isRemoteEnabled'     => false,
            'isHtml5ParserEnabled'=> true,
            'isPhpEnabled'        => false,
        ]);

        $pdf->setPaper('a4', 'portrait');

        return $pdf->download("cash_flow_{$startDate}_to_{$endDate}.pdf");
    }

    public function collectionSummary(Request $request)
    {
        $pagetitle  = 'School Fee Collection Summary';
        $schoolInfo = SchoolInformation::getActiveSchool();

        if ($request->ajax() || $request->wantsJson()) {
            $query = DB::table('student_bill_payment_book')
                ->select(
                    'class_id',
                    'term_id',
                    'session_id',
                    DB::raw('COUNT(DISTINCT student_id) as student_count'),
                    DB::raw('SUM(amount_paid) as total_collected'),
                    DB::raw('SUM(amount_owed) as total_outstanding'),
                    DB::raw('SUM(adjusted_amount) as total_adjusted'),
                    DB::raw('SUM(scholarship_deduction) as total_scholarship'),
                    DB::raw('SUM(discount_deduction) as total_discount')
                )
                ->groupBy('class_id', 'term_id', 'session_id');

            if ($request->filled('class_id')) {
                $query->where('class_id', $request->class_id);
            }
            if ($request->filled('term_id')) {
                $query->where('term_id', $request->term_id);
            }
            if ($request->filled('session_id')) {
                $query->where('session_id', $request->session_id);
            }

            $results = $query->get();
            $data = $results->map(function ($row) {
                $class = DB::table('schoolclass')
                    ->leftJoin('schoolarm', 'schoolclass.arm', '=', 'schoolarm.id')
                    ->where('schoolclass.id', $row->class_id)
                    ->select(DB::raw("CONCAT(schoolclass.schoolclass, ' ', COALESCE(schoolarm.arm, '')) as class_name"))
                    ->first();
                $term    = DB::table('schoolterm')->where('id', $row->term_id)->value('term');
                $session = DB::table('schoolsession')->where('id', $row->session_id)->value('session');
                $rate    = $row->total_adjusted > 0
                    ? round(($row->total_collected / $row->total_adjusted) * 100, 2)
                    : 0;

                return [
                    'class'              => $class->class_name ?? 'N/A',
                    'term'               => $term ?? 'N/A',
                    'session'            => $session ?? 'N/A',
                    'student_count'      => $row->student_count,
                    'total_expected'     => number_format($row->total_adjusted, 2),
                    'total_collected'    => number_format($row->total_collected, 2),
                    'total_outstanding'  => number_format($row->total_outstanding, 2),
                    'total_scholarship'  => number_format($row->total_scholarship, 2),
                    'total_discount'     => number_format($row->total_discount, 2),
                    'collection_rate'    => $rate . '%',
                ];
            });

            return DataTables::of($data)->make(true);
        }

        $classes = DB::table('schoolclass')
            ->leftJoin('schoolarm', 'schoolclass.arm', '=', 'schoolarm.id')
            ->select(
                'schoolclass.id',
                DB::raw("CONCAT(schoolclass.schoolclass, ' ', COALESCE(schoolarm.arm, '')) as display_name")
            )
            ->get();

        $terms    = DB::table('schoolterm')->orderBy('id')->get();
        $sessions = DB::table('schoolsession')->orderBy('session', 'desc')->get();

        return view('reports.financial.collection-summary', compact(
            'pagetitle',
            'classes',
            'terms',
            'sessions',
            'schoolInfo'
        ));
    }

    public function scholarshipImpact(Request $request)
    {
        $pagetitle  = 'Scholarship & Discount Impact Report';
        $schoolInfo = SchoolInformation::getActiveSchool();

        if ($request->ajax() || $request->wantsJson()) {
            $termId    = $request->input('term_id');
            $sessionId = $request->input('session_id');
            $now       = now();

            $scholarshipsQuery = DB::table('scholarship_assignments as sa')
                ->join('scholarships as s', 's.id', '=', 'sa.scholarship_id')
                ->join('studentRegistration as sr', 'sr.id', '=', 'sa.student_id')
                ->leftJoin('studentclass as sc', 'sc.studentId', '=', 'sr.id')
                ->leftJoin('studentpicture as sp', 'sp.studentid', '=', 'sr.id')
                ->where('sa.status', 'active')
                ->where('sa.effective_from', '<=', $now)
                ->where(function ($q) use ($now) {
                    $q->whereNull('sa.effective_to')->orWhere('sa.effective_to', '>=', $now);
                });

            if ($termId) {
                $scholarshipsQuery->where('sc.termid', $termId);
            }
            if ($sessionId) {
                $scholarshipsQuery->where('sc.sessionid', $sessionId);
            }

            $scholarships = $scholarshipsQuery->select(
                's.title as scholarship_name',
                'sa.student_id',
                'sa.value',
                'sa.value_type',
                'sr.firstname',
                'sr.lastname',
                'sr.admissionNo',
                'sp.picture as avatar'
            )->get();

            $discountsQuery = DB::table('discount_assignments as da')
                ->join('discounts as d', 'd.id', '=', 'da.discount_id')
                ->join('studentRegistration as sr', 'sr.id', '=', 'da.student_id')
                ->leftJoin('studentclass as sc', 'sc.studentId', '=', 'sr.id')
                ->leftJoin('studentpicture as sp', 'sp.studentid', '=', 'sr.id')
                ->where('da.status', 'active')
                ->where('da.effective_from', '<=', $now)
                ->where(function ($q) use ($now) {
                    $q->whereNull('da.effective_to')->orWhere('da.effective_to', '>=', $now);
                });

            if ($termId) {
                $discountsQuery->where('sc.termid', $termId);
            }
            if ($sessionId) {
                $discountsQuery->where('sc.sessionid', $sessionId);
            }

            $discounts = $discountsQuery->select(
                'd.title as discount_name',
                'da.student_id',
                'da.value',
                'da.value_type',
                'sr.firstname',
                'sr.lastname',
                'sr.admissionNo',
                'sp.picture as avatar'
            )->get();

            $totalSavings = DB::table('student_bill_payment_book')
                ->when($termId, fn ($q) => $q->where('term_id', $termId))
                ->when($sessionId, fn ($q) => $q->where('session_id', $sessionId))
                ->sum(DB::raw('scholarship_deduction + discount_deduction'));

            $impactByClass = DB::table('student_bill_payment_book as sbpb')
                ->join('schoolclass as sc', 'sc.id', '=', 'sbpb.class_id')
                ->leftJoin('schoolarm as sa', 'sa.id', '=', 'sc.arm')
                ->when($termId, fn ($q) => $q->where('sbpb.term_id', $termId))
                ->when($sessionId, fn ($q) => $q->where('sbpb.session_id', $sessionId))
                ->select(
                    'sc.id as class_id',
                    DB::raw("CONCAT(sc.schoolclass, ' ', COALESCE(sa.arm, '')) as class_name"),
                    DB::raw('SUM(sbpb.scholarship_deduction) as scholarship_value'),
                    DB::raw('SUM(sbpb.discount_deduction) as discount_value'),
                    DB::raw('COUNT(DISTINCT CASE WHEN sbpb.scholarship_deduction > 0 THEN sbpb.student_id END) as scholarship_students'),
                    DB::raw('COUNT(DISTINCT CASE WHEN sbpb.discount_deduction > 0 THEN sbpb.student_id END) as discount_students')
                )
                ->groupBy('sbpb.class_id', 'sc.schoolclass', 'sa.arm')
                ->get();

            return response()->json([
                'success' => true,
                'data'    => [
                    'total_scholarships'  => $scholarships->count(),
                    'total_discounts'     => $discounts->count(),
                    'total_beneficiaries' => $scholarships->pluck('student_id')
                        ->merge($discounts->pluck('student_id'))
                        ->unique()
                        ->count(),
                    'total_savings'       => $totalSavings,
                    'scholarship_by_type' => $scholarships->groupBy('scholarship_name')->map(fn ($items) => $items->sum('value')),
                    'discount_by_type'    => $discounts->groupBy('discount_name')->map(fn ($items) => $items->sum('value')),
                    'impact_by_class'     => $impactByClass,
                    'scholarship_beneficiaries' => $scholarships->map(fn ($s) => [
                        'student_id'   => $s->student_id,
                        'name'         => trim($s->firstname . ' ' . $s->lastname),
                        'admission_no' => $s->admissionNo,
                        'avatar'       => $s->avatar,
                        'scholarship'  => $s->scholarship_name,
                        'value'        => $s->value,
                        'value_type'   => $s->value_type,
                    ]),
                    'discount_beneficiaries' => $discounts->map(fn ($d) => [
                        'student_id'   => $d->student_id,
                        'name'         => trim($d->firstname . ' ' . $d->lastname),
                        'admission_no' => $d->admissionNo,
                        'avatar'       => $d->avatar,
                        'discount'     => $d->discount_name,
                        'value'        => $d->value,
                        'value_type'   => $d->value_type,
                    ]),
                ],
            ]);
        }

        $terms    = DB::table('schoolterm')->orderBy('id')->get();
        $sessions = DB::table('schoolsession')->orderBy('session', 'desc')->get();

        return view('reports.financial.scholarship-impact', compact(
            'pagetitle',
            'schoolInfo',
            'terms',
            'sessions'
        ));
    }

    private function exportTrialBalanceExcel($asAtDate)
    {
        $trialBalance = $this->accountingService->getTrialBalance($asAtDate);
        $totalDebit   = array_sum(array_column($trialBalance, 'debit'));
        $totalCredit  = array_sum(array_column($trialBalance, 'credit'));

        $filename = "trial_balance_{$asAtDate}.csv";
        $handle   = fopen('php://output', 'w');

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        fputcsv($handle, [
            'Account Code',
            'Account Name',
            'Account Type',
            'Debit (₦)',
            'Credit (₦)',
            'Balance (₦)',
        ]);

        foreach ($trialBalance as $row) {
            fputcsv($handle, [
                $row['account_code'],
                $row['account_name'],
                $row['account_type'],
                number_format($row['debit'], 2),
                number_format($row['credit'], 2),
                number_format($row['balance'], 2),
            ]);
        }

        fputcsv($handle, []);
        fputcsv($handle, [
            'TOTALS',
            '',
            '',
            number_format($totalDebit, 2),
            number_format($totalCredit, 2),
            '',
        ]);

        fclose($handle);
        exit;
    }

    private function avatarBase64(?string $picture): ?string
    {
        if (!$picture || $picture === 'unnamed.jpg' || $picture === '') {
            return null;
        }

        $relativePath = 'images/student_avatars/' . $picture;

        if (!Storage::disk('public')->exists($relativePath)) {
            return null;
        }

        $path = Storage::disk('public')->path($relativePath);
        $mime = mime_content_type($path) ?: 'image/jpeg';

        return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path));
    }

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
}