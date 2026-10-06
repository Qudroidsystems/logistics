<?php

namespace App\Modules\Partner;

use App\Modules\Routing\RoutingEngine;

/**
 * Distance and duration for quotes and negotiation. Delegates to the routing engine (road routes when one is
 * configured, a straight-line estimate otherwise). The signature is unchanged so existing callers keep working.
 */
class RouteEstimator
{
    public function __construct(private RoutingEngine $engine)
    {
    }

    /** @return array{distance_m:int, duration_s:int} */
    public function estimate(float $lat1, float $lng1, float $lat2, float $lng2, string $profile = 'car'): array
    {
        $r = $this->engine->route($lat1, $lng1, $lat2, $lng2, $profile);

        return ['distance_m' => $r['distance_m'], 'duration_s' => $r['duration_s']];
    }
}
