<?php

namespace App\Modules\Payments\Escrow;

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
            $toProvider = (int) $agreement->provider_net + (int) $agreement->tip + $goodsSpent;
            $fee = (int) $agreement->platform_fee;

            if ($toProvider + $fee + $unspent !== $remaining) {
                throw new RuntimeException('Escrow balance does not match the amounts being released.');
            }

            $providerWallet = $this->accounts->wallet('operator', (int) $agreement->provider_operator_id, (int) $agreement->provider_operator_id);
            $commission = $this->accounts->platform('platform_commission');
            $customerWallet = $this->accounts->wallet('customer', (int) $agreement->customer_id, 1);

            $entries = [['account_id' => (int) $hold->ledger_account_id, 'direction' => 'debit', 'amount' => $remaining]];
            if ($toProvider > 0) {
                $entries[] = ['account_id' => $providerWallet, 'direction' => 'credit', 'amount' => $toProvider];
            }
            if ($fee > 0) {
                $entries[] = ['account_id' => $commission, 'direction' => 'credit', 'amount' => $fee];
            }
            if ($unspent > 0) {
                $entries[] = ['account_id' => $customerWallet, 'direction' => 'credit', 'amount' => $unspent];
            }

            $tx = $this->ledger->post("escrow-release-{$agreementId}", [
                'operator_id' => (int) $agreement->provider_operator_id,
                'kind' => 'escrow_release',
                'reference_type' => 'agreement',
                'reference_id' => $agreementId,
                'description' => "Release for agreement {$agreement->number}",
                'posted_by' => $postedBy,
                'is_test' => (bool) $agreement->is_test,
            ], $entries);

            DB::table('escrow_holds')->where('id', $hold->id)->update([
                'released_amount' => (int) $hold->released_amount + $toProvider + $fee,
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

    private function escrowTotal(object $agreement): int
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
