<?php

namespace App\Modules\Settlements;

use App\Jobs\ProcessPayout;
use App\Modules\Payments\Gateways\PaystackGateway;
use App\Modules\Payments\Ledger\AccountResolver;
use App\Modules\Payments\Ledger\LedgerPoster;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Withdrawals from a provider's wallet to a verified bank account.
 *
 *  request  : wallet -> payout_in_transit   (money is reserved immediately, so it cannot be spent twice)
 *  success  : payout_in_transit -> gateway_clearing  (cash has left the platform)
 *  failure  : payout_in_transit -> wallet   (reservation returned)
 */
class PayoutService
{
    public const DEFAULT_MIN_KOBO = 100_000;            // N1,000
    public const DEFAULT_AUTO_APPROVE_MAX_KOBO = 50_000_000; // N500,000: larger payouts wait for finance staff

    public function __construct(private LedgerPoster $ledger, private AccountResolver $accounts, private PaystackGateway $paystack)
    {
    }

    public function request(int $operatorId, int $userId, int $amount, int $bankAccountId): int
    {
        $min = (int) ($this->setting('payout.min_kobo') ?? self::DEFAULT_MIN_KOBO);
        if ($amount < $min) {
            throw new RuntimeException('Minimum payout is N'.number_format($min / 100).'.');
        }
        $bank = DB::table('bank_accounts')->where('id', $bankAccountId)->where('owner_type', 'operator')->where('owner_id', $operatorId)->whereNull('deleted_at')->first();
        if (! $bank || ! $bank->verified_at) {
            throw new RuntimeException('Pay out to a verified bank account.');
        }
        $isMember = DB::table('operator_members')->where(['operator_id' => $operatorId, 'user_id' => $userId, 'status' => 'active'])->whereIn('role', ['owner', 'admin', 'finance'])->exists();
        if (! $isMember) {
            throw new RuntimeException('You are not allowed to withdraw for this account.');
        }

        return $this->reserve('operator', $operatorId, $operatorId, $amount, $bank);
    }

    /** A customer withdraws their wallet balance to their own verified bank account. */
    public function requestForCustomer(int $userId, int $amount, int $bankAccountId): int
    {
        $min = (int) ($this->setting('payout.min_kobo') ?? self::DEFAULT_MIN_KOBO);
        if ($amount < $min) {
            throw new RuntimeException('Minimum withdrawal is N'.number_format($min / 100).'.');
        }
        $bank = DB::table('bank_accounts')->where('id', $bankAccountId)->where('owner_type', 'customer')->where('owner_id', $userId)->whereNull('deleted_at')->first();
        if (! $bank || ! $bank->verified_at) {
            throw new RuntimeException('Withdraw to a verified bank account.');
        }

        return $this->reserve('customer', $userId, 1, $amount, $bank);
    }

    /** Reserves the money (wallet -> payout_in_transit) and queues the transfer. Shared by providers and customers. */
    private function reserve(string $ownerType, int $ownerId, int $operatorId, int $amount, object $bank): int
    {
        return DB::transaction(function () use ($ownerType, $ownerId, $operatorId, $amount, $bank) {
            $account = $this->accounts->wallet($ownerType, $ownerId, $operatorId);
            DB::table('wallets')->insertOrIgnore([
                'public_id' => (string) Str::ulid(), 'operator_id' => $operatorId, 'owner_type' => $ownerType, 'owner_id' => $ownerId,
                'ledger_account_id' => $account, 'created_at' => now(), 'updated_at' => now(),
            ]);
            $walletId = (int) DB::table('wallets')->where(['owner_type' => $ownerType, 'owner_id' => $ownerId])->value('id');
            $wallet = DB::table('wallets')->where('id', $walletId)->lockForUpdate()->first();
            if ($wallet->status !== 'active') {
                throw new RuntimeException('Wallet is not active.');
            }

            $id = DB::table('payout_requests')->insertGetId([
                'public_id' => (string) Str::ulid(), 'operator_id' => $operatorId, 'wallet_id' => $walletId, 'bank_account_id' => $bank->id,
                'amount' => $amount, 'status' => 'requested', 'created_at' => now(), 'updated_at' => now(),
            ]);

            // Throws InsufficientFunds when the wallet cannot cover it.
            $tx = $this->ledger->post("payout-request-{$id}", [
                'operator_id' => $operatorId, 'kind' => 'payout', 'reference_type' => 'payout_request', 'reference_id' => $id,
                'description' => 'Payout requested',
            ], [
                ['account_id' => $account, 'direction' => 'debit', 'amount' => $amount],
                ['account_id' => $this->accounts->platform('payout_in_transit'), 'direction' => 'credit', 'amount' => $amount],
            ]);
            DB::table('payout_requests')->where('id', $id)->update(['ledger_tx_id' => $tx['id']]);

            $auto = (int) ($this->setting('payout.auto_approve_max_kobo') ?? self::DEFAULT_AUTO_APPROVE_MAX_KOBO);
            if ($amount <= $auto) {
                DB::table('payout_requests')->where('id', $id)->update(['status' => 'approved']);
                ProcessPayout::dispatch($id)->afterCommit();
            }

            return $id;
        });
    }

    /** Finance staff approve a large payout. */
    public function approve(int $payoutId, int $staffId): void
    {
        $n = DB::table('payout_requests')->where('id', $payoutId)->where('status', 'requested')->update(['status' => 'approved', 'approved_by' => $staffId, 'updated_at' => now()]);
        if (! $n) {
            throw new RuntimeException('Payout is not waiting for approval.');
        }
        ProcessPayout::dispatch($payoutId)->afterCommit();
        $this->tell(DB::table('payout_requests')->find($payoutId), 'payout.processing');
    }

    /** Finance staff decline a payout; the reserved money returns to the provider's wallet. */
    public function reject(int $payoutId, int $staffId, string $reason): void
    {
        $p = DB::table('payout_requests')->find($payoutId);
        if (! $p || $p->status !== 'requested') {
            throw new RuntimeException('Payout is not waiting for approval.');
        }
        DB::table('payout_requests')->where('id', $payoutId)->update(['approved_by' => $staffId]);
        $this->fail($payoutId, $reason);
    }

    /** Sends the transfer. Runs in the queue; safe to retry because the transfer reference is fixed per payout. */
    public function send(int $payoutId): void
    {
        $p = DB::transaction(function () use ($payoutId) {
            $p = DB::table('payout_requests')->where('id', $payoutId)->lockForUpdate()->first();
            if (! $p || $p->status !== 'approved') {
                return null;
            }
            DB::table('payout_requests')->where('id', $payoutId)->update(['status' => 'processing', 'gateway_transfer_ref' => "PO-{$p->public_id}", 'updated_at' => now()]);

            return $p;
        });
        if (! $p) {
            return;
        }

        $bank = DB::table('bank_accounts')->find($p->bank_account_id);
        try {
            $recipient = $bank->verification_ref ?: $this->paystack->createRecipient($bank->account_name, Crypt::decryptString($bank->account_number), $bank->bank_code);
            DB::table('bank_accounts')->where('id', $bank->id)->update(['verification_ref' => $recipient]);
            $this->paystack->transfer($p->amount, $recipient, "PO-{$p->public_id}", 'Logistics payout');
        } catch (\Throwable $e) {
            // Definite failure before money moved: give the reservation back. An ambiguous timeout is left
            // in 'processing' for the webhook or reconciliation to resolve.
            if ($e instanceof RuntimeException) {
                $this->fail($payoutId, $e->getMessage());

                return;
            }
            throw $e;
        }
    }

    /** transfer.success webhook. */
    public function complete(string $reference): void
    {
        $paid = DB::transaction(function () use ($reference) {
            $p = DB::table('payout_requests')->where('gateway_transfer_ref', $reference)->lockForUpdate()->first();
            if (! $p || $p->status === 'paid') {
                return;
            }
            $this->ledger->post("payout-paid-{$p->id}", [
                'operator_id' => (int) $p->operator_id, 'kind' => 'payout', 'reference_type' => 'payout_request', 'reference_id' => $p->id, 'description' => 'Payout sent',
            ], [
                ['account_id' => $this->accounts->platform('payout_in_transit'), 'direction' => 'debit', 'amount' => (int) $p->amount],
                ['account_id' => $this->accounts->platform('gateway_clearing'), 'direction' => 'credit', 'amount' => (int) $p->amount],
            ]);
            DB::table('payout_requests')->where('id', $p->id)->update(['status' => 'paid', 'updated_at' => now()]);

            return $p;
        });
        $this->tell($paid, 'payout.paid');
    }

    /** transfer.failed / transfer.reversed webhook, or a definite send failure. */
    public function fail(int|string $payoutOrReference, string $reason): void
    {
        $failed = DB::transaction(function () use ($payoutOrReference, $reason) {
            $q = DB::table('payout_requests')->lockForUpdate();
            $p = is_int($payoutOrReference) ? $q->where('id', $payoutOrReference)->first() : $q->where('gateway_transfer_ref', $payoutOrReference)->first();
            if (! $p || in_array($p->status, ['paid', 'failed'], true)) {
                return;
            }
            $this->ledger->post("payout-failed-{$p->id}", [
                'operator_id' => (int) $p->operator_id, 'kind' => 'payout', 'reference_type' => 'payout_request', 'reference_id' => $p->id, 'description' => 'Payout failed, returned to wallet',
            ], [
                ['account_id' => $this->accounts->platform('payout_in_transit'), 'direction' => 'debit', 'amount' => (int) $p->amount],
                ['account_id' => (int) DB::table('wallets')->where('id', $p->wallet_id)->value('ledger_account_id'), 'direction' => 'credit', 'amount' => (int) $p->amount],
            ]);
            DB::table('payout_requests')->where('id', $p->id)->update(['status' => 'failed', 'failure_reason' => mb_substr($reason, 0, 250), 'updated_at' => now()]);

            return $p;
        });
        $this->tell($failed, 'payout.failed', ['note' => $reason]);
    }

    /** Tells the wallet's owner (a provider's team, or a customer) what happened to their payout. Never throws. */
    private function tell(?object $p, string $event, array $extra = []): void
    {
        if (! $p) {
            return;
        }
        try {
            $w = DB::table('wallets')->where('id', $p->wallet_id)->first(['owner_type', 'owner_id']);
            $vars = ['amount' => \App\Modules\Notifications\NotificationService::naira((int) $p->amount)] + $extra;
            $n = app(\App\Modules\Notifications\NotificationService::class);
            if ($w && $w->owner_type === 'operator') {
                $n->notifyOperator((int) $w->owner_id, $event, $vars, '/provider/payouts', ['owner', 'admin', 'finance']);
            } elseif ($w) {
                $n->notify((int) $w->owner_id, $event, $vars, '/wallet');
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Payout notification failed: {$e->getMessage()}");
        }
    }

    private function setting(string $key): mixed
    {
        $v = DB::table('platform_settings')->whereNull('operator_id')->where('key', $key)->value('value');

        return $v === null ? null : json_decode($v, true) ?? $v;
    }
}
