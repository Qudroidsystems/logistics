<?php

namespace App\Modules\Marketplace;

use App\Modules\Partner\ShipmentEvents;
use App\Modules\Payments\Escrow\EscrowService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Cancels an agreement/shipment and settles the money by how far the job had got:
 *
 *   unpaid (locked)            nothing to refund
 *   not yet assigned           full refund
 *   driver assigned, not yet   customer pays the policy fee to the provider; provider or staff cancels = full refund
 *   collected
 *   picked up or later         cannot cancel; open a dispute instead
 */
class CancellationService
{
    public const DEFAULT_FEE_BP = 1000; // 10% of the service price when the customer cancels after a driver is on the way

    public function __construct(private EscrowService $escrow, private ShipmentEvents $events)
    {
    }

    /** Pure rule, unit tested. */
    public function fee(string $by, string $stage, int $price, ?array $policy): int
    {
        if ($by !== 'customer' || $stage !== 'after_assignment') {
            return 0;
        }
        $bp = (int) ($policy['after_assignment_fee_bp'] ?? self::DEFAULT_FEE_BP);

        return min($price, intdiv($price * $bp, 10_000));
    }

    public function stage(string $shipmentStatus): string
    {
        return match (true) {
            in_array($shipmentStatus, ['created', 'awaiting_dispatch', 'offered'], true) => 'before_assignment',
            in_array($shipmentStatus, ['assigned', 'heading_to_pickup', 'at_pickup'], true) => 'after_assignment',
            default => 'not_cancellable',
        };
    }

    /** @param 'customer'|'provider'|'staff' $by */
    public function cancelShipment(int $shipmentId, string $by, int $userId, string $reasonCode): array
    {
        $result = DB::transaction(function () use ($shipmentId, $by, $userId, $reasonCode) {
            $s = DB::table('shipments')->where('id', $shipmentId)->lockForUpdate()->first();
            $order = $s ? DB::table('orders')->where('id', $s->order_id)->lockForUpdate()->first() : null;
            if (! $order || ! $order->agreement_id) {
                throw new RuntimeException('Nothing to cancel.');
            }
            $a = DB::table('agreements')->where('id', $order->agreement_id)->lockForUpdate()->first();
            $stage = $this->stage($s->status);
            if ($stage === 'not_cancellable' || in_array($a->status, ['cancelled', 'completed', 'disputed', 'delivered'], true)) {
                throw new RuntimeException('This job can no longer be cancelled. Report a problem instead.');
            }
            if (DB::table('shopping_receipts')->whereIn('shopping_request_id', DB::table('shopping_requests')->where('agreement_id', $a->id)->select('id'))->exists()) {
                throw new RuntimeException('Shopping has already started. Report a problem instead.');
            }

            if (DB::table('shopper_advances')->where('agreement_id', $a->id)->where('status', 'issued')->exists()) {
                throw new RuntimeException('The shopper already has an advance. Report a problem instead.');
            }

            $fee = $this->fee($by, $stage, (int) $a->price, $a->cancellation_policy ? json_decode($a->cancellation_policy, true) : null);
            $money = $fee > 0
                ? $this->escrow->releasePartial((int) $a->id, $fee, 0, $userId)
                : $this->escrow->refund((int) $a->id, null, $userId);
            $refund = $fee > 0 ? $money['to_customer'] : $money['refunded'];

            DB::table('agreements')->where('id', $a->id)->update(['status' => 'cancelled', 'updated_at' => now()]);
            DB::table('orders')->where('id', $order->id)->update([
                'status' => 'cancelled', 'payment_status' => $fee > 0 ? 'partially_refunded' : 'refunded', 'cancelled_at' => now(),
                'cancelled_by' => $by, 'cancel_reason_code' => $reasonCode, 'cancel_fee' => $fee, 'updated_at' => now(),
            ]);
            $from = $s->status;
            DB::table('shipments')->where('id', $s->id)->update(['status' => 'cancelled', 'updated_at' => now()]);
            $this->releaseDriver((int) $s->id);

            DB::table('cancellations')->insert([
                'order_id' => $order->id, 'requested_by' => $userId, 'reason_code' => $reasonCode, 'stage' => $stage,
                'fee' => $fee, 'refund_amount' => $refund, 'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('refunds')->insert([
                'public_id' => (string) Str::ulid(), 'order_id' => $order->id, 'amount' => $refund, 'reason_code' => 'cancelled_'.$by,
                'initiated_by' => $userId, 'status' => 'completed', 'ledger_tx_id' => $money['transaction_id'], 'created_at' => now(), 'updated_at' => now(),
            ]);

            if ($by === 'provider' && $stage === 'after_assignment') {
                DB::table('risk_events')->insert([
                    'subject_type' => 'operator', 'subject_id' => $a->provider_operator_id, 'type' => 'cancel_abuse', 'severity' => 'low',
                    'evidence' => json_encode(['shipment_id' => $s->id, 'reason' => $reasonCode]), 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
            $this->events->record((int) $s->id, 'cancelled', $from, 'cancelled', $by, $userId, ['reason' => $reasonCode]);

            return ['stage' => $stage, 'fee' => $fee, 'refunded' => $refund];
        });

        return $result;
    }

    /** Customer or merchant cancels an agreement that was locked but never paid. */
    public function cancelUnpaid(int $agreementId, int $userId): void
    {
        DB::transaction(function () use ($agreementId, $userId) {
            $n = DB::table('agreements')->where('id', $agreementId)->whereIn('status', ['draft', 'awaiting_customer', 'awaiting_provider', 'locked'])
                ->where(fn ($q) => $q->where('customer_id', $userId))->update(['status' => 'cancelled', 'updated_at' => now()]);
            if (! $n) {
                throw new RuntimeException('Agreement cannot be cancelled here.');
            }
            DB::table('payment_intents')->where('agreement_id', $agreementId)->whereIn('status', ['initiated', 'pending'])->update(['status' => 'abandoned', 'updated_at' => now()]);
        });
    }

    /** Housekeeping: locked agreements nobody paid within the window. */
    public function expireUnpaid(int $hours = 24): int
    {
        $ids = DB::table('agreements')->where('status', 'locked')->where('locked_at', '<', now()->subHours($hours))
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('escrow_holds')->whereColumn('escrow_holds.agreement_id', 'agreements.id'))->pluck('id');
        foreach ($ids as $id) {
            DB::table('agreements')->where('id', $id)->update(['status' => 'cancelled', 'updated_at' => now()]);
            DB::table('payment_intents')->where('agreement_id', $id)->whereIn('status', ['initiated', 'pending'])->update(['status' => 'abandoned', 'updated_at' => now()]);
        }

        return $ids->count();
    }

    private function releaseDriver(int $shipmentId): void
    {
        $drivers = DB::table('assignments')->where('shipment_id', $shipmentId)->whereIn('status', ['assigned', 'accepted', 'en_route', 'active'])->pluck('driver_profile_id');
        DB::table('assignments')->where('shipment_id', $shipmentId)->whereIn('status', ['assigned', 'accepted', 'en_route', 'active'])->update(['status' => 'cancelled', 'updated_at' => now()]);
        DB::table('driver_profiles')->whereIn('id', $drivers)->where('availability', 'on_job')->update(['availability' => 'online', 'updated_at' => now()]);
        DB::table('dispatch_offers')->where('shipment_id', $shipmentId)->whereNull('response')->update(['response' => 'cancelled_by_system', 'responded_at' => now()]);
        DB::table('dispatch_runs')->where('shipment_id', $shipmentId)->whereNull('outcome')->update(['outcome' => 'cancelled', 'finished_at' => now()]);
    }
}
