<?php

namespace App\Modules\Subscriptions;

use App\Modules\Booking\InstantQuoteService;
use App\Modules\Notifications\NotificationService;
use App\Modules\Payments\Ledger\AccountResolver;
use App\Modules\Payments\Ledger\InsufficientFunds;
use App\Modules\Payments\Ledger\LedgerPoster;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Business plans: a monthly fee, taken from the customer's wallet, that includes a number of deliveries.
 *
 * At checkout (AgreementDiscounts) an included delivery is a 100% discount line of kind "subscription", and a delivery
 * after the allowance is priced at the plan's rate (kind "subscription_extra"). The platform funds the discount as it
 * does for promos; the fee is subscription_revenue. A plan with priority dispatch marks its jobs priority 1.
 * Only deliveries from the platform's own service (the instant price list) are covered.
 */
class SubscriptionService
{
    public const LIVE = ['active', 'past_due'];

    public function __construct(private LedgerPoster $ledger, private AccountResolver $accounts, private NotificationService $notifications) {}

    // ---------------------------------------------------------------- plans

    public function plans(bool $all = false): array
    {
        return DB::table('subscription_plans')->when(! $all, fn ($q) => $q->where('active', true))->orderBy('sort')->orderBy('price')->get()
            ->map(fn ($p) => $this->presentPlan($p))->all();
    }

    public function presentPlan(object $p): array
    {
        return [
            'id' => (int) $p->id, 'code' => $p->code, 'name' => $p->name, 'tagline' => $p->tagline, 'price' => (int) $p->price, 'price_text' => Money::format((int) $p->price, true),
            'included' => (int) $p->included_deliveries, 'overage_price' => $p->overage_price !== null ? (int) $p->overage_price : null,
            'overage_text' => $p->overage_price !== null ? Money::format((int) $p->overage_price, true) : null,
            'max_km' => $p->max_km !== null ? (int) $p->max_km : null, 'priority' => (bool) $p->priority_dispatch, 'contact_only' => (bool) $p->contact_only,
            'features' => json_decode((string) $p->features, true) ?: [], 'active' => (bool) $p->active,
        ];
    }

    // ---------------------------------------------------------------- a customer's plan

    /** The customer's live plan (active or past due), or null. */
    public function live(int $userId): ?object
    {
        return DB::table('subscriptions as s')->join('subscription_plans as p', 'p.id', '=', 's.plan_id')
            ->where('s.user_id', $userId)->whereIn('s.status', self::LIVE)
            ->first(['s.*', 'p.code', 'p.name', 'p.overage_price', 'p.max_km', 'p.priority_dispatch', 'p.included_deliveries']);
    }

    /** What the account page and the app show. */
    public function summary(int $userId): array
    {
        $s = $this->live($userId);
        $next = $s && $s->next_plan_id ? DB::table('subscription_plans')->where('id', $s->next_plan_id)->value('name') : null;

        return [
            'plans' => $this->plans(),
            'current' => $s ? [
                'id' => $s->public_id, 'plan' => $s->code, 'name' => $s->name, 'status' => $s->status,
                'period_start' => CarbonImmutable::parse($s->current_period_start)->toIso8601String(), 'period_end' => CarbonImmutable::parse($s->current_period_end)->toIso8601String(),
                'used' => (int) $s->used, 'included' => (int) $s->period_included, 'left' => max(0, (int) $s->period_included - (int) $s->used),
                'price' => (int) $s->period_price, 'price_text' => Money::format((int) $s->period_price, true),
                'overage_text' => $s->overage_price !== null ? Money::format((int) $s->overage_price, true) : null,
                'priority' => (bool) $s->priority_dispatch, 'max_km' => $s->max_km !== null ? (int) $s->max_km : null,
                'cancel_at_period_end' => (bool) $s->cancel_at_period_end, 'next_plan' => $next,
            ] : null,
            'uses' => $s ? DB::table('subscription_uses as u')->leftJoin('orders as o', 'o.id', '=', 'u.order_id')
                ->where('u.subscription_id', $s->id)->where('u.period_start', $s->current_period_start)->orderByDesc('u.id')->limit(100)
                ->get(['u.kind', 'u.discount', 'u.returned', 'u.created_at', 'o.order_number'])->all() : [],
        ];
    }

    /** Starts a plan now, taking the first month's fee from the wallet. */
    public function subscribe(int $userId, string $planCode, ?CarbonImmutable $now = null): string
    {
        $now ??= CarbonImmutable::now();
        $plan = $this->buyable($planCode);
        $publicId = (string) Str::ulid();
        DB::transaction(function () use ($userId, $plan, $now, $publicId) {
            DB::table('users')->where('id', $userId)->lockForUpdate()->first();
            if ($this->live($userId)) {
                throw new RuntimeException('You already have a plan. Change it from your plan page instead.');
            }
            $end = $now->addMonthNoOverflow();
            $id = DB::table('subscriptions')->insertGetId([
                'public_id' => $publicId, 'user_id' => $userId, 'plan_id' => $plan->id, 'status' => 'active',
                'current_period_start' => $now, 'current_period_end' => $end, 'used' => 0,
                'period_price' => (int) $plan->price, 'period_included' => (int) $plan->included_deliveries, 'created_at' => $now, 'updated_at' => $now,
            ]);
            $this->charge($id, $userId, (int) $plan->price, $now, $end, true);
        });
        $this->notifications->notify($userId, 'subscription.started', ['plan' => $plan->name, 'included' => $plan->included_deliveries, 'amount' => Money::format((int) $plan->price, true)], '/account/plan');

        return $publicId;
    }

    /** Switches plan from the next renewal (this month's deliveries and fee are unchanged). */
    public function change(int $userId, string $planCode): void
    {
        $plan = $this->buyable($planCode);
        $s = $this->live($userId) ?? throw new RuntimeException('You do not have a plan yet.');
        DB::table('subscriptions')->where('id', $s->id)->update([
            'next_plan_id' => (int) $plan->id === (int) $s->plan_id ? null : $plan->id, 'cancel_at_period_end' => false, 'updated_at' => now(),
        ]);
    }

    /** Ends the plan at the end of the month already paid for. */
    public function cancel(int $userId): void
    {
        $s = $this->live($userId) ?? throw new RuntimeException('You do not have a plan.');
        if ($s->status === 'past_due') {
            // Nothing paid for this month: stop now.
            DB::table('subscriptions')->where('id', $s->id)->update(['status' => 'cancelled', 'cancelled_at' => now(), 'updated_at' => now()]);

            return;
        }
        DB::table('subscriptions')->where('id', $s->id)->update(['cancel_at_period_end' => true, 'next_plan_id' => null, 'updated_at' => now()]);
    }

    public function resume(int $userId): void
    {
        $s = $this->live($userId) ?? throw new RuntimeException('You do not have a plan.');
        DB::table('subscriptions')->where('id', $s->id)->update(['cancel_at_period_end' => false, 'updated_at' => now()]);
    }

    /** A past-due plan: try the fee again now (after a top-up). The new month starts today. */
    public function payNow(int $userId, ?CarbonImmutable $now = null): void
    {
        $s = $this->live($userId);
        if (! $s || $s->status !== 'past_due') {
            throw new RuntimeException('Your plan is already paid up.');
        }
        $this->renewOne((int) $s->id, $now ?? CarbonImmutable::now(), true);
    }

    /** Hourly: start the next month for plans whose month has ended (subscriptions:renew). Returns how many were handled. */
    public function renewDue(?CarbonImmutable $now = null): int
    {
        $now ??= CarbonImmutable::now();
        $ids = DB::table('subscriptions')->whereIn('status', self::LIVE)->where('current_period_end', '<=', $now)->limit(500)->pluck('id');
        foreach ($ids as $id) {
            try {
                $this->renewOne((int) $id, $now, false);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("Plan renewal {$id}: {$e->getMessage()}");
            }
        }

        return $ids->count();
    }

    private function renewOne(int $id, CarbonImmutable $now, bool $strict): void
    {
        $event = null;
        DB::transaction(function () use ($id, $now, $strict, &$event) {
            $s = DB::table('subscriptions')->where('id', $id)->lockForUpdate()->first();
            if (! $s || ! in_array($s->status, self::LIVE, true) || ($s->status === 'active' && CarbonImmutable::parse($s->current_period_end)->gt($now))) {
                return;
            }
            if ($s->cancel_at_period_end) {
                DB::table('subscriptions')->where('id', $id)->update(['status' => 'cancelled', 'cancelled_at' => $now, 'updated_at' => $now]);
                $event = ['subscription.cancelled', []];

                return;
            }
            $plan = DB::table('subscription_plans')->where('id', $s->next_plan_id ?: $s->plan_id)->first();
            if (! $plan || ! $plan->active || $plan->contact_only) {
                DB::table('subscriptions')->where('id', $id)->update(['status' => 'cancelled', 'cancelled_at' => $now, 'updated_at' => $now]);
                $event = ['subscription.cancelled', []];

                return;
            }
            // A plan that fell behind starts its new month on the day it is paid.
            $start = $s->status === 'past_due' ? $now : CarbonImmutable::parse($s->current_period_end);
            $end = $start->addMonthNoOverflow();
            try {
                // Retries of a plan already behind are not logged again each hour.
                $this->charge($id, (int) $s->user_id, (int) $plan->price, $start, $end, $strict, $s->status === 'active');
            } catch (InsufficientFunds $e) {
                if ($strict) {
                    throw new RuntimeException('Your wallet does not have '.Money::format((int) $plan->price, true).'. Top it up and try again.');
                }
                if ($s->status === 'active') {
                    DB::table('subscriptions')->where('id', $id)->update(['status' => 'past_due', 'updated_at' => $now]);
                    $event = ['subscription.past_due', ['amount' => Money::format((int) $plan->price, true)]];
                }

                return;
            }
            DB::table('subscriptions')->where('id', $id)->update([
                'status' => 'active', 'plan_id' => $plan->id, 'next_plan_id' => null, 'current_period_start' => $start, 'current_period_end' => $end,
                'used' => 0, 'period_price' => (int) $plan->price, 'period_included' => (int) $plan->included_deliveries, 'updated_at' => $now,
            ]);
            $event = ['subscription.renewed', ['plan' => $plan->name, 'included' => $plan->included_deliveries, 'amount' => Money::format((int) $plan->price, true)]];
        });
        if ($event) {
            $userId = (int) DB::table('subscriptions')->where('id', $id)->value('user_id');
            $this->notifications->notify($userId, $event[0], $event[1] + ['plan' => DB::table('subscriptions as s')->join('subscription_plans as p', 'p.id', '=', 's.plan_id')->where('s.id', $id)->value('p.name')], '/account/plan');
        }
    }

    /** Takes the fee from the wallet. A short wallet throws InsufficientFunds (and is recorded as a failed charge unless $strict). */
    private function charge(int $subscriptionId, int $userId, int $amount, CarbonImmutable $start, CarbonImmutable $end, bool $strict, bool $logFailure = true): void
    {
        if ($amount > 0) {
            try {
                DB::transaction(fn () => $this->ledger->post("subscription-{$subscriptionId}-".$start->format('YmdHis'), [
                    'operator_id' => 1, 'kind' => 'subscription_fee', 'reference_type' => 'subscription', 'reference_id' => $subscriptionId,
                    'description' => 'Business plan '.$start->format('j M').' to '.$end->format('j M Y'), 'posted_by' => $userId,
                ], [
                    ['account_id' => $this->accounts->wallet('customer', $userId, 1), 'direction' => 'debit', 'amount' => $amount],
                    ['account_id' => $this->accounts->platform('subscription_revenue'), 'direction' => 'credit', 'amount' => $amount],
                ]));
            } catch (InsufficientFunds $e) {
                if (! $strict && $logFailure) {
                    DB::table('subscription_charges')->insertOrIgnore(['subscription_id' => $subscriptionId, 'period_start' => $start, 'period_end' => $end, 'amount' => $amount,
                        'status' => 'failed', 'note' => 'Wallet balance too low', 'created_at' => now(), 'updated_at' => now()]);
                }
                throw $strict ? new InsufficientFunds('Your wallet does not have '.Money::format($amount, true).'. Top it up first, then start the plan.') : $e;
            }
        }
        DB::table('subscription_charges')->insertOrIgnore(['subscription_id' => $subscriptionId, 'period_start' => $start, 'period_end' => $end, 'amount' => $amount,
            'status' => 'paid', 'created_at' => now(), 'updated_at' => now()]);
    }

    private function buyable(string $code): object
    {
        $plan = DB::table('subscription_plans')->where('code', $code)->where('active', true)->first();
        if (! $plan) {
            throw new RuntimeException('That plan is not available.');
        }
        if ($plan->contact_only) {
            throw new RuntimeException('Talk to us to set up '.$plan->name.'.');
        }

        return $plan;
    }

    // ---------------------------------------------------------------- deliveries

    /**
     * The checkout line a plan gives this agreement, or null: the whole price while deliveries are left this month,
     * then the plan's lower rate for extra ones. Only the platform's own service, within the plan's distance.
     */
    public function lineFor(object $agreement): ?array
    {
        if ($agreement->merchant_id || (int) $agreement->provider_operator_id !== (int) InstantQuoteService::operatorId()) {
            return null;
        }
        $s = $this->live((int) $agreement->customer_id);
        if (! $s || $s->status !== 'active' || CarbonImmutable::parse($s->current_period_end)->isPast()) {
            return null;
        }
        if ($s->max_km !== null && (int) $agreement->distance_m > (int) $s->max_km * 1000) {
            return null;
        }
        $price = (int) $agreement->price;
        if ((int) $s->used < (int) $s->period_included) {
            return ['kind' => 'subscription', 'label' => $s->name.' plan: delivery '.((int) $s->used + 1).' of '.(int) $s->period_included, 'amount' => $price, 'funded_by' => 'platform'];
        }
        if ($s->overage_price !== null && (int) $s->overage_price < $price) {
            return ['kind' => 'subscription_extra', 'label' => $s->name.' plan rate for extra deliveries', 'amount' => $price - (int) $s->overage_price, 'funded_by' => 'platform'];
        }

        return null;
    }

    /** The order was made: count the delivery against the plan and give it priority dispatch if the plan has it. Inside the order transaction. */
    public function recordUse(object $agreement, int $orderId, int $shipmentId): void
    {
        $line = collect(json_decode((string) $agreement->discount_lines, true) ?: [])->first(fn ($l) => in_array($l['kind'] ?? '', ['subscription', 'subscription_extra'], true));
        $s = DB::table('subscriptions')->where('user_id', $agreement->customer_id)->whereIn('status', self::LIVE)->lockForUpdate()->first();
        if (! $s) {
            return;
        }
        if (DB::table('subscription_plans')->where('id', $s->plan_id)->value('priority_dispatch') && (int) $agreement->provider_operator_id === (int) InstantQuoteService::operatorId()) {
            DB::table('shipments')->where('id', $shipmentId)->update(['priority' => 1]);
        }
        if (! $line) {
            return;
        }
        $included = $line['kind'] === 'subscription';
        $n = DB::table('subscription_uses')->insertOrIgnore([
            'subscription_id' => $s->id, 'agreement_id' => $agreement->id, 'order_id' => $orderId, 'kind' => $included ? 'included' : 'extra',
            'discount' => (int) $line['amount'], 'period_start' => $s->current_period_start, 'created_at' => now(), 'updated_at' => now(),
        ]);
        if ($n && $included) {
            DB::table('subscriptions')->where('id', $s->id)->increment('used', 1, ['updated_at' => now()]);
        }
    }

    /** A cancelled delivery this month gives its included delivery back. */
    public function giveBack(int $shipmentId): void
    {
        $orderId = DB::table('shipments')->where('id', $shipmentId)->value('order_id');
        $use = $orderId ? DB::table('subscription_uses')->where('order_id', $orderId)->where('kind', 'included')->where('returned', false)->first() : null;
        if (! $use) {
            return;
        }
        $s = DB::table('subscriptions')->where('id', $use->subscription_id)->lockForUpdate()->first();
        if ($s && CarbonImmutable::parse($s->current_period_start)->equalTo(CarbonImmutable::parse($use->period_start)) && (int) $s->used > 0) {
            DB::table('subscriptions')->where('id', $s->id)->decrement('used', 1, ['updated_at' => now()]);
        }
        DB::table('subscription_uses')->where('id', $use->id)->update(['returned' => true, 'updated_at' => now()]);
    }
}
