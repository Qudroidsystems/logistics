<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Services\Payroll\PayrollHistoryService;
use App\Services\Payroll\PayslipService;
use App\Support\PayrollDocs;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Staff self-service: payslips, tax history, annual tax certificate, pension
 * and deductions. The bursar can open the same pages for any staff member
 * with ?staff=ID (needs "View payroll").
 */
class MyPayController extends Controller
{
    public function __construct(protected PayrollHistoryService $history, protected PayslipService $payslips) {}

    protected function who(Request $request): object
    {
        $u = $request->user();
        if ($request->filled('staff') && $u->can('View payroll')) {
            $s = PayrollDocs::staff((int) $request->get('staff'));
        } else {
            $s = PayrollDocs::staffForUser($u->id);
        }
        abort_unless($s, 404, 'No staff record is linked to your account. Please contact the bursar.');
        $s->viewing_other = (int) $s->userid !== (int) $u->id;
        return $s;
    }

    protected function range(Request $request): array
    {
        if ($request->filled('from') && $request->filled('to')) {
            $from = Carbon::parse($request->get('from') . (strlen($request->get('from')) === 7 ? '-01' : ''))->startOfMonth();
            $to = Carbon::parse($request->get('to') . (strlen($request->get('to')) === 7 ? '-01' : ''))->endOfMonth();
            if ($to->lt($from)) [$from, $to] = [$to->copy()->startOfMonth(), $from->copy()->endOfMonth()];
            return [$from->toDateString(), $to->toDateString(), $from->format('M Y') . ' – ' . $to->format('M Y'), null];
        }
        $year = (int) ($request->get('year') ?: now()->year);
        return ["$year-01-01", "$year-12-31", (string) $year, $year];
    }

    protected function years(int $staffId): array
    {
        $ys = DB::table('payroll_runs as r')->join('payroll_periods as p', 'p.id', '=', 'r.payroll_period_id')->where('r.staff_id', $staffId)
            ->whereIn('p.status', PayrollDocs::PUBLISHED)->selectRaw('DISTINCT YEAR(p.start_date) as y')->orderByDesc('y')->pluck('y')->all();
        return $ys ?: [now()->year];
    }

    protected function base(object $s, string $title): array
    {
        return ['s' => $s, 'pagetitle' => $title, 'q' => $s->viewing_other ? ['staff' => $s->id] : [], 'years' => $this->years((int) $s->id)];
    }

    public function index(Request $request)
    {
        $s = $this->who($request);
        $runs = $this->history->runs((int) $s->id)->sortByDesc('start_date')->values();
        $year = (int) ($request->get('year') ?: ($runs->first() ? Carbon::parse($runs->first()->start_date)->year : now()->year));
        return view('finance.my-pay.index', $this->base($s, 'My Pay') + [
            'runs' => $runs, 'year' => $year, 'ytd' => $this->history->ytd((int) $s->id, $year),
            'profile' => DB::table('staff_pay_profiles')->where('staff_id', $s->id)->first(),
            'otherPayments' => \Illuminate\Support\Facades\Schema::hasTable('staff_payments')
                ? DB::table('staff_payments')->where('staff_id', $s->id)->whereNull('payroll_run_id')->whereNull('deleted_at')
                    ->orderByDesc('payment_date')->limit(20)->get(['payment_date', 'payment_type', 'amount', 'purpose', 'payment_status', 'payment_reference'])
                : collect(),
        ]);
    }

    protected function ownRun(Request $request, int $runId): array
    {
        $s = $this->who($request);
        $run = $this->payslips->run($runId);
        abort_unless($run && ((int) $run->staff_id === (int) $s->id || $request->user()->can('View payroll')), 404);
        abort_unless(in_array($run->period_status, PayrollDocs::PUBLISHED, true) || $request->user()->can('View payroll'), 404);
        return [$s, $run];
    }

    public function payslip(Request $request, int $run)
    {
        [$s, $r] = $this->ownRun($request, $run);
        return view('finance.payroll.payslip-view', $this->payslips->data($r) + ['pagetitle' => 'Payslip', 'admin' => false, 'q' => $s->viewing_other ? ['staff' => $s->id] : []]);
    }

    public function payslipPdf(Request $request, int $run)
    {
        [, $r] = $this->ownRun($request, $run);
        return response($this->payslips->pdf($r), 200, ['Content-Type' => 'application/pdf',
            'Content-Disposition' => ($request->boolean('download') ? 'attachment' : 'inline') . '; filename="' . $this->payslips->filename($r) . '"']);
    }

    public function tax(Request $request)
    {
        $s = $this->who($request);
        [$from, $to, $label, $year] = $this->range($request);
        $data = $this->history->tax((int) $s->id, $from, $to);
        if ($request->get('format') === 'csv') {
            return $this->csv('Tax_History_' . str_replace(' ', '_', $label), ['Month', 'Gross pay', 'Taxable pay', 'Pension', 'NHF', 'NHIA', 'Chargeable (annualised)', 'PAYE', 'Tax state', 'Rule'],
                $data['rows']->map(fn ($r) => [$r->month, $r->gross, $r->taxable, $r->pension, $r->nhf, $r->nhia, $r->chargeable_annual, $r->paye, $r->state, $r->rule])->all(),
                ['TOTAL', $data['totals']['gross'], $data['totals']['taxable'], $data['totals']['pension'], $data['totals']['nhf'], $data['totals']['nhia'], '', $data['totals']['paye']]);
        }
        if ($request->get('format') === 'pdf') {
            return $this->pdfResponse('finance.payroll.pdf.history', ['kind' => 'tax', 'title' => 'Tax (PAYE) History', 'label' => $label, 'data' => $data] + $this->docBase($s), 'Tax_History_' . $label);
        }
        return view('finance.my-pay.tax', $this->base($s, 'Tax History') + ['data' => $data, 'label' => $label, 'from' => $from, 'to' => $to, 'year' => $year]);
    }

    public function certificate(Request $request)
    {
        $s = $this->who($request);
        $year = (int) ($request->get('year') ?: now()->year - (now()->month < 2 ? 1 : 0));
        [$from, $to] = PayrollHistoryService::yearRange($year);
        $data = $this->history->tax((int) $s->id, $from, $to);
        abort_if($data['rows']->isEmpty(), 404, 'No payroll records for ' . $year . '.');

        // A stable code for this certificate so it can be checked (QR).
        $code = strtoupper(substr(hash_hmac('sha256', "taxcert|{$s->id}|{$year}|" . round($data['totals']['paye'], 2), config('app.key')), 0, 12));
        $url = rtrim(config('app.url'), '/') . "/verify/tax-certificate/{$s->id}/{$year}/{$code}";
        return $this->pdfResponse('finance.payroll.pdf.tax-certificate', ['year' => $year, 'data' => $data, 'code' => $code, 'qr' => PayrollDocs::qr($url), 'url' => $url,
            'pension' => $this->history->pension((int) $s->id, $from, $to)] + $this->docBase($s), "Tax_Certificate_{$year}");
    }

    public function pension(Request $request)
    {
        $s = $this->who($request);
        [$from, $to, $label, $year] = $this->range($request);
        $data = $this->history->pension((int) $s->id, $from, $to);
        if ($request->get('format') === 'csv') {
            return $this->csv('Pension_History_' . str_replace(' ', '_', $label), ['Month', 'Pensionable pay', 'Employee', 'Employer', 'Total', 'Running total', 'PFA', 'RSA PIN', 'Remitted'],
                $data['rows']->map(fn ($r) => [$r->month, $r->base, $r->employee, $r->employer, $r->total, $r->running, $r->pfa, $r->rsa, $r->remitted === null ? '' : ($r->remitted ? 'Yes ' . $r->remitted_on : 'No')])->all(),
                ['TOTAL', '', $data['totals']['employee'], $data['totals']['employer'], $data['totals']['total']]);
        }
        if ($request->get('format') === 'pdf') {
            return $this->pdfResponse('finance.payroll.pdf.history', ['kind' => 'pension', 'title' => 'Pension Contribution Statement', 'label' => $label, 'data' => $data] + $this->docBase($s), 'Pension_Statement_' . $label);
        }
        return view('finance.my-pay.pension', $this->base($s, 'Pension History') + ['data' => $data, 'label' => $label, 'from' => $from, 'to' => $to, 'year' => $year]);
    }

    public function deductions(Request $request)
    {
        $s = $this->who($request);
        [$from, $to, $label, $year] = $this->range($request);
        $data = $this->history->deductions((int) $s->id, $from, $to);
        if ($request->get('format') === 'csv') {
            $codes = $data['codes'];
            return $this->csv('Deductions_' . str_replace(' ', '_', $label), array_merge(['Month'], array_values($codes)),
                array_map(fn ($r) => array_merge([$r['month']], array_map(fn ($c) => $r[$c] ?? 0, array_keys($codes))), $data['rows']),
                array_merge(['TOTAL'], array_map(fn ($c) => $data['totals'][$c] ?? 0, array_keys($codes))));
        }
        return view('finance.my-pay.deductions', $this->base($s, 'Deductions History') + ['data' => $data, 'label' => $label, 'from' => $from, 'to' => $to, 'year' => $year]);
    }

    /** Statement of earnings for a range (for loans, visas, landlords). */
    public function statement(Request $request)
    {
        $s = $this->who($request);
        [$from, $to, $label] = $this->range($request);
        $runs = $this->history->runs((int) $s->id, $from, $to);
        return $this->pdfResponse('finance.payroll.pdf.history', ['kind' => 'earnings', 'title' => 'Statement of Earnings', 'label' => $label, 'data' => ['rows' => $runs]] + $this->docBase($s), 'Earnings_Statement_' . $label);
    }

    protected function docBase(object $s): array
    {
        return ['staff' => $s, 'school' => PayrollDocs::school(), 'employer' => PayrollDocs::employer(), 'logo' => PayrollDocs::logo(),
                'profile' => DB::table('staff_pay_profiles')->where('staff_id', $s->id)->first(), 'generated' => now()];
    }

    protected function pdfResponse(string $view, array $data, string $name)
    {
        $file = preg_replace('/[^A-Za-z0-9_-]+/', '_', $name . '_' . ($data['staff']->name ?? '')) . '.pdf';
        return response(PayrollDocs::pdf($view, $data), 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="' . $file . '"']);
    }

    protected function csv(string $name, array $head, array $rows, ?array $total = null)
    {
        return response()->streamDownload(function () use ($head, $rows, $total) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $head);
            foreach ($rows as $r) fputcsv($out, $r);
            if ($total) fputcsv($out, $total);
            fclose($out);
        }, preg_replace('/[^A-Za-z0-9_-]+/', '_', $name) . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
