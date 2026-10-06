<?php
// app/Http/Controllers/Payment/EnhancedSchoolPaymentController.php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Schoolclass;
use App\Models\Schoolterm;
use App\Models\Schoolsession;
use App\Models\SchoolBillTermSession;
use App\Models\StudentBillPayment;
use App\Models\StudentBillPaymentRecord;
use App\Models\StudentBillPaymentBook;
use App\Models\PaymentBatch;
use App\Services\Payment\EnhancedPaymentService;
use App\Services\Scholarship\ScholarshipService;
use App\Services\Discount\DiscountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Barryvdh\DomPDF\Facade\Pdf;

class EnhancedSchoolPaymentController extends Controller
{
    protected $paymentService;
    protected $scholarshipService;
    protected $discountService;

    public function __construct(
        EnhancedPaymentService $paymentService,
        ScholarshipService $scholarshipService,
        DiscountService $discountService
    ) {
        $this->paymentService     = $paymentService;
        $this->scholarshipService = $scholarshipService;
        $this->discountService    = $discountService;

        $this->middleware('permission:View payment',    ['only' => ['index', 'showPaymentDetails', 'showInvoice', 'getPaymentStatusAjax']]);
        $this->middleware('permission:Create payment',  ['only' => ['processPayment']]);
        $this->middleware('permission:Process payment', ['only' => ['processOfflinePayment', 'processOnlinePayment']]);
        $this->middleware('permission:Reverse payment', ['only' => ['reversePayment']]);
        $this->middleware('permission:View invoice',    ['only' => ['showInvoice', 'downloadInvoice']]);
        $this->middleware('permission:Generate invoice',['only' => ['generateInvoice']]);
    }

    // =========================================================================
    // Student list
    // =========================================================================

    /**
     * Display student list for payment selection.
     */
    public function index(Request $request)
    {
        $pagetitle = 'Student Payments';

        $students = Student::leftJoin('studentclass', 'studentclass.studentId', '=', 'studentRegistration.id')
            ->leftJoin('schoolclass',   'schoolclass.id',   '=', 'studentclass.schoolclassid')
            ->leftJoin('schoolarm',     'schoolarm.id',     '=', 'schoolclass.arm')
            ->leftJoin('schoolterm',    'schoolterm.id',    '=', 'studentclass.termid')
            ->leftJoin('schoolsession', 'schoolsession.id', '=', 'studentclass.sessionid')
            ->where('schoolsession.status', 'Current')
            ->select([
                'studentRegistration.id          as id',
                'studentRegistration.admissionNo  as admissionNo',
                'studentRegistration.firstname    as firstname',
                'studentRegistration.lastname     as lastname',
                'studentRegistration.gender       as gender',
                'schoolclass.schoolclass          as schoolclass',
                'schoolarm.arm                    as arm',
                'schoolterm.term                  as term',
                'schoolsession.session            as session',
            ]);

        if ($request->filled('search')) {
            $search = $request->search;
            $students->where(function ($q) use ($search) {
                $q->where('studentRegistration.admissionNo', 'like', "%{$search}%")
                  ->orWhere('studentRegistration.firstname',  'like', "%{$search}%")
                  ->orWhere('studentRegistration.lastname',   'like', "%{$search}%");
            });
        }

        if ($request->filled('class_id')) {
            $students->where('studentclass.schoolclassid', $request->class_id);
        }

        $students = $students->get();

        $classes = Schoolclass::leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->select('schoolclass.id', 'schoolclass.schoolclass', 'schoolarm.arm')
            ->get();

        foreach ($students as $student) {
            $scholarshipSummary    = $this->scholarshipService->getStudentScholarshipSummary($student->id);
            $discountSummary       = $this->discountService->getStudentDiscountSummary($student->id);
            $student->has_scholarship = $scholarshipSummary['has_scholarship'];
            $student->has_discount    = $discountSummary['has_discounts'];
            $student->total_savings   = ($scholarshipSummary['total_savings'] ?? 0)
                                      + ($discountSummary['total_savings']    ?? 0);
        }

        return view('payment.student-list', compact('pagetitle', 'students', 'classes'));
    }

    // =========================================================================
    // Payment details (page view)
    // =========================================================================

    /**
     * Show payment details page for a student.
     * This renders the Blade view — JS inside that view then calls
     * getPaymentStatusAjax() to hydrate it with JSON.
     */
    public function showPaymentDetails($studentId, $classId, $termId, $sessionId)
    {
        // Lightweight check: does the student exist?
        $student = Student::findOrFail($studentId);

        $pagetitle = 'Payment Details - ' . $student->firstname . ' ' . $student->lastname;

        return view('payment.payment-details', compact(
            'pagetitle',
            'studentId',
            'classId',
            'termId',
            'sessionId'
        ));
    }

    // =========================================================================
    // AJAX endpoint — called by payment-details.blade.php JS
    // =========================================================================

    /**
     * Return full payment details as JSON (query-string params).
     *
     * Route:  GET /payment/details/ajax
     * Name:   payment.details.ajax
     *
     * Query params:
     *   studentId  – required
     *   classId    – optional (resolved from studentclass if missing)
     *   termid     – required
     *   sessionid  – required
     */
    public function getPaymentStatusAjax(Request $request)
    {
        try {
            $studentId = $request->query('studentId');
            $classId   = $request->query('classId',   $request->query('classid'));
            $termId    = $request->query('termid',    $request->query('termId'));
            $sessionId = $request->query('sessionid', $request->query('sessionId'));

            if (!$studentId || !$termId || !$sessionId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Missing required parameters: studentId, termid and sessionid are required.',
                ], 422);
            }

            // Resolve classId from studentclass if not supplied
            if (!$classId) {
                $classId = DB::table('studentclass')
                    ->where('studentId', $studentId)
                    ->where('termid',    $termId)
                    ->where('sessionid', $sessionId)
                    ->value('schoolclassid');

                if (!$classId) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Could not determine class for this student in the given term/session.',
                    ], 422);
                }
            }

            $details = $this->paymentService->getStudentPaymentDetails(
                $studentId, $classId, $termId, $sessionId
            );

            return response()->json([
                'success' => true,
                'data'    => $details,
            ]);

        } catch (\Exception $e) {
            Log::error('AJAX payment details error: ' . $e->getMessage(), [
                'studentId' => $request->query('studentId'),
                'termId'    => $request->query('termid'),
                'sessionId' => $request->query('sessionid'),
                'trace'     => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load payment details: ' . $e->getMessage(),
            ], 500);
        }
    }

    // =========================================================================
    // Flexible payment (parent/student portal)
    // =========================================================================

    /**
     * Show flexible payment form.
     */
    public function showFlexiblePayment($studentId, $classId, $termId, $sessionId)
    {
        $details = $this->paymentService->getStudentPaymentDetails($studentId, $classId, $termId, $sessionId);

        $student = $details['student'];
        $bills   = $details['bills'];
        $summary = $details['summary'];

        $pagetitle = 'Make Payment - ' . $student->firstname . ' ' . $student->lastname;

        return view('payment.flexible-payment', compact(
            'pagetitle', 'student', 'bills', 'summary', 'classId', 'termId', 'sessionId'
        ));
    }

    // =========================================================================
    // Process payments
    // =========================================================================

    /**
     * Process offline payment.
     */
    public function processOfflinePayment(Request $request)
    {
        try {
            $validated = $request->validate([
                'student_id'               => 'required|exists:studentRegistration,id',
                'class_id'                 => 'required|exists:schoolclass,id',
                'term_id'                  => 'required|exists:schoolterm,id',
                'session_id'               => 'required|exists:schoolsession,id',
                'payment_items'            => 'required|array|min:1',
                'payment_items.*.bill_id'  => 'required|exists:school_bill,id',
                'payment_items.*.amount'   => 'required|numeric|min:0.01',
                'payment_method'           => 'required|in:Bank Deposit,School POS,Bank Transfer,Cheque,Cash',
                'reference_no'             => 'nullable|string|max:100',
                'notes'                    => 'nullable|string',
            ]);

            if (is_string($validated['payment_items'])) {
                $validated['payment_items'] = json_decode($validated['payment_items'], true);
            }

            $result = $this->paymentService->processPayment(
                $validated['student_id'],
                $validated['class_id'],
                $validated['term_id'],
                $validated['session_id'],
                [
                    'items'          => $validated['payment_items'],
                    'payment_method' => $validated['payment_method'],
                    'reference_no'   => $validated['reference_no'] ?? null,
                    'notes'          => $validated['notes']         ?? null,
                ]
            );

            if ($request->ajax()) {
                return response()->json([
                    'success'      => true,
                    'message'      => 'Payment processed successfully.',
                    'receipt_no'   => $result['receipt']['receipt_no'],
                    'total_paid'   => $result['total_paid'],
                    'total_savings'=> $result['total_savings'],
                    'redirect_url' => route('payment.receipt', ['batch_id' => $result['batch']->id]),
                ]);
            }

            return redirect()->route('payment.receipt', ['batch_id' => $result['batch']->id])
                ->with('success', 'Payment processed successfully!');

        } catch (ValidationException $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error.',
                    'errors'  => $e->errors(),
                ], 422);
            }
            throw $e;
        } catch (\Exception $e) {
            Log::error('Error processing payment: ' . $e->getMessage());

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to process payment: ' . $e->getMessage(),
                ], 500);
            }

            return back()->with('error', 'Failed to process payment: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // Invoice / receipt
    // =========================================================================

    /**
     * Generate invoice for a payment.
     */
    public function generateInvoice($paymentId)
    {
        try {
            $payment = StudentBillPayment::with(['student', 'schoolBill', 'paymentRecords'])
                ->findOrFail($paymentId);

            $invoice = $payment->generateInvoice();

            return response()->json([
                'success'    => true,
                'message'    => 'Invoice generated successfully.',
                'invoice_no' => $invoice->invoice_no,
                'invoice_id' => $invoice->id,
            ]);

        } catch (\Exception $e) {
            Log::error('Error generating invoice: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate invoice: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show / download invoice.
     */
    public function showInvoice($studentId, $classId, $termId, $sessionId, Request $request)
    {
        $details = $this->paymentService->getStudentPaymentDetails($studentId, $classId, $termId, $sessionId);

        $student   = $details['student'];
        $bills     = $details['bills'];
        $summary   = $details['summary'];

        $paidBills = array_filter($bills, fn($bill) => $bill['paid_amount'] > 0);

        $invoiceNumber = 'INV-' . date('Ymd') . '-' . str_pad($studentId, 4, '0', STR_PAD_LEFT);
        $schoolInfo    = \App\Models\SchoolInformation::first();

        $data = compact('student', 'paidBills', 'summary', 'invoiceNumber', 'schoolInfo', 'classId', 'termId', 'sessionId');

        if ($request->has('download')) {
            $pdf = PDF::loadView('payment.invoice-pdf', $data);
            return $pdf->download('invoice_' . $student->admissionNo . '_' . date('Ymd') . '.pdf');
        }

        $pagetitle = 'Invoice - ' . $student->firstname . ' ' . $student->lastname;

        return view('payment.invoice', compact('pagetitle', 'data'));
    }

    /**
     * Show receipt after payment.
     */
    public function showReceipt($batchId)
    {
        $batch       = PaymentBatch::with(['student', 'items.schoolBill'])->findOrFail($batchId);
        $receiptData = $batch->receipt_data;
        $pagetitle   = 'Payment Receipt';

        if (request()->has('download')) {
            $pdf = PDF::loadView('payment.receipt-pdf', compact('batch', 'receiptData'));
            return $pdf->download('receipt_' . $batch->batch_no . '.pdf');
        }

        return view('payment.receipt', compact('pagetitle', 'batch', 'receiptData'));
    }

    // =========================================================================
    // Payment management
    // =========================================================================

    /**
     * Reverse a payment batch.
     */
    public function reversePayment($batchId, Request $request)
    {
        try {
            $request->validate(['reason' => 'required|string|min:5']);

            $this->paymentService->reversePayment($batchId, $request->reason);

            return response()->json([
                'success' => true,
                'message' => 'Payment reversed successfully.',
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error.',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error reversing payment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to reverse payment: ' . $e->getMessage(),
            ], 500);
        }
    }

    // =========================================================================
    // AJAX helpers
    // =========================================================================

    /**
     * Get student payment status — route-segment version (used internally / API).
     * The blade uses getPaymentStatusAjax() via query params instead.
     */
    public function getPaymentStatus($studentId, $classId, $termId, $sessionId)
    {
        try {
            $details = $this->paymentService->getStudentPaymentDetails($studentId, $classId, $termId, $sessionId);

            return response()->json([
                'success' => true,
                'data'    => $details,
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting payment status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to get payment status.',
            ], 500);
        }
    }

    /**
     * Get student's scholarship and discount summary (AJAX).
     */
    public function getSavingsSummary($studentId)
    {
        try {
            $scholarshipSummary = $this->scholarshipService->getStudentScholarshipSummary($studentId);
            $discountSummary    = $this->discountService->getStudentDiscountSummary($studentId);

            return response()->json([
                'success'       => true,
                'scholarship'   => $scholarshipSummary,
                'discount'      => $discountSummary,
                'total_savings' => ($scholarshipSummary['total_savings'] ?? 0)
                                 + ($discountSummary['total_savings']    ?? 0),
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting savings summary: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to get savings summary.',
            ], 500);
        }
    }
}
