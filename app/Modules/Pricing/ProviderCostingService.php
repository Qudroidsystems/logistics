<?php

namespace App\Modules\Pricing;

use App\Modules\Marketplace\CommissionResolver;
use App\Modules\Routing\RoutingEngine;
use App\Support\Platform;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

/**
 * The provider's pricing calculator: their cost profiles, and estimates worked out on the server from a real route.
 *
 * The browser and the apps may preview a figure, but the number a provider relies on comes from here,
 * frozen with the profile version and route that produced it.
 */
class ProviderCostingService
{
    public const MAX_STOPS = 10;

    /** Profile fields a provider sets, money in kobo. */
    public const FIELDS = [
        'base_fee', 'rate_per_km', 'min_fee', 'fuel_price_per_litre', 'labour_per_job', 'labour_per_hour', 'maintenance_per_km',
        'other_per_job', 'loading_fee', 'waiting_per_minute', 'fragile_surcharge', 'insurance_bp', 'return_trip_bp', 'markup_bp', 'rounding_kobo',
    ];

    public function __construct(private CostCalculator $calculator, private RoutingEngine $routing, private CommissionResolver $commission)
    {
    }

    // ---------------------------------------------------------------- profiles

    public function profiles(int $opId)
    {
        return DB::table('provider_cost_profiles')->where(['operator_id' => $opId, 'active' => true])
            ->orderByDesc('is_default')->orderBy('name')->get();
    }

    /** Creates a profile, or updates one and bumps its version. The first profile becomes the default. */
    public function saveProfile(int $opId, array $d, ?string $publicId = null): object
    {
        $method = $d['pricing_method'] ?? 'higher_of';
        if (! in_array($method, CostCalculator::METHODS, true)) {
            throw new InvalidArgumentException('Unknown pricing method.');
        }
        if (! empty($d['vehicle_type_id']) && ! DB::table('vehicle_types')->where('id', $d['vehicle_type_id'])->exists()) {
            throw new InvalidArgumentException('Unknown vehicle type.');
        }
        $row = ['name' => $d['name'], 'vehicle_type_id' => $d['vehicle_type_id'] ?? null, 'pricing_method' => $method,
            'fuel_l_per_100km' => (float) ($d['fuel_l_per_100km'] ?? 0), 'updated_at' => now()];
        foreach (self::FIELDS as $f) {
            $row[$f] = max(0, (int) ($d[$f] ?? ($f === 'rounding_kobo' ? 5000 : 0)));
        }

        return DB::transaction(function () use ($opId, $row, $publicId, $d) {
            if ($publicId) {
                $p = DB::table('provider_cost_profiles')->where(['public_id' => $publicId, 'operator_id' => $opId, 'active' => true])->lockForUpdate()->first();
                if (! $p) {
                    throw new RuntimeException('Cost profile not found.');
                }
                DB::table('provider_cost_profiles')->where('id', $p->id)->update($row + ['version' => $p->version + 1]);
                $id = $p->id;
            } else {
                $first = ! DB::table('provider_cost_profiles')->where(['operator_id' => $opId, 'active' => true])->exists();
                $id = DB::table('provider_cost_profiles')->insertGetId($row + [
                    'public_id' => (string) Str::ulid(), 'operator_id' => $opId, 'is_default' => $first, 'created_at' => now(),
                ]);
            }
            if (! empty($d['is_default'])) {
                $this->makeDefault($opId, $id);
            }

            return DB::table('provider_cost_profiles')->find($id);
        });
    }

    /** Profiles are retired, not deleted: saved estimates still point at them. */
    public function retireProfile(int $opId, string $publicId): void
    {
        DB::transaction(function () use ($opId, $publicId) {
            $p = DB::table('provider_cost_profiles')->where(['public_id' => $publicId, 'operator_id' => $opId, 'active' => true])->first();
            if (! $p) {
                throw new RuntimeException('Cost profile not found.');
            }
            DB::table('provider_cost_profiles')->where('id', $p->id)->update(['active' => false, 'is_default' => false, 'updated_at' => now()]);
            if ($p->is_default && $next = DB::table('provider_cost_profiles')->where(['operator_id' => $opId, 'active' => true])->orderBy('id')->value('id')) {
                $this->makeDefault($opId, (int) $next);
            }
        });
    }

    private function makeDefault(int $opId, int $id): void
    {
        DB::table('provider_cost_profiles')->where('operator_id', $opId)->where('id', '!=', $id)->update(['is_default' => false]);
        DB::table('provider_cost_profiles')->where('id', $id)->update(['is_default' => true]);
    }

    // ---------------------------------------------------------------- estimates

    /**
     * Works out and saves an estimate.
     *
     * $job: profile (public id, optional: the default profile otherwise), stops [{lat,lng}...] or distance_km (+ duration_min),
     *       round_trip?, wait_minutes?, fragile?, loading?, declared_value? (kobo), job_expenses? (kobo), service_type_id?, city_id?
     */
    public function estimate(int $opId, ?int $userId, array $job, ?int $requestId = null): array
    {
        $profile = $this->profile($opId, $job['profile'] ?? null);
        $route = $this->route($job, $profile);

        $op = DB::table('operators')->where('id', $opId)->first(['id', 'type']);
        $serviceTypeId = (int) ($job['service_type_id'] ?? 0);
        $cityId = $job['city_id'] ?? null;
        $fee = Platform::marketplace()
            ? fn (int $price) => $this->commission->resolve($opId, (string) $op->type, $serviceTypeId, $cityId, $price)['fee']
            : null;

        $snapshot = array_intersect_key((array) $profile, array_flip([...self::FIELDS, 'name', 'pricing_method', 'fuel_l_per_100km', 'vehicle_type_id', 'version']));
        $result = $this->calculator->calculate($snapshot, [
            'distance_m' => $route['distance_m'], 'duration_s' => $route['duration_s'],
            'wait_minutes' => (int) ($job['wait_minutes'] ?? 0), 'fragile' => (bool) ($job['fragile'] ?? false),
            'loading' => (bool) ($job['loading'] ?? false), 'declared_value' => (int) ($job['declared_value'] ?? 0),
            'job_expenses' => (int) ($job['job_expenses'] ?? 0),
        ], $fee);

        $inputs = array_intersect_key($job, array_flip(['stops', 'distance_km', 'duration_min', 'round_trip', 'wait_minutes', 'fragile', 'loading', 'declared_value', 'job_expenses', 'service_type_id', 'city_id']));
        $publicId = (string) Str::ulid();
        DB::table('provider_estimates')->insert([
            'public_id' => $publicId, 'operator_id' => $opId, 'profile_id' => $profile->id, 'profile_version' => $profile->version,
            'created_by' => $userId, 'request_id' => $requestId, 'inputs' => json_encode($inputs), 'profile_snapshot' => json_encode($snapshot),
            'route' => json_encode($route), 'result' => json_encode($result), 'operating_cost' => $result['operating_cost'],
            'suggested_price' => $result['suggested_price'], 'platform_fee' => $result['platform_fee'], 'provider_net' => $result['provider_net'],
            'created_at' => now(),
        ]);

        return ['estimate' => $publicId, 'profile' => ['id' => $profile->public_id, 'name' => $profile->name, 'version' => (int) $profile->version], 'route' => $route] + $result;
    }

    /** An estimate for a customer's request, routed over the request's own pickup and drop-off. */
    public function estimateForRequest(int $opId, ?int $userId, string $requestPublicId, array $job = []): array
    {
        $req = DB::table('service_requests as r')->join('request_invitations as i', 'i.request_id', '=', 'r.id')
            ->where('r.public_id', $requestPublicId)->where('i.operator_id', $opId)->first(['r.*']);
        if (! $req) {
            throw new RuntimeException('Request not found.');
        }
        $stops = json_decode($req->stops, true) ?? [];
        $items = json_decode($req->packages ?? '[]', true)['items'] ?? [];
        $job += [
            'stops' => [['lat' => $stops['pickup']['lat'], 'lng' => $stops['pickup']['lng']], ['lat' => $stops['dropoff']['lat'], 'lng' => $stops['dropoff']['lng']]],
            'fragile' => collect($items)->contains(fn ($i) => is_array($i) && ! empty($i['fragile'])),
            'declared_value' => (int) collect($items)->sum(fn ($i) => is_array($i) ? (int) ($i['declared_value'] ?? 0) : 0),
            'service_type_id' => $req->service_type_id, 'city_id' => $stops['city_id'] ?? null,
        ];
        unset($job['distance_km'], $job['duration_min']);

        return $this->estimate($opId, $userId, $job, (int) $req->id);
    }

    public function recent(int $opId, int $limit = 20)
    {
        return DB::table('provider_estimates as e')->join('provider_cost_profiles as p', 'p.id', '=', 'e.profile_id')
            ->leftJoin('service_requests as r', 'r.id', '=', 'e.request_id')->where('e.operator_id', $opId)
            ->orderByDesc('e.id')->limit($limit)
            ->get(['e.public_id', 'p.name as profile', 'e.profile_version', 'r.public_id as request', 'e.route', 'e.operating_cost', 'e.suggested_price', 'e.platform_fee', 'e.provider_net', 'e.created_at'])
            ->map(function ($e) {
                $e->distance_m = (int) (json_decode($e->route, true)['distance_m'] ?? 0);
                unset($e->route);

                return $e;
            });
    }

    public function find(int $opId, string $publicId): ?array
    {
        $e = DB::table('provider_estimates')->where(['public_id' => $publicId, 'operator_id' => $opId])->first();
        if (! $e) {
            return null;
        }

        return ['estimate' => $e->public_id, 'profile_version' => (int) $e->profile_version, 'inputs' => json_decode($e->inputs, true),
            'profile' => json_decode($e->profile_snapshot, true), 'route' => json_decode($e->route, true), 'created_at' => $e->created_at] + json_decode($e->result, true);
    }

    private function profile(int $opId, ?string $publicId): object
    {
        $q = DB::table('provider_cost_profiles')->where(['operator_id' => $opId, 'active' => true]);
        $p = $publicId ? $q->where('public_id', $publicId)->first() : $q->orderByDesc('is_default')->orderBy('id')->first();
        if (! $p) {
            throw new RuntimeException($publicId ? 'Cost profile not found.' : 'Set up a cost profile first: add your fuel, labour and rate figures under Pricing.');
        }

        return $p;
    }

    /** @return array{distance_m:int, duration_s:int, provider:string, legs:array} */
    private function route(array $job, object $profile): array
    {
        $stops = array_values($job['stops'] ?? []);
        if (count($stops) >= 2) {
            if (count($stops) > self::MAX_STOPS) {
                throw new InvalidArgumentException('A job can have at most '.self::MAX_STOPS.' stops.');
            }
            if (! empty($job['round_trip'])) {
                $stops[] = $stops[0];
            }
            $vehicle = $profile->vehicle_type_id ? DB::table('vehicle_types')->where('id', $profile->vehicle_type_id)->value('code') : null;
            $legs = [];
            for ($i = 1; $i < count($stops); $i++) {
                $r = $this->routing->route((float) $stops[$i - 1]['lat'], (float) $stops[$i - 1]['lng'], (float) $stops[$i]['lat'], (float) $stops[$i]['lng'], RoutingEngine::profileFor($vehicle));
                $legs[] = $r;
            }

            return [
                'distance_m' => array_sum(array_column($legs, 'distance_m')), 'duration_s' => array_sum(array_column($legs, 'duration_s')),
                // A straight-line leg makes the whole route an estimate: say so rather than look precise.
                'provider' => collect($legs)->contains(fn ($l) => $l['provider'] === 'straight') ? 'straight' : $legs[0]['provider'],
                'legs' => array_map(fn ($l) => ['distance_m' => $l['distance_m'], 'duration_s' => $l['duration_s']], $legs),
            ];
        }

        if (! isset($job['distance_km']) || (float) $job['distance_km'] <= 0) {
            throw new InvalidArgumentException('Give the pickup and drop-off points, or a distance in kilometres.');
        }
        $m = (int) round((float) $job['distance_km'] * 1000);
        $speed = (float) config('routing.fallback.speed_kmh', 25) * 1000 / 3600;
        $duration = isset($job['duration_min']) && (float) $job['duration_min'] > 0 ? (int) round((float) $job['duration_min'] * 60) : (int) round($m / $speed);

        return ['distance_m' => $m, 'duration_s' => $duration, 'provider' => 'manual', 'legs' => []];
    }
}
