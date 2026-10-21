<?php

namespace App\Modules\Marketing;

use App\Jobs\SendPlainEmail;
use App\Modules\Notifications\Sms\SmsService;
use App\Modules\Payments\Ledger\AccountResolver;
use App\Modules\Payments\Ledger\LedgerPoster;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Gift cards: a code with a naira balance that pays for deliveries. A customer buys one from their wallet, or staff
 * issue one (the platform pays). Each card's unspent balance is a platform liability (ledger purpose gift_card).
 * At payment a card works like a discount: the platform tops escrow up from promo_expense, and when the order is
 * made the card's balance pays promo_expense back. A refund gives the balance back to the card.
 *
 * Codes are shown once, to the buyer, and texted or emailed to the recipient. Only a hash is stored.
 */
class GiftCardService
{
    public const MIN = 100_000;

    public const MAX = 50_000_000;

    /** No 0/O or 1/I, so a code read over the phone is typed right. */
    private const ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    public function __construct(private LedgerPoster $ledger, private AccountResolver $accounts) {}

    public static function normalise(string $code): string
    {
        return preg_replace('/[^A-Z0-9]/', '', strtoupper($code));
    }

    public static function hash(string $code): string
    {
        return hash('sha256', self::normalise($code));
    }

    /** A customer buys a card with money in their wallet. Returns the card with its code (shown only now). */
    public function buy(int $userId, int $amount, array $to = []): array
    {
        $this->checkAmount($amount);

        return $this->create($amount, 'bought', $userId, null, $to, null, fn (int $cardId) => [
            ['account_id' => $this->accounts->wallet('customer', $userId, 1), 'direction' => 'debit', 'amount' => $amount],
            ['account_id' => $this->accounts->for('gift_card', $cardId, 'gift_card', 1), 'direction' => 'credit', 'amount' => $amount],
        ]);
    }

    /** Staff give a card away (a prize, an apology); the platform pays for it. */
    public function issue(int $staffId, int $amount, array $to = [], ?string $expires = null): array
    {
        $this->checkAmount($amount);

        return $this->create($amount, 'issued', null, $staffId, $to, $expires, fn (int $cardId) => [
            ['account_id' => $this->accounts->platform('promo_expense'), 'direction' => 'debit', 'amount' => $amount],
            ['account_id' => $this->accounts->for('gift_card', $cardId, 'gift_card', 1), 'direction' => 'credit', 'amount' => $amount],
        ]);
    }

    /** A card that can pay now, or a reason why not. */
    public function usable(string $code): object
    {
        $card = DB::table('gift_cards')->where('code_hash', self::hash($code))->first();
        if (! $card || $card->status !== 'active') {
            throw new RuntimeException('That gift card code is not right.');
        }
        if ($card->expires_at && now()->greaterThan($card->expires_at)) {
            throw new RuntimeException('That gift card has expired.');
        }
        if ((int) $card->balance <= 0) {
            throw new RuntimeException('That gift card has been used up.');
        }

        return $card;
    }

    /** What a card can take off, at most $left. 0 when the card has run out or expired since it was added. */
    public function discountFor(object $a, int $left): int
    {
        if (! $a->gift_card_id || $a->merchant_id || $left <= 0) {
            return 0;
        }
        $card = DB::table('gift_cards')->where('id', $a->gift_card_id)->first();
        if (! $card || $card->status !== 'active' || ($card->expires_at && now()->greaterThan($card->expires_at))) {
            return 0;
        }

        return max(0, min((int) $card->balance, $left));
    }

    /** When the order is made: take the amount from the card and pay back the platform's top-up. Safe to call twice. */
    public function spend(object $a, int $amount): void
    {
        if ($amount <= 0 || ! $a->gift_card_id || DB::table('gift_card_uses')->where('agreement_id', $a->id)->where('amount', '>', 0)->exists()) {
            return;
        }
        $card = DB::table('gift_cards')->where('id', $a->gift_card_id)->lockForUpdate()->first();
        // Spent somewhere else since payment started: the platform keeps the difference.
        $take = min($amount, (int) $card->balance);
        if ($take <= 0) {
            return;
        }
        DB::table('gift_cards')->where('id', $card->id)->update(['balance' => (int) $card->balance - $take, 'updated_at' => now()]);
        DB::table('gift_card_uses')->insert(['gift_card_id' => $card->id, 'agreement_id' => $a->id, 'user_id' => $a->customer_id, 'amount' => $take, 'created_at' => now()]);
        $this->ledger->post("gift-card-spend-{$a->id}", [
            'operator_id' => 1, 'kind' => 'gift_card_spend', 'reference_type' => 'agreement', 'reference_id' => $a->id,
            'description' => "Gift card ending {$card->last4} used on agreement {$a->number}", 'is_test' => (bool) $a->is_test,
        ], [
            ['account_id' => $this->accounts->for('gift_card', (int) $card->id, 'gift_card', 1), 'direction' => 'debit', 'amount' => $take],
            ['account_id' => $this->accounts->platform('promo_expense'), 'direction' => 'credit', 'amount' => $take],
        ]);
    }

    /** A refund gave the platform's top-up back: put up to $kobo back on the card. Returns what went back. */
    public function restore(int $agreementId, int $kobo): int
    {
        $used = (int) DB::table('gift_card_uses')->where('agreement_id', $agreementId)->sum('amount');
        $cardId = DB::table('gift_card_uses')->where('agreement_id', $agreementId)->value('gift_card_id');
        $back = min($used, $kobo);
        if (! $cardId || $back <= 0) {
            return 0;
        }
        $card = DB::table('gift_cards')->where('id', $cardId)->lockForUpdate()->first();
        DB::table('gift_cards')->where('id', $cardId)->update(['balance' => (int) $card->balance + $back, 'updated_at' => now()]);
        DB::table('gift_card_uses')->insert(['gift_card_id' => $cardId, 'agreement_id' => $agreementId, 'user_id' => DB::table('agreements')->where('id', $agreementId)->value('customer_id'), 'amount' => -$back, 'created_at' => now()]);
        $this->ledger->post("gift-card-restore-{$agreementId}", [
            'operator_id' => 1, 'kind' => 'gift_card_restore', 'reference_type' => 'agreement', 'reference_id' => $agreementId,
            'description' => "Gift card ending {$card->last4} given back after a refund",
        ], [
            ['account_id' => $this->accounts->platform('promo_expense'), 'direction' => 'debit', 'amount' => $back],
            ['account_id' => $this->accounts->for('gift_card', (int) $cardId, 'gift_card', 1), 'direction' => 'credit', 'amount' => $back],
        ]);

        return $back;
    }

    /** Balance check for a code, without using it. */
    public function check(string $code): array
    {
        $card = $this->usable($code);

        return ['balance' => (int) $card->balance, 'last4' => $card->last4, 'expires_at' => $card->expires_at];
    }

    public function setActive(int $cardId, bool $active): void
    {
        DB::table('gift_cards')->where('id', $cardId)->update(['status' => $active ? 'active' : 'disabled', 'updated_at' => now()]);
    }

    /** Cards a customer bought. */
    public function boughtBy(int $userId): array
    {
        return DB::table('gift_cards')->where('bought_by', $userId)->orderByDesc('id')->limit(50)
            ->get(['public_id', 'last4', 'initial_value', 'balance', 'recipient_name', 'status', 'expires_at', 'created_at'])
            ->map(fn ($c) => (array) $c + ['label' => 'Ending '.$c->last4.($c->recipient_name ? ' for '.$c->recipient_name : '')])->all();
    }

    private function checkAmount(int $amount): void
    {
        if ($amount < self::MIN || $amount > self::MAX) {
            throw new RuntimeException('A gift card can be from '.\App\Support\Money::whole(self::MIN).' to '.\App\Support\Money::whole(self::MAX).'.');
        }
    }

    private function create(int $amount, string $source, ?int $buyer, ?int $staff, array $to, ?string $expires, callable $legs): array
    {
        $raw = 'GC'.collect(range(1, 10))->map(fn () => self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)])->implode('');
        $code = implode('-', str_split($raw, 4));
        $card = DB::transaction(function () use ($amount, $source, $buyer, $staff, $to, $expires, $legs, $raw) {
            $id = DB::table('gift_cards')->insertGetId([
                'public_id' => (string) Str::ulid(), 'code_hash' => self::hash($raw), 'last4' => substr($raw, -4),
                'initial_value' => $amount, 'balance' => $amount, 'source' => $source, 'bought_by' => $buyer, 'issued_by' => $staff,
                'recipient_name' => $to['name'] ?? null, 'recipient_phone' => $to['phone'] ?? null, 'recipient_email' => $to['email'] ?? null,
                'message' => isset($to['message']) ? mb_substr((string) $to['message'], 0, 300) : null,
                'expires_at' => $expires, 'status' => 'active', 'created_at' => now(), 'updated_at' => now(),
            ]);
            $this->ledger->post("gift-card-{$source}-{$id}", [
                'operator_id' => 1, 'kind' => 'gift_card_'.$source, 'reference_type' => 'gift_card', 'reference_id' => $id,
                'description' => 'Gift card ending '.substr($raw, -4), 'posted_by' => $staff ?? $buyer,
            ], $legs($id));

            return DB::table('gift_cards')->find($id);
        });
        $this->send($card, $code, $buyer);

        return ['public_id' => $card->public_id, 'code' => $code, 'last4' => $card->last4, 'balance' => (int) $card->balance, 'expires_at' => $card->expires_at];
    }

    /** Text and email the code to the recipient, when given. Best effort. */
    private function send(object $card, string $code, ?int $buyer): void
    {
        $from = $buyer ? (string) Str::of((string) DB::table('users')->where('id', $buyer)->value('name'))->trim()->explode(' ')->first() : config('app.name');
        $text = "{$from} sent you a ".config('app.name').' gift card worth '.\App\Support\Money::whole($card->initial_value).". Code: {$code}. Use it when you pay for a delivery."
            .($card->message ? " Message: {$card->message}" : '');
        try {
            if ($card->recipient_phone) {
                app(SmsService::class)->toNumber($card->recipient_phone, $text, null, 'gift_card.received');
            }
            if ($card->recipient_email) {
                SendPlainEmail::dispatch($card->recipient_email, 'You have a gift card', $text);
            }
        } catch (Throwable $e) {
            Log::warning("Gift card {$card->id} could not be sent: {$e->getMessage()}");
        }
    }
}
