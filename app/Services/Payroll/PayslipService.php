<?php

namespace App\Services\Payroll;

use App\Support\PayrollDocs;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/** Everything a payslip (screen or PDF) needs, from the saved payslip lines. */
class PayslipService
{
    public function __construct(protected PayrollHistoryService $history) {}

    public function run(int $runId): ?object
    {
        return DB::table('payroll_runs as r')->join('payroll_periods as p', 'p.id', '=', 'r.payroll_period_id')->where('r.id', $runId)
            ->first(['r.*', 'p.period_name', 'p.start_date', 'p.end_date', 'p.payment_date', 'p.status as period_status', 'p.locked_at']);
    }

    public function data(object $run): array
    {
        $staff = PayrollDocs::staff((int) $run->staff_id);
        $lines = DB::table('payroll_run_lines')->where('payroll_run_id', $run->id)->orderBy('sort')->get();

        // Older runs (before payslip lines were saved): rebuild lines from the run columns.
        if ($lines->isEmpty()) {
            $map = ['basic_salary' => 'Basic salary', 'housing_allowance' => 'Housing allowance', 'transport_allowance' => 'Transport allowance', 'meal_allowance' => 'Meal allowance',
                    'medical_allowance' => 'Medical allowance', 'utility_allowance' => 'Utility allowance', 'other_allowances' => 'Other allowances', 'overtime_pay' => 'Overtime', 'bonus' => 'Bonus'];
            $l = [];
            foreach ($map as $col => $label) if ((float) ($run->$col ?? 0) > 0) $l[] = (object) ['label' => $label, 'amount' => (float) $run->$col, 'type' => 'earning', 'code' => strtoupper($col)];
            foreach (['paye_tax' => 'PAYE tax', 'employee_pension' => 'Pension (employee)', 'nhf' => 'NHF', 'loan_repayment' => 'Loan repayment', 'advance_repayment' => 'Salary advance', 'union_dues' => 'Union dues', 'cooperative_deductions' => 'Cooperative'] as $col => $label)
                if ((float) ($run->$col ?? 0) > 0) $l[] = (object) ['label' => $label, 'amount' => (float) $run->$col, 'type' => 'deduction', 'code' => strtoupper($col)];
            if ((float) $run->employer_pension > 0) $l[] = (object) ['label' => 'Pension (employer)', 'amount' => (float) $run->employer_pension, 'type' => 'employer', 'code' => 'PENSION_ER'];
            $lines = collect($l);
        }

        $year = Carbon::parse($run->start_date)->year;
        $ytdRuns = $this->history->runs((int) $run->staff_id, "$year-01-01", Carbon::parse($run->end_date)->toDateString());
        $profile = DB::table('staff_pay_profiles')->where('staff_id', $run->staff_id)->first();

        return [
            'run' => $run, 'staff' => $staff, 'school' => PayrollDocs::school(), 'employer' => PayrollDocs::employer(), 'logo' => PayrollDocs::logo(),
            'earnings' => $lines->where('type', 'earning')->values(), 'deductions' => $lines->where('type', 'deduction')->values(),
            'employerLines' => $lines->where('type', 'employer')->values(),
            'tax' => json_decode($run->tax_breakdown ?? 'null', true),
            'ytd' => ['gross' => $ytdRuns->sum('total_earnings'), 'paye' => $ytdRuns->sum('paye_tax'), 'pension' => $ytdRuns->sum('employee_pension'), 'net' => $ytdRuns->sum('net_pay')],
            'bank' => ['name' => $profile->bank_name ?? $run->bank_name, 'account' => ($profile->account_last4 ?? null) ? '******' . $profile->account_last4 : $run->account_number, 'holder' => $profile->account_name ?? $run->account_name],
            'verifyUrl' => $run->verify_code ? PayrollDocs::verifyUrl($run->verify_code) : null,
            'qr' => $run->verify_code ? PayrollDocs::qr(PayrollDocs::verifyUrl($run->verify_code)) : null,
            'final' => in_array($run->period_status, ['locked', 'paid'], true),
        ];
    }

    public function pdf(object $run, ?string $password = null): string
    {
        return PayrollDocs::pdf('finance.payroll.pdf.payslip', $this->data($run), $password);
    }

    public function filename(object $run, ?object $staff = null): string
    {
        $staff ??= PayrollDocs::staff((int) $run->staff_id);
        return 'Payslip_' . preg_replace('/[^A-Za-z0-9]+/', '_', ($staff->name ?? 'staff') . '_' . Carbon::parse($run->start_date)->format('M_Y')) . '.pdf';
    }
}
