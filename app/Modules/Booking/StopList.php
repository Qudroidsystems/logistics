<?php

namespace App\Modules\Booking;

use App\Modules\Partner\RouteEstimator;
use InvalidArgumentException;

/**
 * A booking's stops in driving order. The first pickup and the last drop-off are kept under `pickup` and `dropoff`
 * so every older screen keeps working; anything in between (more pickups, more drop-offs) lives in `extra`.
 *
 * Stop shape: {type: pickup|dropoff, line1, lat, lng, contact_name?, contact_phone?, landmark?, instructions?}
 */
class StopList
{
    public const MAX_STOPS = 10;

    private const FIELDS = ['type', 'line1', 'lat', 'lng', 'contact_name', 'contact_phone', 'landmark', 'instructions'];

    public function __construct(private RouteEstimator $routes)
    {
    }

    /** @return list<array> every stop in order, from the stored {pickup, extra?, dropoff} shape */
    public static function ordered(array $stored): array
    {
        $out = [['type' => 'pickup'] + ($stored['pickup'] ?? [])];
        foreach ((array) ($stored['extra'] ?? []) as $s) {
            $out[] = $s;
        }
        $out[] = ['type' => 'dropoff'] + ($stored['dropoff'] ?? []);

        return $out;
    }

    /** Cleans the extra stops a customer sent. Throws when there are too many or one is not a pickup or drop-off. */
    public static function cleanExtra(?array $extra): array
    {
        $extra = array_values((array) $extra);
        if (count($extra) + 2 > self::MAX_STOPS) {
            throw new InvalidArgumentException('A booking can have at most '.self::MAX_STOPS.' stops.');
        }

        return array_map(function ($s) {
            if (! in_array($s['type'] ?? null, ['pickup', 'dropoff'], true)) {
                throw new InvalidArgumentException('Each extra stop must be a pickup or a drop-off.');
            }
            $s = array_intersect_key($s, array_flip(self::FIELDS));
            $s['lat'] = (float) $s['lat'];
            $s['lng'] = (float) $s['lng'];

            return $s;
        }, $extra);
    }

    /** Road distance and time over every leg, in order. @return array{distance_m:int, duration_s:int} */
    public function route(array $ordered): array
    {
        $distance = 0;
        $duration = 0;
        for ($i = 1; $i < count($ordered); $i++) {
            $r = $this->routes->estimate((float) $ordered[$i - 1]['lat'], (float) $ordered[$i - 1]['lng'], (float) $ordered[$i]['lat'], (float) $ordered[$i]['lng']);
            $distance += $r['distance_m'];
            $duration += $r['duration_s'];
        }

        return ['distance_m' => $distance, 'duration_s' => $duration];
    }
}
