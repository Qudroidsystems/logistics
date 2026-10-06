<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\PayrollPeriod;
use App\Models\StaffPayProfile;
use App\Services\Messaging\MessagingService;
use App\Services\Messaging\PortalNotifier;
use App\Services\Payroll\PayrollEngine;
use App\Services\Payroll\PayrollHistoryService;
use App\Services\Payroll\PayslipService;
use App\Support\PayrollDocs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * One payroll month: staff list, warnings, what changed since last month,
 * approve/lock, registers, bank schedule, payslips and sending them.
 */
class PayrollMonthController extends Controller
{
    public function __construct(protected PayrollEngine $engine, protected PayrollHistoryService $history, protected PayslipService $payslips)
    {
        $this->middleware('permission:View payroll')->only(['show', 'register', 'payslip', 'payslipPdf']);
        $this->middleware('permission:Process payroll')->only(['calculate']);
        $this->middleware('permission:Approve payroll')->only(['approve', 'lock', 'bankSchedule', 'send']);
        $this->middleware('permission:Manage payroll settings')->only(['employer', 'saveEmployer']);
    }

    public function show(Request $request, PayrollPeriod $period)
    {
        $runs = DB::table('payroll_runs as r')->join('staffbioinfo as s', 's.id', '=', 'r.staff_id')->leftJoin('users as u', 'u.id', '=', 's.userid')
            ->where('r.payroll_period_id', $period->id)
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($w) => $w->where('u.name', 'like', '%' . $request->search . '%')->orWhere('s.employmentid', 'like', '%' . $request->search . '%')))
            ->orderBy('u.name')
            ->get(['r.*', 'u.name', 's.employmentid']);
        $runs->each(fn ($r) => $r->warn = json_decode($r->warnings ?? '[]', true) ?: []);
        if ($request->get('filter') === 'warnings') $runs = $runs->filter(fn ($r) => $r->warn)->values();

        $holds = StaffPayProfile::where('pay_status', 'hold')->pluck('staff_id')->all();

        return view('finance.payroll.month', [
            'pagetitle' => $period->period_name, 'period' => $period, 'runs' => $runs, 'holds' => $holds,
            'variance' => $this->history->variance($period->id),
            'warnCount' => DB::table('payroll_runs')->where('payroll_period_id', $period->id)->whereNotNull('warnings')->where('warnings', '!=', '[]')->count(),
            'users' => DB::table('users')->whereIn('id', array_filter([$period->processed_by, $period->approved_by, $period->locked_by]))->pluck('name', 'id'),
            'sent' => Schema::hasColumn('payroll_runs', 'payslip_sent_at') ? DB::table('payroll_runs')->where('payroll_period_id', $period->id)->whereNotNull('payslip_sent_at')->count() : 0,
        ]);
    }

    public function calculate(Request $request, PayrollPeriod $period)
    {
        try {
            $r = $this->engine->process($period, $request->user()->id);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
        $msg = "Calculated {$r['count']} staff.";
        if ($r['skipped']) $msg .= ' Skipped ' . count($r['skipped']) . ': ' . collect($r['skipped'])->take(5)->map(fn ($s) => ($s['name'] ?? '#' . $s['staff_id']) . ' (' . $s['reason'] . ')')->implode(', ') . (count($r['skipped']) > 5 ? '…' : '');
        return back()->with('success', $msg);
    }

    public function approve(Request $request, PayrollPeriod $period)
    {
        try { $this->engine->approve($period, (int) $request->user()->id); } catch (\Throwable $e) { return back()->with('error', $e->getMessage()); }
        return back()->with('success', 'Payroll approved. Staff can now see their payslips.');
    }

    public function lock(Request $request, PayrollPeriod $period)
    {
        try { $this->engine->lock($period, (int) $request->user()->id); } catch (\Throwable $e) { return back()->with('error', $e->getMessage()); }
        return back()->with('success', 'Payroll locked. Payslips now carry a verification QR code, and loan balances were updated.');
    }

    /** Payroll register (all staff, every payslip line as a column). */
    public function register(PayrollPeriod $period)
    {
        $runs = DB::table('payroll_runs as r')->join('staffbioinfo as s', 's.id', '=', 'r.staff_id')->leftJoin('users as u', 'u.id', '=', 's.userid')
            ->where('r.payroll_period_id', $period->id)->orderBy('u.name')->get(['r.*', 'u.name', 's.employmentid']);
        $lines = $this->history->lines($runs->pluck('id')->all());
        $cols = [];
        foreach ($lines as $group) foreach ($group as $l) $cols[$l->type . ':' . $l->code] = $l->label;
        uksort($cols, fn ($a, $b) => (['earning' => 0, 'deduction' => 1, 'employer' => 2][explode(':', $a)[0]] ?? 3) <=> (['earning' => 0, 'deduction' => 1, 'employer' => 2][explode(':', $b)[0]] ?? 3));

        $name = 'Payroll_Register_' . preg_replace('/[^A-Za-z0-9]+/', '_', $period->period_name) . '.csv';
        return response()->streamDownload(function () use ($runs, $lines, $cols) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, array_merge(['Staff ID', 'Name', 'TIN', 'Tax state', 'PFA', 'RSA PIN'], array_values($cols), ['Gross', 'Total deductions', 'Net pay', 'Cost to school', 'Warnings']));
            foreach ($runs as $r) {
                $vals = array_fill_keys(array_keys($cols), 0);
                foreach ($lines[$r->id] ?? [] as $l) $vals[$l->type . ':' . $l->code] += (float) $l->amount;
                fputcsv($out, array_merge([$r->employmentid, $r->name, $r->tin, $r->tax_state, $r->pfa_name, $r->rsa_pin], array_values($vals),
                    [$r->total_earnings, $r->total_deductions, $r->net_pay, $r->employer_cost ?? '', implode('; ', json_decode($r->warnings ?? '[]', true) ?: [])]));
            }
            fclose($out);
        }, $name, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Bank payment schedule (full account numbers) for manual bank upload. */
    public function bankSchedule(PayrollPeriod $period)
    {
        abort_unless(in_array($period->status, PayrollDocs::PUBLISHED, true), 422, 'Approve the payroll first.');
        $runs = DB::table('payroll_runs as r')->join('staffbioinfo as s', 's.id', '=', 'r.staff_id')->leftJoin('users as u', 'u.id', '=', 's.userid')
            ->where('r.payroll_period_id', $period->id)->where('r.net_pay', '>', 0)->orderBy('u.name')->get(['r.*', 'u.name', 's.employmentid']);
        $profiles = StaffPayProfile::whereIn('staff_id', $runs->pluck('staff_id'))->get()->keyBy('staff_id');
        Log::info('Bank schedule downloaded', ['period' => $period->id, 'user' => auth()->id()]);

        $name = 'Bank_Schedule_' . preg_replace('/[^A-Za-z0-9]+/', '_', $period->period_name) . '.csv';
        return response()->streamDownload(function () use ($runs, $profiles, $period) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['S/N', 'Staff ID', 'Name', 'Bank', 'Bank code', 'Account number', 'Account name', 'Verified', 'Amount', 'Narration', 'Status']);
            $i = 0; $total = 0;
            foreach ($runs as $r) {
                $p = $profiles[$r->staff_id] ?? null;
                $hold = $p && $p->pay_status === 'hold';
                fputcsv($out, [++$i, $r->employmentid, $r->name, $p->bank_name ?? '', $p->bank_code ?? '', $p ? "\t" . ($p->accountNumber() ?? '') : '', $p->account_name ?? '',
                    $p && $p->account_verified_at ? 'Yes' : 'No', number_format((float) $r->net_pay, 2, '.', ''), 'Salary ' . $period->period_name, $hold ? 'ON HOLD - do not pay' : '']);
                if (!$hold) $total += (float) $r->net_pay;
            }
            fputcsv($out, ['', '', 'TOTAL (excluding holds)', '', '', '', '', '', number_format($total, 2, '.', '')]);
            fclose($out);
        }, $name, ['Content-Type' => 'text/csv']);
    }

    public function payslip(int $run)
    {
        $r = $this->payslips->run($run);
        abort_unless($r, 404);
        return view('finance.payroll.payslip-view', $this->payslips->data($r) + ['pagetitle' => 'Payslip', 'admin' => true]);
    }

    public function payslipPdf(int $run)
    {
        $r = $this->payslips->run($run);
        abort_unless($r, 404);
        return response($this->payslips->pdf($r), 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="' . $this->payslips->filename($r) . '"']);
    }

    /** Portal notification to every staff member, plus the PDF by email where set up (password-protected). */
    public function send(Request $request, PayrollPeriod $period)
    {
        abort_unless(in_array($period->status, PayrollDocs::PUBLISHED, true), 422);
        $withEmail = $request->boolean('email');
        $ids = DB::table('payroll_runs')->where('payroll_period_id', $period->id)->pluck('id')->all();

        dispatch(function () use ($ids, $withEmail, $period) {
            $svc = app(PayslipService::class); $msg = app(MessagingService::class);
            foreach ($ids as $id) {
                try {
                    $run = $svc->run($id);
                    $staff = PayrollDocs::staff((int) $run->staff_id);
                    if (!$staff) continue;
                    PortalNotifier::toUsers([(int) $staff->userid], 'Payslip ready: ' . $period->period_name,
                        'Net pay ' . PayrollDocs::money($run->net_pay) . '. Open My Pay to view or download it.', route('my-pay.payslip', $id), 'payment', 'payslip:' . $id);
                    if ($withEmail && $msg->enabled('email') && filter_var($staff->email, FILTER_VALIDATE_EMAIL) && !str_contains($staff->email, '@parents.')) {
                        $pw = PayrollDocs::pdfPassword($staff);
                        $path = storage_path('app/tmp/' . uniqid('payslip_') . '.pdf');
                        @mkdir(dirname($path), 0775, true);
                        file_put_contents($path, $svc->pdf($run, $pw));
                        $msg->send('email', $staff->email, "Dear {$staff->name},\n\nYour payslip for {$period->period_name} is attached." . ($pw ? "\n\nThe PDF is protected. The password is the last 4 digits of your phone number on file." : '') . "\n\nYou can also view all your payslips under My Pay in the portal.",
                            ['name' => $staff->name, 'subject' => 'Payslip — ' . $period->period_name, 'attachment' => ['path' => $path, 'filename' => $svc->filename($run, $staff)]]);
                        @unlink($path);
                    }
                    if (Schema::hasColumn('payroll_runs', 'payslip_sent_at')) DB::table('payroll_runs')->where('id', $id)->update(['payslip_sent_at' => now()]);
                } catch (\Throwable $e) {
                    Log::warning('Payslip send failed', ['run' => $id, 'error' => $e->getMessage()]);
                }
            }
        })->afterResponse();

        return back()->with('success', 'Sending payslips to ' . count($ids) . ' staff in the background' . ($withEmail ? ' (portal + email)' : ' (portal)') . '.');
    }

    public function employer()
    {
        return view('finance.payroll.employer', ['pagetitle' => 'Employer Details', 'e' => PayrollDocs::employer()]);
    }

    public function saveEmployer(Request $request)
    {
        $d = $request->validate(['employer_name' => 'required|string|max:150', 'tin' => 'nullable|string|max:30', 'tax_office' => 'nullable|string|max:100',
            'pension_employer_code' => 'nullable|string|max:40', 'nhf_employer_code' => 'nullable|string|max:40',
            'signatory_name' => 'nullable|string|max:100', 'signatory_title' => 'nullable|string|max:100', 'payslip_password' => 'required|in:phone4,staffid,none',
            'due_paye_day' => 'required|integer|min:1|max:31', 'due_pension_working_days' => 'required|integer|min:1|max:31',
            'due_nhf_day' => 'required|integer|min:1|max:31', 'due_other_day' => 'required|integer|min:1|max:31']);
        PayrollDocs::saveEmployer($d, $request->user()->id);
        return back()->with('success', 'Employer details saved.');
    }
}
