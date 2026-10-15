<?php

namespace App\Modules\Eta;

use App\Modules\Routing\RoutingEngine;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * When the rider will reach their next stop, as a range ("10 to 15 min") rather than a falsely exact minute.
 *
 *   - the road time comes from the RoutingEngine (OSRM, or a straight-line guess when it is down)
 *   - it is recalculated only when the rider has moved RECALC_MOVE_M or RECALC_AGE_S has passed, so a ping every few
 *     seconds does not mean a routing call every few seconds
 *   - a straight-line guess gets a wider range than a real route
 *   - when the rider's last GPS fix is older than STALE_S the estimate is marked stale and the range widens by the time
 *     since the fix, because the rider has kept moving (or stopped) without telling us
 */
class EtaService
{
    public const STALE_S = 120;
    public const RECALC_MOVE_M = 150;
    public const RECALC_AGE_S = 60;
    public const ROUTED_SPREAD = 0.15;
    public const GUESSED_SPREAD = 0.3;

    /** Shipment states in which a rider is moving towards a stop. */
    public const MOVING = ['assigned', 'heading_to_pickup', 'at_pickup', 'picked_up', 'in_transit', 'at_dropoff'];

    public function __construct(private RoutingEngine $routing) {}

    /**
     * The estimate for the shipment's next unfinished stop, or null when no rider is on the way or we have no fix.
     *
     * @return array{stop_id:int, stop_type:string, shipment_id:int, travelling:bool, minutes:int, low:int, high:int, label:string, stale:bool, gps_age_s:?int, distance_m:int, due_at:string, provider:string}|null
     */
    public function forShipment(int $shipmentId): ?array
    {
        $s = DB::table('shipments')->where('id', $shipmentId)->first(['id', 'status']);
        if (! $s || ! in_array($s->status, self::MOVING, true)) {
            return null;
        }
        $driver = DB::selectOne(
            "SELECT p.last_seen_at, ST_Y(p.last_point::geometry) AS lat, ST_X(p.last_point::geometry) AS lng, vt.code AS vehicle
             FROM assignments a JOIN driver_profiles p ON p.id = a.driver_profile_id
             LEFT JOIN vehicles v ON v.id = p.current_vehicle_id LEFT JOIN vehicle_types vt ON vt.id = v.vehicle_type_id
             WHERE a.shipment_id = ? AND a.status IN ('accepted','en_route','active') ORDER BY a.id DESC LIMIT 1", [$shipmentId]
        );
        if (! $driver || $driver->lat === null) {
            return null;
        }
        $stop = DB::selectOne(
            "SELECT id, type, ST_Y(point::geometry) AS lat, ST_X(point::geometry) AS lng FROM shipment_stops
             WHERE shipment_id = ? AND status NOT IN ('completed','skipped','failed') ORDER BY seq LIMIT 1", [$shipmentId]
        );
        if (! $stop) {
            return null;
        }

        // Before the rider sets off (or while still at the pickup) the "expected by" time keeps moving with them.
        $travelling = $stop->type === 'pickup' ? $s->status === 'heading_to_pickup' : in_array($s->status, ['picked_up', 'in_transit'], true);

        return $this->forStop($shipmentId, $stop, (float) $driver->lat, (float) $driver->lng, $driver->last_seen_at, RoutingEngine::profileFor($driver->vehicle), $travelling);
    }

    public function forStop(int $shipmentId, object $stop, float $lat, float $lng, ?string $gpsAt, string $profile = 'car', bool $travelling = true): array
    {
        $now = CarbonImmutable::now();
        $row = DB::table('stop_etas')->where('stop_id', $stop->id)->first();
        $moved = $row ? $this->metres((float) $row->from_lat, (float) $row->from_lng, $lat, $lng) : INF;
        $age = $row ? $now->getTimestamp() - CarbonImmutable::parse($row->computed_at)->getTimestamp() : INF;

        if (! $row || $moved >= self::RECALC_MOVE_M || $age >= self::RECALC_AGE_S) {
            $r = $this->routing->route($lat, $lng, (float) $stop->lat, (float) $stop->lng, $profile);
            $due = $row && $travelling ? $row->due_at : $now->addSeconds($r['duration_s'])->toIso8601String();
            DB::table('stop_etas')->upsert([[
                'stop_id' => $stop->id, 'shipment_id' => $shipmentId, 'duration_s' => $r['duration_s'], 'distance_m' => $r['distance_m'],
                'provider' => $r['provider'], 'from_lat' => $lat, 'from_lng' => $lng,
                'gps_at' => $gpsAt ? CarbonImmutable::parse($gpsAt)->toIso8601String() : null, 'computed_at' => $now->toIso8601String(), 'due_at' => $due,
            ]], ['stop_id'], ['duration_s', 'distance_m', 'provider', 'from_lat', 'from_lng', 'gps_at', 'computed_at', 'due_at']);
            $row = DB::table('stop_etas')->where('stop_id', $stop->id)->first();
        }

        // Time already spent since the estimate was made comes off it, so a rider stuck between pings counts down.
        $left = max(0, (int) $row->duration_s - max(0, $now->getTimestamp() - CarbonImmutable::parse($row->computed_at)->getTimestamp()));
        $gpsAge = $gpsAt ? max(0, $now->getTimestamp() - CarbonImmutable::parse($gpsAt)->getTimestamp()) : null;
        $range = self::range($left, $gpsAge, (string) $row->provider);

        return ['stop_id' => (int) $stop->id, 'stop_type' => (string) $stop->type, 'shipment_id' => $shipmentId, 'travelling' => $travelling, 'distance_m' => (int) $row->distance_m,
            'due_at' => CarbonImmutable::parse($row->due_at)->toIso8601String(), 'provider' => (string) $row->provider, 'gps_age_s' => $gpsAge] + $range;
    }

    /** Moves the "expected by" time forward after a delay notice, so the next notice needs a fresh slip. */
    public function rebase(int $stopId, CarbonImmutable $dueAt): void
    {
        DB::table('stop_etas')->where('stop_id', $stopId)->update(['due_at' => $dueAt->toIso8601String()]);
    }

    // ---------------------------------------------------------------- pure helpers (unit tested)

    /** @return array{minutes:int, low:int, high:int, label:string, stale:bool} */
    public static function range(int $durationS, ?int $gpsAgeS, string $provider): array
    {
        $mins = $durationS / 60;
        $spread = $provider === 'straight' ? self::GUESSED_SPREAD : self::ROUTED_SPREAD;
        $stale = $gpsAgeS !== null && $gpsAgeS > self::STALE_S;
        $low = max(1, (int) floor($mins * (1 - $spread)));
        $high = max($low, (int) ceil($mins * (1 + $spread)) + ($stale ? (int) ceil($gpsAgeS / 60) : 0));
        // Ranges read better on round numbers once they are long.
        if ($high >= 20) {
            $low = max(1, (int) floor($low / 5) * 5);
            $high = (int) ceil($high / 5) * 5;
        }

        return ['minutes' => max(1, (int) round($mins)), 'low' => $low, 'high' => $high, 'label' => self::label($low, $high), 'stale' => $stale];
    }

    public static function label(int $low, int $high): string
    {
        if ($high <= 1) {
            return 'about 1 min';
        }

        return $low >= $high ? "about {$high} min" : "{$low} to {$high} min";
    }

    private function metres(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $a = sin(deg2rad($lat2 - $lat1) / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin(deg2rad($lng2 - $lng1) / 2) ** 2;

        return 2 * 6371000 * asin(min(1, sqrt($a)));
    }
}
