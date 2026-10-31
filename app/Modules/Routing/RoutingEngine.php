<?php

namespace App\Modules\Routing;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Road distance and duration between two points.
 * Order of attempts: cache, then the configured engine (OSRM, Google Routes or Mapbox Directions), then a straight-line estimate.
 * It never throws: a routing outage must not stop a customer getting a quote.
 */
class RoutingEngine
{
    /** @return array{distance_m:int, duration_s:int, provider:string} */
    public function route(float $lat1, float $lng1, float $lat2, float $lng2, string $profile = 'car'): array
    {
        $profile = in_array($profile, ['car', 'bike'], true) ? $profile : 'car';
        $a = self::cell($lat1, $lng1);
        $b = self::cell($lat2, $lng2);
        $bucket = (int) now()->format('G');

        $driver = self::activeDriver();
        // Google's and Mapbox's terms don't allow keeping their routes for reuse, so only OSRM answers are cached.
        $cacheable = $driver === 'osrm';
        $hit = $cacheable ? $this->cached($a, $b, $profile, $bucket) : null;
        if ($hit) {
            return $hit;
        }

        if (in_array($driver, ['osrm', 'google', 'mapbox'], true)) {
            try {
                $r = match ($driver) {
                    'google' => $this->google($lat1, $lng1, $lat2, $lng2, $profile),
                    'mapbox' => $this->mapbox($lat1, $lng1, $lat2, $lng2, $profile),
                    default => $this->osrm($lat1, $lng1, $lat2, $lng2, $profile),
                };
                if ($cacheable) {
                    $this->store($a, $b, $profile, $bucket, $r);
                }

                return $r;
            } catch (Throwable $e) {
                Log::warning("Routing engine failed, using straight-line estimate: {$e->getMessage()}");
            }
        }

        // A straight-line answer is not cached: it should be replaced by a real route as soon as the engine is back.
        return $this->straight($lat1, $lng1, $lat2, $lng2);
    }

    /** The engine actually in use: a paid one without its key counts as straight-line (shown on Ops > System). */
    public static function activeDriver(): string
    {
        $d = (string) config('routing.driver', 'auto');
        // Not chosen: use the same paid provider as address search when its key is set, so prices follow real roads.
        if ($d === 'auto' || $d === '') {
            $d = match (true) {
                (string) config('maps.geocoder') === 'google' && (bool) config('maps.google.key') => 'google',
                (bool) config('maps.mapbox.token') => 'mapbox',
                (bool) config('maps.google.key') => 'google',
                default => 'straight',
            };
        }

        return match ($d) {
            'google' => (config('routing.google.key') ?: config('maps.google.key')) ? 'google' : 'straight',
            'mapbox' => config('maps.mapbox.token') ? 'mapbox' : 'straight',
            'osrm' => 'osrm',
            default => 'straight',
        };
    }

    /** Which routing profile a vehicle type uses. Cycles and motorbikes are routed on the bike network when one is configured. */
    public static function profileFor(?string $vehicleTypeCode): string
    {
        return in_array($vehicleTypeCode, ['bicycle', 'motorbike'], true) ? 'bike' : 'car';
    }

    // ---------------------------------------------------------------- pure helpers (unit tested)

    /** ~110 m grid cell id as one integer: lat/lng floored to 0.001 degree. */
    public static function cell(float $lat, float $lng): int
    {
        return (int) floor(($lat + 90) * 1000) * 1_000_000 + (int) floor(($lng + 180) * 1000);
    }

    /** Parses an OSRM /route response. @return array{distance_m:int, duration_s:int} */
    public static function parseOsrm(array $json): array
    {
        $route = $json['routes'][0] ?? null;
        if (($json['code'] ?? null) !== 'Ok' || ! $route || ! isset($route['distance'], $route['duration'])) {
            throw new \RuntimeException('No route found: '.($json['code'] ?? 'unknown'));
        }

        return ['distance_m' => (int) round($route['distance']), 'duration_s' => (int) round($route['duration'])];
    }

    /** Parses a Google Routes API computeRoutes response ("duration": "1234s"). @return array{distance_m:int, duration_s:int} */
    public static function parseGoogle(array $json): array
    {
        $r = $json['routes'][0] ?? null;
        if (! $r || ! isset($r['distanceMeters'], $r['duration'])) {
            throw new \RuntimeException('No route found (Google)');
        }

        return ['distance_m' => (int) $r['distanceMeters'], 'duration_s' => (int) rtrim((string) $r['duration'], 's')];
    }

    /** Parses a Mapbox Directions v5 response. @return array{distance_m:int, duration_s:int} */
    public static function parseMapbox(array $json): array
    {
        $r = $json['routes'][0] ?? null;
        if (($json['code'] ?? null) !== 'Ok' || ! $r) {
            throw new \RuntimeException('No route found (Mapbox): '.($json['code'] ?? 'unknown'));
        }

        return ['distance_m' => (int) round($r['distance']), 'duration_s' => (int) round($r['duration'])];
    }

    /** Pure straight-line fallback from a distance in metres. */
    public static function fallbackFrom(float $metres): array
    {
        $road = (int) round($metres * (float) config('routing.fallback.road_factor', 1.35));
        $speed = (float) config('routing.fallback.speed_kmh', 25) * 1000 / 3600;

        return ['distance_m' => $road, 'duration_s' => (int) round($road / $speed), 'provider' => 'straight'];
    }

    // ---------------------------------------------------------------- engines

    private function osrm(float $lat1, float $lng1, float $lat2, float $lng2, string $profile): array
    {
        $cfg = config('routing.osrm');
        $base = rtrim((string) ($cfg['profiles'][$profile] ?? '') ?: $cfg['base_url'], '/');
        $res = Http::timeout($cfg['timeout'])->connectTimeout(min(1.5, $cfg['timeout']))
            ->get("{$base}/route/v1/driving/{$lng1},{$lat1};{$lng2},{$lat2}", ['overview' => 'false', 'alternatives' => 'false']);
        $res->throw();

        return self::parseOsrm($res->json()) + ['provider' => 'osrm'];
    }

    /** Google Routes API (needs "Routes API" on the key in config('maps.google.key') or ROUTING_GOOGLE_KEY). */
    private function google(float $lat1, float $lng1, float $lat2, float $lng2, string $profile): array
    {
        $key = (string) (config('routing.google.key') ?: config('maps.google.key'));
        $res = Http::timeout((float) config('routing.osrm.timeout', 2.5) + 1.5)->withHeaders([
            'X-Goog-Api-Key' => $key, 'X-Goog-FieldMask' => 'routes.distanceMeters,routes.duration',
        ])->post('https://routes.googleapis.com/directions/v2:computeRoutes', [
            'origin' => ['location' => ['latLng' => ['latitude' => $lat1, 'longitude' => $lng1]]],
            'destination' => ['location' => ['latLng' => ['latitude' => $lat2, 'longitude' => $lng2]]],
            'travelMode' => $profile === 'bike' ? 'TWO_WHEELER' : 'DRIVE',
            'routingPreference' => config('routing.google.traffic') ? 'TRAFFIC_AWARE' : 'TRAFFIC_UNAWARE',
        ]);
        $res->throw();

        return self::parseGoogle($res->json()) + ['provider' => 'google'];
    }

    /** Mapbox Directions (token in MAPBOX_TOKEN). */
    private function mapbox(float $lat1, float $lng1, float $lat2, float $lng2, string $profile): array
    {
        $p = $profile === 'bike' ? 'cycling' : (config('routing.mapbox.traffic') ? 'driving-traffic' : 'driving');
        $res = Http::timeout((float) config('routing.osrm.timeout', 2.5) + 1.5)
            ->get("https://api.mapbox.com/directions/v5/mapbox/{$p}/{$lng1},{$lat1};{$lng2},{$lat2}", ['overview' => 'false', 'alternatives' => 'false', 'access_token' => (string) config('maps.mapbox.token')]);
        $res->throw();

        return self::parseMapbox($res->json()) + ['provider' => 'mapbox'];
    }

    private function straight(float $lat1, float $lng1, float $lat2, float $lng2): array
    {
        $d = DB::selectOne('SELECT ST_Distance(ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography) AS d', [$lng1, $lat1, $lng2, $lat2])->d;

        return self::fallbackFrom((float) $d);
    }

    // ---------------------------------------------------------------- cache (route_cache)

    private function cached(int $a, int $b, string $profile, int $bucket): ?array
    {
        $row = DB::table('route_cache')->where(['origin_h3' => $a, 'dest_h3' => $b, 'profile' => $profile, 'hour_bucket' => $bucket])->where('expires_at', '>', now())->first();

        return $row ? ['distance_m' => (int) $row->distance_m, 'duration_s' => (int) $row->duration_s, 'provider' => $row->provider] : null;
    }

    private function store(int $a, int $b, string $profile, int $bucket, array $r): void
    {
        try {
            DB::transaction(fn () => DB::table('route_cache')->upsert([[
                'origin_h3' => $a, 'dest_h3' => $b, 'profile' => $profile, 'hour_bucket' => $bucket, 'distance_m' => $r['distance_m'], 'duration_s' => $r['duration_s'],
                'provider' => $r['provider'], 'expires_at' => now()->addHours((int) config('routing.cache_hours', 168)), 'created_at' => now(), 'updated_at' => now(),
            ]], ['origin_h3', 'dest_h3', 'profile', 'hour_bucket'], ['distance_m', 'duration_s', 'provider', 'expires_at', 'updated_at']));
        } catch (Throwable $e) {
            Log::warning("Route cache write failed: {$e->getMessage()}");
        }
    }
}
