<?php

namespace App\Modules\Pricing;

/**
 * A provider's own job costing: what a trip costs them to run and what they could charge for it.
 * Pure arithmetic, no database, so the same profile and route always give the same answer.
 *
 * All money is integer kobo, percentages are basis points (100 = 1%), fuel use is litres per 100 km.
 *
 * Three kinds of numbers are kept apart so nothing is charged twice:
 *   costs       fuel, labour, maintenance, other per-job costs, job expenses (tolls, parking) and the empty return trip
 *   surcharges  loading, waiting, fragile handling and insurance: charged to the customer on top of either method
 *   methods     per_km    = base fee + km x rate + job expenses + surcharges
 *               cost_plus = operating cost x (1 + markup) + surcharges
 *               higher_of = the larger of the two (the default, so a long cheap-fuel trip never undercuts cost)
 * The minimum fee is a floor on the chosen price, which is then rounded up to the profile's step.
 * The platform fee and the provider's net and margin are worked out on that final price.
 */
class CostCalculator
{
    public const METHODS = ['per_km', 'cost_plus', 'higher_of'];

    /** Below this margin (as a share of the price) the estimate warns that the job is barely worth it. */
    public const THIN_MARGIN_BP = 1_000;

    /**
     * @param  array{pricing_method:string, base_fee:int, rate_per_km:int, min_fee:int, fuel_price_per_litre:int, fuel_l_per_100km:float|string,
     *               labour_per_job:int, labour_per_hour:int, maintenance_per_km:int, other_per_job:int, loading_fee:int, waiting_per_minute:int,
     *               fragile_surcharge:int, insurance_bp:int, return_trip_bp:int, markup_bp:int, rounding_kobo:int}  $p
     * @param  array{distance_m:int, duration_s:int, wait_minutes?:int, fragile?:bool, declared_value?:int, job_expenses?:int, loading?:bool}  $job
     * @param  ?callable(int):int  $platformFee  price in, platform fee out (null = no fee, e.g. a company install)
     */
    public function calculate(array $p, array $job, ?callable $platformFee = null): array
    {
        $km = max(0, (int) $job['distance_m']) / 1000;
        $hours = max(0, (int) $job['duration_s']) / 3600;
        $expenses = max(0, (int) ($job['job_expenses'] ?? 0));

        // ---- costs
        $fuel = (int) round($km * (float) $p['fuel_l_per_100km'] / 100 * (int) $p['fuel_price_per_litre']);
        $maintenance = (int) round($km * (int) $p['maintenance_per_km']);
        $labour = (int) $p['labour_per_job'] + (int) round($hours * (int) $p['labour_per_hour']);
        $other = (int) $p['other_per_job'];
        // Driving back empty burns fuel and wears the vehicle, but nobody pays for it unless it is priced in.
        $returnTrip = intdiv(($fuel + $maintenance) * max(0, min(10_000, (int) $p['return_trip_bp'])), 10_000);

        $costLines = array_filter([
            'fuel' => $fuel, 'maintenance' => $maintenance, 'labour' => $labour, 'other' => $other,
            'job_expenses' => $expenses, 'return_trip' => $returnTrip,
        ]);
        $operatingCost = array_sum($costLines);

        // ---- surcharges the customer pays on top, whichever method is used
        $surchargeLines = array_filter([
            'loading' => ! empty($job['loading']) ? (int) $p['loading_fee'] : 0,
            'waiting' => max(0, (int) ($job['wait_minutes'] ?? 0)) * (int) $p['waiting_per_minute'],
            'fragile' => ! empty($job['fragile']) ? (int) $p['fragile_surcharge'] : 0,
            'insurance' => intdiv(max(0, (int) ($job['declared_value'] ?? 0)) * (int) $p['insurance_bp'], 10_000),
        ]);
        $surcharges = array_sum($surchargeLines);

        // ---- the two methods
        $perKm = (int) $p['base_fee'] + (int) ceil($km * (int) $p['rate_per_km']) + $expenses + $surcharges;
        $costPlus = $operatingCost + intdiv($operatingCost * (int) $p['markup_bp'], 10_000) + $surcharges;

        $method = in_array($p['pricing_method'], self::METHODS, true) ? $p['pricing_method'] : 'higher_of';
        $chosen = match ($method) {
            'per_km' => $perKm,
            'cost_plus' => $costPlus,
            default => max($perKm, $costPlus),
        };

        $floored = max($chosen, (int) $p['min_fee']);
        $step = max(1, (int) $p['rounding_kobo']);
        $price = (int) (ceil($floored / $step) * $step);

        $fee = $platformFee ? max(0, min($price, (int) $platformFee($price))) : 0;
        $net = $price - $fee;
        $margin = $net - $operatingCost;
        $marginBp = $price > 0 ? intdiv($margin * 10_000, $price) : 0;

        return [
            'distance_m' => (int) $job['distance_m'],
            'duration_s' => (int) $job['duration_s'],
            'method' => $method,
            'costs' => $costLines,
            'operating_cost' => $operatingCost,
            'surcharges' => $surchargeLines,
            'per_km_price' => $perKm,
            'cost_plus_price' => $costPlus,
            'min_fee_applied' => $floored > $chosen,
            'suggested_price' => $price,
            'platform_fee' => $fee,
            'provider_net' => $net,
            'margin' => $margin,
            'margin_bp' => $marginBp,
            'cost_per_km' => $km > 0 ? (int) round($operatingCost / $km) : null,
            'earnings_per_km' => $km > 0 ? (int) round($net / $km) : null,
            'warning' => $this->warning($margin, $marginBp),
        ];
    }

    private function warning(int $margin, int $marginBp): ?string
    {
        if ($margin < 0) {
            return 'loss';
        }

        return $marginBp < self::THIN_MARGIN_BP ? 'thin_margin' : null;
    }
}
