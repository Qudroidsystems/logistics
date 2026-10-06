<?php

namespace App\Modules\Pricing;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Builds and stores an immutable quote: finds the right plan, feeds the rules to PriceCalculator,
 * and saves the whole breakdown so the price can be explained and honoured later.
 *
 * Expected $request keys:
 *   operator_id, service_type_id, vehicle_type_id?, city_id, customer_id?, distance_m, duration_s,
 *   weight_g, stops (array of pickup/dropoff points), fragile?, declared_value?, cod_amount?,
 *   dropoff_zone_ids?, wait_minutes?, promo_code?, scheduled_for?, is_test?
 */
class QuoteService
{
    public const QUOTE_TTL_MINUTES = 10;

    public function __construct(private PriceCalculator $calculator)
    {
    }

    public function quote(array $request): array
    {
        $when = isset($request['scheduled_for']) ? CarbonImmutable::parse($request['scheduled_for']) : CarbonImmutable::now();

        $plan = $this->findPlan($request, $when);
        if (! $plan) {
            throw new NoPricingPlan('No active pricing plan for this operator, service and city.');
        }

        $rules = DB::table('pricing_rules')->where('plan_id', $plan->id)->get()
            ->map(fn ($r) => [
                'kind' => $r->kind,
                'condition' => $r->condition ? json_decode($r->condition, true) : null,
                'amount' => $r->amount,
                'rate_bp' => $r->rate_bp,
                'unit' => $r->unit,
                'priority' => $r->priority,
                'stackable' => (bool) $r->stackable,
            ])->all();

        $vehicleBp = $plan->vehicle_type_id ? 10_000 : $this->vehicleMultiplier($request['vehicle_type_id'] ?? null);
        [$surgeBp, $surgeSnapshotId] = $this->surge($request);
        $promo = $this->promo($request);
        $cityTax = (int) DB::table('cities')->where('id', $request['city_id'])->value('tax_rate_bp');

        $stops = $request['stops'] ?? [];
        $input = [
            'distance_m' => (int) $request['distance_m'],
            'duration_s' => (int) $request['duration_s'],
            'weight_g' => (int) ($request['weight_g'] ?? 0),
            'extra_stops' => max(0, count(array_filter($stops, fn ($s) => ($s['type'] ?? '') === 'dropoff')) - 1),
            'wait_minutes' => (int) ($request['wait_minutes'] ?? 0),
            'hour' => (int) $when->setTimezone(config('app.timezone', 'Africa/Lagos'))->format('G'),
            'fragile' => (bool) ($request['fragile'] ?? false),
            'declared_value' => (int) ($request['declared_value'] ?? 0),
            'cod_amount' => (int) ($request['cod_amount'] ?? 0),
            'dropoff_zone_ids' => $request['dropoff_zone_ids'] ?? [],
            'vehicle_multiplier_bp' => $vehicleBp,
            'surge_multiplier_bp' => $surgeBp,
            'tax_rate_bp' => $cityTax,
            'discount' => 0,
        ];

        // Price first without the promo so a percentage promo applies to the pre-discount subtotal.
        $result = $this->calculator->calculate(
            ['min_fee' => (int) $plan->min_fee, 'max_fee' => $plan->max_fee !== null ? (int) $plan->max_fee : null, 'rounding_kobo' => (int) $plan->rounding_kobo],
            $rules,
            $input,
        );
        if ($promo) {
            $input['discount'] = $this->promoDiscount($promo, $result['subtotal']);
            $result = $this->calculator->calculate(
                ['min_fee' => (int) $plan->min_fee, 'max_fee' => $plan->max_fee !== null ? (int) $plan->max_fee : null, 'rounding_kobo' => (int) $plan->rounding_kobo],
                $rules,
                $input,
            );
        }

        $id = DB::table('quotes')->insertGetId([
            'public_id' => (string) Str::ulid(),
            'operator_id' => $request['operator_id'],
            'customer_id' => $request['customer_id'] ?? null,
            'service_type_id' => $request['service_type_id'],
            'vehicle_type_id' => $request['vehicle_type_id'] ?? null,
            'request' => json_encode($request),
            'route_distance_m' => $request['distance_m'],
            'route_duration_s' => $request['duration_s'],
            'plan_id' => $plan->id,
            'plan_version' => $plan->version,
            'surge_snapshot_id' => $surgeSnapshotId,
            'breakdown' => json_encode(['lines' => $result['lines'], 'inputs' => $input]),
            'subtotal' => $result['subtotal'],
            'discount' => $result['discount'],
            'tax' => $result['tax'],
            'total' => $result['total'],
            'promo_code_id' => $promo->id ?? null,
            'is_test' => (bool) ($request['is_test'] ?? false),
            'expires_at' => now()->addMinutes(self::QUOTE_TTL_MINUTES),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return ['quote_id' => $id] + $result;
    }

    private function findPlan(array $r, CarbonImmutable $when): ?object
    {
        return DB::table('pricing_plans')
            ->where('operator_id', $r['operator_id'])
            ->where('service_type_id', $r['service_type_id'])
            ->where('status', 'active')
            ->where(fn ($q) => $q->whereNull('city_id')->orWhere('city_id', $r['city_id']))
            ->where(fn ($q) => $q->whereNull('vehicle_type_id')->orWhere('vehicle_type_id', $r['vehicle_type_id'] ?? 0))
            ->where(fn ($q) => $q->whereNull('valid_from')->orWhere('valid_from', '<=', $when))
            ->where(fn ($q) => $q->whereNull('valid_to')->orWhere('valid_to', '>=', $when))
            // Most specific plan wins: city and vehicle matches first, then newest version.
            ->orderByRaw('(city_id IS NOT NULL) DESC, (vehicle_type_id IS NOT NULL) DESC, version DESC')
            ->first();
    }

    private function vehicleMultiplier(?int $vehicleTypeId): int
    {
        if (! $vehicleTypeId) {
            return 10_000;
        }

        return (int) (DB::table('vehicle_types')->where('id', $vehicleTypeId)->value('price_multiplier_bp') ?? 10_000);
    }

    /** @return array{0:int, 1:?int} multiplier in basis points and the snapshot it came from */
    private function surge(array $r): array
    {
        $zoneId = $r['pickup_zone_id'] ?? null;
        if (! $zoneId) {
            return [10_000, null];
        }

        $snap = DB::table('surge_snapshots')->where('zone_id', $zoneId)
            ->where('computed_at', '>=', now()->subMinutes(5))
            ->orderByDesc('computed_at')->first();

        return $snap ? [max(10_000, (int) $snap->multiplier_bp), (int) $snap->id] : [10_000, null];
    }

    private function promo(array $r): ?object
    {
        if (empty($r['promo_code'])) {
            return null;
        }

        $promo = DB::table('promo_codes')->where('code', strtoupper($r['promo_code']))->where('active', true)
            ->where(fn ($q) => $q->whereNull('valid_from')->orWhere('valid_from', '<=', now()))
            ->where(fn ($q) => $q->whereNull('valid_to')->orWhere('valid_to', '>=', now()))
            ->first();

        if (! $promo) {
            throw new RuntimeException('That promo code is not valid.');
        }
        if ($promo->usage_limit !== null && DB::table('promo_redemptions')->where('promo_code_id', $promo->id)->count() >= $promo->usage_limit) {
            throw new RuntimeException('That promo code has been fully used.');
        }
        if (! empty($r['customer_id']) && DB::table('promo_redemptions')->where('promo_code_id', $promo->id)->where('user_id', $r['customer_id'])->count() >= $promo->per_user_limit) {
            throw new RuntimeException('You have already used this promo code.');
        }

        return $promo;
    }

    private function promoDiscount(object $promo, int $subtotal): int
    {
        if ($promo->min_order !== null && $subtotal < $promo->min_order) {
            return 0;
        }

        $discount = match ($promo->type) {
            'percent' => intdiv($subtotal * (int) $promo->value, 10_000),
            'fixed' => (int) $promo->value,
            'free_delivery' => $subtotal,
            default => 0,
        };

        return $promo->cap !== null ? min($discount, (int) $promo->cap) : $discount;
    }
}
