<?php

namespace App\Modules\Tracking;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * The live fleet: where every driver is now, whether they are online, on a job or out of signal, and how fast they
 * were last going. Read by the provider's fleet map, the staff console's fleet map and the provider API.
 *
 * Positions come from driver_profiles.last_point (moved forward by LocationIngestService on every ping batch).
 * Speed, heading and battery come from the newest ping in the last half hour, so old partitions are never scanned.
 */
class FleetService
{
    /** A driver whose newest ping is older than this is shown as "no signal" even when they say they are online. */
    public const STALE_MINUTES = 5;

    /** Driver statuses that belong on the map; people who left or were never approved do not. */
    public const VISIBLE_STATUSES = ['active', 'training', 'suspended'];

    /**
     * @param  int|null  $operatorId  null = every provider (the staff console)
     * @return array{drivers: list<array>, counts: array<string,int>, speed_limit_kmh: int, at: string}
     */
    public function snapshot(?int $operatorId, ?string $search = null): array
    {
        $limit = self::speedLimitKmh();
        $rows = DB::table('driver_profiles as d')
            ->join('users as u', 'u.id', '=', 'd.user_id')
            ->join('operators as o', 'o.id', '=', 'd.operator_id')
            ->leftJoin('vehicles as v', 'v.id', '=', 'd.current_vehicle_id')
            ->leftJoin('vehicle_types as vt', 'vt.id', '=', 'v.vehicle_type_id')
            ->leftJoinLateral(
                DB::table('driver_locations as l')->select('l.speed_mps', 'l.heading', 'l.battery_pct', 'l.recorded_at')
                    ->whereColumn('l.driver_profile_id', 'd.id')->whereRaw("l.recorded_at > now() - interval '30 minutes'")
                    ->orderByDesc('l.recorded_at')->limit(1),
                'loc'
            )
            ->leftJoinLateral(
                DB::table('assignments as a')->join('shipments as s', 's.id', '=', 'a.shipment_id')->join('orders as ord', 'ord.id', '=', 's.order_id')
                    ->select('s.public_id', 's.status', 'ord.order_number', 'ord.public_id as order_public_id')
                    ->whereColumn('a.driver_profile_id', 'd.id')->whereIn('a.status', ['assigned', 'accepted', 'en_route', 'active'])
                    ->orderByDesc('a.id')->limit(1),
                'job'
            )
            ->whereNull('d.deleted_at')
            ->whereIn('d.status', self::VISIBLE_STATUSES)
            ->when($operatorId !== null, fn ($q) => $q->where('d.operator_id', $operatorId))
            ->when($search !== null && trim($search) !== '', function ($q) use ($search) {
                $s = '%'.mb_strtolower(ltrim(trim($search), '@')).'%';
                $q->where(fn ($w) => $w->whereRaw('LOWER(u.name) LIKE ?', [$s])->orWhereRaw('LOWER(d.handle) LIKE ?', [$s])
                    ->orWhereRaw('LOWER(v.plate) LIKE ?', [$s])->orWhereRaw('LOWER(o.display_name) LIKE ?', [$s]));
            })
            ->orderBy('u.name')
            ->limit(2000)
            ->get([
                'd.id', 'd.public_id', 'd.status', 'd.availability', 'd.last_seen_at', 'd.handle', 'd.rating_avg',
                DB::raw('ST_Y(d.last_point::geometry) AS lat'), DB::raw('ST_X(d.last_point::geometry) AS lng'),
                'u.name', 'u.phone_number as phone', 'o.public_id as operator_id', 'o.display_name as company',
                'v.plate', 'vt.code as vehicle_type',
                'loc.speed_mps', 'loc.heading', 'loc.battery_pct',
                'job.public_id as job_id', 'job.status as job_status', 'job.order_number as job_order', 'job.order_public_id as job_order_id',
            ]);

        $drivers = $rows->map(fn ($r) => self::present($r, $limit))->values()->all();
        $counts = ['total' => count($drivers), 'on_job' => 0, 'online' => 0, 'break' => 0, 'offline' => 0, 'no_signal' => 0, 'speeding' => 0, 'unplaced' => 0];
        foreach ($drivers as $d) {
            $counts[$d['state']] = ($counts[$d['state']] ?? 0) + 1;
            $counts['speeding'] += $d['speeding'] ? 1 : 0;
            $counts['unplaced'] += $d['lat'] === null ? 1 : 0;
        }

        return ['drivers' => $drivers, 'counts' => $counts, 'speed_limit_kmh' => $limit, 'at' => now()->toIso8601String()];
    }

    /**
     * The path a driver drove in the last [$minutes], oldest first, thinned to at most 400 points.
     *
     * @return list<array{lat:float, lng:float, at:string, speed_kmh:?int}>|null null when the driver is not in scope
     */
    public function trail(string $driverPublicId, ?int $operatorId, int $minutes = 60): ?array
    {
        $driver = DB::table('driver_profiles')->where('public_id', $driverPublicId)->whereNull('deleted_at')
            ->when($operatorId !== null, fn ($q) => $q->where('operator_id', $operatorId))->value('id');
        if (! $driver) {
            return null;
        }
        $minutes = max(5, min(24 * 60, $minutes));
        $rows = DB::select(
            "SELECT ST_Y(point::geometry) AS lat, ST_X(point::geometry) AS lng, recorded_at, speed_mps
             FROM driver_locations WHERE driver_profile_id = ? AND recorded_at > now() - make_interval(mins => ?)
             ORDER BY recorded_at",
            [$driver, $minutes]
        );
        $step = max(1, (int) ceil(count($rows) / 400));
        $out = [];
        foreach ($rows as $i => $r) {
            if ($i % $step !== 0 && $i !== count($rows) - 1) {
                continue;
            }
            $out[] = ['lat' => (float) $r->lat, 'lng' => (float) $r->lng, 'at' => Carbon::parse($r->recorded_at)->toIso8601String(),
                'speed_kmh' => $r->speed_mps !== null ? (int) round($r->speed_mps * 3.6) : null];
        }

        return $out;
    }

    /** Pure: one row as the map and list show it. */
    public static function present(object $r, int $speedLimitKmh, ?int $now = null): array
    {
        $now ??= time();
        $seen = $r->last_seen_at ? strtotime((string) $r->last_seen_at) : null;
        $ageS = $seen ? max(0, $now - $seen) : null;
        $kmh = isset($r->speed_mps) && $r->speed_mps !== null ? (int) round(((float) $r->speed_mps) * 3.6) : null;
        $fresh = $ageS !== null && $ageS <= self::STALE_MINUTES * 60;

        return [
            'id' => $r->public_id,
            'name' => $r->name,
            'handle' => $r->handle,
            'phone' => $r->phone,
            'company' => $r->company,
            'operator_id' => $r->operator_id,
            'status' => $r->status,
            'availability' => $r->availability,
            'state' => self::state((string) $r->availability, $fresh),
            'lat' => $r->lat !== null ? (float) $r->lat : null,
            'lng' => $r->lng !== null ? (float) $r->lng : null,
            'last_seen_at' => $seen ? date(DATE_ATOM, $seen) : null,
            'seen_ago_s' => $ageS,
            'speed_kmh' => $fresh ? $kmh : null,
            'speeding' => $fresh && $kmh !== null && $kmh > $speedLimitKmh,
            'heading' => $fresh && isset($r->heading) && $r->heading !== null ? (int) round((float) $r->heading) : null,
            'battery_pct' => isset($r->battery_pct) && $r->battery_pct !== null ? (int) $r->battery_pct : null,
            'vehicle' => $r->plate ? trim($r->plate.' '.($r->vehicle_type ? '('.$r->vehicle_type.')' : '')) : null,
            'rating' => $r->rating_avg !== null ? round((float) $r->rating_avg, 1) : null,
            'job' => $r->job_id ? ['id' => $r->job_id, 'status' => $r->job_status, 'order' => $r->job_order, 'order_id' => $r->job_order_id ?? null] : null,
        ];
    }

    /** Pure. on_job | online | break | offline | no_signal (says online or on a job but has stopped sending positions). */
    public static function state(string $availability, bool $fresh): string
    {
        if ($availability === 'offline') {
            return 'offline';
        }
        if (! $fresh) {
            return 'no_signal';
        }

        return in_array($availability, ['on_job', 'online', 'break'], true) ? $availability : 'offline';
    }

    public static function speedLimitKmh(): int
    {
        return max(10, (int) config('tracking.speed_limit_kmh', 80));
    }
}
