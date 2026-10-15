<?php

namespace App\Modules\Pricing;

use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Promo codes and corporate discounts, shared by instant quotes (QuoteService) and negotiated jobs
 * (AgreementDiscounts). Money is kobo, rates are basis points.
 */
class Discounts
{
    /** The active promo for this code, or an exception that says why it cannot be used. */
    public function promo(string $code, ?int $userId, ?int $operatorId = null): object
    {
        $promo = DB::table('promo_codes')->where('code', strtoupper(trim($code)))->where('active', true)
            ->where(fn ($q) => $q->whereNull('valid_from')->orWhere('valid_from', '<=', now()))
            ->where(fn ($q) => $q->whereNull('valid_to')->orWhere('valid_to', '>=', now()))
            ->first();

        if (! $promo) {
            throw new RuntimeException('That promo code is not valid.');
        }
        if ($promo->operator_id !== null && $operatorId !== null && (int) $promo->operator_id !== $operatorId) {
            throw new RuntimeException('That promo code is not valid with this provider.');
        }
        if ($promo->usage_limit !== null && DB::table('promo_redemptions')->where('promo_code_id', $promo->id)->count() >= $promo->usage_limit) {
            throw new RuntimeException('That promo code has been fully used.');
        }
        if ($promo->budget !== null && (int) $promo->budget_spent >= (int) $promo->budget) {
            throw new RuntimeException('That promo code has been fully used.');
        }
        if ($userId && DB::table('promo_redemptions')->where('promo_code_id', $promo->id)->where('user_id', $userId)->count() >= $promo->per_user_limit) {
            throw new RuntimeException('You have already used this promo code.');
        }
        if ($userId && $promo->type === 'first_order' && DB::table('orders')->where('customer_id', $userId)->where('payment_status', 'paid')->exists()) {
            throw new RuntimeException('This promo code is for your first delivery only.');
        }

        return $promo;
    }

    /** What the promo takes off a delivery price. 0 when the price is under the promo's minimum. */
    public function promoAmount(object $promo, int $price): int
    {
        if ($promo->min_order !== null && $price < $promo->min_order) {
            return 0;
        }

        $discount = match ($promo->type) {
            'percent', 'first_order', 'referral' => intdiv($price * (int) $promo->value, 10_000),
            'fixed' => (int) $promo->value,
            'free_delivery' => $price,
            default => 0,
        };
        $discount = $promo->cap !== null ? min($discount, (int) $promo->cap) : $discount;
        if ($promo->budget !== null) {
            $discount = min($discount, max(0, (int) $promo->budget - (int) $promo->budget_spent));
        }

        return max(0, min($discount, $price));
    }

    /** The best active corporate discount for this business (merchant) or customer with this provider. */
    public function corporate(?int $merchantId, ?int $userId, int $operatorId): ?object
    {
        if (! $merchantId && ! $userId) {
            return null;
        }

        return DB::table('corporate_discounts')->where('active', true)
            ->where(fn ($q) => $q->whereNull('valid_to')->orWhere('valid_to', '>=', now()))
            ->where(fn ($q) => $q->whereNull('operator_id')->orWhere('operator_id', $operatorId))
            ->where(function ($q) use ($merchantId, $userId) {
                if ($merchantId) {
                    $q->orWhere('merchant_id', $merchantId);
                }
                if ($userId) {
                    $q->orWhere('user_id', $userId);
                }
            })
            ->orderByDesc('rate_bp')->first();
    }

    public function corporateAmount(object $row, int $price): int
    {
        $d = intdiv($price * (int) $row->rate_bp, 10_000);

        return max(0, min($row->cap !== null ? min($d, (int) $row->cap) : $d, $price));
    }

    /** Records that a promo was spent on an order. Safe to call twice for the same order. */
    public function redeem(int $promoId, int $userId, int $orderId, int $amount): void
    {
        if ($amount <= 0 || DB::table('promo_redemptions')->where(['promo_code_id' => $promoId, 'order_id' => $orderId])->exists()) {
            return;
        }
        DB::table('promo_redemptions')->insert(['promo_code_id' => $promoId, 'user_id' => $userId, 'order_id' => $orderId, 'amount' => $amount, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('promo_codes')->where('id', $promoId)->increment('budget_spent', $amount);
    }
}
