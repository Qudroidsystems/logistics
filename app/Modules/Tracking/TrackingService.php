<?php

namespace App\Modules\Tracking;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Live tracking for customers and recipients, and the delivery code the driver must collect at drop-off.
 *
 * The delivery code is derived (HMAC of the shipment id with the app key), so the customer app can show it
 * again at any time without storing the plain code anywhere; only its hash sits on the drop-off stop.
 */
class TrackingService
{
    public const CITY_SPEED_KMH = 25;

    public function deliveryCode(string $shipmentPublicId, ?string $key = null): string
    {
        $key ??= (string) config('app.key');
        $n = hexdec(substr(hash_hmac('sha256', $shipmentPublicId, $key), 0, 8)) % 10000;

        return str_pad((string) $n, 4, '0', STR_PAD_LEFT);
    }

    /** Called when the shipment is created: arms the drop-off with its code and creates the share links. */
    public function arm(int $shipmentId, ?string $key = null): array
    {
        $s = DB::table('shipments')->find($shipmentId);
        $code = $this->deliveryCode($s->public_id, $key);

        DB::table('shipment_stops')->where('shipment_id', $shipmentId)->where('type', 'dropoff')
            ->update(['otp_hash' => hash('sha256', $code), 'updated_at' => now()]);

        $tokens = [];
        foreach (['customer', 'recipient'] as $audience) {
            $tokens[$audience] = Str::random(40);
            DB::table('tracking_links')->insert([
                'shipment_id' => $shipmentId, 'token' => $tokens[$audience], 'audience' => $audience,
                'expires_at' => now()->addDays(14), 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        return $tokens;
    }

    /** What a tracking link may see. Never exposes phone numbers, price, or the delivery code. */
    public function snapshot(string $token): ?array
    {
        $link = DB::table('tracking_links')->where('token', $token)->whereNull('revoked_at')
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))->first();
        if (! $link) {
            return null;
        }
        $s = DB::table('shipments')->find($link->shipment_id);
        $stops = DB::select('SELECT seq, type, line1, landmark, status, arrived_at, completed_at, ST_Y(point::geometry) AS lat, ST_X(point::geometry) AS lng FROM shipment_stops WHERE shipment_id = ? ORDER BY seq', [$s->id]);

        $driver = null;
        $assignment = DB::table('assignments')->where('shipment_id', $s->id)->whereIn('status', ['accepted', 'en_route', 'active'])->first();
        if ($assignment && in_array($s->status, ['assigned', 'heading_to_pickup', 'at_pickup', 'picked_up', 'in_transit', 'at_dropoff'], true)) {
            $d = DB::selectOne(
                'SELECT u.name, p.rating_avg, p.last_seen_at, ST_Y(p.last_point::geometry) AS lat, ST_X(p.last_point::geometry) AS lng
                 FROM driver_profiles p JOIN users u ON u.id = p.user_id WHERE p.id = ?', [$assignment->driver_profile_id]
            );
            $vehicle = DB::selectOne('SELECT v.plate, v.make, v.model FROM vehicles v JOIN driver_profiles p ON p.current_vehicle_id = v.id WHERE p.id = ?', [$assignment->driver_profile_id]);
            $next = collect($stops)->first(fn ($x) => $x->status !== 'completed');
            $eta = $next && $d->lat ? $this->etaMinutes($this->haversineM($d->lat, $d->lng, $next->lat, $next->lng)) : null;
            $driver = [
                'first_name' => Str::before($d->name, ' '), 'rating' => $d->rating_avg,
                'vehicle' => $vehicle ? trim("{$vehicle->make} {$vehicle->model}")." · {$vehicle->plate}" : null,
                'lat' => (float) $d->lat, 'lng' => (float) $d->lng,
                'seconds_since_update' => $d->last_seen_at ? now()->diffInSeconds($d->last_seen_at) : null,
                'eta_minutes' => $eta,
            ];
        }

        return [
            'tracking_code' => $s->tracking_code, 'status' => $s->status, 'delivered_at' => $s->delivered_at,
            'stops' => array_map(fn ($x) => ['seq' => $x->seq, 'type' => $x->type, 'address' => $x->line1, 'status' => $x->status, 'lat' => (float) $x->lat, 'lng' => (float) $x->lng], $stops),
            'driver' => $driver,
            'poll_after_seconds' => (int) (DB::table('platform_settings')->whereNull('operator_id')->where('key', 'tracking.location_interval_seconds')->value('value') ?? 8),
        ];
    }

    /** Straight-line distance padded for roads (x1.35); a placeholder until the routing engine supplies real ETAs. */
    public function etaMinutes(float $metres): int
    {
        return max(1, (int) ceil(($metres * 1.35 / 1000) / self::CITY_SPEED_KMH * 60));
    }

    public function haversineM(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $r = 6371000;
        $a = sin(deg2rad($lat2 - $lat1) / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin(deg2rad($lng2 - $lng1) / 2) ** 2;

        return 2 * $r * asin(min(1, sqrt($a)));
    }
}
