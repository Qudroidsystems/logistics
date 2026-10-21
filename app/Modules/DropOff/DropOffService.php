<?php

namespace App\Modules\DropOff;

use App\Models\User;
use App\Modules\Notifications\NotificationService;
use App\Modules\Partner\ShipmentEvents;
use App\Support\Money;
use App\Support\Phone;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Drop-off centres (partner stores): the "become a partner" form, approving stores, the counter where parcels are
 * handed in and collected, and the store's commission.
 *
 * A delivery booked "I'll drop it at a store" starts at the store. Dispatch waits until the store has the parcel
 * (DispatchService::start asks waitingForDropOff), so a driver is never sent to collect a parcel that is not there.
 */
class DropOffService
{
    public function __construct(private NotificationService $notifications, private ShipmentEvents $events) {}

    // ---------------------------------------------------------------- finding stores

    /** Active stores, nearest first when a point is given. */
    public function list(?float $lat = null, ?float $lng = null, int $limit = 50): array
    {
        $dist = $lat !== null && $lng !== null ? 'ST_Distance(point, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography)' : 'NULL';
        $rows = DB::select(
            "SELECT public_id, code, name, line1, phone, opening_hours, ST_Y(point::geometry) AS lat, ST_X(point::geometry) AS lng, $dist AS distance_m
             FROM dropoff_centres WHERE status = 'active' ORDER BY ".($dist === 'NULL' ? 'name' : 'distance_m').' LIMIT ?',
            $dist === 'NULL' ? [$limit] : [$lng, $lat, $limit]
        );

        return array_map(fn ($r) => [
            'id' => $r->public_id, 'code' => $r->code, 'name' => $r->name, 'address' => $r->line1, 'phone' => $r->phone ? Phone::display($r->phone) : null,
            'hours' => $r->opening_hours, 'lat' => (float) $r->lat, 'lng' => (float) $r->lng,
            'distance_km' => $r->distance_m !== null ? round($r->distance_m / 1000, 1) : null,
        ], $rows);
    }

    /** An active store by its public id, with its location. */
    public function active(string $publicId): object
    {
        $c = DB::selectOne(
            "SELECT id, public_id, code, name, line1, phone, opening_hours, commission_flat, commission_bp, ST_Y(point::geometry) AS lat, ST_X(point::geometry) AS lng
             FROM dropoff_centres WHERE public_id = ? AND status = 'active'",
            [$publicId]
        );
        if (! $c) {
            throw new RuntimeException('That store is not taking parcels right now. Choose another one.');
        }

        return $c;
    }

    /** The stores this user works at (the counter screen is for these). */
    public function centresFor(int $userId): array
    {
        return DB::table('dropoff_centre_users as u')->join('dropoff_centres as c', 'c.id', '=', 'u.dropoff_centre_id')
            ->where('u.user_id', $userId)->orderBy('c.name')->get(['c.id', 'c.public_id', 'c.code', 'c.name', 'c.line1', 'c.status', 'c.commission_flat', 'c.commission_bp'])->all();
    }

    // ---------------------------------------------------------------- partner applications

    public function apply(array $d): string
    {
        $id = (string) Str::ulid();
        DB::table('partner_applications')->insert([
            'public_id' => $id, 'business_name' => trim($d['business_name']), 'contact_name' => trim($d['contact_name']), 'email' => strtolower(trim($d['email'])),
            'phone' => Phone::store((string) $d['phone']) ?? trim((string) $d['phone']), 'line1' => trim($d['line1']), 'city' => trim($d['city']),
            'subdivision' => $d['subdivision'] ?? null, 'postal_code' => isset($d['postal_code']) ? strtoupper(trim($d['postal_code'])) : null,
            'lat' => $d['lat'] ?? null, 'lng' => $d['lng'] ?? null, 'opening_hours' => $d['opening_hours'] ?? null, 'business_type' => $d['business_type'] ?? null,
            'message' => $d['message'] ?? null, 'status' => 'new', 'created_at' => now(), 'updated_at' => now(),
        ]);

        return $id;
    }

    /**
     * Approves an application: creates the store and gives the contact a sign-in (an existing account with that email is
     * reused). New accounts get a link to set their password.
     *
     * @param  array{lat:float, lng:float, commission_flat:int, commission_bp:int, city_id?:?int, name?:?string, line1?:?string, opening_hours?:?string}  $d
     */
    public function approve(int $applicationId, int $staffId, array $d): int
    {
        [$centreId, $userId, $isNew] = DB::transaction(function () use ($applicationId, $staffId, $d) {
            $a = DB::table('partner_applications')->where('id', $applicationId)->lockForUpdate()->first();
            if (! $a || $a->status !== 'new') {
                throw new RuntimeException('That application has already been decided.');
            }
            $user = User::where('email', $a->email)->first();
            $isNew = ! $user;
            if ($isNew) {
                $phoneTaken = $a->phone && User::where('phone_number', $a->phone)->exists();
                $user = User::create(['name' => $a->contact_name, 'email' => $a->email, 'phone_number' => $phoneTaken ? null : $a->phone, 'password' => Hash::make(Str::random(40))]);
            }
            $name = trim((string) ($d['name'] ?? '')) ?: $a->business_name;
            $line1 = trim((string) ($d['line1'] ?? '')) ?: trim($a->line1.', '.$a->city.($a->postal_code ? ' '.$a->postal_code : ''));
            $centreId = DB::table('dropoff_centres')->insertGetId([
                'public_id' => (string) Str::ulid(), 'code' => $this->newCode($a->city), 'name' => $name, 'line1' => $line1,
                'city_id' => $d['city_id'] ?? null, 'point' => DB::raw(sprintf('ST_SetSRID(ST_MakePoint(%F, %F), 4326)::geography', (float) $d['lng'], (float) $d['lat'])),
                'phone' => $a->phone, 'email' => $a->email, 'opening_hours' => ($d['opening_hours'] ?? null) ?: $a->opening_hours,
                'commission_flat' => max(0, (int) $d['commission_flat']), 'commission_bp' => max(0, min(10_000, (int) $d['commission_bp'])),
                'status' => 'active', 'application_id' => $a->id, 'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('dropoff_centre_users')->insertOrIgnore(['dropoff_centre_id' => $centreId, 'user_id' => $user->id, 'created_at' => now(), 'updated_at' => now()]);
            DB::table('partner_applications')->where('id', $a->id)->update([
                'status' => 'approved', 'decided_by' => $staffId, 'decided_at' => now(), 'dropoff_centre_id' => $centreId, 'updated_at' => now(),
            ]);

            return [$centreId, (int) $user->id, $isNew];
        });

        $this->notifications->notify($userId, 'partner.approved', ['store' => DB::table('dropoff_centres')->where('id', $centreId)->value('name'), 'link' => route('counter')], '/counter');
        if ($isNew) {
            Password::broker()->sendResetLink(['email' => User::find($userId)->email]);
        }

        return $centreId;
    }

    public function decline(int $applicationId, int $staffId, string $note): void
    {
        $n = DB::table('partner_applications')->where('id', $applicationId)->where('status', 'new')
            ->update(['status' => 'declined', 'decided_by' => $staffId, 'decided_at' => now(), 'decision_note' => mb_substr($note, 0, 300), 'updated_at' => now()]);
        if (! $n) {
            throw new RuntimeException('That application has already been decided.');
        }
    }

    /** Store staff: adds a colleague who already has an account. */
    public function addStaff(int $centreId, string $email): void
    {
        $user = User::where('email', strtolower(trim($email)))->first();
        if (! $user) {
            throw new RuntimeException('No account uses that email. Ask them to sign up first, then add them.');
        }
        DB::table('dropoff_centre_users')->insertOrIgnore(['dropoff_centre_id' => $centreId, 'user_id' => $user->id, 'created_at' => now(), 'updated_at' => now()]);
    }

    private function newCode(string $city): string
    {
        $prefix = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $city) ?: 'ST', 0, 3));
        for ($n = DB::table('dropoff_centres')->where('code', 'like', $prefix.'%')->count() + 1; ; $n++) {
            $code = $prefix.str_pad((string) $n, 2, '0', STR_PAD_LEFT);
            if (! DB::table('dropoff_centres')->where('code', $code)->exists()) {
                return $code;
            }
        }
    }

    // ---------------------------------------------------------------- parcels

    /** Pure: what the store earns for one parcel. */
    public static function commission(int $flat, int $bp, int $deliveryPrice): int
    {
        return max(0, $flat) + intdiv(max(0, $deliveryPrice) * max(0, $bp), 10_000);
    }

    /** A paid delivery that starts at a store: the store expects it, and the customer is told where to take it. Runs inside the order transaction. */
    public function expect(int $shipmentId, int $centreId, int $deliveryPrice): void
    {
        $c = DB::table('dropoff_centres')->where('id', $centreId)->first(['commission_flat', 'commission_bp']);
        if (! $c) {
            return;
        }
        DB::table('dropoff_parcels')->insertOrIgnore([
            'dropoff_centre_id' => $centreId, 'shipment_id' => $shipmentId, 'status' => 'expected',
            'commission' => self::commission((int) $c->commission_flat, (int) $c->commission_bp, $deliveryPrice),
            'commission_status' => 'pending', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::afterCommit(fn () => $this->tellCustomer($shipmentId));
    }

    /** Dispatch asks this: a parcel booked to a store that the store does not have yet. */
    public static function waitingForDropOff(int $shipmentId): bool
    {
        return DB::table('dropoff_parcels')->where('shipment_id', $shipmentId)->where('status', 'expected')->exists();
    }

    private function tellCustomer(int $shipmentId): void
    {
        $r = DB::table('dropoff_parcels as p')->join('dropoff_centres as c', 'c.id', '=', 'p.dropoff_centre_id')->join('shipments as s', 's.id', '=', 'p.shipment_id')
            ->join('orders as o', 'o.id', '=', 's.order_id')->where('p.shipment_id', $shipmentId)
            ->first(['o.customer_id', 'o.order_number', 's.public_id', 's.tracking_code', 'c.name', 'c.line1', 'c.opening_hours']);
        if ($r && $r->customer_id) {
            $this->notifications->notify((int) $r->customer_id, 'dropoff.bring_parcel', [
                'order' => $r->order_number, 'code' => $r->tracking_code, 'store' => $r->name, 'address' => $r->line1, 'hours' => $r->opening_hours ?: 'during opening hours',
            ], "/orders/{$r->public_id}");
        }
    }

    /** Looks a parcel up at this store by its tracking code or a package label code. */
    private function find(int $centreId, string $code): object
    {
        $code = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $code));
        $shipmentId = DB::table('shipments')->where('tracking_code', $code)->value('id')
            ?? DB::table('packages')->where('barcode', $code)->value('shipment_id');
        $p = $shipmentId ? DB::table('dropoff_parcels')->where('shipment_id', $shipmentId)->first() : null;
        if (! $p) {
            throw new RuntimeException("No parcel with code {$code} is booked to come through a store.");
        }
        if ((int) $p->dropoff_centre_id !== $centreId) {
            $other = DB::table('dropoff_centres')->where('id', $p->dropoff_centre_id)->value('name');
            throw new RuntimeException("Parcel {$code} was booked to {$other}, not this store. Ask the customer to take it there.");
        }

        return $p;
    }

    /** The sender hands the parcel in at the counter. The delivery is then offered to drivers. Returns the shipment id. */
    public function receive(int $centreId, string $code, int $userId): int
    {
        $shipmentId = DB::transaction(function () use ($centreId, $code, $userId) {
            $p = $this->find($centreId, $code);
            $p = DB::table('dropoff_parcels')->where('id', $p->id)->lockForUpdate()->first();
            if ($p->status !== 'expected') {
                throw new RuntimeException($p->status === 'received' ? 'This parcel is already at the counter.' : 'This parcel has already left the store.');
            }
            $status = DB::table('shipments')->where('id', $p->shipment_id)->value('status');
            if (in_array($status, ['cancelled', 'delivered', 'completed'], true)) {
                throw new RuntimeException('This delivery was cancelled. Give the parcel back to the customer.');
            }
            DB::table('dropoff_parcels')->where('id', $p->id)->update(['status' => 'received', 'received_at' => now(), 'received_by' => $userId, 'updated_at' => now()]);
            $this->events->record((int) $p->shipment_id, 'at_dropoff_centre', null, null, 'user', $userId, ['centre_id' => $centreId]);

            return (int) $p->shipment_id;
        });

        // After commit, as with payment: offer the job to drivers now that the parcel is there.
        try {
            app(\App\Modules\Dispatch\DispatchService::class)->start($shipmentId);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Dispatch after drop-off for shipment {$shipmentId}: {$e->getMessage()}");
        }

        return $shipmentId;
    }

    /** The driver takes the parcel from the counter. Also called when the driver marks the pickup in the app. */
    public function collect(int $centreId, string $code, ?int $userId): void
    {
        DB::transaction(function () use ($centreId, $code, $userId) {
            $p = $this->find($centreId, $code);
            $this->markCollected((int) $p->shipment_id, $userId, true);
        });
    }

    /** The driver picked the parcel up (from the app): the store has handed it over and earns its commission. */
    public function markCollected(int $shipmentId, ?int $userId, bool $strict = false): void
    {
        $p = DB::table('dropoff_parcels')->where('shipment_id', $shipmentId)->lockForUpdate()->first();
        if (! $p || $p->status === 'collected') {
            if ($strict && $p) {
                throw new RuntimeException('This parcel has already been collected.');
            }

            return;
        }
        if ($strict && $p->status !== 'received') {
            throw new RuntimeException('Receive this parcel at the counter first.');
        }
        DB::table('dropoff_parcels')->where('id', $p->id)->update([
            'status' => 'collected', 'collected_at' => now(), 'collected_by' => $userId,
            'received_at' => $p->received_at ?? now(), 'commission_status' => 'earned', 'updated_at' => now(),
        ]);
    }

    /** A cancelled delivery: nothing to hand over, nothing earned. */
    public function cancelled(int $shipmentId): void
    {
        DB::table('dropoff_parcels')->where('shipment_id', $shipmentId)->whereIn('status', ['expected', 'received'])
            ->update(['status' => 'cancelled', 'commission_status' => 'void', 'updated_at' => now()]);
    }

    /** The counter screen: parcels on their way in, parcels waiting for a driver, and today's collections. */
    public function counter(int $centreId): array
    {
        $rows = DB::table('dropoff_parcels as p')->join('shipments as s', 's.id', '=', 'p.shipment_id')->join('orders as o', 'o.id', '=', 's.order_id')
            ->leftJoin('users as u', 'u.id', '=', 'o.customer_id')
            ->leftJoin('assignments as a', fn ($j) => $j->on('a.shipment_id', '=', 's.id')->whereIn('a.status', ['accepted', 'en_route', 'active']))
            ->leftJoin('driver_profiles as dp', 'dp.id', '=', 'a.driver_profile_id')->leftJoin('users as du', 'du.id', '=', 'dp.user_id')
            ->where('p.dropoff_centre_id', $centreId)
            ->where(fn ($q) => $q->whereIn('p.status', ['expected', 'received'])->orWhere('p.collected_at', '>=', now()->startOfDay()))
            ->orderBy('p.created_at')
            ->get(['p.id', 'p.status', 'p.received_at', 'p.collected_at', 'p.commission', 'p.created_at', 's.tracking_code', 's.status as shipment_status', 'u.name as sender', 'du.name as driver']);

        return [
            'expected' => $rows->where('status', 'expected')->values()->all(),
            'waiting' => $rows->where('status', 'received')->values()->all(),
            'collected' => $rows->where('status', 'collected')->values()->all(),
        ];
    }

    /** What the store has earned, by month, and what is still to be paid. */
    public function earnings(int $centreId): array
    {
        $byMonth = DB::table('dropoff_parcels')->where('dropoff_centre_id', $centreId)->whereIn('commission_status', ['earned', 'paid'])
            ->selectRaw("to_char(collected_at, 'YYYY-MM') AS month, count(*) AS parcels, sum(commission) AS amount, sum(CASE WHEN commission_status = 'earned' THEN commission ELSE 0 END) AS unpaid")
            ->groupByRaw("to_char(collected_at, 'YYYY-MM')")->orderByDesc('month')->limit(12)->get();

        return [
            'unpaid' => (int) DB::table('dropoff_parcels')->where('dropoff_centre_id', $centreId)->where('commission_status', 'earned')->sum('commission'),
            'months' => $byMonth->map(fn ($m) => ['month' => $m->month, 'parcels' => (int) $m->parcels, 'amount' => (int) $m->amount, 'unpaid' => (int) $m->unpaid])->all(),
        ];
    }

    /** Staff paid the store (e.g. by e-Transfer): everything earned so far is marked paid. Returns the amount. */
    public function markPaid(int $centreId, string $reference): int
    {
        return DB::transaction(function () use ($centreId, $reference) {
            $ids = DB::table('dropoff_parcels')->where('dropoff_centre_id', $centreId)->where('commission_status', 'earned')->lockForUpdate()->pluck('id');
            $amount = (int) DB::table('dropoff_parcels')->whereIn('id', $ids)->sum('commission');
            if ($amount <= 0) {
                throw new RuntimeException('Nothing is owed to this store.');
            }
            DB::table('dropoff_parcels')->whereIn('id', $ids)->update(['commission_status' => 'paid', 'paid_at' => now(), 'payout_ref' => mb_substr($reference, 0, 60), 'updated_at' => now()]);

            return $amount;
        });
    }

    public static function describeCommission(int $flat, int $bp): string
    {
        $parts = array_filter([$flat > 0 ? Money::format($flat, true) : null, $bp > 0 ? rtrim(rtrim(number_format($bp / 100, 2), '0'), '.').'% of the delivery price' : null]);

        return $parts ? implode(' + ', $parts).' per parcel' : 'No commission';
    }
}
