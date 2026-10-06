<?php

namespace App\Http\Controllers;

use App\Models\SchoolInformation;
use App\Models\Schoolsession;
use App\Models\Schoolterm;
use App\Models\Student;
use App\Services\Billing\StudentFeeStatementService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Student-facing "My Payments" portal.
 *
 * Every figure here is computed the SAME way as the bursar's payment screen
 * (SchoolPaymentController::getPaymentDetailsAjax), so what a student sees
 * always matches what the school sees:
 *
 *  - Bills: school_bill_class_term_session for the class/term/session,
 *    excluding soft-deleted assignments and bills meant for a different
 *    student status (e.g. boarding-only bills for a day student).
 *  - Payable amount: BillAdjustmentService::buildBillAdjustment() -- the one
 *    canonical scholarship + discount calculation (stacking, priority,
 *    caps, sibling discounts).
 *  - Amount paid: SUM of the payment LEDGER (student_bill_payment_record),
 *    excluding soft-deleted rows -- not the running total_paid counter, and
 *    not just the first payment row.
 *  - History: one line per ledger transaction, not per bill summary.
 *  - Arrears: ArrearsService, i.e. unpaid balances from other terms.
 *
 * The student's class for a term is resolved from their enrolment, payment
 * book, payment rows or results -- studentclass only holds the CURRENT
 * placement, so on its own it made every past session show "no bills".
 */
class StudentPaymentController extends Controller
{
    public function __construct(protected StudentFeeStatementService $statements)
    {
        $this->middleware('permission:View student payments', ['only' => ['index', 'printReceipt']]);
    }

    // =========================================================================
    // INDEX
    // =========================================================================

    public function index(Request $request)
    {
        $pagetitle = 'My Payments';
        $studentId = (int) auth()->user()->student_id;

        $student = $studentId
            ? Student::where('id', $studentId)
                ->select('id', 'firstname', 'lastname', 'othername', 'admissionNo', 'gender', 'statusId')
                ->first()
            : null;

        if (!$student) {
            return redirect()->route('dashboard')->with('error', 'Student profile not found.');
        }

        [$sessions, $terms] = $this->periodOptions($studentId);
        [$sessionId, $termId] = $this->resolvePeriod($request, $studentId, $sessions);

        $statement = $this->statements->buildStatement($student, $termId, $sessionId);

        $studentPicture = DB::table('studentpicture')->where('studentid', $studentId)->orderByDesc('id')->value('picture');

        return view('student.payments.index', array_merge($statement, [
            'pagetitle'         => $pagetitle,
            'student'           => $student,
            'terms'             => $terms,
            'sessions'          => $sessions,
            'selectedSessionId' => $sessionId,
            'selectedTermId'    => $termId,
            'studentPicture'    => $studentPicture,
            'schoolInfo'        => SchoolInformation::first(),
            'paymentTrend'      => $this->statements->buildPaymentTrend($studentId, $sessionId),
        ]));
    }

    // =========================================================================
    // RECEIPT / STATEMENT PDF
    // =========================================================================

    public function printReceipt(Request $request)
    {
        ini_set('max_execution_time', 120);
        ini_set('memory_limit', '512M');

        $studentId = (int) auth()->user()->student_id;
        $student   = $studentId ? Student::find($studentId) : null;

        if (!$student) {
            return back()->with('error', 'Student profile not found.');
        }

        [$sessions] = $this->periodOptions($studentId);
        [$sessionId, $termId] = $this->resolvePeriod($request, $studentId, $sessions);

        $statement = $this->statements->buildStatement($student, $termId, $sessionId);

        if (!$statement['class']) {
            return back()->with('error', 'No class or fee record found for the selected term and session.');
        }

        $schoolInfo  = SchoolInformation::first();
        $termName    = $statement['term']->term ?? 'Term';
        $sessionName = $statement['session']->session ?? 'Session';

        $filename  = 'Payment_Statement_' . Str::slug($student->admissionNo ?? 'student', '-') . '_' . Str::slug($termName, '-') . '.pdf';
        $receiptNo = 'RCP-' . strtoupper(substr(md5($studentId . '-' . $termId . '-' . $sessionId), 0, 8));

        $pdf = Pdf::loadView('student.payments.receipt-pdf', [
            'student'        => $student,
            'bills'          => $statement['bills'],
            'totals'         => $statement['totals'],
            'paymentHistory' => $statement['paymentHistory'],
            'termName'       => $termName,
            'sessionName'    => $sessionName,
            'schoolInfo'     => $schoolInfo,
            'logoBase64'     => $this->logoToBase64($schoolInfo),
            'pictureBase64'  => $this->imageToBase64ForPdf(
                DB::table('studentpicture')->where('studentid', $studentId)->orderByDesc('id')->value('picture')
            ),
            'receiptNo'      => $receiptNo,
            'className'      => $statement['class']->schoolclass,
            'generatedAt'    => Carbon::now()->format('d M Y, h:i A'),
        ])
            ->setPaper('A4', 'portrait')
            ->setOptions([
                'dpi'                  => 150,
                'defaultFont'          => 'DejaVu Sans',
                'isRemoteEnabled'      => true,
                'isHtml5ParserEnabled' => true,
            ]);

        return $pdf->download($filename);
    }

    // =========================================================================
    // PERIOD HELPERS
    // =========================================================================

    /**
     * Sessions the student actually has a record in (enrolment, fees,
     * payments or results), plus the current session. Newest first.
     */
    protected function periodOptions(int $studentId): array
    {
        $ids = collect()
            ->merge(DB::table('studentclass')->where('studentId', $studentId)->pluck('sessionid'))
            ->merge(DB::table('student_bill_payment_book')->where('student_id', $studentId)->pluck('session_id'))
            ->merge(DB::table('student_bill_payment')->where('student_id', $studentId)->whereNull('deleted_at')->pluck('session_id'))
            ->merge(DB::table('broadsheet_records')->where('student_id', $studentId)->pluck('session_id'))
            ->merge(Schoolsession::where('status', 'Current')->pluck('id'))
            ->map(fn ($v) => (int) $v)->filter()->unique();

        $sessions = Schoolsession::whereIn('id', $ids)->orderByDesc('id')->get(['id', 'session', 'status']);
        $terms    = Schoolterm::orderBy('id')->get(['id', 'term']);

        return [$sessions, $terms];
    }

    /** Selected session/term, defaulting to the current session and the student's latest term in it. */
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
                ?: DB::table('student_bill_payment')->where('student_id', $studentId)->where('session_id', $sessionId)->whereNull('deleted_at')->max('termid_id')
                ?: Schoolterm::min('id')
            );
        }

        return [$sessionId ?: null, $termId ?: null];
    }

    // =========================================================================
    // IMAGE HELPERS (PDF)
    // =========================================================================

    private function logoToBase64($schoolInfo): string
    {
        $placeholder = 'data:image/svg+xml;base64,' . base64_encode(
            '<svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 80 80"><rect width="80" height="80" rx="40" fill="#0f2342"/><text x="40" y="46" text-anchor="middle" fill="white" font-family="Arial" font-size="14" font-weight="bold">SCH</text></svg>'
        );
        if (!$schoolInfo || empty($schoolInfo->school_logo)) return $placeholder;

        foreach ([
            storage_path('app/public/' . $schoolInfo->school_logo),
            public_path('storage/' . $schoolInfo->school_logo),
            public_path($schoolInfo->school_logo),
        ] as $path) {
            if (file_exists($path) && filesize($path) > 100) {
                return 'data:' . (mime_content_type($path) ?: 'image/jpeg') . ';base64,' . base64_encode(file_get_contents($path));
            }
        }
        return $placeholder;
    }

    private function imageToBase64ForPdf(?string $path): string
    {
        $placeholder = 'data:image/svg+xml;base64,' . base64_encode(
            '<svg xmlns="http://www.w3.org/2000/svg" width="80" height="95" viewBox="0 0 80 95"><rect width="80" height="95" fill="#e2e8f0"/><circle cx="40" cy="32" r="18" fill="#94a3b8"/><rect x="20" y="56" width="40" height="28" rx="4" fill="#94a3b8"/></svg>'
        );
        if (!$path) return $placeholder;

        foreach ([
            public_path('storage/student_avatars/' . basename($path)),
            storage_path('app/public/student_avatars/' . basename($path)),
            public_path('storage/' . $path),
            storage_path('app/public/' . $path),
        ] as $fullPath) {
            if (file_exists($fullPath) && filesize($fullPath) > 100) {
                return 'data:' . (mime_content_type($fullPath) ?: 'image/jpeg') . ';base64,' . base64_encode(file_get_contents($fullPath));
            }
        }
        return $placeholder;
    }
}
