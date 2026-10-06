<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\PayoutBatch;
use App\Models\PayoutItem;
use App\Models\PayrollPeriod;
use App\Models\StaffPayProfile;
use App\Services\Payment\PaystackTransfers;
use App\Services\Payroll\PayoutService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/** Paying staff: salary batches (Paystack Transfers or bank upload), loan disbursements, refunds. */
class PayoutController extends Controller
{
    public function __construct(protected PayoutService $svc)
    {
        $this->middleware('permission:View payroll|Release salary payments|Approve payroll')->only(['index', 'show']);
        $this->middleware('permission:Approve payroll|Release salary payments')->only(['create', 'prepare']);
        $this->middleware('permission:Release salary payments')->only(['release', 'refresh', 'otp', 'manual', 'retry', 'cancel', 'schedule']);
    }

    public function index(Request $request)
    {
        $batches = PayoutBatch::with(['period:id,period_name', 'preparer:id,name', 'releaser:id,name'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->latest()->paginate(20)->withQueryString();
        $gw = app(PaystackTransfers::class);
        $periods = PayrollPeriod::whereIn('status', ['approved', 'paid', 'locked'])->orderByDesc('start_date')->limit(12)->get();

        return view('finance.payroll.payouts', [
            'pagetitle' => 'Salary Payments', 'batches' => $batches, 'periods' => $periods,
            'gateway' => ['ready' => $gw->isReady(), 'mode' => $gw->mode(), 'problem' => $gw->problem(), 'balance' => $gw->isReady() ? $gw->balance() : null],
            'stats' => [
                'in_flight' => PayoutItem::whereIn('status', ['queued', 'otp'])->sum('amount'),
                'failed' => PayoutItem::whereIn('status', ['failed', 'reversed'])->whereHas('batch', fn ($b) => $b->where('status', '!=', 'cancelled'))->count(),
                'paid_month' => PayoutItem::whereIn('status', ['success', 'manual'])->where('paid_at', '>=', now()->startOfMonth())->sum('amount'),
                'drafts' => PayoutBatch::where('status', 'draft')->count(),
            ],
        ]);
    }

    public function create(Request $request, PayrollPeriod $period)
    {
        $provider = $request->get('provider') === 'manual' ? 'manual' : 'paystack';
        return view('finance.payroll.payout-create', [
            'pagetitle' => 'Pay staff · ' . $period->period_name, 'period' => $period, 'provider' => $provider,
            'r' => $this->svc->readiness($period, $provider), 'gw' => app(PaystackTransfers::class),
        ]);
    }

    public function prepare(Request $request, PayrollPeriod $period)
    {
        $data = $request->validate(['provider' => 'required|in:paystack,manual', 'runs' => 'nullable|array', 'runs.*' => 'integer']);
        try {
            $batch = $this->svc->prepareSalary($period, (int) auth()->id(), $data['provider'], $data['runs'] ?? null);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
        return redirect()->route('payroll.payouts.show', $batch)->with('success', 'Batch prepared. Another authorised person must now release it.');
    }

    public function show(PayoutBatch $batch)
    {
        $batch->load(['period', 'preparer:id,name', 'releaser:id,name']);
        $items = $batch->items()->orderByRaw("FIELD(status,'failed','reversed','otp','queued','pending','success','manual','skipped')")->get();
        $names = DB::table('staffbioinfo as s')->leftJoin('users as u', 'u.id', '=', 's.userid')->whereIn('s.id', $items->pluck('staff_id'))->pluck('u.name', 's.id');
        return view('finance.payroll.payout-show', ['pagetitle' => 'Payout ' . $batch->reference, 'batch' => $batch, 'items' => $items, 'names' => $names]);
    }

    public function release(Request $request, PayoutBatch $batch)
    {
        $request->validate(['password' => 'required|string']);
        if (!Hash::check($request->password, (string) auth()->user()->password)) {
            return back()->with('error', 'Your password was not correct. Nothing was sent.');
        }
        try {
            $r = $this->svc->release($batch, (int) auth()->id());
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
        return back()->with($r['failed'] ? 'error' : 'success', $r['message']);
    }

    public function refresh(PayoutBatch $batch)
    {
        $n = $this->svc->refresh($batch);
        return back()->with('success', $n ? "{$n} transfer(s) updated." : 'No changes yet — banks can take a few minutes.');
    }

    public function otp(Request $request, PayoutItem $item)
    {
        $request->validate(['otp' => 'required|digits_between:4,8']);
        $r = $this->svc->finalizeOtp($item, $request->otp);
        return back()->with($r['ok'] ? 'success' : 'error', $r['ok'] ? 'OTP accepted.' : $r['message']);
    }

    public function manual(Request $request, PayoutBatch $batch)
    {
        $data = $request->validate(['items' => 'required|array|min:1', 'items.*' => 'integer', 'reference' => 'required|string|max:60']);
        $n = $this->svc->markManual($batch, $data['items'], $data['reference'], (int) auth()->id());
        return back()->with('success', "{$n} transfer(s) marked as paid.");
    }

    public function retry(PayoutBatch $batch)
    {
        try {
            $new = $this->svc->retryFailed($batch, (int) auth()->id());
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
        return redirect()->route('payroll.payouts.show', $new)->with('success', 'Failed transfers moved into a new batch. Fix the bank details if needed, then release it.');
    }

    public function cancel(PayoutBatch $batch)
    {
        try { $this->svc->cancel($batch); } catch (\Throwable $e) { return back()->with('error', $e->getMessage()); }
        return redirect()->route('payroll.payouts')->with('success', 'Batch cancelled.');
    }

    /** Bank upload file for a manual batch (full account numbers — restricted and logged). */
    public function schedule(PayoutBatch $batch)
    {
        $items = $batch->items()->whereNotIn('status', ['skipped'])->get();
        $profiles = StaffPayProfile::whereIn('staff_id', $items->pluck('staff_id'))->get()->keyBy('staff_id');
        if (class_exists(\App\Services\Activity\ActivityLogger::class)) {
            try { \App\Services\Activity\ActivityLogger::log((int) auth()->id(), 'export', 'Downloaded payout bank schedule ' . $batch->reference, request()); } catch (\Throwable $e) {}
        }
        $name = 'payout-' . $batch->reference . '.csv';
        return response()->streamDownload(function () use ($items, $profiles, $batch) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['S/N', 'Account name', 'Account number', 'Bank', 'Bank code', 'Amount', 'Narration', 'Reference']);
            foreach ($items->values() as $i => $it) {
                $p = $profiles[$it->staff_id] ?? null;
                fputcsv($out, [$i + 1, $it->account_name, $p?->accountNumber() ?? '', $it->bank_name, $p?->bank_code, number_format($it->amount, 2, '.', ''),
                               $batch->note, $it->reference]);
            }
            fclose($out);
        }, $name, ['Content-Type' => 'text/csv']);
    }
}
