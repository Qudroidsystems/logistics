<?php

namespace App\Modules\Pricing;

/**
 * Pure price arithmetic: no database, no framework, so it can be unit-tested in isolation.
 *
 * All money is integer kobo. Percentages are basis points (100 = 1%).
 * Every rule that fires is written to the breakdown so a price can always be explained.
 *
 * Rule kinds
 *   base          flat amount
 *   per_km        amount per kilometre (condition: from_km / to_km band)
 *   per_minute    amount per minute of route duration
 *   per_kg        amount per kilogram
 *   per_stop      amount per extra stop beyond the first dropoff
 *   waiting       amount per expected waiting minute
 *   fragile       flat amount when any package is fragile
 *   insurance     rate_bp of declared value
 *   cod_fee       rate_bp of the COD amount (flat `amount` also allowed)
 *   remote_area   flat amount when the dropoff is in a listed zone (condition: zone_ids)
 *   night, peak_hour   rate_bp surcharge on the running subtotal (condition: hours = [0..23])
 */
class PriceCalculator
{
    /**
     * @param  array{min_fee:int, max_fee:?int, rounding_kobo:int}  $plan
     * @param  array<int, array{kind:string, condition?:?array, amount?:?int, rate_bp?:?int, unit?:?string, priority?:int, stackable?:bool}>  $rules
     * @param  array{distance_m:int, duration_s:int, weight_g:int, extra_stops:int, wait_minutes:int, hour:int, fragile:bool, declared_value:int, cod_amount:int, dropoff_zone_ids:int[], vehicle_multiplier_bp:int, surge_multiplier_bp:int, tax_rate_bp:int, discount:int}  $input
     * @return array{subtotal:int, discount:int, tax:int, total:int, lines:array<int, array{label:string, amount:int}>}
     */
    public function calculate(array $plan, array $rules, array $input): array
    {
        $lines = [];
        $running = 0;

        usort($rules, fn ($a, $b) => ($a['priority'] ?? 0) <=> ($b['priority'] ?? 0));

        // First pass: absolute-amount rules.
        foreach ($rules as $rule) {
            if (in_array($rule['kind'], ['night', 'peak_hour'], true)) {
                continue;
            }
            $amount = $this->ruleAmount($rule, $input);
            if ($amount !== 0) {
                $lines[] = ['label' => $rule['kind'], 'amount' => $amount];
                $running += $amount;
            }
        }

        // Second pass: percentage surcharges on what has accumulated so far.
        foreach ($rules as $rule) {
            if (! in_array($rule['kind'], ['night', 'peak_hour'], true)) {
                continue;
            }
            if (! $this->hourMatches($rule['condition'] ?? null, $input['hour'])) {
                continue;
            }
            $amount = intdiv($running * (int) ($rule['rate_bp'] ?? 0), 10_000);
            if ($amount !== 0) {
                $lines[] = ['label' => $rule['kind'], 'amount' => $amount];
                $running += $amount;
            }
        }

        // Vehicle multiplier, then surge.
        foreach ([['vehicle', $input['vehicle_multiplier_bp']], ['surge', $input['surge_multiplier_bp']]] as [$label, $bp]) {
            if ($bp !== 10_000) {
                $adjusted = intdiv($running * $bp, 10_000);
                $lines[] = ['label' => $label, 'amount' => $adjusted - $running];
                $running = $adjusted;
            }
        }

        // Floor and ceiling.
        if ($running < $plan['min_fee']) {
            $lines[] = ['label' => 'minimum_fee', 'amount' => $plan['min_fee'] - $running];
            $running = $plan['min_fee'];
        }
        if ($plan['max_fee'] !== null && $running > $plan['max_fee']) {
            $lines[] = ['label' => 'maximum_fee', 'amount' => $plan['max_fee'] - $running];
            $running = $plan['max_fee'];
        }

        // Round up to the plan's step so customers never see odd kobo.
        $step = max(1, $plan['rounding_kobo']);
        $rounded = (int) (ceil($running / $step) * $step);
        if ($rounded !== $running) {
            $lines[] = ['label' => 'rounding', 'amount' => $rounded - $running];
            $running = $rounded;
        }

        $subtotal = $running;
        $discount = min($input['discount'], $subtotal);
        $taxable = $subtotal - $discount;
        $tax = intdiv($taxable * $input['tax_rate_bp'], 10_000);

        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'tax' => $tax,
            'total' => $taxable + $tax,
            'lines' => $lines,
        ];
    }

    private function ruleAmount(array $rule, array $in): int
    {
        $amount = (int) ($rule['amount'] ?? 0);
        $cond = $rule['condition'] ?? null;

        return match ($rule['kind']) {
            'base' => $amount,
            'per_km' => $this->perKm($amount, $cond, $in['distance_m']),
            'per_minute' => $amount * (int) ceil($in['duration_s'] / 60),
            'per_kg' => $amount * (int) ceil($in['weight_g'] / 1000),
            'per_stop' => $amount * max(0, $in['extra_stops']),
            'waiting' => $amount * max(0, $in['wait_minutes']),
            'fragile' => $in['fragile'] ? $amount : 0,
            'insurance' => intdiv($in['declared_value'] * (int) ($rule['rate_bp'] ?? 0), 10_000),
            'cod_fee' => $in['cod_amount'] > 0 ? $amount + intdiv($in['cod_amount'] * (int) ($rule['rate_bp'] ?? 0), 10_000) : 0,
            'remote_area' => $this->inZones($cond, $in['dropoff_zone_ids']) ? $amount : 0,
            default => 0,
        };
    }

    /** Charge only the kilometres that fall inside the rule's band, so bands can stack into tiers. */
    private function perKm(int $perKm, ?array $cond, int $distanceM): int
    {
        $from = (int) round(($cond['from_km'] ?? 0) * 1000);
        $to = isset($cond['to_km']) ? (int) round($cond['to_km'] * 1000) : PHP_INT_MAX;
        $inBand = max(0, min($distanceM, $to) - $from);

        return (int) ceil($inBand / 1000 * $perKm);
    }

    private function hourMatches(?array $cond, int $hour): bool
    {
        return $cond === null || ! isset($cond['hours']) || in_array($hour, $cond['hours'], true);
    }

    private function inZones(?array $cond, array $zoneIds): bool
    {
        return ! empty($cond['zone_ids']) && count(array_intersect($cond['zone_ids'], $zoneIds)) > 0;
    }
}
