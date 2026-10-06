<?php

namespace App\Modules\Marketplace;

<<<<<<< HEAD
=======
use App\Modules\Tracking\TrackingService;
>>>>>>> f13ef7283d8990e676f244093d5e997780b4fb1d
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Creates the order, shipment, stops and packages once an agreement is paid. Idempotent per agreement.
 *
 * Expected terms shape: pickup/dropoff = {line1, lat, lng, contact_name?, contact_phone?, landmark?, instructions?},
 * packages = [{description, weight_g?, declared_value?, fragile?}], vehicle_type_id?, errand ('delivery'|'shopping').
 */
class OrderFromAgreement
{
    /** @return array{order_id:int, shipment_id:int, created:bool} */
<<<<<<< HEAD
=======
    public function __construct(private ?TrackingService $tracking = null)
    {
        $this->tracking ??= new TrackingService();
    }

>>>>>>> f13ef7283d8990e676f244093d5e997780b4fb1d
    public function create(int $agreementId): array
    {
        return DB::transaction(function () use ($agreementId) {
            $a = DB::table('agreements')->where('id', $agreementId)->lockForUpdate()->first();
            if ($existing = DB::table('orders')->where('agreement_id', $agreementId)->first()) {
                return ['order_id' => (int) $existing->id, 'shipment_id' => (int) DB::table('shipments')->where('order_id', $existing->id)->value('id'), 'created' => false];
            }
            $t = json_decode($a->terms, true);
            $total = $a->price + $a->goods_budget + $a->tip;

            $orderId = DB::table('orders')->insertGetId([
                'public_id' => (string) Str::ulid(),
                'order_number' => 'OR'.now()->format('ymd').strtoupper(Str::random(6)),
                'operator_id' => $a->provider_operator_id, 'channel' => $a->merchant_id ? 'api' : 'customer_app',
<<<<<<< HEAD
                'customer_id' => $a->customer_id, 'merchant_id' => $a->merchant_id, 'agreement_id' => $a->id,
=======
                'customer_id' => $a->customer_id, 'merchant_id' => $a->merchant_id, 'external_order_id' => $t['external_order_id'] ?? null, 'agreement_id' => $a->id,
>>>>>>> f13ef7283d8990e676f244093d5e997780b4fb1d
                'service_type_id' => $t['service_type_id'], 'status' => 'confirmed', 'payment_status' => 'paid',
                'payment_method' => 'card', 'subtotal' => $a->goods_budget, 'delivery_fee' => $a->price, 'service_fee' => 0,
                'tip' => $a->tip, 'tax' => 0, 'discount' => 0, 'total' => $total, 'cancel_fee' => 0,
                'placed_at' => now(), 'is_test' => $a->is_test, 'created_at' => now(), 'updated_at' => now(),
            ]);

            $shipmentId = DB::table('shipments')->insertGetId([
                'public_id' => (string) Str::ulid(), 'order_id' => $orderId, 'operator_id' => $a->provider_operator_id,
                'service_type_id' => $t['service_type_id'], 'vehicle_type_id' => $t['vehicle_type_id'] ?? null,
                'status' => 'created', 'tracking_code' => strtoupper(Str::random(12)), 'distance_m' => $a->distance_m,
                'cod_amount' => 0, 'insured_value' => 0, 'is_test' => $a->is_test, 'created_at' => now(), 'updated_at' => now(),
            ]);

            foreach ([['pickup', 1, $t['pickup']], ['dropoff', 2, $t['dropoff']]] as [$type, $seq, $s]) {
                DB::insert(
                    'INSERT INTO shipment_stops (shipment_id, seq, type, line1, landmark, point, contact_name, contact_phone, instructions, status, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography, ?, ?, ?, \'pending\', now(), now())',
                    [$shipmentId, $seq, $type, $s['line1'], $s['landmark'] ?? null, $s['lng'], $s['lat'], $s['contact_name'] ?? null, $s['contact_phone'] ?? null, $s['instructions'] ?? null]
                );
            }

<<<<<<< HEAD
=======
            // Arm the drop-off code and share links in the same transaction as the shipment.
            $this->tracking->arm($shipmentId);
            app(\App\Modules\Partner\ShipmentEvents::class)->record($shipmentId, 'created', null, 'created');

>>>>>>> f13ef7283d8990e676f244093d5e997780b4fb1d
            return ['order_id' => $orderId, 'shipment_id' => $shipmentId, 'created' => true];
        });
    }
}
