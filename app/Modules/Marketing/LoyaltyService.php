<?php

namespace App\Modules\Marketing;

use App\Modules\Notifications\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Loyalty points. Customers earn points on each finished delivery they paid for and can spend them at payment for
 * money off the delivery price. The platform pays for the points (promo_expense, through the escrow top-up like a
 * promo code). Off until staff switch it on and set the rates on the Promo codes page.
 */
class LoyaltyService
{
    public const KEY = 'loyalty.settings';

    public const DEFAULTS = [
        'enabled' => false,
        // Points earned for each 100 (₦100, $100) of delivery price the customer paid.
        'earn_per_100' => 1,
        // What one point is worth when spent, in kobo.
        'point_value' => 100,
        // Points needed before any can be spent.
        'min_redeem' => 500,
        // The most of one delivery price points can pay for, in basis points (5000 = half).
        'max_share_bp' => 5000,
    ];

    public function __construct(private NotificationService $notify) {}

    public static function settings(): array
    {
        $v = DB::table('platform_settings')->whereNull('operator_id')->where('scope', 'global')->where('key', self::KEY)->value('value');

        return array_merge(self::DEFAULTS, $v ? (array) json_decode($v, true) : []);
    }

    public static function save(array $s, int $userId): void
    {
        DB::table('platform_settings')->updateOrInsert(
            ['operator_id' => null, 'scope' => 'global', 'scope_id' => null, 'key' => self::KEY],
            ['value' => json_encode(array_intersect_key($s, self::DEFAULTS) + self::settings()), 'updated_by' => $userId, 'updated_at' => now()]
        );
    }

    public function balance(int $userId): int
    {
        return (int) DB::table('loyalty_points')->where('user_id', $userId)->sum('points');
    }

    /** Money off for an agreement whose customer chose to use points, at most $left (what other discounts left of the price). */
    public function discountFor(object $a, int $left): int
    {
        $s = self::settings();
        if (! $s['enabled'] || ! $a->use_points || $a->merchant_id || $left <= 0 || (int) $s['point_value'] <= 0) {
            return 0;
        }
        $points = $this->balance((int) $a->customer_id);
        if ($points < (int) $s['min_redeem']) {
            return 0;
        }
        $cap = min($left, intdiv((int) $a->price * (int) $s['max_share_bp'], 10_000));
        $use = min($points, intdiv($cap, (int) $s['point_value']));

        return $use * (int) $s['point_value'];
    }

    /** When the order is made: take the points the payment used. Safe to call twice. */
    public function spend(object $a, int $amount): void
    {
        $value = max(1, (int) self::settings()['point_value']);
        if ($amount <= 0) {
            return;
        }
        DB::table('loyalty_points')->insertOrIgnore([
            'user_id' => $a->customer_id, 'points' => -intdiv($amount + $value - 1, $value), 'amount' => $amount, 'kind' => 'redeem',
            'agreement_id' => $a->id, 'note' => "Spent on agreement {$a->number}", 'created_at' => now(),
        ]);
    }

    /** A refund gave the platform's top-up back: return the customer's points, up to $kobo worth. Returns what was used. */
    public function restore(int $agreementId, int $kobo): int
    {
        $spent = DB::table('loyalty_points')->where('agreement_id', $agreementId)->where('kind', 'redeem')->first();
        if (! $spent || $kobo <= 0 || DB::table('loyalty_points')->where('agreement_id', $agreementId)->where('kind', 'restore')->exists()) {
            return 0;
        }
        $back = min((int) $spent->amount, $kobo);
        $points = (int) round(-$spent->points * $back / max(1, (int) $spent->amount));
        DB::table('loyalty_points')->insert([
            'user_id' => $spent->user_id, 'points' => $points, 'amount' => $back, 'kind' => 'restore',
            'agreement_id' => $agreementId, 'note' => 'Given back after a refund', 'created_at' => now(),
        ]);

        return $back;
    }

    /** When a job pays out: points for what the customer paid for the delivery. Best effort. */
    public function earnFor(int $agreementId): void
    {
        try {
            $s = self::settings();
            $a = DB::table('agreements')->where('id', $agreementId)->first(['id', 'customer_id', 'merchant_id', 'price', 'discount', 'number', 'is_test']);
            if (! $s['enabled'] || ! $a || $a->is_test || $a->merchant_id) {
                return;
            }
            $points = intdiv(max(0, (int) $a->price - (int) $a->discount) * (int) $s['earn_per_100'], 10_000);
            if ($points <= 0) {
                return;
            }
            $made = DB::table('loyalty_points')->insertOrIgnore([
                'user_id' => $a->customer_id, 'points' => $points, 'kind' => 'earn', 'agreement_id' => $a->id,
                'note' => "Delivery {$a->number}", 'created_at' => now(),
            ]);
            if ($made) {
                $this->notify->notify((int) $a->customer_id, 'loyalty.earned', ['points' => number_format($points), 'balance' => number_format($this->balance((int) $a->customer_id))], '/loyalty');
            }
        } catch (Throwable $e) {
            Log::warning("Loyalty points for agreement {$agreementId} failed: {$e->getMessage()}");
        }
    }

    /** Staff add or take away points, with a reason. */
    public function adjust(int $userId, int $points, string $note, int $staffId): void
    {
        DB::table('loyalty_points')->insert(['user_id' => $userId, 'points' => $points, 'kind' => 'adjust', 'note' => mb_substr($note, 0, 255), 'created_by' => $staffId, 'created_at' => now()]);
    }

    /** The customer's points screen. */
    public function forCustomer(int $userId): array
    {
        $s = self::settings();
        $balance = $this->balance($userId);
        $naira = fn ($k) => \App\Support\Money::symbol().number_format(((int) $k) / 100);
        $history = DB::table('loyalty_points')->where('user_id', $userId)->orderByDesc('id')->limit(50)->get(['points', 'kind', 'note', 'created_at']);

        return [
            'enabled' => (bool) $s['enabled'], 'balance' => $balance, 'worth' => $balance * (int) $s['point_value'],
            'min_redeem' => (int) $s['min_redeem'], 'can_spend' => $s['enabled'] && $balance >= (int) $s['min_redeem'],
            'how' => $s['enabled']
                ? "You earn {$s['earn_per_100']} point for every ".\App\Support\Money::whole(10_000)." you pay for deliveries. Each point is worth {$naira($s['point_value'])} off a delivery once you have ".number_format($s['min_redeem']).' points.'
                : 'Loyalty points are not running right now.',
            'history' => $history->map(fn ($r) => ['points' => (int) $r->points, 'kind' => $r->kind, 'label' => $r->note ?? ucfirst($r->kind), 'at' => $r->created_at])->all(),
        ];
    }
}
