<?php

namespace App\Modules\Tracking;

use App\Modules\Dispatch\DriverService;
use App\Modules\Marketplace\FailedDeliveryService;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * Takes the actions a driver's phone saved while offline and applies them once each.
 *
 * Rules
 *   - Every action carries the id the phone gave it. An id seen before returns the first answer again, so a
 *     retry after a timeout never creates a second pickup or delivery.
 *   - Actions run in the order they happened on the phone. Each one is checked against the job's current state
 *     by the same services the online screens use, so a stale or conflicting action is rejected with a reason
 *     instead of overwriting what already happened.
 *   - The phone's clock is recorded but not trusted: the server time is what moves a job, and a phone clock
 *     running ahead of ours is flagged on the event.
 *   - An action is only "accepted" once the server has applied it. Rejected actions say why, so the driver can act.
 */
class DriverSyncService
{
    public const BATCH_LIMIT = 50;
    public const MAX_AGE_HOURS = 72;
    public const FUTURE_TOLERANCE_S = 300;

    public const TYPES = ['job.start', 'stop.complete', 'package.scan', 'job.issue', 'job.fail'];

    public function __construct(private DriverService $drivers, private StopService $stops, private ParcelCodes $codes)
    {
    }

    /**
     * @param  array<int, array{id:string, type:string, at?:string, data?:array}>  $events
     * @return array<int, array{id:string, status:string, duplicate:bool, message:?string, result:?array}>
     */
    public function apply(int $userId, int $driverProfileId, array $events): array
    {
        $events = array_slice($events, 0, self::BATCH_LIMIT);
        // Oldest first on the phone's clock; events without a time keep their upload order.
        $order = array_keys($events);
        usort($order, fn ($a, $b) => strcmp((string) ($events[$a]['at'] ?? ''), (string) ($events[$b]['at'] ?? '')) ?: $a <=> $b);

        $out = [];
        foreach ($order as $i) {
            $e = $events[$i];
            $out[$i] = $this->one($userId, $driverProfileId, (string) $e['id'], (string) $e['type'], $e['at'] ?? null, (array) ($e['data'] ?? []),
                fn () => $this->run($driverProfileId, (string) $e['type'], (array) ($e['data'] ?? []), $e['at'] ?? null));
        }
        ksort($out);

        return array_values($out);
    }

    /**
     * Records one action under its phone id and runs it exactly once. Also used by the photo proof upload,
     * which cannot travel in a JSON batch.
     */
    public function one(int $userId, ?int $driverProfileId, string $clientId, string $type, ?string $at, array $data, callable $work): array
    {
        if ($seen = $this->seen($userId, $clientId)) {
            return $seen;
        }

        $received = CarbonImmutable::now();
        $device = null;
        $skew = null;
        $suspect = false;
        $status = 'accepted';
        $message = null;
        $result = null;

        try {
            $device = $at ? CarbonImmutable::parse($at) : null;
        } catch (Throwable) {
            $device = null;
        }
        if ($device) {
            $skew = (int) $device->diffInSeconds($received, false);
            // Offline actions are legitimately old, so only a phone clock running ahead of ours is a sure sign it is wrong.
            $suspect = $skew < -self::FUTURE_TOLERANCE_S;
        }

        if (! in_array($type, self::TYPES, true) && $type !== 'stop.photo') {
            [$status, $message] = ['rejected', 'This app version sent an action the server does not know. Update the app.'];
        } elseif ($device && $device->lt($received->subHours(self::MAX_AGE_HOURS))) {
            [$status, $message] = ['rejected', 'This action is too old to apply. Ask your dispatcher to update the job.'];
        } else {
            try {
                $result = DB::transaction(fn () => $work());
            } catch (QueryException $e) {
                // A database fault is not the driver's mistake: answer with an error so the phone keeps the action and retries.
                throw $e;
            } catch (RuntimeException|InvalidArgumentException $e) {
                [$status, $message] = ['rejected', mb_substr($e->getMessage(), 0, 255)];
            }
        }

        try {
            DB::table('sync_events')->insert([
                'user_id' => $userId, 'driver_profile_id' => $driverProfileId, 'client_event_id' => $clientId, 'type' => $type,
                'shipment_id' => $result['shipment_id'] ?? null, 'payload' => json_encode($this->redact($data)),
                'device_time' => $device, 'received_at' => $received, 'clock_skew_s' => $skew, 'clock_suspect' => $suspect,
                'status' => $status, 'message' => $message, 'result' => $result ? json_encode($result) : null,
            ]);
        } catch (UniqueConstraintViolationException) {
            // The same action arrived twice at the same moment; the other upload's answer stands.
            return $this->seen($userId, $clientId) ?? ['id' => $clientId, 'status' => $status, 'duplicate' => true, 'message' => $message, 'result' => $result];
        }

        return ['id' => $clientId, 'status' => $status, 'duplicate' => false, 'message' => $message, 'result' => $result, 'clock_suspect' => $suspect];
    }

    private function seen(int $userId, string $clientId): ?array
    {
        $row = DB::table('sync_events')->where(['user_id' => $userId, 'client_event_id' => $clientId])->first();

        return $row ? ['id' => $clientId, 'status' => $row->status, 'duplicate' => true, 'message' => $row->message,
            'result' => $row->result ? json_decode($row->result, true) : null, 'clock_suspect' => (bool) $row->clock_suspect] : null;
    }

    private function run(int $driverId, string $type, array $d, ?string $at): ?array
    {
        return match ($type) {
            'job.start' => $this->withShipment($d, fn ($s) => $this->drivers->startTrip($driverId, $s)),
            'job.issue' => $this->withShipment($d, fn ($s) => $this->drivers->reportIssue($driverId, $s, (string) ($d['reason'] ?? ''), $d['note'] ?? null)),
            'job.fail' => $this->withShipment($d, fn ($s) => app(FailedDeliveryService::class)->markFailed($driverId, $s, (string) ($d['reason'] ?? ''), $d['note'] ?? null)),
            'stop.complete' => $this->completeStop($driverId, $d, $at),
            'package.scan' => $this->scan($driverId, $d, $at),
            default => throw new InvalidArgumentException('Unknown action.'),
        };
    }

    private function withShipment(array $d, callable $fn): array
    {
        $public = (string) ($d['shipment'] ?? '');
        $id = DB::table('shipments')->where('public_id', $public)->value('id');
        if (! $id) {
            throw new RuntimeException('That job no longer exists.');
        }
        $fn($public);

        return ['shipment_id' => (int) $id, 'shipment_status' => DB::table('shipments')->where('id', $id)->value('status')];
    }

    private function completeStop(int $driverId, array $d, ?string $at): array
    {
        if (($d['proof_type'] ?? null) === 'photo') {
            throw new InvalidArgumentException('Photo proofs are uploaded with the photo, not in a batch.');
        }
        foreach (['stop_id', 'lat', 'lng'] as $k) {
            if (! isset($d[$k]) || ! is_numeric($d[$k])) {
                throw new InvalidArgumentException("Missing {$k}.");
            }
        }
        $stopId = (int) $d['stop_id'];
        $this->stops->complete($driverId, $stopId, (string) ($d['proof_type'] ?? 'otp'), null, isset($d['otp']) ? (string) $d['otp'] : null,
            (float) $d['lat'], (float) $d['lng'], $d['recipient_name'] ?? null, $at);
        $shipmentId = (int) DB::table('shipment_stops')->where('id', $stopId)->value('shipment_id');

        return ['shipment_id' => $shipmentId, 'stop_id' => $stopId, 'shipment_status' => DB::table('shipments')->where('id', $shipmentId)->value('status')];
    }

    /** A parcel scanned at pickup, drop-off or a check. Only for parcels on a job the driver holds. */
    private function scan(int $driverId, array $d, ?string $at): array
    {
        $stage = in_array($d['stage'] ?? null, ['pickup', 'dropoff', 'check'], true) ? $d['stage'] : 'check';
        $p = $this->codes->find((string) ($d['code'] ?? ''));
        $live = $p && DB::table('assignments')->where('shipment_id', $p->shipment_id)->where('driver_profile_id', $driverId)
            ->whereIn('status', ['accepted', 'en_route', 'active'])->exists();
        if (! $live) {
            throw new RuntimeException('This code is not on any of your current jobs.');
        }
        $stop = DB::table('shipment_stops')->where('shipment_id', $p->shipment_id)->where('type', $stage === 'dropoff' ? 'dropoff' : 'pickup')->value('id');
        $hasPoint = isset($d['lat'], $d['lng']) && is_numeric($d['lat']) && is_numeric($d['lng']);

        $scanType = ['pickup' => 'pickup', 'dropoff' => 'delivery', 'check' => 'check'][$stage];
        DB::insert(
            'INSERT INTO package_scans (package_id, shipment_id, stop_id, driver_profile_id, scan_type, scanned_by, point, device_time, created_at)
             VALUES (?, ?, ?, ?, ?, (SELECT user_id FROM driver_profiles WHERE id = ?), '.($hasPoint ? 'ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography' : 'NULL').', ?, now())',
            array_merge([$p->id, $p->shipment_id, $stop, $driverId, $scanType, $driverId], $hasPoint ? [(float) $d['lng'], (float) $d['lat']] : [], [$at])
        );
        $next = ['pickup' => 'picked_up', 'dropoff' => 'delivered'][$stage] ?? null;
        if ($next && $p->status !== $next) {
            DB::table('packages')->where('id', $p->id)->update(['status' => $next, 'updated_at' => now()]);
        }

        $all = DB::table('packages')->where('shipment_id', $p->shipment_id)->count();
        $scanned = DB::table('package_scans')->where('shipment_id', $p->shipment_id)->where('scan_type', $scanType)->distinct()->count('package_id');

        return ['shipment_id' => (int) $p->shipment_id, 'package' => $p->public_id, 'seq' => (int) $p->seq, 'stage' => $stage, 'scanned' => $scanned, 'of' => $all];
    }

    /** Delivery codes are never stored in the sync log. */
    private function redact(array $d): array
    {
        if (isset($d['otp'])) {
            $d['otp'] = '***';
        }

        return $d;
    }
}
