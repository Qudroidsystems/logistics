<?php

namespace App\Modules\Payments;

use App\Modules\Payments\Gateways\PaystackGateway;
use App\Modules\Payments\Ledger\AccountResolver;
use App\Modules\Payments\Ledger\LedgerPoster;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class WalletService
{
    public const MIN_TOPUP_KOBO = 10_000;        // N100
    public const MAX_TOPUP_KOBO = 100_000_000;   // N1,000,000 per top-up

    public function __construct(private LedgerPoster $ledger, private AccountResolver $accounts, private PaystackGateway $paystack)
    {
    }

    public function balance(int $userId): int
    {
        return (int) DB::table('ledger_accounts')->where('id', $this->accounts->wallet('customer', $userId, 1))->value('balance');
    }

    /** Starts a card top-up. The wallet is only credited when Paystack's webhook confirms the exact amount. */
    public function startTopUp(int $userId, int $amount): array
    {
        if ($amount < self::MIN_TOPUP_KOBO || $amount > self::MAX_TOPUP_KOBO) {
            throw new RuntimeException('Top-up must be between N'.number_format(self::MIN_TOPUP_KOBO / 100).' and N'.number_format(self::MAX_TOPUP_KOBO / 100).'.');
        }
        $walletId = $this->ensureWallet($userId);
        $ref = 'WT-'.strtoupper(Str::random(18));

        $intentId = DB::table('payment_intents')->insertGetId([
            'public_id' => (string) Str::ulid(), 'operator_id' => 1, 'payer_id' => $userId, 'gateway' => 'paystack', 'amount' => $amount,
            'reference' => $ref, 'status' => 'pending', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('wallet_topups')->insert(['wallet_id' => $walletId, 'payment_intent_id' => $intentId, 'amount' => $amount, 'status' => 'pending', 'created_at' => now(), 'updated_at' => now()]);

        $email = DB::table('users')->where('id', $userId)->value('email');
        $init = $this->paystack->initialize($email, $amount, $ref, route('payments.callback'), ['purpose' => 'wallet_topup']);

        return ['reference' => $ref, 'authorization_url' => $init['authorization_url'], 'amount' => $amount];
    }

    /** Called by the gateway-event job once the charge is verified. Idempotent. */
    public function creditTopUp(int $paymentIntentId): void
    {
        DB::transaction(function () use ($paymentIntentId) {
            $t = DB::table('wallet_topups')->where('payment_intent_id', $paymentIntentId)->lockForUpdate()->first();
            if (! $t || $t->status === 'completed') {
                return;
            }
            $wallet = DB::table('wallets')->find($t->wallet_id);
            $tx = $this->ledger->post("wallet-topup-{$t->id}", [
                'operator_id' => 1, 'kind' => 'topup', 'reference_type' => 'wallet_topup', 'reference_id' => $t->id, 'description' => 'Wallet top-up by card',
            ], [
                ['account_id' => $this->accounts->platform('gateway_clearing'), 'direction' => 'debit', 'amount' => (int) $t->amount],
                ['account_id' => (int) $wallet->ledger_account_id, 'direction' => 'credit', 'amount' => (int) $t->amount],
            ]);
            DB::table('wallet_topups')->where('id', $t->id)->update(['status' => 'completed', 'ledger_tx_id' => $tx['id'], 'updated_at' => now()]);
        });
    }

    private function ensureWallet(int $userId): int
    {
        $account = $this->accounts->wallet('customer', $userId, 1);
        DB::table('wallets')->insertOrIgnore([
            'public_id' => (string) Str::ulid(), 'operator_id' => 1, 'owner_type' => 'customer', 'owner_id' => $userId,
            'ledger_account_id' => $account, 'created_at' => now(), 'updated_at' => now(),
        ]);

        return (int) DB::table('wallets')->where(['owner_type' => 'customer', 'owner_id' => $userId])->value('id');
    }
}
