<?php

namespace App\Modules\Payments;

use App\Modules\Payments\Gateways\GatewayManager;
use App\Modules\Payments\Ledger\AccountResolver;
use App\Modules\Payments\Ledger\LedgerPoster;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Cancellations refund to the customer's wallet instantly. This sends wallet money back to the card or bank the
 * customer originally paid with, at their request and with staff approval.
 *
 *  request : customer wallet -> gateway_clearing (cash leaves the gateway), refund row 'processing'
 *  success : refund.processed webhook marks it completed
 *  failure : refund.failed webhook puts the money back in the wallet
 */
class RefundService
{
    public function __construct(private LedgerPoster $ledger, private AccountResolver $accounts, private GatewayManager $gateways)
    {
    }

    public function toOriginalPayment(int $orderId, int $amount, int $staffId): int
    {
        $order = DB::table('orders')->find($orderId);
        $intent = $order ? DB::table('payment_intents')->where('agreement_id', $order->agreement_id)->where('status', 'succeeded')->whereIn('gateway', ['paystack', 'stripe', 'monnify', 'opay'])->first() : null;
        if (! $intent) {
            throw new RuntimeException('No online payment found for this order.');
        }
        $gateway = $this->gateways->get($intent->gateway);
        if (! $gateway->supportsRefund()) {
            throw new RuntimeException($gateway->label().' payments are refunded from its own dashboard, or credit the customer wallet instead.');
        }
        $already = (int) DB::table('refunds')->where('payment_intent_id', $intent->id)->where('reason_code', 'refund_to_source')->whereIn('status', ['processing', 'completed'])->sum('amount');
        if ($amount <= 0 || $amount + $already > (int) $intent->amount) {
            throw new RuntimeException('Refund exceeds what was paid by card.');
        }

        $id = DB::transaction(function () use ($order, $intent, $amount, $staffId) {
            $id = DB::table('refunds')->insertGetId([
                'public_id' => (string) Str::ulid(), 'order_id' => $order->id, 'payment_intent_id' => $intent->id, 'amount' => $amount,
                'reason_code' => 'refund_to_source', 'initiated_by' => $staffId, 'approved_by' => $staffId, 'status' => 'processing',
                'created_at' => now(), 'updated_at' => now(),
            ]);
            // Throws InsufficientFunds if the customer has already spent the refunded money.
            $tx = $this->ledger->post("refund-source-{$id}", [
                'operator_id' => (int) $order->operator_id, 'kind' => 'refund', 'reference_type' => 'refund', 'reference_id' => $id,
                'description' => 'Refund to original payment method', 'posted_by' => $staffId,
            ], [
                ['account_id' => $this->accounts->wallet('customer', (int) $order->customer_id, 1), 'direction' => 'debit', 'amount' => $amount],
                ['account_id' => $this->accounts->platform('gateway_clearing'), 'direction' => 'credit', 'amount' => $amount],
            ]);
            DB::table('refunds')->where('id', $id)->update(['ledger_tx_id' => $tx['id']]);

            return $id;
        });

        try {
            $ref = $gateway->refund($intent->reference, $amount, json_decode((string) $intent->raw, true) ?: null);
            DB::table('refunds')->where('id', $id)->update(['gateway_ref' => $ref, 'updated_at' => now()]);
        } catch (RuntimeException $e) {
            $this->fail($id, $e->getMessage()); // rejected outright: nothing left the gateway
            throw $e;
        }

        return $id;
    }

    /** refund.processed */
    public function complete(string $gatewayRef): void
    {
        DB::table('refunds')->where('gateway_ref', $gatewayRef)->where('status', 'processing')->update(['status' => 'completed', 'updated_at' => now()]);
    }

    /** refund.failed, or a rejected request */
    public function fail(int|string $idOrRef, string $reason): void
    {
        DB::transaction(function () use ($idOrRef, $reason) {
            $q = DB::table('refunds')->lockForUpdate();
            $r = is_int($idOrRef) ? $q->where('id', $idOrRef)->first() : $q->where('gateway_ref', $idOrRef)->first();
            if (! $r || $r->status !== 'processing') {
                return;
            }
            $order = DB::table('orders')->find($r->order_id);
            $this->ledger->post("refund-source-failed-{$r->id}", [
                'operator_id' => (int) $order->operator_id, 'kind' => 'refund', 'reference_type' => 'refund', 'reference_id' => $r->id, 'description' => 'Card refund failed, returned to wallet',
            ], [
                ['account_id' => $this->accounts->platform('gateway_clearing'), 'direction' => 'debit', 'amount' => (int) $r->amount],
                ['account_id' => $this->accounts->wallet('customer', (int) $order->customer_id, 1), 'direction' => 'credit', 'amount' => (int) $r->amount],
            ]);
            DB::table('refunds')->where('id', $r->id)->update(['status' => 'failed', 'updated_at' => now()]);
        });
    }
}
