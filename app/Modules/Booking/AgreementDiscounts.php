<?php

namespace App\Modules\Booking;

use App\Modules\Marketing\ReferralService;
use App\Modules\Pricing\Discounts;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Discounts on a negotiated job, settled before the customer pays: a promo code the customer enters and any corporate
 * discount their account has, or a first-delivery discount for a customer a friend invited. All come off the delivery
 * price only (never goods money or the tip). The platform pays for its own discounts, so the provider still receives
 * what was agreed; escrow records the platform's share. A provider's own promo code is paid by that provider: it comes
 * out of their share when the job pays out (EscrowService).
 */
class AgreementDiscounts
{
    public function __construct(private Discounts $discounts, private ReferralService $referrals)
    {
    }

    /** What the customer will pay, with each discount line. Re-works the corporate part so it is always current. */
    public function summary(int $agreementId, int $customerId): array
    {
        $a = $this->agreement($agreementId, $customerId);
        if ($a->status === 'locked') {
            $this->settle($a, $a->promo_code_id ? DB::table('promo_codes')->where('id', $a->promo_code_id)->first() : null);
            $a = DB::table('agreements')->where('id', $agreementId)->first();
        }

        return $this->shape($a);
    }

    public function applyPromo(int $agreementId, int $customerId, string $code): array
    {
        return DB::transaction(function () use ($agreementId, $customerId, $code) {
            $a = $this->agreement($agreementId, $customerId, true);
            $this->assertPayable($a);
            $promo = $this->discounts->promo($code, $customerId, (int) $a->provider_operator_id);
            if ($this->discounts->promoAmount($promo, (int) $a->price) === 0) {
                throw new RuntimeException($promo->min_order !== null && (int) $a->price < (int) $promo->min_order
                    ? 'This promo code needs a delivery price of at least ₦'.number_format($promo->min_order / 100).'.'
                    : 'That promo code takes nothing off this delivery.');
            }
            $this->settle($a, $promo);
            $this->dropOpenCheckouts($agreementId);

            return $this->shape(DB::table('agreements')->where('id', $agreementId)->first());
        });
    }

    public function removePromo(int $agreementId, int $customerId): array
    {
        return DB::transaction(function () use ($agreementId, $customerId) {
            $a = $this->agreement($agreementId, $customerId, true);
            $this->assertPayable($a);
            $this->settle($a, null);
            $this->dropOpenCheckouts($agreementId);

            return $this->shape(DB::table('agreements')->where('id', $agreementId)->first());
        });
    }

    /** Called just before payment: re-checks the promo (it may have run out) and refreshes the corporate discount. */
    public function settleForPayment(object $a): object
    {
        if ($a->status !== 'locked') {
            return $a;
        }
        $promo = null;
        if ($a->promo_code_id) {
            $code = (string) DB::table('promo_codes')->where('id', $a->promo_code_id)->value('code');
            try {
                $promo = $this->discounts->promo($code, (int) $a->customer_id, (int) $a->provider_operator_id);
            } catch (RuntimeException) {
                $promo = null; // expired or used up since it was added: the customer pays the full price
            }
        }
        $this->settle($a, $promo);

        return DB::table('agreements')->where('id', $a->id)->first();
    }

    private function settle(object $a, ?object $promo): void
    {
        $price = (int) $a->price;
        $lines = [];
        // Merchant jobs were already discounted in their instant quote.
        $corp = $a->merchant_id ? null : $this->discounts->corporate(null, (int) $a->customer_id, (int) $a->provider_operator_id);
        if ($corp && ($amt = $this->discounts->corporateAmount($corp, $price)) > 0) {
            $lines[] = ['kind' => 'corporate', 'label' => $corp->name, 'amount' => $amt];
        }
        if ($promo && ($amt = $this->discounts->promoAmount($promo, $price)) > 0) {
            $byProvider = $promo->funded_by === 'operator';
            // A provider's code can't cost them more than they would have earned on the job.
            $amt = $byProvider ? min($amt, (int) $a->provider_net) : $amt;
            $lines[] = ['kind' => 'promo', 'label' => 'Promo '.$promo->code, 'code' => $promo->code, 'amount' => $amt, 'funded_by' => $byProvider ? 'operator' : 'platform'];
        } elseif (! $a->merchant_id && ($amt = $this->referrals->friendDiscount((int) $a->customer_id, $price)) > 0) {
            // Invited by a friend: money off the first delivery, unless they use a promo code instead.
            $lines[] = ['kind' => 'referral', 'label' => 'Invited by a friend', 'amount' => $amt, 'funded_by' => 'platform'];
        }
        $total = min($price, array_sum(array_column($lines, 'amount')));

        DB::table('agreements')->where('id', $a->id)->update([
            'promo_code_id' => $promo->id ?? null, 'discount' => $total, 'discount_lines' => $lines ? json_encode($lines) : null, 'updated_at' => now(),
        ]);
    }

    /** A checkout opened at the old amount must not be paid: the next pay call opens one at the new amount. */
    private function dropOpenCheckouts(int $agreementId): void
    {
        DB::table('payment_intents')->where('agreement_id', $agreementId)->whereIn('status', ['initiated', 'pending'])->update(['status' => 'abandoned', 'updated_at' => now()]);
    }

    private function assertPayable(object $a): void
    {
        if ($a->status !== 'locked') {
            throw new RuntimeException('Promo codes can only be changed before you pay.');
        }
    }

    private function agreement(int $id, int $customerId, bool $lock = false): object
    {
        $q = DB::table('agreements')->where('id', $id)->where('customer_id', $customerId);
        $a = $lock ? $q->lockForUpdate()->first() : $q->first();
        if (! $a) {
            throw new RuntimeException('Agreement not found.');
        }

        return $a;
    }

    private function shape(object $a): array
    {
        $gross = (int) $a->price + (int) $a->goods_budget + (int) $a->tip;
        $promo = $a->promo_code_id ? DB::table('promo_codes')->where('id', $a->promo_code_id)->value('code') : null;

        return [
            'agreement' => $a->public_id, 'status' => $a->status, 'price' => (int) $a->price, 'goods_budget' => (int) $a->goods_budget, 'tip' => (int) $a->tip,
            'discount' => (int) $a->discount, 'discount_lines' => $a->discount_lines ? json_decode($a->discount_lines, true) : [],
            'promo_code' => $promo, 'payable' => $gross - (int) $a->discount,
        ];
    }
}
