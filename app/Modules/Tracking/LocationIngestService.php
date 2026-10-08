<?php

namespace App\Modules\Tracking;

use Illuminate\Support\Facades\DB;

/**
 * Accepts a batch of GPS pings from the driver app (the app buffers offline and flushes in batches).
 * Bad pings are dropped, not errors, so one spoofed ping never blocks the rest of a batch.
 */
class LocationIngestService
{
    public const MAX_ACCURACY_M = 100;
    public const MAX_AGE_S = 600;
    public const MAX_SPEED_MPS = 70;     // ~250 km/h: faster is a GPS glitch
    public const ARRIVE_RADIUS_M = 100;
    public const BATCH_LIMIT = 100;

    /** @param array<int,array{lat:float,lng:float,at:string,accuracy?:float,speed?:float,heading?:float,battery?:int,mocked?:bool}> $pings */
    public function ingest(int $driverProfileId, array $pings): array
    {
        $driver = DB::table('driver_profiles')->where('id', $driverProfileId)->first();
        $pings = array_slice($pings, 0, self::BATCH_LIMIT);
        $shipmentId = DB::table('assignments')->where('driver_profile_id', $driverProfileId)
            ->whereIn('status', ['accepted', 'en_route', 'active'])->value('shipment_id');

        $accepted = 0;
        $last = null;
        usort($pings, fn ($a, $b) => strcmp($a['at'], $b['at']));

        foreach ($pings as $p) {
            if (! $this->usable($p)) {
                $this->flagIfMocked($driver, $p);
                continue;
            }
            DB::insert(
                'INSERT INTO driver_locations (driver_profile_id, shipment_id, operator_id, point, speed_mps, heading, accuracy_m, battery_pct, is_mocked, recorded_at)
                 VALUES (?, ?, ?, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography, ?, ?, ?, ?, false, ?)',
                [$driverProfileId, $shipmentId, $driver->operator_id, $p['lng'], $p['lat'], $p['speed'] ?? null, $p['heading'] ?? null, $p['accuracy'] ?? null, $p['battery'] ?? null, $p['at']]
            );
            $accepted++;
            $last = $p;
        }

        if ($last) {
            // Only move the driver's "current" position forward in time.
            DB::update(
                'UPDATE driver_profiles SET last_point = ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography, last_seen_at = ?, updated_at = now()
                 WHERE id = ? AND (last_seen_at IS NULL OR last_seen_at <= ?)',
                [$last['lng'], $last['lat'], $last['at'], $driverProfileId, $last['at']]
            );
            if ($shipmentId) {
                $this->geofence($driverProfileId, (int) $shipmentId, $last);
            }
        }

        return ['accepted' => $accepted, 'dropped' => count($pings) - $accepted, 'shipment_id' => $shipmentId];
    }

    /** Pure rule, unit tested. */
    public function usable(array $p, ?int $now = null): bool
    {
        $now ??= time();
        if (! isset($p['lat'], $p['lng'], $p['at']) || abs($p['lat']) > 90 || abs($p['lng']) > 180) {
            return false;
        }
        if (! empty($p['mocked']) || ($p['accuracy'] ?? 0) > self::MAX_ACCURACY_M || ($p['speed'] ?? 0) > self::MAX_SPEED_MPS) {
            return false;
        }
        $t = strtotime($p['at']);

        return $t !== false && $t <= $now + 60 && $t >= $now - self::MAX_AGE_S * 6; // offline batches may be old, within an hour
    }

    private function flagIfMocked(object $driver, array $p): void
    {
        if (! empty($p['mocked'])) {
            DB::table('risk_events')->insert([
                'subject_type' => 'driver', 'subject_id' => $driver->id, 'type' => 'gps_spoof', 'severity' => 'high',
                'evidence' => json_encode($p), 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    /** First time the driver gets within radius of the next pending stop, record "arrived" once. */
    private function geofence(int $driverProfileId, int $shipmentId, array $p): void
    {
        $stop = DB::selectOne(
            "SELECT id, type, ST_Distance(point, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography) AS d
             FROM shipment_stops WHERE shipment_id = ? AND status IN ('pending','en_route') ORDER BY seq LIMIT 1",
            [$p['lng'], $p['lat'], $shipmentId]
        );
        if (! $stop || $stop->d > self::ARRIVE_RADIUS_M) {
            return;
        }
        $already = DB::table('geofence_events')->where('stop_id', $stop->id)->where('type', 'arrived')->exists();
        if ($already) {
            return;
        }
        DB::insert(
            "INSERT INTO geofence_events (shipment_id, stop_id, driver_profile_id, type, distance_m, point, created_at)
             VALUES (?, ?, ?, 'arrived', ?, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography, now())",
            [$shipmentId, $stop->id, $driverProfileId, (int) round($stop->d), $p['lng'], $p['lat']]
        );
        DB::table('shipment_stops')->where('id', $stop->id)->update(['status' => 'arrived', 'arrived_at' => now(), 'updated_at' => now()]);
        if ($stop->type === 'return') {
            return; // back at the pickup address with the parcel: the driver completes the stop themselves
        }
        $to = $stop->type === 'pickup' ? 'at_pickup' : 'at_dropoff';
        $from = DB::table('shipments')->where('id', $shipmentId)->value('status');
        DB::table('shipments')->where('id', $shipmentId)->update(['status' => $to, 'updated_at' => now()]);
        app(\App\Modules\Partner\ShipmentEvents::class)->record($shipmentId, 'arrived', $from, $to, 'driver', $driverProfileId, ['stop_type' => $stop->type]);
    }
}
