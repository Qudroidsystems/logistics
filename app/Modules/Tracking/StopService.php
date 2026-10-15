<?php

namespace App\Modules\Tracking;

use App\Modules\Marketplace\DeliveryService;
use Illuminate\Support\Facades\DB;
use App\Modules\Verification\CustodyLog;
use App\Modules\Verification\StopVerifier;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/** Driver completes a stop with the proofs the job giver and provider agreed. Pickup moves the job to in-transit; the drop-off starts the customer's confirmation clock. */
class StopService
{
    public function __construct(private DeliveryService $delivery)
    {
    }

    /**
     * $evidence: code, photo_path, signature_path, recipient_name, id_type, id_last4, cod_collected (see StopVerifier).
     * The stop's agreed proofs decide which are needed. $capturedAt is when the phone says the proof was taken
     * (offline sync); the server's own time is kept in created_at.
     */
    public function complete(int $driverProfileId, int $stopId, array $evidence, float $lat, float $lng, ?string $capturedAt = null): void
    {
        DB::transaction(function () use ($driverProfileId, $stopId, $evidence, $lat, $lng, $capturedAt) {
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

            $checks = app(StopVerifier::class)->check($stop, $evidence);
            $fileHash = $this->fileHash($evidence['photo_path'] ?? null);
            $sigHash = $this->fileHash($evidence['signature_path'] ?? null);
            $proofId = DB::table('proofs')->insertGetId([
                'public_id' => (string) Str::ulid(), 'shipment_id' => $stop->shipment_id, 'stop_id' => $stopId,
                'type' => $checks['satisfied'][0] ?? ($checks['waived'] ? 'exception' : 'arrival'),
                'file_path' => $evidence['photo_path'] ?? null, 'file_sha256' => $fileHash,
                'signature_path' => $evidence['signature_path'] ?? null, 'signature_sha256' => $sigHash,
                'otp_verified' => in_array('code', $checks['satisfied'], true) ? true : null,
                'recipient_name' => isset($evidence['recipient_name']) ? mb_substr(trim((string) $evidence['recipient_name']), 0, 120) : null,
                'id_type' => $evidence['id_type'] ?? null, 'id_last4' => $evidence['id_last4'] ?? null,
                'cod_collected' => isset($evidence['cod_collected']) && is_numeric($evidence['cod_collected']) ? (int) $evidence['cod_collected'] : null,
                'checks' => json_encode($checks),
                'point' => DB::raw(sprintf('ST_SetSRID(ST_MakePoint(%F, %F), 4326)::geography', $lng, $lat)),
                'distance_from_stop_m' => $dist, 'verified_by' => 'system', 'captured_at' => $capturedAt ?? now(), 'created_at' => now(),
            ]);
            app(CustodyLog::class)->append((int) $stop->shipment_id, 'proof', 'proof', $proofId, [
                'stop_id' => $stopId, 'stop_type' => $stop->type, 'driver_profile_id' => $driverProfileId, 'satisfied' => $checks['satisfied'],
                'waived' => $checks['waived'], 'file_sha256' => $fileHash, 'signature_sha256' => $sigHash, 'distance_m' => $dist,
                'captured_at' => $capturedAt,
            ]);
            DB::table('shipment_stops')->where('id', $stopId)->update(['status' => 'completed', 'completed_at' => now(), 'updated_at' => now()]);

            if ($stop->type === 'return') {
                app(\App\Modules\Marketplace\FailedDeliveryService::class)->completeReturn((int) $stop->shipment_id, $driverProfileId);
            } elseif ($stop->type === 'pickup') {
                // The first pickup puts the job in transit; later pickups on a multi-stop route just tick off.
                if (DB::table('shipments')->where('id', $stop->shipment_id)->value('status') !== 'in_transit') {
                    DB::table('shipments')->where('id', $stop->shipment_id)->update(['status' => 'in_transit', 'updated_at' => now()]);
                    app(\App\Modules\Partner\ShipmentEvents::class)->record((int) $stop->shipment_id, 'picked_up', 'at_pickup', 'in_transit', 'driver', $driverProfileId);
                }
                DB::table('assignments')->where('shipment_id', $stop->shipment_id)->where('driver_profile_id', $driverProfileId)->update(['status' => 'active', 'updated_at' => now()]);
            } elseif (DB::table('shipment_stops')->where('shipment_id', $stop->shipment_id)->whereIn('type', ['pickup', 'dropoff'])
                ->where('id', '!=', $stopId)->whereNotIn('status', ['completed', 'skipped'])->exists()) {
                // A drop-off with more stops still to do: the job carries on.
                app(\App\Modules\Partner\ShipmentEvents::class)->record((int) $stop->shipment_id, 'stop_completed', 'in_transit', 'in_transit', 'driver', $driverProfileId);
            } else {
                DB::table('assignments')->where('shipment_id', $stop->shipment_id)->where('driver_profile_id', $driverProfileId)->update(['status' => 'completed', 'completed_at' => now(), 'updated_at' => now()]);
                DB::table('driver_profiles')->where('id', $driverProfileId)->update(['availability' => 'online', 'last_trip_at' => now(), 'updated_at' => now()]);
                $this->delivery->markDelivered((int) $stop->shipment_id);
            }
        });
    }

    /** Fingerprint of a stored proof file, so a swapped photo or signature is detectable later. */
    private function fileHash(?string $path): ?string
    {
        if (! $path || ! Storage::exists($path)) {
            return null;
        }

        return hash('sha256', (string) Storage::get($path));
    }
}
