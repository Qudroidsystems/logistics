<?php
// app/Http/Controllers/Finance/PayrollController.php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\SchoolInformation;
use App\Models\Staff;
use App\Models\StaffPayment;
use App\Models\StaffSalaryStructure;
use App\Services\Accounting\AccountingService;
use App\Services\Payroll\NigerianPayrollService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;

class PayrollController extends Controller
{
    protected $payrollService;
    protected $accountingService;

    public function __construct(
        NigerianPayrollService $payrollService,
        AccountingService $accountingService
    ) {
        $this->payrollService = $payrollService;
        $this->accountingService = $accountingService;

        $this->middleware('permission:View payroll', ['only' => ['periods', 'getPayrollRuns', 'showPayrollRun', 'summaryReport', 'salaryStructures', 'statutoryReport']]);
        $this->middleware('permission:Process payroll', ['only' => ['processPayroll']]);
        $this->middleware('permission:Approve payroll', ['only' => ['approvePayroll']]);
        $this->middleware('permission:View payslip', ['only' => ['showPayrollRun']]);
        $this->middleware('permission:Download payslip', ['only' => ['showPayrollRun']]);
        $this->middleware('permission:Manage salary structures', ['only' => ['storeSalaryStructure', 'updateSalaryStructure', 'destroySalaryStructure']]);
    }

    /**
     * Display payroll periods with DataTable AJAX.
     */
    public function periods(Request $request)
    {
        $pagetitle = 'Payroll Management';

        if ($request->ajax()) {
            $periods = PayrollPeriod::with(['processedBy', 'approvedBy'])
                ->orderBy('id', 'desc');

            return DataTables::of($periods)
                ->addIndexColumn()
                ->addColumn('period_info', function($period) {
                    return $period->period_name . ' (' . $period->start_date->format('M d') . ' - ' . $period->end_date->format('M d, Y') . ')';
                })
                ->addColumn('total_gross', function($period) {
                    return '₦' . number_format($period->total_gross_pay, 2);
                })
                ->addColumn('total_net', function($period) {
                    return '₦' . number_format($period->total_net_pay, 2);
                })
                ->addColumn('staff_count', function($period) {
                    return $period->payrollRuns()->count();
                })
                ->addColumn('status_badge', function($period) {
                    if ($period->status == 'draft') return '<span class="badge bg-secondary">Draft</span>';
                    if ($period->status == 'processing') return '<span class="badge bg-info">Processing</span>';
                    if ($period->status == 'approved') return '<span class="badge bg-primary">Approved</span>';
                    if ($period->status == 'paid') return '<span class="badge bg-success">Paid</span>';
                    if ($period->status == 'locked') return '<span class="badge bg-dark">Locked</span>';
                    return '<span class="badge bg-secondary">' . ucfirst($period->status) . '</span>';
                })
                ->addColumn('action', function($period) {
                    $buttons = '';

                    if ($period->status === 'draft') {
                        $buttons .= '<button class="btn btn-sm btn-primary process-payroll me-1" data-id="'.$period->id.'"><i class="ri-play-line"></i> Process</button>';
                    }
                    if ($period->status === 'processing') {
                        $buttons .= '<button class="btn btn-sm btn-success approve-payroll me-1" data-id="'.$period->id.'"><i class="ri-check-line"></i> Approve</button>';
                    }
                    if ($period->status === 'approved') {
                        $buttons .= '<button class="btn btn-sm btn-warning mark-paid me-1" data-id="'.$period->id.'"><i class="ri-bank-card-line"></i> Mark Paid</button>';
                    }
                    if ($period->status !== 'locked') {
                        $buttons .= '<button class="btn btn-sm btn-secondary lock-period me-1" data-id="'.$period->id.'"><i class="ri-lock-line"></i> Lock</button>';
                    }

                    $buttons .= '<a href="' . route('payroll.month.show', $period->id) . '" class="btn btn-sm btn-info"><i class="ri-eye-line"></i> Open month</a>';

                    return $buttons;
                })
                ->rawColumns(['status_badge', 'action'])
                ->make(true);
        }

        $currentPeriod = PayrollPeriod::where('status', 'draft')
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->first();

        return view('finance.payroll.periods', compact('pagetitle', 'currentPeriod'));
    }

    /**
     * Create new payroll period (AJAX).
     */
    public function createPeriod(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:2030',
            'payment_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $exists = PayrollPeriod::where('month', $request->month)
            ->where('year', $request->year)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Payroll period for ' . date('F Y', mktime(0, 0, 0, $request->month, 1, $request->year)) . ' already exists'
            ], 422);
        }

        $startDate = date('Y-m-d', strtotime("{$request->year}-{$request->month}-01"));
        $endDate = date('Y-m-t', strtotime($startDate));
        $periodName = date('F Y', strtotime($startDate));

        $period = PayrollPeriod::create([
            'period_name' => $periodName,
            'month' => $request->month,
            'year' => $request->year,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'payment_date' => $request->payment_date,
            'status' => 'draft',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Payroll period created successfully!',
            'data' => $period
        ]);
    }

    /**
     * Process payroll for a period (AJAX).
     */
    public function processPayroll($periodId)
    {
        try {
            $result = $this->payrollService->processPayroll($periodId);

            return response()->json([
                'success' => true,
                'message' => 'Payroll processed successfully!',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            Log::error('Payroll processing error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to process payroll: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Approve payroll and create accounting entries (AJAX).
     */
    public function approvePayroll($periodId)
    {
        try {
            $result = $this->payrollService->approvePayroll($periodId);

            return response()->json([
                'success' => true,
                'message' => 'Payroll approved successfully!',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            Log::error('Payroll approval error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve payroll: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lock payroll period (AJAX).
     */
    public function lockPeriod($periodId)
    {
        try {
            $this->payrollService->lockPayroll($periodId);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Payroll period locked successfully!'
        ]);
    }

    /**
     * Get payroll runs for a period with DataTable AJAX.
     */
    public function getPayrollRuns(Request $request, $periodId)
    {
        $period = PayrollPeriod::findOrFail($periodId);
        $pagetitle = 'Payroll Details - ' . $period->period_name;

        if ($request->ajax()) {
            $runs = PayrollRun::where('payroll_period_id', $periodId)
                ->with(['staff.user', 'salaryStructure'])
                ->orderBy('staff_id');

            return DataTables::of($runs)
                ->addIndexColumn()
                ->addColumn('staff_name', function($run) {
                    return $run->staff->user->name ?? 'N/A';
                })
                ->addColumn('staff_id', function($run) {
                    return $run->staff->employmentid ?? 'N/A';
                })
                ->addColumn('gross_pay', function($run) {
                    return '₦' . number_format($run->total_earnings, 2);
                })
                ->addColumn('deductions', function($run) {
                    return '₦' . number_format($run->total_deductions, 2);
                })
                ->addColumn('net_pay', function($run) {
                    return '₦' . number_format($run->net_pay, 2);
                })
                ->addColumn('status_badge', function($run) {
                    if ($run->payment_status === 'paid') {
                        return '<span class="badge bg-success">Paid</span>';
                    }
                    return '<span class="badge bg-warning">Pending</span>';
                })
                ->addColumn('action', function($run) use ($period) {
                    $buttons = '<a href="' . route('payroll.run.show', $run->id) . '" class="btn btn-sm btn-info"><i class="ri-eye-line"></i> View</a>';

                    if ($period->status === 'approved' && $run->payment_status !== 'paid') {
                        $buttons .= '<button class="btn btn-sm btn-success mark-staff-paid ms-1" data-id="'.$run->id.'"><i class="ri-bank-card-line"></i> Mark Paid</button>';
                    }

                    return $buttons;
                })
                ->rawColumns(['status_badge', 'action'])
                ->make(true);
        }

        $summary = [
            'total_staff' => PayrollRun::where('payroll_period_id', $periodId)->count(),
            'total_gross' => $period->total_gross_pay,
            'total_deductions' => $period->total_gross_pay - $period->total_net_pay,
            'total_net' => $period->total_net_pay,
            'total_tax' => $period->total_tax,
            'total_pension' => $period->total_employee_pension,
        ];

        return view('finance.payroll.runs', compact('pagetitle', 'period', 'summary'));
    }

    /**
     * Show individual payroll run (payslip view).
     */
    public function showPayrollRun($payrollRunId)
    {
        $payrollRun = PayrollRun::with(['staff.user', 'payrollPeriod', 'salaryStructure'])
            ->findOrFail($payrollRunId);

        $user = Auth::user();
        $staff = $user->staff;

        if (!$staff || ($staff->id != $payrollRun->staff_id && !$user->hasPermissionTo('View payroll'))) {
            abort(403);
        }

        $earnings = array_filter([
            ['name' => 'Basic Salary', 'amount' => $payrollRun->basic_salary],
            ['name' => 'Housing Allowance', 'amount' => $payrollRun->housing_allowance],
            ['name' => 'Transport Allowance', 'amount' => $payrollRun->transport_allowance],
            ['name' => 'Meal Allowance', 'amount' => $payrollRun->meal_allowance],
            ['name' => 'Medical Allowance', 'amount' => $payrollRun->medical_allowance],
            ['name' => 'Utility Allowance', 'amount' => $payrollRun->utility_allowance],
            ['name' => 'Other Allowances', 'amount' => $payrollRun->other_allowances],
        ], function($item) {
            return $item['amount'] > 0;
        });

        $customAllowances = $payrollRun->custom_allowances ?? [];
        foreach ($customAllowances as $allowance) {
            if (($allowance['amount'] ?? 0) > 0) {
                $earnings[] = ['name' => $allowance['name'], 'amount' => $allowance['amount']];
            }
        }

        $deductions = array_filter([
            ['name' => 'PAYE Tax', 'amount' => $payrollRun->paye_tax],
            ['name' => 'Pension (Employee)', 'amount' => $payrollRun->employee_pension],
            ['name' => 'NHF', 'amount' => $payrollRun->nhf],
            ['name' => 'Loan Repayment', 'amount' => $payrollRun->loan_repayment],
            ['name' => 'Salary Advance', 'amount' => $payrollRun->advance_repayment],
        ], function($item) {
            return $item['amount'] > 0;
        });

        $employerContributions = [
            ['name' => 'Pension (Employer)', 'amount' => $payrollRun->employer_pension],
            ['name' => 'NSITF', 'amount' => $payrollRun->nsitf],
        ];

        $schoolInfo = SchoolInformation::first();
        $pagetitle = 'Payslip - ' . ($payrollRun->staff->user->name ?? 'Staff');

        if (request()->has('download')) {
            $pdf = PDF::loadView('finance.payroll.payslip-pdf', compact(
                'payrollRun', 'earnings', 'deductions', 'employerContributions', 'schoolInfo'
            ));
            return $pdf->download('payslip_' . ($payrollRun->staff->employmentid ?? 'staff') . '_' . $payrollRun->payrollPeriod->period_name . '.pdf');
        }

        return view('finance.payroll.payslip', compact(
            'pagetitle', 'payrollRun', 'earnings', 'deductions', 'employerContributions', 'schoolInfo'
        ));
    }

    /**
     * Process staff payment (AJAX).
     */
    public function processStaffPayment(Request $request, $payrollRunId)
    {
        $validator = Validator::make($request->all(), [
            'payment_date' => 'required|date',
            'payment_method' => 'required|in:bank_transfer,cash,cheque',
            'bank_name' => 'required_if:payment_method,bank_transfer',
            'account_number' => 'required_if:payment_method,bank_transfer',
            'transaction_ref' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $payrollRun = PayrollRun::findOrFail($payrollRunId);

            if ($payrollRun->payment_status === 'paid') {
                return response()->json([
                    'success' => false,
                    'message' => 'This payroll has already been paid'
                ], 400);
            }

            $payment = $this->payrollService->recordStaffPayment(
                $payrollRun->staff_id,
                $payrollRunId,
                $request->all()
            );

            return response()->json([
                'success' => true,
                'message' => 'Staff payment recorded successfully!',
                'data' => $payment
            ]);
        } catch (\Exception $e) {
            Log::error('Staff payment error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to process payment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Summary report with AJAX.
     */
    public function summaryReport(Request $request)
    {
        $pagetitle = 'Payroll Summary Report';

        if ($request->ajax()) {
            $year = $request->input('year', date('Y'));
            $getStats = $request->input('stats', false);

            $data = PayrollPeriod::where('year', $year)
                ->where('status', 'paid')
                ->select(
                    'month',
                    'period_name',
                    'total_gross_pay',
                    'total_tax',
                    'total_employee_pension',
                    'total_employer_pension',
                    'total_nhf',
                    'total_net_pay'
                )
                ->orderBy('month')
                ->get();

            if ($getStats) {
                $stats = [
                    'total_gross' => $data->sum('total_gross_pay'),
                    'total_tax' => $data->sum('total_tax'),
                    'total_pension' => $data->sum('total_employee_pension') + $data->sum('total_employer_pension'),
                    'total_net' => $data->sum('total_net_pay'),
                ];

                $statutoryData = [
                    $data->sum('total_tax'),
                    $data->sum('total_employee_pension'),
                    $data->sum('total_nhf'),
                ];

                $trendData = $data->pluck('total_net_pay')->toArray();

                return response()->json([
                    'success' => true,
                    'stats' => $stats,
                    'statutory_data' => $statutoryData,
                    'trend_data' => $trendData,
                ]);
            }

            return DataTables::of($data)
                ->addColumn('month', function($row) {
                    return date('F', mktime(0, 0, 0, $row->month, 1));
                })
                ->addColumn('gross_pay', function($row) {
                    return '₦' . number_format($row->total_gross_pay, 2);
                })
                ->addColumn('paye', function($row) {
                    return '₦' . number_format($row->total_tax, 2);
                })
                ->addColumn('pension', function($row) {
                    return '₦' . number_format($row->total_employee_pension, 2);
                })
                ->addColumn('nhf', function($row) {
                    return '₦' . number_format($row->total_nhf, 2);
                })
                ->addColumn('net_pay', function($row) {
                    return '₦' . number_format($row->total_net_pay, 2);
                })
                ->addColumn('employer_cost', function($row) {
                    return '₦' . number_format($row->total_employer_pension, 2);
                })
                ->rawColumns([])
                ->make(true);
        }

        $years = PayrollPeriod::select('year')->distinct()->orderBy('year', 'desc')->pluck('year');
        $currentYear = date('Y');

        return view('finance.payroll.summary', compact('pagetitle', 'years', 'currentYear'));
    }

    /**
     * Statutory remittance report.
     */
    public function statutoryReport(Request $request)
    {
        $pagetitle = 'Statutory Remittance Report';

        $year = $request->input('year', date('Y'));

        $data = PayrollPeriod::where('year', $year)
            ->whereIn('status', ['approved', 'paid'])
            ->select(
                'month',
                'period_name',
                'total_tax as paye',
                'total_employee_pension as employee_pension',
                'total_employer_pension as employer_pension',
                'total_nhf as nhf',
                'total_net_pay'
            )
            ->orderBy('month')
            ->get();

        $totalPaye = $data->sum('paye');
        $totalEmployeePension = $data->sum('employee_pension');
        $totalEmployerPension = $data->sum('employer_pension');
        $totalNhf = $data->sum('nhf');

        $years = PayrollPeriod::select('year')->distinct()->orderBy('year', 'desc')->pluck('year');

        return view('finance.payroll.statutory', compact('pagetitle', 'data', 'year', 'years', 'totalPaye', 'totalEmployeePension', 'totalEmployerPension', 'totalNhf'));
    }

    /**
     * Get staff salary structures (AJAX).
     */
    public function salaryStructures(Request $request)
    {
        $pagetitle = 'Staff Salary Structures';

        if ($request->ajax()) {
            try {
                $structures = StaffSalaryStructure::with(['staff.user', 'createdBy'])
                    ->orderBy('id', 'desc')
                    ->get();

                $data = [];
                $i = 1;
                foreach ($structures as $structure) {
                    $data[] = [
                        'DT_RowIndex' => $i++,
                        'id' => $structure->id,
                        'staff_id' => $structure->staff_id,
                        'staff_name' => $structure->staff->user->name ?? 'N/A',
                        'staff_id_no' => $structure->staff->employmentid ?? 'N/A',
                        'basic_salary' => '₦' . number_format($structure->basic_salary, 2),
                        'total_earnings' => '₦' . number_format($structure->total_earnings, 2),
                        'effective_from' => $structure->effective_from->format('Y-m-d'),
                        'effective_to' => $structure->effective_to ? $structure->effective_to->format('Y-m-d') : null,
                        'effective_period' => $structure->effective_period,
                        'housing_allowance' => $structure->housing_allowance,
                        'transport_allowance' => $structure->transport_allowance,
                        'meal_allowance' => $structure->meal_allowance,
                        'medical_allowance' => $structure->medical_allowance,
                        'utility_allowance' => $structure->utility_allowance,
                        'other_allowances' => $structure->other_allowances,
                        'custom_allowances' => $structure->custom_allowances,
                        'is_active' => $structure->is_active ? '<span class="status-pill st-paid">Active</span>' : '<span class="status-pill st-muted">Ended</span>',
                        'action' => '<div class="text-end text-nowrap"><button class="action-btn btn-open view-structure" title="View" data-id="'.$structure->id.'" data-bs-toggle="modal" data-bs-target="#viewStructureModal"><i class="ri-eye-line"></i></button>
                                     <button class="action-btn btn-open edit-structure" title="Edit" data-id="'.$structure->id.'"><i class="ri-pencil-line"></i></button>
                                     <button class="action-btn btn-open delete-structure text-danger" title="Delete" data-id="'.$structure->id.'"><i class="ri-delete-bin-line"></i></button></div>',
                    ];
                }

                return response()->json([
                    'data' => $data,
                    'recordsTotal' => count($data),
                    'recordsFiltered' => count($data)
                ]);
            } catch (\Exception $e) {
                Log::error('Salary structures error: ' . $e->getMessage());
                return response()->json([
                    'data' => [],
                    'recordsTotal' => 0,
                    'recordsFiltered' => 0,
                    'error' => $e->getMessage()
                ]);
            }
        }

        $staff = Staff::with('user')->active()->get();
        return view('finance.payroll.structures', compact('pagetitle', 'staff'));
    }

    /**
     * Store salary structure (AJAX).
     */
    public function storeSalaryStructure(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'staff_id' => 'required|exists:staffbioinfo,id',
            'basic_salary' => 'required|numeric|min:0',
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after:effective_from',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Deactivate previous structures
            StaffSalaryStructure::where('staff_id', $request->staff_id)
                ->where('is_active', true)
                ->update(['is_active' => false, 'effective_to' => now()]);

            $customAllowances = [];
            if ($request->has('custom_allowances')) {
                foreach ($request->custom_allowances as $allowance) {
                    if (!empty($allowance['name']) && !empty($allowance['amount'])) {
                        $customAllowances[] = [
                            'name' => $allowance['name'],
                            'amount' => (float)$allowance['amount']
                        ];
                    }
                }
            }

            $structure = StaffSalaryStructure::create([
                'staff_id' => $request->staff_id,
                'basic_salary' => $request->basic_salary,
                'housing_allowance' => $request->housing_allowance ?? 0,
                'transport_allowance' => $request->transport_allowance ?? 0,
                'meal_allowance' => $request->meal_allowance ?? 0,
                'medical_allowance' => $request->medical_allowance ?? 0,
                'utility_allowance' => $request->utility_allowance ?? 0,
                'other_allowances' => $request->other_allowances ?? 0,
                'custom_allowances' => $customAllowances,
                'effective_from' => $request->effective_from,
                'effective_to' => $request->effective_to,
                'is_active' => true,
                'created_by' => Auth::id(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Salary structure created successfully!',
                'data' => $structure
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Store salary structure error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create salary structure: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Edit salary structure (AJAX).
     */
    public function editSalaryStructure($id)
    {
        try {
            $structure = StaffSalaryStructure::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $structure
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Structure not found'
            ], 404);
        }
    }

    /**
     * Update salary structure (AJAX).
     */
    public function updateSalaryStructure(Request $request, $id)
    {
        $structure = StaffSalaryStructure::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'basic_salary' => 'required|numeric|min:0',
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after:effective_from',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $customAllowances = [];
            if ($request->has('custom_allowances')) {
                foreach ($request->custom_allowances as $allowance) {
                    if (!empty($allowance['name']) && !empty($allowance['amount'])) {
                        $customAllowances[] = [
                            'name' => $allowance['name'],
                            'amount' => (float)$allowance['amount']
                        ];
                    }
                }
            }

            $structure->update([
                'basic_salary' => $request->basic_salary,
                'housing_allowance' => $request->housing_allowance ?? 0,
                'transport_allowance' => $request->transport_allowance ?? 0,
                'meal_allowance' => $request->meal_allowance ?? 0,
                'medical_allowance' => $request->medical_allowance ?? 0,
                'utility_allowance' => $request->utility_allowance ?? 0,
                'other_allowances' => $request->other_allowances ?? 0,
                'custom_allowances' => $customAllowances,
                'effective_from' => $request->effective_from,
                'effective_to' => $request->effective_to,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Salary structure updated successfully!',
                'data' => $structure
            ]);
        } catch (\Exception $e) {
            Log::error('Update salary structure error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update salary structure: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete salary structure (AJAX).
     */
    public function destroySalaryStructure($id)
    {
        $structure = StaffSalaryStructure::findOrFail($id);

        $isUsed = PayrollRun::where('salary_structure_id', $id)->exists();

        if ($isUsed) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete this salary structure because it has been used in payroll runs.'
            ], 400);
        }

        $structure->delete();

        return response()->json([
            'success' => true,
            'message' => 'Salary structure deleted successfully!'
        ]);
    }

    /**
 * Show salary structure details (AJAX).
 */
public function showSalaryStructure($id)
{
    try {
        $structure = StaffSalaryStructure::with(['staff.user'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $structure
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Structure not found'
        ], 404);
    }
}
}
