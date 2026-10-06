<?php
// app/Services/Payroll/NigerianPayrollService.php

namespace App\Services\Payroll;

use App\Models\StaffSalaryStructure;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\StaffPayment;
use App\Services\Accounting\AccountingService;
use Illuminate\Support\Facades\DB;


class NigerianPayrollService
{
    protected $accountingService;

    // Nigerian Tax Brackets (FIRS)
    protected $taxBrackets = [
        ['up_to' => 300000, 'rate' => 0.07],
        ['up_to' => 600000, 'rate' => 0.11],
        ['up_to' => 1100000, 'rate' => 0.15],
        ['up_to' => 1600000, 'rate' => 0.19],
        ['up_to' => 3200000, 'rate' => 0.21],
        ['up_to' => PHP_INT_MAX, 'rate' => 0.24],
    ];

    const CRA_MINIMUM = 200000;
    const CRA_PERCENTAGE = 0.20;
    const EMPLOYEE_PENSION_RATE = 0.08;
    const EMPLOYER_PENSION_RATE = 0.10;
    const NHF_RATE = 0.025;
    const NSITF_RATE = 0.01;

    public function __construct(AccountingService $accountingService)
    {
        $this->accountingService = $accountingService;
    }

    /**
     * Monthly PAYE on a monthly gross (Nigeria Tax Act 2025 rules, no reliefs
     * other than none given). Kept for older callers; payroll uses PayrollEngine.
     */
    public function calculatePAYE($monthlyGrossPay)
    {
        $rates = StatutoryRates::on(now());
        $r = PayrollCalculator::compute([['code' => 'GROSS', 'label' => 'Gross', 'amount' => (float) $monthlyGrossPay, 'taxable' => true]],
            ['paye' => true], $rates);
        return $r['paye'];
    }

    /**
     * Calculate employee pension
     */
    public function calculateEmployeePension($grossPay)
    {
        return round($grossPay * self::EMPLOYEE_PENSION_RATE, 2);
    }

    /**
     * Calculate employer pension
     */
    public function calculateEmployerPension($grossPay)
    {
        return round($grossPay * self::EMPLOYER_PENSION_RATE, 2);
    }

    /**
     * Calculate NHF
     */
    public function calculateNHF($basicSalary)
    {
        return round($basicSalary * self::NHF_RATE, 2);
    }

    /**
     * Calculate NSITF
     */
    public function calculateNSITF($grossPay)
    {
        return round($grossPay * self::NSITF_RATE, 2);
    }

    /**
     * Process payroll for a period (new engine: staffbioinfo + pay profiles,
     * NTA 2025 PAYE, payslip lines saved).
     */
    public function processPayroll($periodId)
    {
        $period = PayrollPeriod::findOrFail($periodId);
        $result = app(PayrollEngine::class)->process($period, auth()->id());
        return $period->fresh()->setAttribute('result', $result);
    }

    /**
     * Approve payroll
     */
    public function approvePayroll($periodId)
    {
        $period = PayrollPeriod::findOrFail($periodId);
        app(PayrollEngine::class)->approve($period, (int) auth()->id());
        return $period->fresh();
    }

    public function lockPayroll($periodId)
    {
        $period = PayrollPeriod::findOrFail($periodId);
        app(PayrollEngine::class)->lock($period, (int) auth()->id());
        return $period->fresh();
    }

    /**
     * Record staff payment
     */
    public function recordStaffPayment($staffId, $payrollRunId, $paymentData)
    {
        $payrollRun = PayrollRun::findOrFail($payrollRunId);

        if ($payrollRun->payment_status === 'paid') {
            throw new \Exception('This payroll has already been paid');
        }

        $payment = StaffPayment::create([
            'staff_id' => $staffId,
            'payroll_run_id' => $payrollRunId,
            'payment_reference' => $this->generatePaymentReference(),
            'payment_type' => 'salary',
            'amount' => $payrollRun->net_pay,
            'payment_date' => $paymentData['payment_date'],
            'payment_method' => $paymentData['payment_method'],
            'bank_name' => $paymentData['bank_name'] ?? null,
            'account_number' => $paymentData['account_number'] ?? null,
            'transaction_ref' => $paymentData['transaction_ref'] ?? null,
            'purpose' => "Salary payment",
            'payment_status' => 'paid',
            'created_by' => auth()->id(),
        ]);

        $payrollRun->update([
            'payment_status' => 'paid',
            'paid_at' => now(),
            'transaction_reference' => $payment->payment_reference,
        ]);

        return $payment;
    }

    /**
     * Generate payment reference
     */
    private function generatePaymentReference()
    {
        return 'PAY-' . date('Ymd') . '-' . strtoupper(uniqid());
    }

    /**
     * Get active loan deductions
     */
    public function getActiveLoanDeductions($staffId)
    {
        return [
            'total_deduction' => 0,
            'loans' => []
        ];
    }
}

