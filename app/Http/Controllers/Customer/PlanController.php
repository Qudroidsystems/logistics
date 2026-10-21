<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Modules\Payments\WalletService;
use App\Modules\Subscriptions\SubscriptionService;
use Illuminate\Http\Request;
use RuntimeException;

/** My account > Business plan: start, change, cancel or pay for a monthly plan. The fee comes from the wallet. */
class PlanController extends Controller
{
    public function __construct(private SubscriptionService $plans) {}

    public function show(Request $request, WalletService $wallet)
    {
        return view('account.plan', $this->plans->summary($request->user()->id) + [
            'balance' => $wallet->balance($request->user()->id), 'pagetitle' => 'Business plan',
            'salesEmail' => config('booking.sales_email'),
        ]);
    }

    private function run(callable $fn, string $ok)
    {
        try {
            $fn();
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('account.plan')->with('success', $ok);
    }

    public function subscribe(Request $request)
    {
        $d = $request->validate(['plan' => 'required|string|max:24']);

        return $this->run(fn () => $this->plans->subscribe($request->user()->id, $d['plan']), 'Your plan has started.');
    }

    public function change(Request $request)
    {
        $d = $request->validate(['plan' => 'required|string|max:24']);

        return $this->run(fn () => $this->plans->change($request->user()->id, $d['plan']), 'Done. The new plan starts at your next renewal.');
    }

    public function cancel(Request $request)
    {
        return $this->run(fn () => $this->plans->cancel($request->user()->id), 'Your plan will end at the end of this month.');
    }

    public function resume(Request $request)
    {
        return $this->run(fn () => $this->plans->resume($request->user()->id), 'Your plan will carry on.');
    }

    public function payNow(Request $request)
    {
        return $this->run(fn () => $this->plans->payNow($request->user()->id), 'Paid. Your new month has started.');
    }
}
