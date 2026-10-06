<?php

namespace App\Http\Controllers;

use App\Services\Payroll\PayrollHistoryService;
use App\Support\PayrollDocs;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/** Public check for payslip / tax certificate QR codes. */
class PayslipVerifyController extends Controller
{
    public function payslip(string $code)
    {
        $run = DB::table('payroll_runs as r')->join('payroll_periods as p', 'p.id', '=', 'r.payroll_period_id')
            ->where('r.verify_code', strtoupper($code))->whereIn('p.status', ['locked', 'paid'])
            ->first(['r.*', 'p.period_name', 'p.start_date']);
        $staff = $run ? PayrollDocs::staff((int) $run->staff_id) : null;
        return view('finance.payroll.verify', [
            'ok' => (bool) $run, 'kind' => 'Payslip', 'school' => PayrollDocs::school(),
            'rows' => $run ? ['Staff' => $staff->name ?? '—', 'Staff ID' => $staff->employmentid ?? '—', 'Pay month' => $run->period_name,
                              'Gross pay' => PayrollDocs::money($run->total_earnings), 'PAYE' => PayrollDocs::money($run->paye_tax), 'Net pay' => PayrollDocs::money($run->net_pay)] : [],
        ]);
    }

    public function certificate(int $staff, int $year, string $code)
    {
        $data = app(PayrollHistoryService::class)->tax($staff, "$year-01-01", "$year-12-31");
        $expect = strtoupper(substr(hash_hmac('sha256', "taxcert|{$staff}|{$year}|" . round($data['totals']['paye'], 2), config('app.key')), 0, 12));
        $ok = $data['rows']->isNotEmpty() && hash_equals($expect, strtoupper($code));
        $s = $ok ? PayrollDocs::staff($staff) : null;
        return view('finance.payroll.verify', [
            'ok' => $ok, 'kind' => 'Annual tax certificate', 'school' => PayrollDocs::school(),
            'rows' => $ok ? ['Staff' => $s->name ?? '—', 'Year' => $year, 'Months' => $data['totals']['months'], 'Total pay' => PayrollDocs::money($data['totals']['gross']),
                             'PAYE deducted' => PayrollDocs::money($data['totals']['paye'])] : [],
        ]);
    }
}
