<?php

namespace App\Modules\Dispatch;

use App\Modules\Notifications\NotificationService;
use App\Modules\Partner\ShipmentEvents;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * What a driver needs from the system: go online or offline, see offers, see their jobs and stops, and start a trip.
 * Answering an offer is DispatchService::respond; completing a stop is StopService::complete; arrival is detected from
 * location pings. This class is read-mostly and shared by the driver API and the driver web pages.
 */
class DriverService
{
    public const LIVE = ['accepted', 'en_route', 'active'];

    /** The driver profile to act as: an active one, preferring the operator the user is currently working for. */
    public function profileFor(int $userId, ?int $currentOperatorId = null): ?object
    {
        return DB::table('driver_profiles as d')->join('operators as o', 'o.id', '=', 'd.operator_id')
            ->where('d.user_id', $userId)->where('d.status', 'active')->whereNull('d.deleted_at')
            ->orderByRaw('(d.operator_id = ?) desc', [(int) $currentOperatorId])->orderBy('d.id')
            ->first(['d.id', 'd.public_id', 'd.operator_id', 'd.availability', 'd.max_active_jobs', 'd.rating_avg', 'd.rating_count', 'o.display_name as company']);
    }

    public function setAvailability(int $driverId, string $availability): void
    {
        if (! in_array($availability, ['online', 'offline', 'break'], true)) {
            throw new RuntimeException('Choose online, offline or break.');
        }
        $d = DB::table('driver_profiles')->where('id', $driverId)->first();
        if ($d->availability === 'on_job') {
            throw new RuntimeException('Finish your current job first.');
        }
        DB::table('driver_profiles')->where('id', $driverId)->update(['availability' => $availability, 'last_seen_at' => now(), 'updated_at' => now()]);
    }

    /** Offers waiting for this driver's answer. */
    public function offers(int $driverId): array
    {
        return DB::table('dispatch_offers as f')->join('shipments as s', 's.id', '=', 'f.shipment_id')
            ->where('f.driver_profile_id', $driverId)->whereNull('f.response')->where('f.expires_at', '>', now())
            ->orderBy('f.expires_at')
            ->get(['f.id', 'f.payout_offered', 'f.expires_at', 's.id as shipment_id', 's.public_id as shipment'])
            ->map(fn ($o) => $this->withEnds($o) + ['seconds_left' => max(0, (int) now()->diffInSeconds($o->expires_at, false))])
            ->all();
    }

    public function jobs(int $driverId): array
    {
        return DB::table('assignments as a')->join('shipments as s', 's.id', '=', 'a.shipment_id')
            ->where('a.driver_profile_id', $driverId)->whereIn('a.status', self::LIVE)
            ->orderBy('a.id')
            ->get(['s.id as shipment_id', 's.public_id as shipment', 's.status', 'a.status as assignment', 'a.payout_amount'])
            ->map(fn ($j) => $this->withEnds($j))->all();
    }

    public function history(int $driverId, int $limit = 20): array
    {
        return DB::table('assignments as a')->join('shipments as s', 's.id', '=', 'a.shipment_id')
            ->where('a.driver_profile_id', $driverId)->where('a.status', 'completed')
            ->orderByDesc('a.completed_at')->limit($limit)
            ->get(['s.id as shipment_id', 's.public_id as shipment', 's.status', 'a.payout_amount', 'a.completed_at'])
            ->map(fn ($j) => $this->withEnds($j))->all();
    }

    /** One of this driver's jobs with every stop, or null when it is not theirs. */
    public function job(int $driverId, string $shipmentPublicId): ?array
    {
        $row = DB::table('assignments as a')->join('shipments as s', 's.id', '=', 'a.shipment_id')
            ->where('a.driver_profile_id', $driverId)->where('s.public_id', $shipmentPublicId)
            ->orderByDesc('a.id')
            ->first(['s.id as shipment_id', 's.public_id as shipment', 's.status', 'a.status as assignment', 'a.payout_amount']);
        if (! $row) {
            return null;
        }
        $stops = DB::table('shipment_stops')->selectRaw(
            'id, seq, type, line1, landmark, contact_name, contact_phone, instructions, status, requires_photo, (otp_hash is not null) as needs_code,
             ST_Y(point::geometry) as lat, ST_X(point::geometry) as lng'
        )->where('shipment_id', $row->shipment_id)->orderBy('seq')->get()->map(fn ($s) => (array) $s)->all();

        return (array) $row + ['stops' => $stops, 'live' => in_array($row->assignment, self::LIVE, true)];
    }

    /** Driver sets off for the pickup. Arrival is then picked up from their location. */
    public function startTrip(int $driverId, string $shipmentPublicId): void
    {
        DB::transaction(function () use ($driverId, $shipmentPublicId) {
            $s = DB::table('shipments')->where('public_id', $shipmentPublicId)->lockForUpdate()->first();
            $a = $s ? DB::table('assignments')->where('shipment_id', $s->id)->where('driver_profile_id', $driverId)->where('status', 'accepted')->lockForUpdate()->first() : null;
            if (! $a || $s->status !== 'assigned') {
                throw new RuntimeException('This job is not ready to start.');
            }
            DB::table('assignments')->where('id', $a->id)->update(['status' => 'en_route', 'updated_at' => now()]);
            DB::table('shipments')->where('id', $s->id)->update(['status' => 'heading_to_pickup', 'updated_at' => now()]);
            DB::table('shipment_stops')->where('shipment_id', $s->id)->where('type', 'pickup')->where('status', 'pending')->update(['status' => 'en_route', 'updated_at' => now()]);
            app(ShipmentEvents::class)->record((int) $s->id, 'heading_to_pickup', 'assigned', 'heading_to_pickup', 'driver', $driverId);
        });
    }


    public const ISSUES = [
        'receiver_unreachable' => 'Receiver not answering',
        'wrong_address' => 'Address is wrong or cannot be found',
        'goods_issue' => 'Problem with the goods',
        'vehicle_problem' => 'Vehicle problem',
        'safety' => 'I do not feel safe',
        'other' => 'Something else',
    ];

    /** Pay recorded on finished jobs: today, this week and this month, in kobo. Jobs given by hand carry no pay figure. */
    public function earnings(int $driverId): array
    {
        $sum = fn ($from) => (int) DB::table('assignments')->where('driver_profile_id', $driverId)->where('status', 'completed')->where('completed_at', '>=', $from)->sum('payout_amount');

        return ['today' => $sum(now()->startOfDay()), 'week' => $sum(now()->startOfWeek()), 'month' => $sum(now()->startOfMonth())];
    }

    /** Tell the company something is wrong. Nothing changes on the job; the dispatcher decides what to do. */
    public function reportIssue(int $driverId, string $shipmentPublicId, string $reason, ?string $note = null): void
    {
        if (! isset(self::ISSUES[$reason])) {
            throw new RuntimeException('Choose what went wrong.');
        }
        [$s, $a] = $this->liveJob($driverId, $shipmentPublicId);
        app(ShipmentEvents::class)->record((int) $s->id, 'driver_issue', $s->status, $s->status, 'driver', $driverId, ['reason' => $reason, 'note' => $note ? mb_substr($note, 0, 500) : null]);
        $this->tellCompany($s, 'delivery.driver_issue', ['order' => $this->orderNumber($s), 'reason' => self::ISSUES[$reason]]);
    }

    /**
     * The driver cannot do this job. Only before the goods are picked up; after that, report an issue instead.
     * The job goes back to the dispatcher to hand to someone else, and is not offered straight back to this driver.
     */
    public function release(int $driverId, string $shipmentPublicId, string $reason, ?string $note = null): void
    {
        if (! isset(self::ISSUES[$reason])) {
            throw new RuntimeException('Choose why you cannot do this job.');
        }
        $s = DB::transaction(function () use ($driverId, $shipmentPublicId, $reason, $note) {
            $s = DB::table('shipments')->where('public_id', $shipmentPublicId)->lockForUpdate()->first();
            $a = $s ? DB::table('assignments')->where('shipment_id', $s->id)->where('driver_profile_id', $driverId)->whereIn('status', self::LIVE)->lockForUpdate()->first() : null;
            if (! $a) {
                throw new RuntimeException('This job is not yours.');
            }
            if (! in_array($s->status, ['assigned', 'heading_to_pickup', 'at_pickup'], true)) {
                throw new RuntimeException('The goods are already picked up. Report a problem instead and your dispatcher will help.');
            }
            DB::table('assignments')->where('id', $a->id)->update(['status' => 'reassigned', 'reassign_reason' => 'driver released: '.$reason, 'updated_at' => now()]);
            DB::table('shipment_stops')->where('shipment_id', $s->id)->where('type', 'pickup')->whereIn('status', ['en_route', 'arrived'])->update(['status' => 'pending', 'arrived_at' => null, 'updated_at' => now()]);
            DB::table('shipments')->where('id', $s->id)->update(['status' => 'awaiting_dispatch', 'needs_manual_dispatch' => true, 'updated_at' => now()]);
            DB::table('driver_profiles')->where(['id' => $driverId, 'availability' => 'on_job'])->update(['availability' => 'online', 'updated_at' => now()]);
            app(ShipmentEvents::class)->record((int) $s->id, 'driver_released', $s->status, 'awaiting_dispatch', 'driver', $driverId, ['reason' => $reason, 'note' => $note ? mb_substr($note, 0, 500) : null]);

            return $s;
        });
        $this->tellCompany($s, 'delivery.driver_released', ['order' => $this->orderNumber($s), 'reason' => self::ISSUES[$reason]]);
    }

    // ----------------------------------------------------------------

    /** @return array{0:object,1:object} the shipment and this driver's live assignment on it */
    private function liveJob(int $driverId, string $shipmentPublicId): array
    {
        $s = DB::table('shipments')->where('public_id', $shipmentPublicId)->first();
        $a = $s ? DB::table('assignments')->where('shipment_id', $s->id)->where('driver_profile_id', $driverId)->whereIn('status', self::LIVE)->first() : null;
        if (! $a) {
            throw new RuntimeException('This job is not yours.');
        }

        return [$s, $a];
    }

    private function orderNumber(object $shipment): string
    {
        return (string) DB::table('orders')->where('id', $shipment->order_id)->value('order_number');
    }

    private function tellCompany(object $shipment, string $event, array $vars): void
    {
        app(NotificationService::class)->notifyOperator((int) $shipment->operator_id, $event, $vars, "/provider/jobs/{$shipment->public_id}");
    }

    /** Adds the pickup and drop-off lines so a list row can say "from A to B". */
    private function withEnds(object $row): array
    {
        $stops = DB::table('shipment_stops')->where('shipment_id', $row->shipment_id)->orderBy('seq')->get(['type', 'line1']);

        return (array) $row + [
            'pickup' => $stops->firstWhere('type', 'pickup')->line1 ?? null,
            'dropoff' => $stops->last()->line1 ?? null,
        ];
    }
}
