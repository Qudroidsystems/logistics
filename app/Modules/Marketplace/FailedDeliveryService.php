<?php

namespace App\Modules\Marketplace;

use App\Modules\Partner\ShipmentEvents;
use App\Modules\Payments\DriverPayService;
use App\Modules\Payments\Escrow\EscrowService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * A driver reports that the receiver cannot be reached, refuses the parcel, or the address is wrong. The money and the
 * parcel then follow the agreement's frozen FailedDeliveryPolicy: the customer keeps paying a share, the rest is refunded,
 * and the driver brings the parcel back (a return stop) or the job simply ends as a failed attempt.
 * Problems on the company's side (vehicle trouble and the like) are not failures of the delivery: report a problem instead.
 */
class FailedDeliveryService
{
    public const REASONS = [
        'receiver_unreachable' => 'Receiver not answering or not there',
        'receiver_refused' => 'Receiver refused the parcel',
        'wrong_address' => 'Address is wrong or cannot be found',
    ];

    public function __construct(private EscrowService $escrow, private ShipmentEvents $events, private DriverPayService $pay)
    {
    }

    /** @return array{returning:bool,charged:int,refunded:int,money:bool} */
    public function markFailed(int $driverId, string $shipmentPublicId, string $reason, ?string $note = null): array
    {
        if (! isset(self::REASONS[$reason])) {
            throw new RuntimeException('Choose why the delivery failed.');
        }

        return DB::transaction(function () use ($driverId, $shipmentPublicId, $reason, $note) {
            $s = DB::table('shipments')->where('public_id', $shipmentPublicId)->lockForUpdate()->first();
            $a = $s ? DB::table('assignments')->where('shipment_id', $s->id)->where('driver_profile_id', $driverId)->whereIn('status', ['accepted', 'en_route', 'active'])->lockForUpdate()->first() : null;
            if (! $a) {
                throw new RuntimeException('This job is not yours.');
            }
            if ($s->status !== 'at_dropoff') {
                throw new RuntimeException('You can report a failed delivery once you have arrived at the drop-off.');
            }
            $order = DB::table('orders')->where('id', $s->order_id)->lockForUpdate()->first();
            $agreement = $order && $order->agreement_id ? DB::table('agreements')->where('id', $order->agreement_id)->lockForUpdate()->first() : null;
            $policy = FailedDeliveryPolicy::normalize($agreement && $agreement->failed_delivery_policy ? json_decode($agreement->failed_delivery_policy, true) : null);

            $drop = DB::table('shipment_stops')->where('shipment_id', $s->id)->where('type', 'dropoff')->orderByDesc('seq')->first();
            if ($drop && $drop->arrived_at && $policy['wait_minutes'] > 0) {
                $waited = (int) floor((time() - strtotime($drop->arrived_at)) / 60);
                if ($waited < $policy['wait_minutes']) {
                    throw new RuntimeException('Please keep trying the receiver. You can report a failed delivery after '.($policy['wait_minutes'] - $waited).' more minutes.');
                }
            }

            // The parcel goes back only when the agreement says so. Otherwise the job ends here as a failed attempt.
            $returning = $policy['return_to_sender'];
            if ($drop) {
                DB::table('shipment_stops')->where('id', $drop->id)->update(['status' => 'failed', 'updated_at' => now()]);
            }
            if ($returning) {
                $this->addReturnStop((int) $s->id);
            } else {
                DB::table('assignments')->where('id', $a->id)->update(['status' => 'completed', 'completed_at' => now(), 'updated_at' => now()]);
                DB::table('driver_profiles')->where('id', $driverId)->update(['availability' => 'online', 'last_trip_at' => now(), 'updated_at' => now()]);
            }
            $to = $returning ? 'returning' : 'failed_attempt';
            DB::table('shipments')->where('id', $s->id)->update(['status' => $to, 'updated_at' => now()]);

            // Money last, so the driver-pay step already sees the final shipment and assignment state.
            $charged = $refunded = 0;
            $money = false;
            $hasShopping = $agreement && DB::table('shopping_requests')->where('agreement_id', $agreement->id)->exists();
            if ($agreement && ! $hasShopping && in_array($agreement->status, ['paid', 'in_progress', 'delivered'], true)) {
                $charged = FailedDeliveryPolicy::charge((int) $agreement->price, $policy);
                $res = $charged > 0 ? $this->escrow->releasePartial((int) $agreement->id, $charged, 0, null) : $this->escrow->refund((int) $agreement->id, null, null);
                $refunded = $charged > 0 ? (int) $res['to_customer'] : (int) $res['refunded'];
                $money = true;
                DB::table('orders')->where('id', $order->id)->update([
                    'status' => 'failed', 'payment_status' => $charged > 0 ? 'partially_refunded' : 'refunded', 'cancelled_at' => now(),
                    'cancelled_by' => 'system', 'cancel_reason_code' => 'delivery_failed', 'cancel_fee' => $charged, 'updated_at' => now(),
                ]);
                if ($refunded > 0) {
                    DB::table('refunds')->insert([
                        'public_id' => (string) Str::ulid(), 'order_id' => $order->id, 'amount' => $refunded, 'reason_code' => 'delivery_failed',
                        'initiated_by' => null, 'status' => 'completed', 'ledger_tx_id' => $res['transaction_id'], 'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
            }

            $this->events->record((int) $s->id, 'delivery_failed', 'at_dropoff', $to, 'driver', $driverId, [
                'reason' => $reason, 'note' => $note ? mb_substr($note, 0, 500) : null, 'charged' => $charged, 'refunded' => $refunded, 'returning' => $returning,
            ]);

            return ['returning' => $returning, 'charged' => $charged, 'refunded' => $refunded, 'money' => $money];
        });
    }

    /** The driver has handed the parcel back at the pickup address (called by StopService for the return stop). */
    public function completeReturn(int $shipmentId, int $driverId): void
    {
        DB::table('assignments')->where('shipment_id', $shipmentId)->where('driver_profile_id', $driverId)->whereIn('status', ['accepted', 'en_route', 'active'])
            ->update(['status' => 'completed', 'completed_at' => now(), 'updated_at' => now()]);
        DB::table('driver_profiles')->where('id', $driverId)->update(['availability' => 'online', 'last_trip_at' => now(), 'updated_at' => now()]);
        DB::table('shipments')->where('id', $shipmentId)->update(['status' => 'returned', 'updated_at' => now()]);
        $this->events->record($shipmentId, 'returned', 'returning', 'returned', 'driver', $driverId);
        // Pay was held back until the driver finished the whole trip, including the way back.
        $this->pay->payFromCommission($shipmentId);
    }

    /** A stop back at the pickup address, copied from the pickup stop. */
    private function addReturnStop(int $shipmentId): void
    {
        $seq = (int) DB::table('shipment_stops')->where('shipment_id', $shipmentId)->max('seq') + 1;
        DB::insert(
            "INSERT INTO shipment_stops (shipment_id, seq, type, address_id, line1, landmark, point, h3_9, contact_name, contact_phone, instructions, status, requires_photo, created_at, updated_at)
             SELECT shipment_id, ?, 'return', address_id, line1, landmark, point, h3_9, contact_name, contact_phone, instructions, 'pending', false, now(), now()
             FROM shipment_stops WHERE shipment_id = ? AND type = 'pickup' ORDER BY seq LIMIT 1",
            [$seq, $shipmentId]
        );
    }
}
