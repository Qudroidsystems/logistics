<?php

namespace App\Modules\Payments\Cards;

use App\Modules\Payments\Gateways\GatewayManager;
use App\Modules\Payments\Gateways\SavesCards;
use App\Modules\Payments\WalletService;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * A customer's saved card, and topping their wallet up from it when a plan renewal would otherwise fail.
 *
 * A card is saved only when the customer ticks "Save this card" on a wallet top-up. One card per customer: a newer
 * one replaces the old. The renewal charge is taken at most once per plan month (card_charges.idempotency_key), so
 * a declined card is not retried every hour.
 */
class SavedCards
{
    public function __construct(private GatewayManager $gateways, private WalletService $wallets) {}

    /** The customer's card, for the account and app screens. */
    public function current(int $userId): ?array
    {
        $c = DB::table('saved_cards')->where('user_id', $userId)->whereIn('status', ['active', 'failing'])->orderByDesc('id')->first();

        return $c ? self::present($c) : null;
    }

    public static function present(object $c): array
    {
        $expired = $c->exp_year && $c->exp_month && now()->greaterThan(\Illuminate\Support\Carbon::create((int) $c->exp_year, (int) $c->exp_month, 1)->endOfMonth());

        return ['id' => $c->public_id, 'brand' => $c->brand ?: 'Card', 'last4' => $c->last4, 'exp' => $c->exp_month ? sprintf('%02d/%02d', $c->exp_month, $c->exp_year % 100) : null,
            'expired' => $expired, 'auto_topup' => (bool) $c->auto_topup, 'failing' => $c->status === 'failing', 'gateway' => $c->gateway];
    }

    /** Whether a top-up can offer "Save this card" (the gateway it will go through can keep cards). */
    public function canSave(?string $gateway = null): bool
    {
        try {
            return $this->gateways->choose($gateway) instanceof SavesCards;
        } catch (RuntimeException) {
            return false;
        }
    }

    /** After a top-up the customer asked to keep the card for: store it. Quietly does nothing when the payment had no reusable card. */
    public function captureFrom(int $paymentIntentId): void
    {
        $intent = DB::table('payment_intents')->where('id', $paymentIntentId)->first();
        $raw = json_decode((string) ($intent->raw ?? ''), true) ?: [];
        if (! $intent || empty($raw['save_card']) || $intent->status !== 'succeeded') {
            return;
        }
        try {
            $gw = $this->gateways->get($intent->gateway);
            $card = $gw instanceof SavesCards ? $gw->savedCard($raw) : null;
        } catch (Throwable $e) {
            Log::warning("Could not read the saved card for payment {$intent->reference}: {$e->getMessage()}");

            return;
        }
        if (! $card) {
            return;
        }
        $this->store((int) $intent->payer_id, $intent->gateway, $card);
    }

    /** A top-up was credited (webhook or reconcile): keep the card if asked, and close an off-session charge that had no answer at the time. */
    public function afterTopUp(int $paymentIntentId): void
    {
        DB::table('card_charges')->where('payment_intent_id', $paymentIntentId)->where('status', 'pending')->update(['status' => 'succeeded', 'updated_at' => now()]);
        // Reading the card may call the gateway: do it once the payment is committed, never inside its transaction.
        DB::afterCommit(fn () => $this->captureFrom($paymentIntentId));
    }

    public function store(int $userId, string $gateway, array $card): string
    {
        return DB::transaction(function () use ($userId, $gateway, $card) {
            // One card per customer: the newest replaces any other.
            DB::table('saved_cards')->where('user_id', $userId)->whereIn('status', ['active', 'failing'])->update(['status' => 'removed', 'updated_at' => now()]);
            $id = (string) Str::ulid();
            DB::table('saved_cards')->insert([
                'public_id' => $id, 'user_id' => $userId, 'gateway' => $gateway, 'token' => Crypt::encryptString($card['token']),
                'customer_ref' => $card['customer_ref'] ? Crypt::encryptString((string) $card['customer_ref']) : null, 'email' => $card['email'] ?? null,
                'brand' => $card['brand'] ?? null, 'last4' => $card['last4'] ?? null, 'exp_month' => $card['exp_month'] ?? null, 'exp_year' => $card['exp_year'] ?? null,
                'fingerprint' => $card['fingerprint'] ?? null, 'auto_topup' => true, 'status' => 'active', 'created_at' => now(), 'updated_at' => now(),
            ]);

            return $id;
        });
    }

    public function setAutoTopUp(int $userId, string $publicId, bool $on): void
    {
        $n = DB::table('saved_cards')->where(['user_id' => $userId, 'public_id' => $publicId])->whereIn('status', ['active', 'failing'])->update(['auto_topup' => $on, 'updated_at' => now()]);
        if (! $n) {
            throw new RuntimeException('That card was not found.');
        }
    }

    /** Forgets the card here. (The gateway's copy can't be used without our token.) */
    public function remove(int $userId, string $publicId): void
    {
        $n = DB::table('saved_cards')->where(['user_id' => $userId, 'public_id' => $publicId])->where('status', '<>', 'removed')
            ->update(['status' => 'removed', 'token' => Crypt::encryptString('removed'), 'customer_ref' => null, 'updated_at' => now()]);
        if (! $n) {
            throw new RuntimeException('That card was not found.');
        }
    }

    /**
     * Before a plan renewal: if the wallet is short, top it up from the saved card by the shortfall, once per key.
     * Returns succeeded | failed | pending | skipped. Never throws: a renewal must not stop because a card did.
     */
    public function topUpFor(int $userId, int $needed, string $key, string $purpose = 'plan_renewal'): string
    {
        $short = $needed - $this->wallets->balance($userId);
        if ($short <= 0) {
            return 'skipped';
        }
        $c = DB::table('saved_cards')->where('user_id', $userId)->whereIn('status', ['active', 'failing'])->where('auto_topup', true)->orderByDesc('id')->first();
        if (! $c || self::present($c)['expired']) {
            return 'skipped';
        }
        $gw = null;
        try {
            $gw = $this->gateways->get($c->gateway);
        } catch (Throwable) {
        }
        if (! $gw instanceof SavesCards || ! $gw->isReady()) {
            return 'skipped';
        }
        // Small top-ups cost more in card fees than they are worth: take at least the wallet minimum.
        $amount = max($short, WalletService::MIN_TOPUP_KOBO);
        $ref = 'CT-'.strtoupper(Str::random(18));
        $claimed = DB::table('card_charges')->insertOrIgnore([
            'user_id' => $userId, 'saved_card_id' => $c->id, 'idempotency_key' => mb_substr($key, 0, 120), 'purpose' => $purpose, 'amount' => $amount,
            'status' => 'pending', 'created_at' => now(), 'updated_at' => now(),
        ]);
        if (! $claimed) {
            return 'skipped'; // already tried for this renewal
        }
        $chargeId = (int) DB::table('card_charges')->where('idempotency_key', mb_substr($key, 0, 120))->value('id');
        $walletId = $this->wallets->ensureWallet($userId);
        $intentId = DB::table('payment_intents')->insertGetId([
            'public_id' => (string) Str::ulid(), 'operator_id' => 1, 'payer_id' => $userId, 'gateway' => $c->gateway, 'amount' => $amount, 'currency' => \App\Support\Money::code(),
            'reference' => $ref, 'status' => 'pending', 'raw' => json_encode(['off_session' => true, 'saved_card' => $c->public_id]), 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('wallet_topups')->insert(['wallet_id' => $walletId, 'payment_intent_id' => $intentId, 'amount' => $amount, 'status' => 'pending', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('card_charges')->where('id', $chargeId)->update(['payment_intent_id' => $intentId, 'updated_at' => now()]);

        $card = ['token' => Crypt::decryptString($c->token), 'customer_ref' => $c->customer_ref ? Crypt::decryptString($c->customer_ref) : null, 'email' => $c->email];
        $email = (string) DB::table('users')->where('id', $userId)->value('email');
        try {
            $r = $gw->chargeSaved($card, $amount, $ref, $email);
        } catch (Throwable $e) {
            // Unknown outcome (timeout): payments:reconcile asks the gateway about this reference later.
            Log::warning("Saved-card charge {$ref} had no answer: {$e->getMessage()}");
            $r = ['status' => 'pending', 'amount' => 0, 'currency' => '', 'raw' => [], 'message' => null];
        }

        return $this->settle($intentId, $chargeId, $c, $r);
    }

    private function settle(int $intentId, int $chargeId, object $card, array $r): string
    {
        $raw = array_merge(['off_session' => true, 'saved_card' => $card->public_id], array_filter((array) $r['raw'], fn ($v) => $v !== null));
        if ($r['status'] === 'success') {
            $intent = DB::table('payment_intents')->find($intentId);
            if ((int) $r['amount'] !== (int) $intent->amount || ($r['currency'] && strtoupper($r['currency']) !== strtoupper((string) $intent->currency))) {
                // Never credit a different amount than we asked for; leave it for staff.
                DB::table('payment_intents')->where('id', $intentId)->update(['status' => 'failed', 'raw' => json_encode($raw), 'updated_at' => now()]);
                DB::table('card_charges')->where('id', $chargeId)->update(['status' => 'failed', 'message' => 'Amount did not match; check this payment.', 'updated_at' => now()]);
                DB::table('risk_events')->insert(['type' => 'payment_amount_mismatch', 'subject_type' => 'payment_intent', 'subject_id' => $intentId, 'severity' => 'high',
                    'evidence' => json_encode($r), 'created_at' => now(), 'updated_at' => now()]);

                return 'failed';
            }
            DB::transaction(function () use ($intentId, $chargeId, $card, $raw) {
                $n = DB::table('payment_intents')->where('id', $intentId)->whereIn('status', ['initiated', 'pending'])
                    ->update(['status' => 'succeeded', 'paid_at' => now(), 'channel' => 'card', 'raw' => json_encode($raw), 'updated_at' => now()]);
                if ($n) {
                    $this->wallets->creditTopUp($intentId);
                }
                DB::table('card_charges')->where('id', $chargeId)->update(['status' => 'succeeded', 'updated_at' => now()]);
                DB::table('saved_cards')->where('id', $card->id)->where('status', 'failing')->update(['status' => 'active', 'updated_at' => now()]);
            });

            return 'succeeded';
        }
        if ($r['status'] === 'failed') {
            DB::table('payment_intents')->where('id', $intentId)->update(['status' => 'failed', 'raw' => json_encode($raw), 'updated_at' => now()]);
            DB::table('card_charges')->where('id', $chargeId)->update(['status' => 'failed', 'message' => mb_substr((string) $r['message'], 0, 300), 'updated_at' => now()]);
            DB::table('saved_cards')->where('id', $card->id)->update(['status' => 'failing', 'updated_at' => now()]);

            return 'failed';
        }
        DB::table('payment_intents')->where('id', $intentId)->update(['raw' => json_encode($raw), 'updated_at' => now()]);

        return 'pending';
    }
}
