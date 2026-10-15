<?php

namespace App\Modules\Returns;

use App\Modules\Backoffice\ManualBookingService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * A return: the parcel goes back the other way after the delivery is over (the buyer changed their mind, the wrong
 * item was sent, it arrived damaged, or the receiver never collected it from a pickup point).
 *
 * A return is a delivery of its own (shipments.kind = return, parent_shipment_id = the one it reverses), booked
 * through the same agreement and payment code as any booking, so it is priced, paid, tracked and proved the same way.
 * It picks up where the parcel is now (the receiver's address, or the pickup point it is waiting at) and goes back to
 * the original pickup address, or to one of the provider's pickup points. A delivery has at most one open return.
 */
class ReturnService
{
    public const REASONS = [
        'changed_mind' => 'The buyer changed their mind',
        'wrong_item' => 'Wrong item sent',
        'damaged' => 'Arrived damaged',
        'not_collected' => 'Not collected from the pickup point',
        'other' => 'Another reason',
    ];

    /** A return can be booked this long after the delivery. */
    public const RETURN_DAYS = 30;

    public function __construct(private ManualBookingService $booking, private PickupPointService $points)
    {
    }

    /** Why this shipment cannot be returned now, or null when it can. Pure on the row plus one lookup. */
    public function blocked(object $s): ?string
    {
        if ($s->kind === 'return') {
            return 'This job is itself a return.';
        }
        $open = DB::table('shipments')->where('parent_shipment_id', $s->id)->where('kind', 'return')->where('status', '!=', 'cancelled')->exists();
        if ($open) {
            return 'A return has already been booked for this delivery.';
        }

        return self::window($s->status, $s->delivered_at, CarbonImmutable::now());
    }

    /** Pure: only finished deliveries go back, and only for RETURN_DAYS. */
    public static function window(string $status, ?string $deliveredAt, CarbonImmutable $now): ?string
    {
        if (in_array($status, ['delivered', 'completed'], true)) {
            return $deliveredAt && CarbonImmutable::parse($deliveredAt)->addDays(self::RETURN_DAYS)->isBefore($now)
                ? 'Returns can be booked up to '.self::RETURN_DAYS.' days after delivery.' : null;
        }
        // Closed as not collected: the parcel is still at the pickup point.
        if ($status === 'failed_attempt') {
            return null;
        }

        return 'A return can be booked once the delivery is finished.';
    }

    /**
     * Staff book a return for one of their deliveries.
     *
     * @param  array{reason:string, payment:string, price?:?int, to_point?:?string, note?:?string, driver_user_id?:?int}  $d
     * @return array booking result from ManualBookingService::book
     */
    public function book(int $operatorId, string $shipmentPublicId, array $d, int $staffUserId): array
    {
        if (! isset(self::REASONS[$d['reason'] ?? ''])) {
            throw new RuntimeException('Choose why the parcel is coming back.');
        }
        $s = DB::table('shipments')->where('public_id', $shipmentPublicId)->where('operator_id', $operatorId)->first();
        if (! $s) {
            throw new RuntimeException('That job was not found.');
        }
        if ($why = $this->blocked($s)) {
            throw new RuntimeException($why);
        }

        $in = $this->reversed($s);
        if (! empty($d['to_point'])) {
            $in['dropoff'] = $this->points->asDropoff($operatorId, (string) $d['to_point'], $in['dropoff']);
        }
        $customer = DB::table('orders as o')->join('users as u', 'u.id', '=', 'o.customer_id')->where('o.id', $s->order_id)->first(['u.name', 'u.phone_number', 'u.email']);

        return $this->booking->book($operatorId, $in + [
            'customer' => ['name' => $customer->name, 'phone' => $customer->phone_number,
                'email' => str_ends_with((string) $customer->email, '@phone.invalid') ? null : $customer->email],
            'price' => $d['price'] ?? null, 'payment' => $d['payment'], 'driver_user_id' => $d['driver_user_id'] ?? null,
            'note' => trim('Return: '.self::REASONS[$d['reason']].'. '.($d['note'] ?? '')),
            'return_of' => (int) $s->id, 'return_reason' => $d['reason'],
        ], $staffUserId);
    }

    /**
     * The booking that undoes a shipment: from where the parcel is now back to its first pickup, with the same
     * service, city, vehicle and parcels. Shared by staff returns and partner API returns.
     */
    public function reversed(object $s): array
    {
        $stops = DB::table('shipment_stops')->where('shipment_id', $s->id)->whereIn('type', ['pickup', 'dropoff'])->orderBy('seq')
            ->get(['type', 'line1', 'landmark', 'contact_name', 'contact_phone', DB::raw('ST_Y(point::geometry) AS lat'), DB::raw('ST_X(point::geometry) AS lng')]);
        $from = $stops->where('type', 'dropoff')->last();
        $to = $stops->firstWhere('type', 'pickup');
        if (! $from || ! $to) {
            throw new RuntimeException('This job has no pickup and drop-off to reverse.');
        }
        $stop = fn (object $x) => array_filter(['line1' => $x->line1, 'lat' => (float) $x->lat, 'lng' => (float) $x->lng, 'landmark' => $x->landmark,
            'contact_name' => $x->contact_name, 'contact_phone' => $x->contact_phone], fn ($v) => $v !== null && $v !== '');
        $terms = json_decode((string) DB::table('orders as o')->join('agreements as a', 'a.id', '=', 'o.agreement_id')->where('o.id', $s->order_id)->value('a.terms'), true) ?: [];
        $packages = DB::table('packages')->where('shipment_id', $s->id)->orderBy('id')->get(['description', 'weight_g', 'fragile'])
            ->map(fn ($p) => array_filter(['description' => $p->description ?: 'Parcel', 'weight_g' => $p->weight_g, 'fragile' => $p->fragile ?: null], fn ($v) => $v !== null))->all();

        return [
            'service_type_id' => (int) $s->service_type_id, 'city_id' => (int) ($terms['city_id'] ?? 0) ?: null,
            'vehicle_type_id' => $s->vehicle_type_id ? (int) $s->vehicle_type_id : null,
            'weight_g' => (int) array_sum(array_column($packages, 'weight_g')),
            'pickup' => $stop($from), 'dropoff' => $stop($to), 'packages' => $packages ?: [['description' => 'Parcel']],
        ];
    }

    /** What the job page shows: the return of this delivery, or the delivery this return reverses. */
    public function forJob(object $s): array
    {
        $link = fn ($id) => $id ? DB::table('shipments as s')->join('orders as o', 'o.id', '=', 's.order_id')->where('s.id', $id)
            ->first(['s.public_id', 's.status', 's.return_reason', 'o.order_number']) : null;

        return [
            'parent' => $s->kind === 'return' ? $link($s->parent_shipment_id) : null,
            'return' => $link(DB::table('shipments')->where('parent_shipment_id', $s->id)->where('kind', 'return')->where('status', '!=', 'cancelled')->value('id')),
            'blocked' => $this->blocked($s),
            'reason' => $s->return_reason ? (self::REASONS[$s->return_reason] ?? $s->return_reason) : null,
        ];
    }
}
