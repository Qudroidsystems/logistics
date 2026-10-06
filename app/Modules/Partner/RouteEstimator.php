<?php

namespace App\Modules\Partner;

use Illuminate\Support\Facades\DB;

/** Placeholder until a routing engine is wired in: straight line x1.35 for roads, at 25 km/h. */
class RouteEstimator
{
    /** @return array{distance_m:int, duration_s:int} */
    public function estimate(float $lat1, float $lng1, float $lat2, float $lng2): array
    {
        $d = DB::selectOne('SELECT ST_Distance(ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography) AS d', [$lng1, $lat1, $lng2, $lat2])->d;
        $road = (int) round($d * 1.35);

        return ['distance_m' => $road, 'duration_s' => (int) round($road / (25_000 / 3600))];
    }
}
