<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Modules\Payments\WalletService;
use App\Modules\Subscriptions\SubscriptionService;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Business plans for the app:
 *   GET  /v1/customer/plan          plans on offer, the customer's plan and this month's deliveries
 *   POST /v1/customer/plan          {plan} start a plan (fee from the wallet)
 *   POST /v1/customer/plan/change   {plan} switch at the next renewal
 *   POST /v1/customer/plan/cancel | /resume | /pay
 */
class SubscriptionApiController extends Controller
{
    public function __construct(private SubscriptionService $plans) {}

    public function show(Request $request, WalletService $wallet)
    {
        $s = $this->plans->summary($request->user()->id);
        $s['uses'] = collect($s['uses'])->map(fn ($u) => ['kind' => $u->kind, 'discount' => (int) $u->discount, 'returned' => (bool) $u->returned, 'order' => $u->order_number, 'at' => $u->created_at])->all();

        return response()->json($s + ['wallet_balance' => $wallet->balance($request->user()->id)]);
    }

    private function run(Request $request, callable $fn, WalletService $wallet)
    {
        try {
            $fn();
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return $this->show($request, $wallet);
    }

    public function subscribe(Request $request, WalletService $wallet)
    {
        $d = $request->validate(['plan' => 'required|string|max:24']);

        return $this->run($request, fn () => $this->plans->subscribe($request->user()->id, $d['plan']), $wallet);
    }

    public function change(Request $request, WalletService $wallet)
    {
        $d = $request->validate(['plan' => 'required|string|max:24']);

        return $this->run($request, fn () => $this->plans->change($request->user()->id, $d['plan']), $wallet);
    }

    public function cancel(Request $request, WalletService $wallet)
    {
        return $this->run($request, fn () => $this->plans->cancel($request->user()->id), $wallet);
    }

    public function resume(Request $request, WalletService $wallet)
    {
        return $this->run($request, fn () => $this->plans->resume($request->user()->id), $wallet);
    }

    public function pay(Request $request, WalletService $wallet)
    {
        return $this->run($request, fn () => $this->plans->payNow($request->user()->id), $wallet);
    }
}
