<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Marketplace\CancellationService;
use App\Modules\Payments\RefundService;
use App\Modules\Settlements\PayoutService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * The platform owner's money actions in the back office: approve or reject payout requests from providers and
 * customers, cancel a delivery, and send a customer's refund back to their card.
 */
class PlatformFinanceController extends Controller
{
    public const PAYOUT_STATUSES = ['requested' => 'Waiting for approval', 'approved' => 'Approved', 'processing' => 'Sending', 'paid' => 'Paid', 'failed' => 'Failed', 'rejected' => 'Rejected'];

    public const CANCEL_REASONS = ['customer_request' => 'Customer asked', 'provider_unavailable' => 'Provider could not do it', 'fraud' => 'Suspected fraud', 'duplicate' => 'Duplicate booking', 'other' => 'Other'];

    public function payouts(Request $request)
    {
        $status = array_key_exists($request->query('status'), self::PAYOUT_STATUSES) ? $request->query('status') : 'requested';
        $rows = DB::table('payout_requests as p')->leftJoin('operators as o', 'o.id', '=', 'p.operator_id')
            ->leftJoin('bank_accounts as b', 'b.id', '=', 'p.bank_account_id')->leftJoin('users as u', 'u.id', '=', 'p.approved_by')
            ->where('p.status', $status)->orderBy($status === 'requested' ? 'p.id' : 'p.updated_at', $status === 'requested' ? 'asc' : 'desc')->limit(200)
            ->get(['p.id', 'p.public_id', 'p.amount', 'p.fee', 'p.status', 'p.failure_reason', 'p.created_at', 'p.updated_at', 'p.operator_id',
                'o.display_name as provider', 'o.public_id as provider_public_id', 'b.bank_name', 'b.account_number_last4', 'b.account_name', 'b.owner_type', 'u.name as approved_by_name']);
        $counts = DB::table('payout_requests')->selectRaw('status, COUNT(*) n, COALESCE(SUM(amount), 0) total')->groupBy('status')->get()->keyBy('status');

        return view('ops.payouts', ['rows' => $rows, 'status' => $status, 'statuses' => self::PAYOUT_STATUSES, 'counts' => $counts, 'pagetitle' => 'Payouts']);
    }

    public function approvePayout(Request $request, int $payout, PayoutService $svc)
    {
        return $this->run(fn () => $svc->approve($payout, $request->user()->id), 'Payout approved. It is being sent to the bank account.');
    }

    public function rejectPayout(Request $request, int $payout, PayoutService $svc)
    {
        $d = $request->validate(['reason' => 'required|string|max:250']);

        return $this->run(fn () => $svc->reject($payout, $request->user()->id, $d['reason']), 'Payout rejected. The money is back in their wallet.');
    }

    /** Staff cancel a delivery: the customer is refunded by the usual cancellation rules (staff cancellations carry no fee). */
    public function cancelOrder(Request $request, string $order, CancellationService $svc)
    {
        $d = $request->validate(['reason' => 'required|in:'.implode(',', array_keys(self::CANCEL_REASONS))]);
        $o = DB::table('orders')->where('public_id', $order)->first();
        abort_unless($o, 404);
        $shipment = DB::table('shipments')->where('order_id', $o->id)->orderBy('id')->value('id');

        return $this->run(function () use ($svc, $o, $shipment, $d, $request) {
            if ($shipment) {
                return $svc->cancelShipment((int) $shipment, 'staff', $request->user()->id, $d['reason']);
            }
            if ($o->agreement_id) {
                return $svc->cancelUnpaid((int) $o->agreement_id, (int) $o->customer_id);
            }
            throw new RuntimeException('Nothing to cancel.');
        }, 'Delivery cancelled. The customer has been refunded to their wallet.');
    }

    /** Send money from the customer's wallet back to the card or bank they paid with. */
    public function refundToCard(Request $request, string $order, RefundService $svc)
    {
        $d = $request->validate(['amount_naira' => 'required|numeric|min:1|max:100000000']);
        $id = DB::table('orders')->where('public_id', $order)->value('id');
        abort_unless($id, 404);

        return $this->run(fn () => $svc->toOriginalPayment((int) $id, (int) round($d['amount_naira'] * 100), $request->user()->id),
            'Refund sent to the customer\'s original payment method.');
    }

    private function run(callable $fn, string $ok)
    {
        try {
            $fn();
        } catch (\App\Modules\Payments\Ledger\InsufficientFunds) {
            return back()->with('error', 'The customer\'s wallet no longer holds that amount.');
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', $ok);
    }
}
