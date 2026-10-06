<?php

namespace App\Modules\Partner;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * The one place a shipment's lifecycle is recorded: it writes the shipment_events timeline row and,
 * for customer-visible milestones, queues signed webhooks to the merchant and the provider.
 * A webhook problem is logged and never allowed to break the delivery itself.
 */
class ShipmentEvents
{
    /** internal event type => public webhook name. Types not listed stay internal. */
    public const PUBLIC = [
        'created' => 'delivery.created',
        'assigned' => 'delivery.assigned',
        'arrived' => 'delivery.driver_arrived',
        'picked_up' => 'delivery.picked_up',
        'delivered' => 'delivery.delivered',
        'confirmed' => 'delivery.confirmed',
        'disputed' => 'delivery.disputed',
        'dispute_resolved' => 'delivery.dispute_resolved',
        'cancelled' => 'delivery.cancelled',
    ];

    public function __construct(private WebhookService $webhooks)
    {
    }

    public function record(int $shipmentId, string $type, ?string $from = null, ?string $to = null, string $actorType = 'system', ?int $actorId = null, array $meta = []): void
    {
        $seq = (int) DB::table('shipment_events')->where('shipment_id', $shipmentId)->max('seq') + 1;
        DB::table('shipment_events')->insert([
            'shipment_id' => $shipmentId, 'seq' => $seq, 'type' => $type, 'from_status' => $from, 'to_status' => $to,
            'actor_type' => $actorType, 'actor_id' => $actorId, 'meta' => json_encode($meta), 'source' => 'system', 'created_at' => now(),
        ]);

        app(\App\Modules\Notifications\NotificationService::class)->shipmentEvent($shipmentId, $type, $meta);

        if (isset(self::PUBLIC[$type])) {
            try {
                // Nested transaction = savepoint, so a failed webhook insert cannot abort the caller's Postgres transaction.
                DB::transaction(fn () => $this->publish($shipmentId, self::PUBLIC[$type], $meta));
            } catch (Throwable $e) {
                Log::warning("Webhook emit failed for shipment {$shipmentId}: {$e->getMessage()}");
            }
        }
    }

    /** Who hears about it, and what the payload may contain. Never phone numbers, addresses' contacts or delivery codes. */
    public function publish(int $shipmentId, string $event, array $meta = []): void
    {
        $row = DB::table('shipments as s')->join('orders as o', 'o.id', '=', 's.order_id')
            ->leftJoin('merchants as m', 'm.id', '=', 'o.merchant_id')
            ->where('s.id', $shipmentId)
            ->select('s.status', 's.tracking_code', 's.operator_id as provider_operator_id', 's.is_test', 'o.order_number', 'o.external_order_id', 'o.agreement_id', 'm.operator_id as merchant_operator_id', 'm.merchant_code')
            ->first();
        if (! $row) {
            return;
        }

        $data = [
            'order_number' => $row->order_number, 'external_order_id' => $row->external_order_id, 'merchant_code' => $row->merchant_code,
            'tracking_code' => $row->tracking_code, 'status' => $row->status,
        ] + array_intersect_key($meta, array_flip(['reason', 'decision', 'eta_minutes']));

        foreach (array_unique(array_filter([$row->merchant_operator_id, $row->provider_operator_id])) as $operatorId) {
            $this->webhooks->emit((int) $operatorId, $event, $data, (bool) $row->is_test);
        }
    }
}
