<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Marketplace\CancellationService;
use App\Modules\Payments\RefundService;
use App\Modules\Settlements\PayoutService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/** Staff-only. Routes are guarded by spatie permissions Approve withdrawal, Refund payment and Cancel delivery. */
class AdminFinanceController extends Controller
{
    public function payouts(Request $request)
    {
        $status = $request->query('status', 'requested');

        return response()->json(DB::table('payout_requests as p')->join('operators as o', 'o.id', '=', 'p.operator_id')
            ->join('bank_accounts as b', 'b.id', '=', 'p.bank_account_id')->where('p.status', $status)->orderBy('p.id')->limit(100)
            ->get(['p.id', 'p.public_id', 'o.display_name as operator', 'p.amount', 'p.status', 'b.bank_name', 'b.account_number_last4', 'b.account_name', 'p.created_at']));
    }

    public function approvePayout(Request $request, int $payout, PayoutService $svc)
    {
        return $this->run(fn () => $svc->approve($payout, $request->user()->id));
    }

    public function rejectPayout(Request $request, int $payout, PayoutService $svc)
    {
        $d = $request->validate(['reason' => 'required|string|max:250']);

        return $this->run(fn () => $svc->reject($payout, $request->user()->id, $d['reason']));
    }

    public function cancelShipment(Request $request, string $shipment, CancellationService $svc)
    {
        $d = $request->validate(['reason' => 'required|string|max:40']);
        $id = DB::table('shipments')->where('public_id', $shipment)->value('id');
        abort_unless($id, 404);

        return $this->run(fn () => $svc->cancelShipment((int) $id, 'staff', $request->user()->id, $d['reason']));
    }

    /** Sends wallet money back to the customer's card. */
    public function refundToCard(Request $request, string $order, RefundService $svc)
    {
        $d = $request->validate(['amount' => 'required|integer|min:1']);
        $id = DB::table('orders')->where('public_id', $order)->value('id');
        abort_unless($id, 404);

        return $this->run(fn () => ['refund_id' => $svc->toOriginalPayment((int) $id, (int) $d['amount'], $request->user()->id)]);
    }

    private function run(callable $fn)
    {
        try {
            $out = $fn();

            return response()->json($out ?: ['ok' => true]);
        } catch (\App\Modules\Payments\Ledger\InsufficientFunds) {
            return response()->json(['error' => 'insufficient_funds', 'message' => 'The customer wallet no longer holds this amount.'], 422);
        } catch (RuntimeException $e) {
            return response()->json(['error' => 'rejected', 'message' => $e->getMessage()], 422);
        }
    }
}
