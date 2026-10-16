<?php

namespace App\Modules\Marketplace;

use App\Modules\Tracking\ParcelCodes;
use App\Modules\Tracking\TrackingService;
use App\Modules\Verification\VerificationPolicy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Creates the order, shipment, stops and packages once an agreement is paid. Idempotent per agreement.
 *
 * Expected terms shape: pickup/dropoff = {line1, lat, lng, contact_name?, contact_phone?, landmark?, instructions?},
 * packages = [{description, weight_g?, declared_value?, fragile?, dropoff?}], vehicle_type_id?, errand ('delivery'|'shopping'),
 * stops = extra stops between the two in driving order (same shape plus type), pickup_at?, rush?.
 */
class OrderFromAgreement
{
    /** @return array{order_id:int, shipment_id:int, created:bool} */
    public function __construct(private ?TrackingService $tracking = null)
    {
        $this->tracking ??= new TrackingService();
    }

    public function create(int $agreementId): array
    {
        return DB::transaction(function () use ($agreementId) {
            $a = DB::table('agreements')->where('id', $agreementId)->lockForUpdate()->first();
            if ($existing = DB::table('orders')->where('agreement_id', $agreementId)->first()) {
                return ['order_id' => (int) $existing->id, 'shipment_id' => (int) DB::table('shipments')->where('order_id', $existing->id)->value('id'), 'created' => false];
            }
            $t = json_decode($a->terms, true);
            $discount = (int) ($a->discount ?? 0);
            $total = $a->price + $a->goods_budget + $a->tip - $discount;
            $pickupAt = ! empty($t['pickup_at']) ? \Carbon\Carbon::parse($t['pickup_at']) : null;

            $orderId = DB::table('orders')->insertGetId([
                'public_id' => (string) Str::ulid(),
                'order_number' => 'OR'.now()->format('ymd').strtoupper(Str::random(6)),
                'operator_id' => $a->provider_operator_id, 'channel' => $a->merchant_id ? 'api' : 'customer_app',
                'customer_id' => $a->customer_id, 'merchant_id' => $a->merchant_id, 'external_order_id' => $t['external_order_id'] ?? null, 'agreement_id' => $a->id,
                'service_type_id' => $t['service_type_id'], 'status' => 'confirmed', 'payment_status' => 'paid',
                'payment_method' => 'card', 'subtotal' => $a->goods_budget, 'delivery_fee' => $a->price, 'service_fee' => 0,
                'tip' => $a->tip, 'tax' => 0, 'discount' => $discount, 'total' => $total, 'cancel_fee' => 0,
                'scheduled_for' => $pickupAt, 'placed_at' => now(), 'is_test' => $a->is_test, 'created_at' => now(), 'updated_at' => now(),
            ]);

            $shipmentId = DB::table('shipments')->insertGetId([
                'public_id' => (string) Str::ulid(), 'order_id' => $orderId, 'operator_id' => $a->provider_operator_id,
                // A return (ReturnService) carries the delivery it reverses.
                'kind' => empty($t['return_of']) ? 'forward' : 'return', 'parent_shipment_id' => $t['return_of'] ?? null, 'return_reason' => $t['return_reason'] ?? null,
                'service_type_id' => $t['service_type_id'], 'vehicle_type_id' => $t['vehicle_type_id'] ?? null,
                'status' => 'created', 'tracking_code' => ParcelCodes::make('QD', 9), 'distance_m' => $a->distance_m,
                // Goods money the rider collects from the receiver for the sender (cash on delivery).
                'cod_amount' => max(0, (int) ($t['cod_amount'] ?? 0)), 'insured_value' => 0, 'is_test' => $a->is_test, 'created_at' => now(), 'updated_at' => now(),
                // A booking for later: dispatch waits until shortly before this (DispatchService::start).
                'pickup_window_start' => $pickupAt, 'pickup_window_end' => $pickupAt?->copy()->addMinutes(30),
                // The proofs both sides signed up to; agreements made before policies existed get the provider's standard.
                'verification_policy' => json_encode(VerificationPolicy::normalize($t['verification']
                    ?? app(VerificationPolicy::class)->forProvider((int) $a->provider_operator_id, (int) $t['service_type_id']))),
            ]);

            $ordered = \App\Modules\Booking\StopList::ordered(['pickup' => $t['pickup'], 'extra' => $t['stops'] ?? [], 'dropoff' => $t['dropoff']]);
            foreach ($ordered as $i => $s) {
                [$type, $seq] = [$s['type'], $i + 1];
                DB::insert(
                    'INSERT INTO shipment_stops (shipment_id, seq, type, line1, landmark, point, contact_name, contact_phone, instructions, pickup_point_id, status, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography, ?, ?, ?, ?, \'pending\', now(), now())',
                    [$shipmentId, $seq, $type, $s['line1'], $s['landmark'] ?? null, $s['lng'], $s['lat'], $s['contact_name'] ?? null, $s['contact_phone'] ?? null, $s['instructions'] ?? null,
                        $type === 'dropoff' ? ($s['pickup_point_id'] ?? null) : null]
                );
            }

            // Every parcel gets its own label code and QR payload.
            // A parcel leaves from the first pickup and goes to the last drop-off unless it names its drop-off
            // (packages[].dropoff = 1 for the first drop-off on the route, 2 for the second, and so on).
            $stopRows = DB::table('shipment_stops')->where('shipment_id', $shipmentId)->orderBy('seq')->get(['id', 'type']);
            $drops = $stopRows->where('type', 'dropoff')->pluck('id')->values();
            $items = (array) ($t['packages'] ?? []);
            app(ParcelCodes::class)->createPackages($shipmentId, $items, $stopRows->firstWhere('type', 'pickup')->id ?? null, $drops->last());
            foreach (array_values(array_filter($items, fn ($i) => is_string($i) ? trim($i) !== '' : is_array($i))) as $n => $item) {
                $to = is_array($item) && isset($item['dropoff']) ? $drops->get((int) $item['dropoff'] - 1) : null;
                if ($to) {
                    DB::table('packages')->where('shipment_id', $shipmentId)->where('seq', $n + 1)->update(['dropoff_stop_id' => $to]);
                }
            }

            if ($a->promo_code_id && $discount > 0) {
                $promoPart = (int) collect(json_decode((string) $a->discount_lines, true) ?: [])->where('kind', 'promo')->sum('amount');
                app(\App\Modules\Pricing\Discounts::class)->redeem((int) $a->promo_code_id, (int) $a->customer_id, $orderId, $promoPart);
            }

            // Arm the drop-off code and share links in the same transaction as the shipment.
            $this->tracking->arm($shipmentId);
            app(\App\Modules\Partner\ShipmentEvents::class)->record($shipmentId, 'created', null, 'created');

            return ['order_id' => $orderId, 'shipment_id' => $shipmentId, 'created' => true];
        });
    }
}
