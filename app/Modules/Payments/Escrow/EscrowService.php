<?php

namespace App\Modules\Payments\Escrow;

use App\Modules\Payments\DriverPayService;
use App\Modules\Payments\Ledger\AccountResolver;
use App\Modules\Payments\Ledger\LedgerPoster;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

/**
 * Holds a customer's money against an agreement and decides where it goes.
 *
 * Escrow amount = price + goods_budget + tip. Money leaves escrow only through the methods below,
 * each of which posts one balanced ledger transaction and updates escrow_holds in the same DB transaction.
 */
class EscrowService
{
    public function __construct(
        private LedgerPoster $ledger,
        private AccountResolver $accounts,
    ) {
    }

    /**
     * Customer pays: move the money from the payer's source account into the agreement's escrow.
     *
     * @param  'wallet'|'gateway'  $source  wallet = customer wallet balance; gateway = card/transfer just confirmed by a webhook
     */
    public function hold(int $agreementId, string $source = 'gateway', ?int $postedBy = null): int
    {
        return DB::transaction(function () use ($agreementId, $source, $postedBy) {
            $agreement = $this->lockAgreement($agreementId);

            if (DB::table('escrow_holds')->where('agreement_id', $agreementId)->exists()) {
                return (int) DB::table('escrow_holds')->where('agreement_id', $agreementId)->value('id');
            }

            $total = $this->escrowTotal($agreement);
            $escrowAccount = $this->accounts->for('agreement', $agreementId, 'order_escrow', (int) $agreement->provider_operator_id);

            $from = $source === 'wallet'
                ? $this->accounts->wallet('customer', (int) $agreement->customer_id, 1)
                : $this->accounts->platform('gateway_clearing');

            // Gateway payment: debit gateway_clearing (an asset: money the gateway owes us) and credit escrow.
            // Wallet payment: debit the customer's wallet (a liability that shrinks) and credit escrow.
            $this->ledger->post("escrow-hold-{$agreementId}", [
                'operator_id' => (int) $agreement->provider_operator_id,
                'kind' => 'escrow_hold',
                'reference_type' => 'agreement',
                'reference_id' => $agreementId,
                'description' => "Escrow for agreement {$agreement->number}",
                'posted_by' => $postedBy,
                'is_test' => (bool) $agreement->is_test,
            ], [
                ['account_id' => $from, 'direction' => 'debit', 'amount' => $total],
                ['account_id' => $escrowAccount, 'direction' => 'credit', 'amount' => $total],
            ]);

            $holdId = DB::table('escrow_holds')->insertGetId([
                'agreement_id' => $agreementId,
                'ledger_account_id' => $escrowAccount,
                'amount' => $total,
                'status' => 'held',
                'release_after' => null,
                'is_test' => (bool) $agreement->is_test,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('agreements')->where('id', $agreementId)->update(['status' => 'paid', 'updated_at' => now()]);

            return $holdId;
        });
    }

    /** Start the confirmation clock once the shipment is delivered. */
    public function startConfirmationWindow(int $agreementId): void
    {
        $agreement = DB::table('agreements')->find($agreementId);
        DB::table('escrow_holds')->where('agreement_id', $agreementId)->update([
            'release_after' => now()->addHours((int) $agreement->confirmation_window_hours),
            'updated_at' => now(),
        ]);
    }

    /**
     * Release to the provider: provider net + tip to the provider's wallet, the platform fee to the
     * commission account, and any unspent goods budget back to the customer.
     */
    public function release(int $agreementId, int $goodsSpent = 0, ?int $postedBy = null): array
    {
        return DB::transaction(function () use ($agreementId, $goodsSpent, $postedBy) {
            $agreement = $this->lockAgreement($agreementId);
            $hold = DB::table('escrow_holds')->where('agreement_id', $agreementId)->lockForUpdate()->first();

            if (! $hold || ! in_array($hold->status, ['held', 'partially_released'], true)) {
                throw new RuntimeException('Escrow is not available for release.');
            }
            if ($goodsSpent < 0 || $goodsSpent > $agreement->goods_budget) {
                throw new InvalidArgumentException('Goods spent must be between zero and the goods budget.');
            }
            if ($agreement->platform_fee + $agreement->provider_net !== (int) $agreement->price) {
                throw new RuntimeException('Agreement fee and provider net do not add up to the price.');
            }

            $remaining = (int) $hold->amount - (int) $hold->released_amount - (int) $hold->refunded_amount;
            $unspent = (int) $agreement->goods_budget - $goodsSpent;
            $fee = (int) $agreement->platform_fee;
            // A shopper may already hold part of the goods money as an advance. What they spent is theirs to keep;
            // what they did not spend comes back out of their wallet. Without an advance this is simply
            // provider_net + tip + goodsSpent.
            $advance = (int) DB::table('shopper_advances')->where('agreement_id', $agreementId)->where('status', 'issued')->sum('amount');
            $net = (int) $agreement->provider_net + (int) $agreement->tip + $goodsSpent - $advance;

            if ($net + $fee + $unspent !== $remaining) {
                throw new RuntimeException('Escrow balance does not match the amounts being released.');
            }

            $providerWallet = $this->accounts->wallet('operator', (int) $agreement->provider_operator_id, (int) $agreement->provider_operator_id);
            $commission = $this->accounts->platform('platform_commission');
            $customerWallet = $this->accounts->wallet('customer', (int) $agreement->customer_id, 1);

            $entries = [['account_id' => (int) $hold->ledger_account_id, 'direction' => 'debit', 'amount' => $remaining]];
            if ($net > 0) {
                $entries[] = ['account_id' => $providerWallet, 'direction' => 'credit', 'amount' => $net];
            } elseif ($net < 0) {
                // The shopper returns unspent advance beyond what they earned; fails if they already withdrew it.
                $entries[] = ['account_id' => $providerWallet, 'direction' => 'debit', 'amount' => -$net];
            }
            if ($fee > 0) {
                $entries[] = ['account_id' => $commission, 'direction' => 'credit', 'amount' => $fee];
            }
            if ($unspent > 0) {
                $entries[] = ['account_id' => $customerWallet, 'direction' => 'credit', 'amount' => $unspent];
            }
            $toProvider = $net;

            $tx = $this->ledger->post("escrow-release-{$agreementId}", [
                'operator_id' => (int) $agreement->provider_operator_id,
                'kind' => 'escrow_release',
                'reference_type' => 'agreement',
                'reference_id' => $agreementId,
                'description' => "Release for agreement {$agreement->number}",
                'posted_by' => $postedBy,
                'is_test' => (bool) $agreement->is_test,
            ], $entries);

            $this->recordCommission($agreement, $fee, (int) $agreement->provider_net, $tx['id']);
            DB::table('shopper_advances')->where('agreement_id', $agreementId)->where('status', 'issued')->update(['status' => 'settled', 'updated_at' => now()]);
            DB::table('escrow_holds')->where('id', $hold->id)->update([
                'released_amount' => (int) $hold->released_amount + max($net, 0) + $fee,
                'refunded_amount' => (int) $hold->refunded_amount + $unspent,
                'status' => 'released',
                'updated_at' => now(),
            ]);
            DB::table('agreements')->where('id', $agreementId)->update(['status' => 'completed', 'updated_at' => now()]);
            DB::table('shopping_requests')->where('agreement_id', $agreementId)->update(['actual_spend' => $goodsSpent, 'status' => 'completed', 'updated_at' => now()]);

            return ['transaction_id' => $tx['id'], 'to_provider' => $toProvider, 'platform_fee' => $fee, 'to_customer' => $unspent];
        });
    }

    /** Return money to the customer. Pass null to refund everything still held. */
    public function refund(int $agreementId, ?int $amount = null, ?int $postedBy = null): array
    {
        return DB::transaction(function () use ($agreementId, $amount, $postedBy) {
            $agreement = $this->lockAgreement($agreementId);
            $hold = DB::table('escrow_holds')->where('agreement_id', $agreementId)->lockForUpdate()->first();

            if (! $hold || $hold->status === 'released' || $hold->status === 'refunded') {
                throw new RuntimeException('Escrow has already been settled.');
            }

            $remaining = (int) $hold->amount - (int) $hold->released_amount - (int) $hold->refunded_amount;
            $amount ??= $remaining;
            if ($amount <= 0 || $amount > $remaining) {
                throw new InvalidArgumentException('Refund must be between 1 and the amount still held.');
            }

            $customerWallet = $this->accounts->wallet('customer', (int) $agreement->customer_id, 1);
            $tx = $this->ledger->post("escrow-refund-{$agreementId}-{$hold->refunded_amount}-{$amount}", [
                'operator_id' => (int) $agreement->provider_operator_id,
                'kind' => 'refund',
                'reference_type' => 'agreement',
                'reference_id' => $agreementId,
                'description' => "Refund for agreement {$agreement->number}",
                'posted_by' => $postedBy,
                'is_test' => (bool) $agreement->is_test,
            ], [
                ['account_id' => (int) $hold->ledger_account_id, 'direction' => 'debit', 'amount' => $amount],
                ['account_id' => $customerWallet, 'direction' => 'credit', 'amount' => $amount],
            ]);

            $refunded = (int) $hold->refunded_amount + $amount;
            $status = ($refunded + (int) $hold->released_amount) >= (int) $hold->amount ? 'refunded' : 'partially_released';
            DB::table('escrow_holds')->where('id', $hold->id)->update(['refunded_amount' => $refunded, 'status' => $status, 'updated_at' => now()]);

            if ($status === 'refunded') {
                DB::table('agreements')->where('id', $agreementId)->update(['status' => 'cancelled', 'updated_at' => now()]);
            }

            return ['transaction_id' => $tx['id'], 'refunded' => $amount];
        });
    }

    /**
     * Dispute outcome that splits the money: the provider receives $providerShare of the service price
     * (minus a proportional platform fee) plus any goods actually spent; everything else returns to the customer.
     * A zero share is a full refund; a full share is a normal release.
     */
    public function releasePartial(int $agreementId, int $providerShare, int $goodsSpent = 0, ?int $postedBy = null): array
    {
        return DB::transaction(function () use ($agreementId, $providerShare, $goodsSpent, $postedBy) {
            $agreement = $this->lockAgreement($agreementId);
            $hold = DB::table('escrow_holds')->where('agreement_id', $agreementId)->lockForUpdate()->first();
            if (! $hold || ! in_array($hold->status, ['held', 'partially_released'], true)) {
                throw new RuntimeException('Escrow is not available for settlement.');
            }
            $price = (int) $agreement->price;
            if ($providerShare < 0 || $providerShare > $price || $goodsSpent < 0 || $goodsSpent > (int) $agreement->goods_budget) {
                throw new InvalidArgumentException('Settlement amounts are out of range.');
            }

            $remaining = (int) $hold->amount - (int) $hold->released_amount - (int) $hold->refunded_amount;
            $fee = intdiv((int) $agreement->platform_fee * $providerShare, max($price, 1));
            $advance = (int) DB::table('shopper_advances')->where('agreement_id', $agreementId)->where('status', 'issued')->sum('amount');
            // What the provider ends up with in their wallet; negative when they must hand back unspent advance.
            $net = $providerShare - $fee + $goodsSpent - $advance;
            $toCustomer = $remaining - $net - $fee;
            if ($toCustomer < 0) {
                throw new RuntimeException('Settlement exceeds the escrow balance.');
            }
            $toProvider = $net;

            $entries = [['account_id' => (int) $hold->ledger_account_id, 'direction' => 'debit', 'amount' => $remaining]];
            $providerWallet = $this->accounts->wallet('operator', (int) $agreement->provider_operator_id, (int) $agreement->provider_operator_id);
            if ($net > 0) {
                $entries[] = ['account_id' => $providerWallet, 'direction' => 'credit', 'amount' => $net];
            } elseif ($net < 0) {
                $entries[] = ['account_id' => $providerWallet, 'direction' => 'debit', 'amount' => -$net];
            }
            if ($fee > 0) {
                $entries[] = ['account_id' => $this->accounts->platform('platform_commission'), 'direction' => 'credit', 'amount' => $fee];
            }
            if ($toCustomer > 0) {
                $entries[] = ['account_id' => $this->accounts->wallet('customer', (int) $agreement->customer_id, 1), 'direction' => 'credit', 'amount' => $toCustomer];
            }

            $tx = $this->ledger->post("escrow-split-{$agreementId}", [
                'operator_id' => (int) $agreement->provider_operator_id, 'kind' => 'escrow_release',
                'reference_type' => 'agreement', 'reference_id' => $agreementId,
                'description' => "Dispute settlement for agreement {$agreement->number}",
                'posted_by' => $postedBy, 'is_test' => (bool) $agreement->is_test,
            ], $entries);

            if ($providerShare > 0) {
                $this->recordCommission($agreement, $fee, $providerShare - $fee, $tx['id']);
            }
            DB::table('shopper_advances')->where('agreement_id', $agreementId)->where('status', 'issued')->update(['status' => 'settled', 'updated_at' => now()]);
            DB::table('escrow_holds')->where('id', $hold->id)->update([
                'released_amount' => (int) $hold->released_amount + max($net, 0) + $fee,
                'refunded_amount' => (int) $hold->refunded_amount + $toCustomer,
                'status' => $providerShare + $goodsSpent > 0 ? 'released' : 'refunded', 'updated_at' => now(),
            ]);
            DB::table('agreements')->where('id', $agreementId)->update(['status' => $providerShare + $goodsSpent > 0 ? 'completed' : 'cancelled', 'updated_at' => now()]);

            return ['transaction_id' => $tx['id'], 'to_provider' => $toProvider, 'platform_fee' => $fee, 'to_customer' => $toCustomer];
        });
    }

    /** One commissions row per shipment: the platform's fee and the provider's net, for settlement statements. */
    private function recordCommission(object $agreement, int $fee, int $providerNet, int $txId): void
    {
        $shipmentId = DB::table('shipments')->join('orders', 'orders.id', '=', 'shipments.order_id')
            ->where('orders.agreement_id', $agreement->id)->value('shipments.id');
        if (! $shipmentId) {
            return;
        }
        $added = DB::table('commissions')->insertOrIgnore([
            'shipment_id' => $shipmentId, 'agreement_id' => $agreement->id, 'operator_id' => $agreement->provider_operator_id,
            'commission_rule_id' => $agreement->fee_rule_id, 'gross_amount' => $fee + $providerNet, 'platform_fee' => $fee,
            'operator_net' => $providerNet, 'ledger_tx_id' => $txId, 'status' => 'pending', 'created_at' => now(), 'updated_at' => now(),
        ]);
        // The company's money has just landed in its wallet; pay the driver their agreed share out of it.
        if ($added) {
            app(DriverPayService::class)->payForShipment((int) $shipmentId, $providerNet, (int) $agreement->provider_operator_id, (bool) $agreement->is_test);
        }
    }

    /** Customer approved extra goods money (shopping budget amendment): move it from their wallet into escrow. */
    public function addToHold(int $agreementId, int $extra, ?int $postedBy = null): void
    {
        DB::transaction(function () use ($agreementId, $extra, $postedBy) {
            $agreement = $this->lockAgreement($agreementId);
            $hold = DB::table('escrow_holds')->where('agreement_id', $agreementId)->lockForUpdate()->first();
            if (! $hold || ! in_array($hold->status, ['held', 'partially_released'], true) || $extra <= 0) {
                throw new RuntimeException('Escrow cannot take extra funds now.');
            }
            // Throws InsufficientFunds when the customer's wallet is short.
            $this->ledger->post("escrow-topup-{$agreementId}-{$hold->amount}-{$extra}", [
                'operator_id' => (int) $agreement->provider_operator_id, 'kind' => 'escrow_hold', 'reference_type' => 'agreement', 'reference_id' => $agreementId,
                'description' => "Extra budget for agreement {$agreement->number}", 'posted_by' => $postedBy, 'is_test' => (bool) $agreement->is_test,
            ], [
                ['account_id' => $this->accounts->wallet('customer', (int) $agreement->customer_id, 1), 'direction' => 'debit', 'amount' => $extra],
                ['account_id' => (int) $hold->ledger_account_id, 'direction' => 'credit', 'amount' => $extra],
            ]);
            DB::table('escrow_holds')->where('id', $hold->id)->update(['amount' => (int) $hold->amount + $extra, 'updated_at' => now()]);
            DB::table('agreements')->where('id', $agreementId)->update(['goods_budget' => (int) $agreement->goods_budget + $extra, 'updated_at' => now()]);
        });
    }

    /** Shopper's advance: goods money leaves escrow into the shopper's wallet so they can pay vendors. */
    public function issueAdvance(int $agreementId, int $amount, ?int $postedBy = null): int
    {
        return DB::transaction(function () use ($agreementId, $amount, $postedBy) {
            $agreement = $this->lockAgreement($agreementId);
            $hold = DB::table('escrow_holds')->where('agreement_id', $agreementId)->lockForUpdate()->first();
            if (! $hold || $hold->status !== 'held' || $amount <= 0) {
                throw new RuntimeException('No escrow available for an advance.');
            }
            $issued = (int) DB::table('shopper_advances')->where('agreement_id', $agreementId)->whereIn('status', ['issued'])->sum('amount');
            if ($issued + $amount > (int) $agreement->goods_budget) {
                throw new InvalidArgumentException('Advance cannot exceed the goods budget.');
            }
            $id = DB::table('shopper_advances')->insertGetId(['agreement_id' => $agreementId, 'amount' => $amount, 'status' => 'issued', 'issued_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
            $tx = $this->ledger->post("shopper-advance-{$id}", [
                'operator_id' => (int) $agreement->provider_operator_id, 'kind' => 'shopper_advance', 'reference_type' => 'agreement', 'reference_id' => $agreementId,
                'description' => "Goods advance for agreement {$agreement->number}", 'posted_by' => $postedBy, 'is_test' => (bool) $agreement->is_test,
            ], [
                ['account_id' => (int) $hold->ledger_account_id, 'direction' => 'debit', 'amount' => $amount],
                ['account_id' => $this->accounts->wallet('operator', (int) $agreement->provider_operator_id, (int) $agreement->provider_operator_id), 'direction' => 'credit', 'amount' => $amount],
            ]);
            DB::table('shopper_advances')->where('id', $id)->update(['ledger_transaction_id' => $tx['id']]);
            DB::table('escrow_holds')->where('id', $hold->id)->update(['released_amount' => (int) $hold->released_amount + $amount, 'updated_at' => now()]);

            return $id;
        });
    }

    /** Stop any release while a dispute is open. */
    public function freeze(int $agreementId, string $reason): void
    {
        DB::table('escrow_holds')->where('agreement_id', $agreementId)
            ->whereIn('status', ['held', 'partially_released'])
            ->update(['status' => 'frozen', 'frozen_reason' => $reason, 'updated_at' => now()]);
        DB::table('agreements')->where('id', $agreementId)->update(['status' => 'disputed', 'updated_at' => now()]);
    }

    /** Lift a freeze so release() or refund() can run after a decision. */
    public function unfreeze(int $agreementId): void
    {
        DB::table('escrow_holds')->where('agreement_id', $agreementId)->where('status', 'frozen')
            ->update(['status' => 'held', 'frozen_reason' => null, 'updated_at' => now()]);
    }

    public function escrowTotal(object $agreement): int
    {
        return (int) $agreement->price + (int) $agreement->goods_budget + (int) $agreement->tip;
    }

    private function lockAgreement(int $id): object
    {
        $agreement = DB::table('agreements')->where('id', $id)->lockForUpdate()->first();
        if (! $agreement) {
            throw new RuntimeException("Agreement {$id} not found.");
        }

        return $agreement;
    }
}
