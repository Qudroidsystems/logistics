<?php
// app/Http/Controllers/EnhancedSchoolPaymentController.php

namespace App\Http\Controllers;

use PDF;
use Carbon\Carbon;
use App\Models\Student;
use App\Models\Schoolterm;
use App\Models\Schoolclass;
use Illuminate\Http\Request;
use App\Models\Schoolsession;
use App\Models\SchoolBillModel;
use App\Models\SchoolInformation;
use App\Models\StudentBillPayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\SchoolBillTermSession;
use App\Models\StudentBillPaymentBook;
use App\Models\StudentBillPaymentRecord;
use Yajra\DataTables\Facades\DataTables;

class EnhancedSchoolPaymentController extends Controller
{
    /**
     * Display student list with AJAX DataTable.
     */
    public function index(Request $request)
    {
        $pagetitle = 'Student Payments';

        if ($request->ajax()) {
            $students = Student::leftJoin('studentclass', 'studentclass.studentId', '=', 'studentRegistration.id')
                ->leftJoin('schoolclass', 'schoolclass.id', '=', 'studentclass.schoolclassid')
                ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
                ->leftJoin('schoolterm', 'schoolterm.id', '=', 'studentclass.termid')
                ->leftJoin('schoolsession', 'schoolsession.id', '=', 'studentclass.sessionid')
                ->where('schoolsession.status', 'Current')
                ->select([
                    'studentRegistration.id as id',
                    'studentRegistration.admissionNo as admissionNo',
                    'studentRegistration.firstname as firstname',
                    'studentRegistration.lastname as lastname',
                    'studentRegistration.gender as gender',
                    'schoolclass.schoolclass as schoolclass',
                    'schoolarm.arm as arm',
                    'schoolterm.term as term',
                    'schoolsession.session as session',
                ]);

            return DataTables::of($students)
                ->addIndexColumn()
                ->addColumn('fullname', function($row) {
                    return $row->firstname . ' ' . $row->lastname;
                })
                ->addColumn('action', function($row) {
                    return '<a href="' . route('schoolpayment.termsession', $row->id) . '" class="btn btn-sm btn-info"><i class="ri-eye-line me-1"></i>View Payments</a>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('schoolpayment.index', compact('pagetitle'));
    }

    /**
     * Get student payment details via AJAX.
     */
    public function getPaymentDetails(Request $request)
    {
        $studentId = $request->studentId;
        $termid = $request->termid;
        $sessionid = $request->sessionid;

        if (!$studentId || !$termid || !$sessionid) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid parameters'
            ], 400);
        }

        $studentdata = Student::where('studentRegistration.id', $studentId)
            ->leftJoin('studentclass', 'studentclass.studentId', '=', 'studentRegistration.id')
            ->leftJoin('schoolclass', 'schoolclass.id', '=', 'studentclass.schoolclassid')
            ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->leftJoin('schoolterm', 'schoolterm.id', '=', 'studentclass.termid')
            ->leftJoin('schoolsession', 'schoolsession.id', '=', 'studentclass.sessionid')
            ->where('studentclass.termid', $termid)
            ->where('studentclass.sessionid', $sessionid)
            ->select([
                'studentRegistration.id as id',
                'studentRegistration.admissionNo as admissionNo',
                'studentRegistration.firstname as firstname',
                'studentRegistration.lastname as lastname',
                'schoolclass.schoolclass as schoolclass',
                'schoolarm.arm as arm',
                'schoolterm.term as term',
                'schoolsession.session as session',
                'studentclass.schoolclassid as schoolclassId',
            ])
            ->first();

        if (!$studentdata) {
            return response()->json([
                'success' => false,
                'message' => 'Student not found'
            ], 404);
        }

        // Get school bills with payment status
        $student_bill_info = SchoolBillTermSession::where('school_bill_class_term_session.class_id', $studentdata->schoolclassId)
            ->where('school_bill_class_term_session.termid_id', $termid)
            ->where('school_bill_class_term_session.session_id', $sessionid)
            ->leftJoin('school_bill', 'school_bill.id', '=', 'school_bill_class_term_session.bill_id')
            ->select([
                'school_bill.id as schoolbillid',
                'school_bill.title as title',
                'school_bill.description as description',
                'school_bill.bill_amount as amount'
            ])
            ->get();

        $paymentBook = StudentBillPaymentBook::where('student_id', $studentId)
            ->where('term_id', $termid)
            ->where('session_id', $sessionid)
            ->get()
            ->keyBy('school_bill_id');

        $bills = [];
        $totalBilled = 0;
        $totalPaid = 0;

        foreach ($student_bill_info as $bill) {
            $paidAmount = $paymentBook->get($bill->schoolbillid)?->amount_paid ?? 0;
            $balance = $bill->amount - $paidAmount;
            $totalBilled += $bill->amount;
            $totalPaid += $paidAmount;

            $bills[] = [
                'id' => $bill->schoolbillid,
                'title' => $bill->title,
                'description' => $bill->description,
                'amount' => $bill->amount,
                'paid' => $paidAmount,
                'balance' => max(0, $balance),
                'is_completed' => $balance <= 0,
                'progress' => $bill->amount > 0 ? round(($paidAmount / $bill->amount) * 100, 1) : 0
            ];
        }

        return response()->json([
            'success' => true,
            'student' => $studentdata,
            'bills' => $bills,
            'summary' => [
                'total_billed' => $totalBilled,
                'total_paid' => $totalPaid,
                'total_outstanding' => $totalBilled - $totalPaid,
                'completion_rate' => $totalBilled > 0 ? round(($totalPaid / $totalBilled) * 100, 1) : 0
            ]
        ]);
    }

    /**
     * Process payment via AJAX.
     */
    public function processPayment(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|integer|exists:studentRegistration,id',
            'class_id' => 'required|integer|exists:schoolclass,id',
            'term_id' => 'required|integer|exists:schoolterm,id',
            'session_id' => 'required|integer|exists:schoolsession,id',
            'school_bill_id' => 'required|integer|exists:school_bill,id',
            'payment_amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string|in:Bank Deposit,School POS,Bank Transfer,Cheque,Cash',
            'reference_no' => 'nullable|string|max:100',
        ]);

        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Please log in to make payments'
            ], 401);
        }

        DB::beginTransaction();
        try {
            $studentPayment = StudentBillPayment::where([
                'student_id' => $validated['student_id'],
                'school_bill_id' => $validated['school_bill_id'],
                'class_id' => $validated['class_id'],
                'termid_id' => $validated['term_id'],
                'session_id' => $validated['session_id'],
            ])->first();

            $billAmount = SchoolBillModel::find($validated['school_bill_id'])->bill_amount;
            $currentPaid = $studentPayment ? $studentPayment->total_paid : 0;
            $currentBalance = $billAmount - $currentPaid;

            if ($validated['payment_amount'] > $currentBalance) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment amount exceeds outstanding balance of ₦' . number_format($currentBalance, 2)
                ], 422);
            }

            $newTotalPaid = $currentPaid + $validated['payment_amount'];
            $newBalance = $currentBalance - $validated['payment_amount'];
            $isComplete = $newBalance <= 0;

            if (!$studentPayment) {
                $studentPayment = StudentBillPayment::create([
                    'student_id' => $validated['student_id'],
                    'school_bill_id' => $validated['school_bill_id'],
                    'class_id' => $validated['class_id'],
                    'termid_id' => $validated['term_id'],
                    'session_id' => $validated['session_id'],
                    'payment_method' => $validated['payment_method'],
                    'status' => $isComplete ? 'Completed' : 'Pending',
                    'generated_by' => Auth::id(),
                    'delete_status' => '1',
                    'total_paid' => $validated['payment_amount'],
                    'total_balance' => $newBalance,
                    'payment_status' => $isComplete ? 'completed' : 'partial',
                    'last_payment_date' => now(),
                ]);
            } else {
                $studentPayment->update([
                    'payment_method' => $validated['payment_method'],
                    'status' => $isComplete ? 'Completed' : 'Pending',
                    'total_paid' => $newTotalPaid,
                    'total_balance' => $newBalance,
                    'payment_status' => $isComplete ? 'completed' : 'partial',
                    'last_payment_date' => now(),
                ]);
            }

            StudentBillPaymentRecord::create([
                'student_bill_payment_id' => $studentPayment->id,
                'class_id' => $validated['class_id'],
                'termid_id' => $validated['term_id'],
                'session_id' => $validated['session_id'],
                'amount_paid' => $validated['payment_amount'],
                'last_payment' => $validated['payment_amount'],
                'amount_owed' => $newBalance,
                'total_bill' => $billAmount,
                'complete_payment' => $isComplete ? 1 : 0,
                'generated_by' => Auth::id(),
                'transaction_reference' => $validated['reference_no'] ?? null,
            ]);

            StudentBillPaymentBook::updateOrCreate(
                [
                    'student_id' => $validated['student_id'],
                    'school_bill_id' => $validated['school_bill_id'],
                    'class_id' => $validated['class_id'],
                    'term_id' => $validated['term_id'],
                    'session_id' => $validated['session_id'],
                ],
                [
                    'amount_paid' => DB::raw("amount_paid + {$validated['payment_amount']}"),
                    'amount_owed' => $newBalance,
                    'payment_status' => $isComplete ? 'completed' : 'partial',
                    'generated_by' => Auth::id(),
                ]
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment recorded successfully!',
                'data' => [
                    'bill_id' => $validated['school_bill_id'],
                    'amount_paid' => $validated['payment_amount'],
                    'new_balance' => $newBalance,
                    'is_completed' => $isComplete,
                    'payment_id' => $studentPayment->id
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payment error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to process payment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get payment history via AJAX.
     */
    public function getPaymentHistory(Request $request)
    {
        $studentId = $request->studentId;
        $termid = $request->termid;
        $sessionid = $request->sessionid;

        $payments = StudentBillPayment::where('student_bill_payment.student_id', $studentId)
            ->where('student_bill_payment.termid_id', $termid)
            ->where('student_bill_payment.session_id', $sessionid)
            ->leftJoin('student_bill_payment_record', 'student_bill_payment_record.student_bill_payment_id', '=', 'student_bill_payment.id')
            ->leftJoin('school_bill', 'school_bill.id', '=', 'student_bill_payment.school_bill_id')
            ->leftJoin('users', 'users.id', '=', 'student_bill_payment.generated_by')
            ->select([
                'student_bill_payment_record.id as record_id',
                'student_bill_payment_record.created_at as payment_date',
                'student_bill_payment.payment_method',
                'school_bill.title',
                'student_bill_payment_record.amount_paid',
                'student_bill_payment_record.amount_owed as balance',
                DB::raw('CASE WHEN student_bill_payment_record.complete_payment = 1 THEN "Completed" ELSE "Pending" END as status'),
                DB::raw('COALESCE(users.name, "System") as received_by'),
            ])
            ->orderBy('student_bill_payment_record.created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $payments
        ]);
    }
}
