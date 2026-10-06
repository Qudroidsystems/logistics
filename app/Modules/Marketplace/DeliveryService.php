<?php

namespace App\Modules\Marketplace;

use App\Modules\Payments\Escrow\EscrowService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Delivered -> customer confirms (escrow releases) or objects (escrow freezes and a dispute opens).
 * Silence past the window is handled by escrow:auto-release.
 */
class DeliveryService
{
    public function __construct(private EscrowService $escrow)
    {
    }

    /** Called when the driver completes the drop-off stop (proof captured). Starts the customer's clock. */
    public function markDelivered(int $shipmentId): void
    {
        DB::transaction(function () use ($shipmentId) {
            $s = DB::table('shipments')->where('id', $shipmentId)->lockForUpdate()->first();
            $agreementId = DB::table('orders')->where('id', $s->order_id)->value('agreement_id');
            if (! $agreementId) {
                return; // single-company orders settle through their own flow
            }
            $a = DB::table('agreements')->find($agreementId);

            DB::table('shipments')->where('id', $shipmentId)->update(['status' => 'delivered', 'delivered_at' => now(), 'updated_at' => now()]);
            DB::table('agreements')->where('id', $agreementId)->update(['status' => 'delivered', 'updated_at' => now()]);
            DB::table('delivery_confirmations')->insertOrIgnore([
                'agreement_id' => $agreementId, 'shipment_id' => $shipmentId, 'status' => 'pending',
                'requested_at' => now(), 'deadline_at' => now()->addHours((int) $a->confirmation_window_hours),
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $this->escrow->startConfirmationWindow($agreementId);
            app(\App\Modules\Partner\ShipmentEvents::class)->record($shipmentId, 'delivered', $s->status, 'delivered');
        });
    }

    /** Customer is satisfied: pay the provider. */
    public function confirm(int $shipmentId, int $customerId, ?int $rating = null): array
    {
        return DB::transaction(function () use ($shipmentId, $customerId, $rating) {
            [$c, $a] = $this->pending($shipmentId, $customerId);
            $result = $this->escrow->release((int) $a->id, $this->goodsSpent((int) $a->id), $customerId);
            app(\App\Modules\Partner\ShipmentEvents::class)->record($shipmentId, 'confirmed', 'delivered', 'delivered', 'customer', $customerId);
            DB::table('delivery_confirmations')->where('id', $c->id)->update([
                'status' => 'confirmed', 'responded_at' => now(), 'rating_given' => $rating, 'updated_at' => now(),
            ]);

            return $result;
        });
    }

    /** Customer is not satisfied: freeze escrow and open a dispute for staff to decide. */
    public function object(int $shipmentId, int $customerId, string $type, string $reason, array $evidence = []): int
    {
        return DB::transaction(function () use ($shipmentId, $customerId, $type, $reason, $evidence) {
            [$c, $a] = $this->pending($shipmentId, $customerId);
            $this->escrow->freeze((int) $a->id, "dispute:{$type}");
            DB::table('delivery_confirmations')->where('id', $c->id)->update([
                'status' => 'objected', 'responded_at' => now(), 'objection_reason' => $reason, 'evidence' => json_encode($evidence), 'updated_at' => now(),
            ]);

            app(\App\Modules\Partner\ShipmentEvents::class)->record($shipmentId, 'disputed', 'delivered', 'delivered', 'customer', $customerId, ['reason' => $type]);

            return DB::table('disputes')->insertGetId([
                'public_id' => (string) Str::ulid(), 'shipment_id' => $shipmentId, 'agreement_id' => $a->id,
                'escrow_hold_id' => DB::table('escrow_holds')->where('agreement_id', $a->id)->value('id'),
                'raised_by' => $customerId, 'type' => $type, 'status' => 'open',
                'evidence' => json_encode(['reason' => $reason] + $evidence), 'created_at' => now(), 'updated_at' => now(),
            ]);
        });
    }

    /** Shopping errands settle on the receipts the customer has not rejected; deliveries have no goods. */
    private function goodsSpent(int $agreementId): int
    {
        return app(ShoppingService::class)->spend($agreementId);
    }

    private function pending(int $shipmentId, int $customerId): array
    {
        $c = DB::table('delivery_confirmations')->where('shipment_id', $shipmentId)->lockForUpdate()->first();
        $a = $c ? DB::table('agreements')->where('id', $c->agreement_id)->where('customer_id', $customerId)->first() : null;
        if (! $c || ! $a || $c->status !== 'pending') {
            throw new RuntimeException('This delivery is not awaiting your confirmation.');
        }

        return [$c, $a];
    }
}
