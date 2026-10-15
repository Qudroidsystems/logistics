<?php

namespace App\Modules\Returns;

use App\Modules\Partner\ShipmentEvents;
use App\Modules\Verification\CustodyLog;
use App\Modules\Verification\DeliveryCodes;
use App\Modules\Verification\WrongDeliveryCode;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * A provider's pickup points: shops or offices where a parcel waits for the receiver.
 *
 *   1. A booking's drop-off is a pickup point (its address is copied onto the stop, as for any address).
 *   2. The rider leaves the parcel there with a photo; the job is not "delivered" yet. The parcel is held for the
 *      point's hold_days and the receiver gets a text with the point's address and a fresh delivery code that lasts
 *      until the hold ends.
 *   3. The receiver collects it: staff type the receiver's code on the pickup points page. That is the delivery, and
 *      the customer's confirmation window starts then, as for a door delivery.
 *   4. A parcel not collected in time can be sent back (ReturnService, reason not_collected).
 */
class PickupPointService
{
    public function __construct(private ShipmentEvents $events, private DeliveryCodes $codes)
    {
    }

    /** @return list<object> the provider's points, active first */
    public function all(int $operatorId): array
    {
        return DB::table('pickup_points')->where('operator_id', $operatorId)->orderByRaw("status = 'active' DESC")->orderBy('name')
            ->get(['id', 'public_id', 'code', 'name', 'line1', 'landmark', 'phone', 'opening_hours', 'hold_days', 'status', 'city_id',
                DB::raw('ST_Y(point::geometry) AS lat'), DB::raw('ST_X(point::geometry) AS lng')])->all();
    }

    /** @param array{code:string, name:string, line1:string, lat:float, lng:float, landmark?:?string, phone?:?string, opening_hours?:?string, hold_days?:?int, city_id?:?int} $d */
    public function save(int $operatorId, array $d, ?string $publicId = null): string
    {
        $code = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $d['code']));
        if ($code === '' || strlen($code) > 12) {
            throw new RuntimeException('Give the point a short code of letters and numbers, up to 12 (for example IKJ01).');
        }
        $taken = DB::table('pickup_points')->where('operator_id', $operatorId)->where('code', $code)
            ->when($publicId, fn ($q) => $q->where('public_id', '!=', $publicId))->exists();
        if ($taken) {
            throw new RuntimeException("Another of your pickup points already uses the code {$code}.");
        }
        $row = [
            'code' => $code, 'name' => trim($d['name']), 'line1' => trim($d['line1']), 'landmark' => $d['landmark'] ?? null,
            'phone' => $d['phone'] ?? null, 'opening_hours' => $d['opening_hours'] ?? null, 'city_id' => $d['city_id'] ?? null,
            'hold_days' => max(1, min(30, (int) ($d['hold_days'] ?? 5))),
            'point' => DB::raw(sprintf('ST_SetSRID(ST_MakePoint(%F, %F), 4326)::geography', (float) $d['lng'], (float) $d['lat'])),
            'updated_at' => now(),
        ];
        if ($publicId) {
            $n = DB::table('pickup_points')->where('operator_id', $operatorId)->where('public_id', $publicId)->update($row);
            if (! $n) {
                throw new RuntimeException('That pickup point was not found.');
            }

            return $publicId;
        }
        $publicId = (string) Str::ulid();
        DB::table('pickup_points')->insert($row + ['public_id' => $publicId, 'operator_id' => $operatorId, 'status' => 'active', 'created_at' => now()]);

        return $publicId;
    }

    public function setStatus(int $operatorId, string $publicId, bool $active): void
    {
        DB::table('pickup_points')->where('operator_id', $operatorId)->where('public_id', $publicId)
            ->update(['status' => $active ? 'active' : 'paused', 'updated_at' => now()]);
    }

    /** An active point of this provider, by its code or public id; throws a sentence for the form when there is none. */
    public function resolve(int $operatorId, string $ref): object
    {
        $ref = trim($ref);
        $p = DB::table('pickup_points')->where('operator_id', $operatorId)
            ->where(fn ($q) => $q->where('public_id', $ref)->orWhere('code', strtoupper($ref)))
            ->first(['id', 'public_id', 'code', 'name', 'line1', 'landmark', 'phone', 'status', DB::raw('ST_Y(point::geometry) AS lat'), DB::raw('ST_X(point::geometry) AS lng')]);
        if (! $p) {
            throw new RuntimeException("There is no pickup point with the code {$ref}.");
        }
        if ($p->status !== 'active') {
            throw new RuntimeException("The pickup point {$p->name} is paused and is not taking parcels.");
        }

        return $p;
    }

    /**
     * The drop-off for a booking that goes to a point: the point's address, with the receiver kept as the contact,
     * so the receiver is the one texted and the one who collects.
     */
    public function asDropoff(int $operatorId, string $ref, array $receiver = []): array
    {
        $p = $this->resolve($operatorId, $ref);

        return array_filter([
            'line1' => $p->name.', '.$p->line1, 'lat' => (float) $p->lat, 'lng' => (float) $p->lng, 'landmark' => $p->landmark,
            'contact_name' => $receiver['contact_name'] ?? null, 'contact_phone' => $receiver['contact_phone'] ?? null,
            'instructions' => trim('Leave at pickup point '.$p->code.'. '.($receiver['instructions'] ?? '')),
            'pickup_point_id' => (int) $p->id,
        ], fn ($v) => $v !== null && $v !== '');
    }

    /**
     * The rider has left the parcel at the point (called by StopService inside its transaction). The rider's part of
     * the job is done; the delivery itself happens when the receiver collects.
     */
    public function receive(int $shipmentId, int $driverProfileId, object $stop): void
    {
        $point = DB::table('pickup_points')->where('id', $stop->pickup_point_id)->first(['name', 'line1', 'opening_hours', 'hold_days']);
        $until = CarbonImmutable::now()->addDays((int) ($point->hold_days ?? 5))->endOfDay();

        DB::table('assignments')->where('shipment_id', $shipmentId)->where('driver_profile_id', $driverProfileId)->whereIn('status', ['accepted', 'en_route', 'active'])
            ->update(['status' => 'completed', 'completed_at' => now(), 'updated_at' => now()]);
        DB::table('driver_profiles')->where('id', $driverProfileId)->update(['availability' => 'online', 'last_trip_at' => now(), 'updated_at' => now()]);
        $from = (string) DB::table('shipments')->where('id', $shipmentId)->value('status');
        DB::table('shipments')->where('id', $shipmentId)->update([
            'status' => 'at_pickup_point', 'held_at' => now(), 'hold_until' => $until->toIso8601String(), 'updated_at' => now(),
        ]);
        // The code the receiver brings: fresh, and good for as long as the parcel is kept.
        $code = $this->codes->issue($shipmentId, $until);
        $this->events->record($shipmentId, 'at_pickup_point', $from, 'at_pickup_point', 'driver', $driverProfileId, ['hold_until' => $until->toIso8601String()]);

        $phone = $stop->contact_phone;
        if ($phone && $code && $point) {
            $order = (string) DB::table('orders as o')->join('shipments as s', 's.order_id', '=', 'o.id')->where('s.id', $shipmentId)->value('o.order_number');
            $hours = $point->opening_hours ? " Open {$point->opening_hours}." : '';
            $text = "Your parcel for order {$order} is ready to collect at {$point->name}, {$point->line1}.{$hours} Bring this code: {$code}. It is kept until ".$until->format('j M').'.';
            try {
                // A savepoint, so a failed text never undoes the hand-over.
                DB::transaction(function () use ($phone, $text, $shipmentId) {
                    app(\App\Modules\Notifications\Sms\SmsService::class)->toNumber($phone, $text, null, 'delivery.at_pickup_point');
                    $this->codes->markSent($shipmentId);
                });
            } catch (Throwable $e) {
                Log::warning("Pickup point text for shipment {$shipmentId} failed: {$e->getMessage()}");
            }
        }
    }

    /**
     * The receiver collects: staff type the code the receiver shows. A wrong code still counts towards the lock, as
     * at the door.
     */
    public function collect(int $operatorId, int $staffUserId, string $shipmentPublicId, string $code, ?string $receiverName = null): void
    {
        $stopId = null;
        try {
            DB::transaction(function () use ($operatorId, $staffUserId, $shipmentPublicId, $code, $receiverName, &$stopId) {
                $s = DB::table('shipments')->where('public_id', $shipmentPublicId)->where('operator_id', $operatorId)->lockForUpdate()->first();
                if (! $s || $s->status !== 'at_pickup_point') {
                    throw new RuntimeException('That parcel is not waiting at a pickup point.');
                }
                $stop = DB::table('shipment_stops')->where('shipment_id', $s->id)->where('type', 'dropoff')->orderByDesc('seq')->first();
                $stopId = (int) $stop->id;
                if (trim($code) === '') {
                    throw new RuntimeException('Type the code the receiver shows you.');
                }
                $this->codes->verify($stopId, $code);

                $proofId = DB::table('proofs')->insertGetId([
                    'public_id' => (string) Str::ulid(), 'shipment_id' => $s->id, 'stop_id' => $stopId, 'type' => 'code', 'otp_verified' => true,
                    'recipient_name' => $receiverName ? mb_substr(trim($receiverName), 0, 120) : null,
                    'checks' => json_encode(['required' => ['code'], 'satisfied' => ['code'], 'waived' => [], 'at' => 'pickup_point']),
                    'point' => $stop->point, 'verified_by' => 'staff', 'captured_at' => now(), 'created_at' => now(),
                ]);
                app(CustodyLog::class)->append((int) $s->id, 'proof', 'proof', $proofId, [
                    'stop_id' => $stopId, 'stop_type' => 'pickup_point', 'staff_user_id' => $staffUserId, 'satisfied' => ['code'], 'waived' => [],
                ]);
                app(\App\Modules\Marketplace\DeliveryService::class)->markDelivered((int) $s->id);
                // Orders without an agreement (single-company mode) settle elsewhere, but the parcel is still out of the point.
                DB::table('shipments')->where('id', $s->id)->where('status', 'at_pickup_point')
                    ->update(['status' => 'delivered', 'delivered_at' => now(), 'updated_at' => now()]);
            });
        } catch (WrongDeliveryCode $e) {
            $this->codes->recordFailure($stopId);

            throw new RuntimeException($e->attemptsLeft > 0
                ? "That code is not right. {$e->attemptsLeft} more ".($e->attemptsLeft === 1 ? 'try' : 'tries').' before it locks.'
                : 'That code is not right, and it is now locked for '.DeliveryCodes::LOCK_MINUTES.' minutes.');
        }
    }

    /** Parcels waiting at this provider's points, the ones past their hold first. */
    public function waiting(int $operatorId): array
    {
        return DB::table('shipments as s')->join('orders as o', 'o.id', '=', 's.order_id')
            ->join('shipment_stops as st', fn ($j) => $j->on('st.shipment_id', '=', 's.id')->whereNotNull('st.pickup_point_id'))
            ->join('pickup_points as p', 'p.id', '=', 'st.pickup_point_id')
            ->where('s.operator_id', $operatorId)->where('s.status', 'at_pickup_point')
            ->orderBy('s.hold_until')
            ->get(['s.public_id', 's.tracking_code', 's.held_at', 's.hold_until', 'o.order_number', 'p.name as point', 'p.code', 'st.contact_name', 'st.contact_phone'])
            ->map(fn ($r) => (array) $r + ['overdue' => CarbonImmutable::parse($r->hold_until)->isPast()])->all();
    }

    /** What the job page shows about the point, or null for a door delivery. */
    public function forJob(object $s): ?array
    {
        $p = DB::table('shipment_stops as st')->join('pickup_points as p', 'p.id', '=', 'st.pickup_point_id')
            ->where('st.shipment_id', $s->id)->where('st.type', 'dropoff')->first(['p.name', 'p.code', 'p.line1', 'p.phone', 'p.opening_hours']);

        return $p ? ['point' => $p, 'waiting' => $s->status === 'at_pickup_point', 'held_at' => $s->held_at, 'hold_until' => $s->hold_until,
            'overdue' => $s->status === 'at_pickup_point' && $s->hold_until && CarbonImmutable::parse($s->hold_until)->isPast()] : null;
    }
}
