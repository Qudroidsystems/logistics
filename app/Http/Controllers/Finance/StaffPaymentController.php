<?php
// app/Http/Controllers/Finance/StaffPaymentController.php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Models\StaffPayment;
use App\Models\PayrollRun;
use App\Models\PayrollPeriod;
use App\Models\User;
use App\Services\Payroll\NigerianPayrollService;
use App\Services\Accounting\AccountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;
use Barryvdh\DomPDF\Facade\Pdf;

class StaffPaymentController extends Controller
{
    protected $payrollService;
    protected $accountingService;

    public function __construct(
        NigerianPayrollService $payrollService,
        AccountingService $accountingService
    ) {
        $this->payrollService = $payrollService;
        $this->accountingService = $accountingService;

        $this->middleware('permission:View staff payments', ['only' => ['index', 'show', 'staffDashboard', 'getPaymentHistory']]);
        $this->middleware('permission:Create staff payment', ['only' => ['create', 'store']]);
        $this->middleware('permission:Update staff payment', ['only' => ['edit', 'update']]);
        $this->middleware('permission:Delete staff payment', ['only' => ['destroy']]);
        $this->middleware('permission:Reverse staff payment', ['only' => ['reversePayment']]);
    }

    /**
     * Display list of staff payments (Admin view).
     */
    public function index(Request $request)
    {
        $pagetitle = 'Staff Payments Management';


        if ($request->ajax()) {
            $payments = StaffPayment::with(['staff.user', 'payrollRun'])
                ->select('staff_payments.*')
                ->orderBy('created_at', 'desc');

            return DataTables::of($payments)
                ->addIndexColumn()
                ->addColumn('staff_name', function($payment) {
                    return $payment->staff->user->name ?? 'N/A';
                })
                ->addColumn('staff_id', function($payment) {
                    return $payment->staff->employmentid ?? 'N/A';
                })
                ->addColumn('formatted_amount', function($payment) {
                    return '₦' . number_format($payment->amount, 2);
                })
                ->addColumn('payment_type_badge', function($payment) {
                    $badges = [
                        'salary' => 'primary',
                        'bonus' => 'success',
                        'loan_disbursement' => 'info',
                        'reimbursement' => 'warning',
                        'advance' => 'danger',
                        'other' => 'secondary',
                    ];
                    $color = $badges[$payment->payment_type] ?? 'secondary';
                    return '<span class="badge bg-' . $color . '">' . ucfirst(str_replace('_', ' ', $payment->payment_type)) . '</span>';
                })
                ->addColumn('status_badge', function($payment) {
                    $badges = [
                        'pending' => 'warning',
                        'processed' => 'info',
                        'paid' => 'success',
                        'failed' => 'danger',
                        'reversed' => 'secondary',
                    ];
                    $color = $badges[$payment->payment_status] ?? 'secondary';
                    return '<span class="badge bg-' . $color . '">' . ucfirst($payment->payment_status) . '</span>';
                })
                ->addColumn('action', function($payment) {
                    $buttons = '<button class="btn btn-sm btn-info view-payment me-1" data-id="'.$payment->id.'"><i class="ri-eye-line"></i></button>';

                    if ($payment->payment_status === 'processed') {
                        $buttons .= '<button class="btn btn-sm btn-success mark-paid me-1" data-id="'.$payment->id.'"><i class="ri-check-line"></i> Mark Paid</button>';
                    }

                    if ($payment->payment_status !== 'reversed' && $payment->payment_status !== 'paid') {
                        $buttons .= '<button class="btn btn-sm btn-danger reverse-payment me-1" data-id="'.$payment->id.'"><i class="ri-refund-line"></i> Reverse</button>';
                    }

                    return $buttons;
                })
                ->rawColumns(['payment_type_badge', 'status_badge', 'action'])
                ->make(true);
        }

        $q = StaffPayment::with(['staff.user'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $s = $request->search;
                $q->where(fn ($w) => $w->where('payment_reference', 'like', "%$s%")->orWhere('transaction_ref', 'like', "%$s%")
                    ->orWhereHas('staff', fn ($st) => $st->where('employmentid', 'like', "%$s%")->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%$s%"))));
            })
            ->when($request->filled('month'), fn ($q) => $q->whereYear('payment_date', substr($request->month, 0, 4))->whereMonth('payment_date', substr($request->month, 5, 2)))
            ->when($request->filled('type'), fn ($q) => $q->where('payment_type', $request->type))
            ->when($request->filled('status'), fn ($q) => $q->where('payment_status', $request->status))
            ->orderByDesc('payment_date')->orderByDesc('id');
        $payments = $q->paginate(25)->withQueryString();

        $stats = [
            'month_paid'    => StaffPayment::where('payment_status', 'paid')->whereYear('payment_date', now()->year)->whereMonth('payment_date', now()->month)->sum('amount'),
            'month_count'   => StaffPayment::where('payment_status', 'paid')->whereYear('payment_date', now()->year)->whereMonth('payment_date', now()->month)->count(),
            'pending'       => StaffPayment::whereIn('payment_status', ['pending', 'processed'])->sum('amount'),
            'pending_count' => StaffPayment::whereIn('payment_status', ['pending', 'processed'])->count(),
            'year_paid'     => StaffPayment::where('payment_status', 'paid')->whereYear('payment_date', now()->year)->sum('amount'),
            'reversed'      => StaffPayment::where('payment_status', 'reversed')->count(),
        ];

        return view('finance.staff.payments-index', compact('pagetitle', 'payments', 'stats'));
    }

    /**
     * Show form for creating a new staff payment.
     */
    public function create()
    {
        $pagetitle = 'Record Staff Payment';
        $staff = Staff::with('user')->active()->get();

        return view('finance.staff.payments-create', compact('pagetitle', 'staff'));
    }

    /**
     * Store a new staff payment.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'staff_id' => 'required|exists:staffbioinfo,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'payment_method' => 'required|in:bank_transfer,cash,cheque',
            'payment_type' => 'required|in:salary,bonus,loan_disbursement,reimbursement,advance,other',
            'purpose' => 'required|string|min:5',
            'bank_name' => 'required_if:payment_method,bank_transfer',
            'account_number' => 'required_if:payment_method,bank_transfer',
            'transaction_ref' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:pdf,jpg,png|max:2048',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();
        try {
            $attachmentPath = null;
            if ($request->hasFile('attachment')) {
                $attachmentPath = $request->file('attachment')->store('staff-payments', 'public');
            }

            $payment = StaffPayment::create([
                'staff_id' => $request->staff_id,
                'payment_reference' => $this->generatePaymentReference(),
                'payment_type' => $request->payment_type,
                'amount' => $request->amount,
                'payment_date' => $request->payment_date,
                'payment_method' => $request->payment_method,
                'bank_name' => $request->bank_name,
                'account_number' => $request->account_number,
                'account_name' => $request->account_name,
                'transaction_ref' => $request->transaction_ref,
                'purpose' => $request->purpose,
                'attachment' => $attachmentPath,
                'notes' => $request->notes,
                'payment_status' => 'processed',
                'created_by' => Auth::id(),
            ]);

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Payment recorded successfully!',
                    'data' => $payment
                ], 201);
            }

            return redirect()->route('staff.payments.index')->with('success', 'Payment recorded successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Staff payment creation error: ' . $e->getMessage());

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to record payment: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()->with('error', 'Failed to record payment: ' . $e->getMessage());
        }
    }

    /**
     * Show staff payment details.
     */
    public function show($id)
    {
        $payment = StaffPayment::with(['staff.user', 'createdBy', 'reversedBy'])
            ->findOrFail($id);

        return view('finance.staff.payments-show', compact('payment'));
    }

    /**
     * Edit staff payment.
     */
    public function edit($id)
    {
        $payment = StaffPayment::findOrFail($id);

        if ($payment->payment_status === 'paid') {
            return redirect()->route('staff.payments.index')->with('error', 'Cannot edit a paid payment.');
        }

        $staff = Staff::with('user')->active()->get();
        $pagetitle = 'Edit Staff Payment';

        return view('finance.staff.payments-edit', compact('payment', 'staff', 'pagetitle'));
    }

    /**
     * Update staff payment.
     */
    public function update(Request $request, $id)
    {
        $payment = StaffPayment::findOrFail($id);

        if ($payment->payment_status === 'paid') {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot edit a paid payment.'
                ], 400);
            }
            return redirect()->route('staff.payments.index')->with('error', 'Cannot edit a paid payment.');
        }

        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'payment_method' => 'required|in:bank_transfer,cash,cheque',
            'purpose' => 'required|string|min:5',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            $payment->update([
                'amount' => $request->amount,
                'payment_date' => $request->payment_date,
                'payment_method' => $request->payment_method,
                'bank_name' => $request->bank_name,
                'account_number' => $request->account_number,
                'transaction_ref' => $request->transaction_ref,
                'purpose' => $request->purpose,
                'notes' => $request->notes,
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Payment updated successfully!',
                    'data' => $payment
                ]);
            }

            return redirect()->route('staff.payments.index')->with('success', 'Payment updated successfully!');

        } catch (\Exception $e) {
            Log::error('Staff payment update error: ' . $e->getMessage());

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update payment: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()->with('error', 'Failed to update payment.');
        }
    }

    /**
     * Delete staff payment.
     */
    public function destroy($id)
    {
        $payment = StaffPayment::findOrFail($id);

        if ($payment->payment_status === 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete a paid payment.'
            ], 400);
        }

        $payment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Payment deleted successfully!'
        ]);
    }

    /**
     * Staff dashboard view (for staff members).
     */
    public function staffDashboard(Request $request)
    {
        if (!$request->ajax() && \Illuminate\Support\Facades\Route::has('my-pay.index')) {
            return redirect()->route('my-pay.index');
        }

        $staff = Auth::user()->staff;

        if (!$staff) {
            return redirect()->route('dashboard')->with('error', 'Staff record not found.');
        }

        $pagetitle = 'My Payment Dashboard';

        if ($request->ajax()) {
            $payments = StaffPayment::where('staff_id', $staff->id)
                ->orderBy('payment_date', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'payments' => $payments->map(function($payment) {
                    return [
                        'id' => $payment->id,
                        'reference' => $payment->payment_reference,
                        'amount' => '₦' . number_format($payment->amount, 2),
                        'date' => $payment->payment_date->format('d M, Y'),
                        'type' => ucfirst(str_replace('_', ' ', $payment->payment_type)),
                        'method' => ucfirst(str_replace('_', ' ', $payment->payment_method)),
                        'status' => ucfirst($payment->payment_status),
                        'purpose' => $payment->purpose,
                    ];
                })
            ]);
        }

        $payments = StaffPayment::where('staff_id', $staff->id)
            ->orderBy('payment_date', 'desc')
            ->paginate(20);

        $payrollHistory = PayrollRun::where('staff_id', $staff->id)
            ->where('payment_status', 'paid')
            ->with('payrollPeriod')
            ->orderBy('created_at', 'desc')
            ->get();

        $loanSummary = $this->payrollService->getActiveLoanDeductions($staff->id);
        $stats = $this->getStaffPaymentStats($staff->id);

        return view('finance.staff.payment-dashboard', compact(
            'pagetitle', 'staff', 'payments', 'payrollHistory', 'loanSummary', 'stats'
        ));
    }

    /**
     * Get payment history (AJAX).
     */
    public function getPaymentHistory(Request $request)
    {
        $staffId = $request->input('staff_id');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $paymentType = $request->input('payment_type');

        $query = StaffPayment::with(['staff.user']);

        if ($staffId) {
            $query->where('staff_id', $staffId);
        }
        if ($startDate) {
            $query->where('payment_date', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('payment_date', '<=', $endDate);
        }
        if ($paymentType) {
            $query->where('payment_type', $paymentType);
        }

        $payments = $query->orderBy('payment_date', 'desc')->get();

        return response()->json([
            'success' => true,
            'payments' => $payments->map(function($payment) {
                return [
                    'id' => $payment->id,
                    'reference' => $payment->payment_reference,
                    'staff_name' => $payment->staff->user->name ?? 'N/A',
                    'amount' => number_format($payment->amount, 2),
                    'date' => $payment->payment_date->format('Y-m-d'),
                    'type' => ucfirst(str_replace('_', ' ', $payment->payment_type)),
                    'method' => ucfirst(str_replace('_', ' ', $payment->payment_method)),
                    'status' => ucfirst($payment->payment_status),
                    'purpose' => $payment->purpose,
                ];
            })
        ]);
    }

    /**
     * Reverse a payment.
     */
    public function reversePayment(Request $request, $paymentId)
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|min:10',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $payment = StaffPayment::findOrFail($paymentId);

            if ($payment->payment_status === 'paid') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot reverse a payment that has already been disbursed.'
                ], 400);
            }

            $payment->update([
                'payment_status' => 'reversed',
                'reversal_reason' => $request->reason,
                'reversed_by' => Auth::id(),
                'reversed_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment reversed successfully!'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payment reversal error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to reverse payment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark payment as paid.
     */
    public function markAsPaid(Request $request, $paymentId)
    {
        DB::beginTransaction();
        try {
            $payment = StaffPayment::findOrFail($paymentId);

            $payment->update([
                'payment_status' => 'paid',
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment marked as paid successfully!'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Mark payment as paid error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update payment status.'
            ], 500);
        }
    }

    /**
     * View payslip.
     */
    public function viewPayslip($payrollRunId)
    {
        $payrollRun = PayrollRun::with(['staff.user', 'payrollPeriod', 'salaryStructure'])
            ->findOrFail($payrollRunId);

        $user = Auth::user();
        $staff = $user->staff;

        if (!$staff || ($staff->id != $payrollRun->staff_id && !$user->hasPermissionTo('View payroll'))) {
            abort(403);
        }

        $earnings = $this->getEarningsBreakdown($payrollRun);
        $deductions = $this->getDeductionsBreakdown($payrollRun);
        $employerContributions = [
            ['name' => 'Pension (Employer)', 'amount' => $payrollRun->employer_pension],
            ['name' => 'NSITF', 'amount' => $payrollRun->nsitf],
        ];

        $schoolInfo = \App\Models\SchoolInformation::first();
        $pagetitle = 'Payslip - ' . ($payrollRun->staff->user->name ?? 'Staff');

        return view('finance.staff.payslip', compact(
            'pagetitle', 'payrollRun', 'earnings', 'deductions', 'employerContributions', 'schoolInfo'
        ));
    }

    /**
     * Download payslip as PDF.
     */
    public function downloadPayslip($payrollRunId)
    {
        $payrollRun = PayrollRun::with(['staff.user', 'payrollPeriod', 'salaryStructure'])
            ->findOrFail($payrollRunId);

        $user = Auth::user();
        $staff = $user->staff;

        if (!$staff || ($staff->id != $payrollRun->staff_id && !$user->hasPermissionTo('View payroll'))) {
            abort(403);
        }

        $earnings = $this->getEarningsBreakdown($payrollRun);
        $deductions = $this->getDeductionsBreakdown($payrollRun);
        $employerContributions = [
            ['name' => 'Pension (Employer)', 'amount' => $payrollRun->employer_pension],
            ['name' => 'NSITF', 'amount' => $payrollRun->nsitf],
        ];

        $schoolInfo = \App\Models\SchoolInformation::first();

        $pdf = PDF::loadView('finance.staff.payslip-pdf', compact(
            'payrollRun', 'earnings', 'deductions', 'employerContributions', 'schoolInfo'
        ));

        return $pdf->download('payslip_' . ($payrollRun->staff->employmentid ?? 'staff') . '_' . $payrollRun->payrollPeriod->period_name . '.pdf');
    }

    /**
     * Generate payment reference number.
     */
    private function generatePaymentReference()
    {
        return 'SP-' . date('Ymd') . '-' . strtoupper(uniqid());
    }

    /**
     * Get staff payment statistics.
     */
    private function getStaffPaymentStats($staffId)
    {
        $totalPaid = StaffPayment::where('staff_id', $staffId)
            ->where('payment_status', 'paid')
            ->sum('amount');

        $totalPending = StaffPayment::where('staff_id', $staffId)
            ->where('payment_status', 'processed')
            ->sum('amount');

        $paymentCount = StaffPayment::where('staff_id', $staffId)->count();

        return [
            'total_paid' => $totalPaid,
            'total_pending' => $totalPending,
            'payment_count' => $paymentCount,
        ];
    }

    /**
     * Get earnings breakdown.
     */
    private function getEarningsBreakdown($payrollRun)
    {
        $earnings = [
            ['name' => 'Basic Salary', 'amount' => $payrollRun->basic_salary],
            ['name' => 'Housing Allowance', 'amount' => $payrollRun->housing_allowance],
            ['name' => 'Transport Allowance', 'amount' => $payrollRun->transport_allowance],
            ['name' => 'Meal Allowance', 'amount' => $payrollRun->meal_allowance],
            ['name' => 'Medical Allowance', 'amount' => $payrollRun->medical_allowance],
            ['name' => 'Utility Allowance', 'amount' => $payrollRun->utility_allowance],
            ['name' => 'Other Allowances', 'amount' => $payrollRun->other_allowances],
        ];

        $customAllowances = $payrollRun->custom_allowances ?? [];
        foreach ($customAllowances as $allowance) {
            if (($allowance['amount'] ?? 0) > 0) {
                $earnings[] = ['name' => $allowance['name'], 'amount' => $allowance['amount']];
            }
        }

        return array_filter($earnings, function($item) {
            return $item['amount'] > 0;
        });
    }

    /**
     * Get deductions breakdown.
     */
    private function getDeductionsBreakdown($payrollRun)
    {
        $deductions = [
            ['name' => 'PAYE Tax', 'amount' => $payrollRun->paye_tax],
            ['name' => 'Pension (Employee)', 'amount' => $payrollRun->employee_pension],
            ['name' => 'NHF', 'amount' => $payrollRun->nhf],
            ['name' => 'Loan Repayment', 'amount' => $payrollRun->loan_repayment],
            ['name' => 'Salary Advance', 'amount' => $payrollRun->advance_repayment],
        ];

        return array_filter($deductions, function($item) {
            return $item['amount'] > 0;
        });
    }
}
