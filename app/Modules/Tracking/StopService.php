<?php

namespace App\Modules\Tracking;

use App\Modules\Marketplace\DeliveryService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/** Driver completes a stop. Pickup moves the job to in-transit; the dropoff needs proof and starts the customer's confirmation clock. */
class StopService
{
    public function __construct(private DeliveryService $delivery)
    {
    }

    public function complete(int $driverProfileId, int $stopId, string $proofType, ?string $filePath, ?string $otp, float $lat, float $lng, ?string $recipientName = null): void
    {
        DB::transaction(function () use ($driverProfileId, $stopId, $proofType, $filePath, $otp, $lat, $lng, $recipientName) {
            $stop = DB::table('shipment_stops')->where('id', $stopId)->lockForUpdate()->first();
            $owns = $stop && DB::table('assignments')->where('shipment_id', $stop->shipment_id)
                ->where('driver_profile_id', $driverProfileId)->whereIn('status', ['accepted', 'en_route', 'active'])->exists();
            if (! $owns || $stop->status === 'completed') {
                throw new RuntimeException('This stop is not yours to complete.');
            }

            $dist = (int) round(DB::selectOne('SELECT ST_Distance(point, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography) AS d FROM shipment_stops WHERE id = ?', [$lng, $lat, $stopId])->d);
            if ($dist > 300) {
                throw new RuntimeException('You are too far from this stop to complete it.');
            }

            $otpOk = null;
            if ($stop->type === 'dropoff') {
                if ($stop->otp_hash && ! hash_equals($stop->otp_hash, hash('sha256', (string) $otp))) {
                    throw new RuntimeException('Delivery code does not match.');
                }
                $otpOk = $stop->otp_hash ? true : null;
                if (! $stop->otp_hash && ! $filePath) {
                    throw new RuntimeException('A photo or delivery code is required to complete the drop-off.');
                }
            }

            DB::insert(
                "INSERT INTO proofs (public_id, shipment_id, stop_id, type, file_path, otp_verified, recipient_name, point, distance_from_stop_m, verified_by, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography, ?, 'system', now(), now())",
                [(string) \Illuminate\Support\Str::ulid(), $stop->shipment_id, $stopId, $proofType, $filePath, $otpOk, $recipientName, $lng, $lat, $dist]
            );
            DB::table('shipment_stops')->where('id', $stopId)->update(['status' => 'completed', 'completed_at' => now(), 'updated_at' => now()]);

            if ($stop->type === 'pickup') {
                DB::table('shipments')->where('id', $stop->shipment_id)->update(['status' => 'in_transit', 'updated_at' => now()]);
                app(\App\Modules\Partner\ShipmentEvents::class)->record((int) $stop->shipment_id, 'picked_up', 'at_pickup', 'in_transit', 'driver', $driverProfileId);
                DB::table('assignments')->where('shipment_id', $stop->shipment_id)->where('driver_profile_id', $driverProfileId)->update(['status' => 'active', 'updated_at' => now()]);
            } else {
                DB::table('assignments')->where('shipment_id', $stop->shipment_id)->where('driver_profile_id', $driverProfileId)->update(['status' => 'completed', 'completed_at' => now(), 'updated_at' => now()]);
                DB::table('driver_profiles')->where('id', $driverProfileId)->update(['availability' => 'online', 'last_trip_at' => now(), 'updated_at' => now()]);
                $this->delivery->markDelivered((int) $stop->shipment_id);
            }
        });
    }
}
