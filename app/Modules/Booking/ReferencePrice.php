<?php

namespace App\Modules\Booking;

use App\Modules\Pricing\NoPricingPlan;
use App\Modules\Pricing\QuoteService;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * The going rate for a trip: what the platform's instant price list (InstantQuoteService::operatorId) charges for the
 * same road distance, time, service, weight and stops. Shown beside provider offers; never charged.
 */
class ReferencePrice
{
    public function __construct(private QuoteService $quotes) {}

    /**
     * @param  array{service_type_id:int, city_id:?int, distance_m:int, duration_s:int, weight_g?:int, vehicle_type_id?:?int, stops?:array, scheduled_for?:?string, fragile?:bool, customer_id?:?int}  $trip
     */
    public function for(array $trip): ?int
    {
        $operatorId = InstantQuoteService::operatorId();
        if (! $operatorId || empty($trip['city_id']) || empty($trip['service_type_id']) || ($trip['distance_m'] ?? 0) <= 0) {
            return null;
        }
        try {
            $q = $this->quotes->quote([
                'operator_id' => $operatorId, 'service_type_id' => (int) $trip['service_type_id'], 'city_id' => (int) $trip['city_id'],
                'customer_id' => $trip['customer_id'] ?? null, 'vehicle_type_id' => $trip['vehicle_type_id'] ?? null,
                'distance_m' => (int) $trip['distance_m'], 'duration_s' => (int) ($trip['duration_s'] ?? 0), 'weight_g' => (int) ($trip['weight_g'] ?? 0),
                'fragile' => (bool) ($trip['fragile'] ?? false), 'stops' => $trip['stops'] ?? [['type' => 'pickup'], ['type' => 'dropoff']],
                'scheduled_for' => $trip['scheduled_for'] ?? null, 'dry_run' => true,
            ]);

            return (int) $q['total'];
        } catch (NoPricingPlan) {
            return null;
        } catch (Throwable $e) {
            Log::info('Reference price not worked out: '.$e->getMessage());

            return null;
        }
    }

    /** Pure: how an offer compares with the going rate, for the customer. */
    public static function compare(?int $offer, ?int $reference): ?string
    {
        if (! $offer || ! $reference) {
            return null;
        }
        $pct = (int) round(($offer - $reference) / $reference * 100);

        return match (true) {
            abs($pct) < 3 => 'About the instant price',
            $pct < 0 => abs($pct).'% below the instant price',
            default => $pct.'% above the instant price',
        };
    }
}
