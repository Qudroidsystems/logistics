<?php

namespace App\Modules\Dispatch;

use App\Modules\Notifications\NotificationService;
use App\Modules\Partner\ShipmentEvents;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * A provider puts one of its own drivers on a paid job (or takes it itself, for a solo rider).
 * This replaces any automatic offers still open, and can change the driver until the goods are collected.
 * One live assignment per shipment is also enforced by a unique index, so a race cannot double-assign.
 */
class ManualAssignmentService
{
    /** Paid and waiting for someone. */
    public const UNASSIGNED = ['created', 'awaiting_dispatch', 'offered', 'no_supply', 'unassigned'];

    /** A driver has it but has not collected it yet, so it can still move. */
    public const REASSIGNABLE = ['assigned'];

    public function __construct(private ShipmentEvents $events, private NotificationService $notify)
    {
    }

    /** Active drivers on the provider's team with how busy they are, for choosing from. */
    public function drivers(int $operatorId): array
    {
        return DB::table('driver_profiles as d')->join('users as u', 'u.id', '=', 'd.user_id')->where('d.operator_id', $operatorId)->where('d.status', 'active')->whereNull('d.deleted_at')
            ->selectRaw("u.id as user_id, u.name, d.availability, d.max_active_jobs, (SELECT COUNT(*) FROM assignments a WHERE a.driver_profile_id = d.id AND a.status IN ('assigned','accepted','en_route','active')) AS active_jobs")
            ->orderBy('u.name')->get()->map(fn ($r) => (array) $r)->all();
    }

    /** Pure: can a job in this state be given to a driver (or taken from one)? */
    public static function canAssign(string $shipmentStatus): bool
    {
        return in_array($shipmentStatus, array_merge(self::UNASSIGNED, self::REASSIGNABLE), true);
    }

    /** @return int the assignment id */
    public function assign(int $operatorId, string $shipmentPublicId, int $driverUserId, int $actorUserId, ?string $reason = null): int
    {
        $result = DB::transaction(function () use ($operatorId, $shipmentPublicId, $driverUserId, $actorUserId, $reason) {
            $s = DB::table('shipments')->where('public_id', $shipmentPublicId)->where('operator_id', $operatorId)->lockForUpdate()->first();
            if (! $s) {
                throw new RuntimeException('Job not found.');
            }
            if (DB::table('orders')->where('id', $s->order_id)->value('payment_status') !== 'paid') {
                throw new RuntimeException('This job is not paid yet.');
            }
            if (! self::canAssign($s->status)) {
                throw new RuntimeException('This job can no longer be reassigned because the goods are already on the move or it is finished.');
            }

            $driver = DB::table('driver_profiles')->where(['user_id' => $driverUserId, 'operator_id' => $operatorId, 'status' => 'active'])->whereNull('deleted_at')->lockForUpdate()->first();
            if (! $driver) {
                throw new RuntimeException('That person is not an approved driver on your team.');
            }

            $current = DB::table('assignments')->where('shipment_id', $s->id)->whereIn('status', ['assigned', 'accepted', 'en_route', 'active'])->lockForUpdate()->first();
            if ($current && (int) $current->driver_profile_id === (int) $driver->id) {
                throw new RuntimeException('That driver already has this job.');
            }
            $busy = DB::table('assignments')->where('driver_profile_id', $driver->id)->whereIn('status', ['assigned', 'accepted', 'en_route', 'active'])->count();
            if ($busy >= (int) $driver->max_active_jobs) {
                throw new RuntimeException('That driver already has the most jobs they can take at once.');
            }

            if ($current) {
                DB::table('assignments')->where('id', $current->id)->update(['status' => 'reassigned', 'reassign_reason' => $reason ?: 'reassigned by provider', 'updated_at' => now()]);
                $stillBusy = DB::table('assignments')->where('driver_profile_id', $current->driver_profile_id)->whereIn('status', ['assigned', 'accepted', 'en_route', 'active'])->exists();
                if (! $stillBusy) {
                    DB::table('driver_profiles')->where(['id' => $current->driver_profile_id, 'availability' => 'on_job'])->update(['availability' => 'online', 'updated_at' => now()]);
                }
            }

            // Automatic offers for this job are over: nobody should be able to accept it from their phone now.
            DB::table('dispatch_offers')->where('shipment_id', $s->id)->whereNull('response')->update(['response' => 'cancelled_by_system', 'responded_at' => now()]);
            DB::table('dispatch_runs')->where('shipment_id', $s->id)->whereNull('outcome')->update([
                'outcome' => 'assigned', 'winner_driver_id' => $driver->id, 'overridden_by' => $actorUserId, 'override_reason' => $reason ?: 'assigned by provider', 'finished_at' => now(),
            ]);

            $assignmentId = DB::table('assignments')->insertGetId([
                'public_id' => (string) Str::ulid(), 'shipment_id' => $s->id, 'driver_profile_id' => $driver->id, 'operator_id' => $operatorId,
                'vehicle_id' => $driver->current_vehicle_id, 'status' => 'accepted', 'assigned_by' => $actorUserId === $driverUserId ? 'driver_self' : 'dispatcher',
                'assigned_by_user_id' => $actorUserId, 'assigned_at' => now(), 'accepted_at' => now(), 'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('shipments')->where('id', $s->id)->update(['status' => 'assigned', 'updated_at' => now()]);
            DB::table('driver_profiles')->where('id', $driver->id)->update(['availability' => 'on_job', 'updated_at' => now()]);

            $this->events->record((int) $s->id, $current ? 'reassigned' : 'assigned', $s->status, 'assigned', 'dispatcher', $actorUserId,
                ['assignment_id' => $assignmentId, 'driver_profile_id' => $driver->id, 'manual' => true]);

            return [$assignmentId, (int) $s->order_id, $s->public_id];
        });

        [$assignmentId, $orderId, $publicId] = $result;
        if ($driverUserId !== $actorUserId) {
            $this->notify->notify($driverUserId, 'delivery.job_assigned', ['order' => DB::table('orders')->where('id', $orderId)->value('order_number')], "/driver/jobs/{$publicId}");
        }

        return $assignmentId;
    }
}
