<?php

namespace App\Modules\Backoffice;

use App\Modules\Notifications\NotificationService;
use App\Modules\Partner\RouteEstimator;
use App\Modules\Partner\ShipmentEvents;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Staff correct a job after it was booked: a wrong address, a new contact number, better directions, a clearer parcel
 * description. The price is not touched here; it was agreed and paid, and changing it is a refund or dispute matter.
 *
 * A stop can be changed until the driver has completed it, so the pickup closes once the goods are collected and the
 * drop-off once they are delivered. Every change is written to the job's timeline with the old and new values, and the
 * driver on the job is told.
 */
class JobEditService
{
    public const STOP_FIELDS = ['line1', 'landmark', 'contact_name', 'contact_phone', 'instructions'];

    private const CLOSED = ['delivered', 'completed', 'cancelled', 'returned', 'returning', 'failed_attempt'];
    private const COLLECTED = ['picked_up', 'in_transit', 'at_dropoff'];

    public function __construct(private ShipmentEvents $events, private NotificationService $notify, private RouteEstimator $routes)
    {
    }

    /** Pure: which stops of a job in this state may still change. @return array{pickup:bool, dropoff:bool} */
    public static function editable(string $shipmentStatus): array
    {
        $open = ! in_array($shipmentStatus, self::CLOSED, true);

        return ['pickup' => $open && ! in_array($shipmentStatus, self::COLLECTED, true), 'dropoff' => $open];
    }

    /** The job's stops and parcels as the edit form shows them. */
    public function load(int $shipmentId): array
    {
        $stops = DB::table('shipment_stops')->where('shipment_id', $shipmentId)->orderBy('seq')
            ->selectRaw('id, seq, type, status, line1, landmark, contact_name, contact_phone, instructions, ST_Y(point::geometry) AS lat, ST_X(point::geometry) AS lng')->get();

        return [
            'pickup' => $stops->firstWhere('type', 'pickup'),
            'dropoff' => $stops->where('type', 'dropoff')->last(),
            'packages' => DB::table('packages')->where('shipment_id', $shipmentId)->orderBy('id')->get(['id', 'public_id', 'description', 'weight_g', 'barcode']),
        ];
    }

    /**
     * @param  array{pickup?:array, dropoff?:array, packages?:array<string,array{description?:?string, weight_kg?:?float}>}  $d
     * @return array<string, array{0:mixed, 1:mixed}> what changed, as field => [old, new]
     */
    public function update(int $shipmentId, array $d, int $actorUserId, string $actorType = 'dispatcher'): array
    {
        [$changes, $driverUserId, $publicId, $order] = DB::transaction(function () use ($shipmentId, $d, $actorUserId, $actorType) {
            $s = DB::table('shipments')->where('id', $shipmentId)->lockForUpdate()->first();
            if (! $s) {
                throw new RuntimeException('Job not found.');
            }
            $can = self::editable($s->status);
            $now = $this->load($shipmentId);
            $changes = [];
            $moved = false;

            foreach (['pickup', 'dropoff'] as $type) {
                if (empty($d[$type]) || ! $now[$type]) {
                    continue;
                }
                $stop = $now[$type];
                $in = $d[$type];
                $set = [];
                foreach (self::STOP_FIELDS as $f) {
                    if (! array_key_exists($f, $in)) {
                        continue;
                    }
                    $new = is_string($in[$f]) ? trim($in[$f]) : $in[$f];
                    $new = $new === '' ? null : $new;
                    if ($f === 'line1' && $new === null) {
                        throw new RuntimeException('The '.($type === 'pickup' ? 'pickup' : 'drop-off').' address cannot be empty.');
                    }
                    if ($new !== $stop->{$f}) {
                        $set[$f] = $new;
                        $changes["{$type}.{$f}"] = [$stop->{$f}, $new];
                    }
                }
                $lat = isset($in['lat']) && $in['lat'] !== '' ? round((float) $in['lat'], 6) : null;
                $lng = isset($in['lng']) && $in['lng'] !== '' ? round((float) $in['lng'], 6) : null;
                $pointMoved = $lat !== null && $lng !== null && (abs($lat - (float) $stop->lat) > 0.000001 || abs($lng - (float) $stop->lng) > 0.000001);
                if (! $set && ! $pointMoved) {
                    continue;
                }
                if (! $can[$type] || $stop->status !== 'pending') {
                    throw new RuntimeException($type === 'pickup'
                        ? 'The pickup can no longer be changed because the goods have been collected.'
                        : 'The drop-off can no longer be changed because this job is finished.');
                }
                if ($set) {
                    DB::table('shipment_stops')->where('id', $stop->id)->update($set + ['updated_at' => now()]);
                }
                if ($pointMoved) {
                    DB::update('UPDATE shipment_stops SET point = ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography, updated_at = now() WHERE id = ?', [$lng, $lat, $stop->id]);
                    $changes["{$type}.pin"] = [round((float) $stop->lat, 6).','.round((float) $stop->lng, 6), "{$lat},{$lng}"];
                    $moved = true;
                }
            }

            foreach ((array) ($d['packages'] ?? []) as $pid => $p) {
                $pkg = $now['packages']->firstWhere('public_id', $pid);
                if (! $pkg) {
                    continue;
                }
                $set = [];
                if (array_key_exists('description', $p) && trim((string) $p['description']) !== (string) $pkg->description) {
                    $set['description'] = trim((string) $p['description']) ?: null;
                    $changes["parcel {$pkg->barcode}.description"] = [$pkg->description, $set['description']];
                }
                if (array_key_exists('weight_kg', $p)) {
                    $g = $p['weight_kg'] === null || $p['weight_kg'] === '' ? null : (int) round(((float) $p['weight_kg']) * 1000);
                    if ($g !== ($pkg->weight_g === null ? null : (int) $pkg->weight_g)) {
                        $set['weight_g'] = $g;
                        $changes["parcel {$pkg->barcode}.weight_g"] = [$pkg->weight_g, $g];
                    }
                }
                if ($set) {
                    if (in_array($s->status, array_merge(self::CLOSED, self::COLLECTED), true)) {
                        throw new RuntimeException('Parcel details can no longer be changed because the goods have been collected.');
                    }
                    DB::table('packages')->where('id', $pkg->id)->update($set + ['updated_at' => now()]);
                }
            }

            if (! $changes) {
                return [[], null, $s->public_id, null];
            }

            if ($moved) {
                $p = $this->load($shipmentId);
                $r = $this->routes->estimate((float) $p['pickup']->lat, (float) $p['pickup']->lng, (float) $p['dropoff']->lat, (float) $p['dropoff']->lng);
                DB::table('shipments')->where('id', $shipmentId)->update(['distance_m' => $r['distance_m'], 'duration_s' => $r['duration_s'], 'updated_at' => now()]);
            } else {
                DB::table('shipments')->where('id', $shipmentId)->update(['updated_at' => now()]);
            }

            // Phone numbers stay out of the timeline: it is shown to more people than the stop itself.
            $logged = [];
            foreach ($changes as $k => [$old, $new]) {
                $logged[$k] = str_ends_with($k, 'contact_phone') ? ['changed', 'changed'] : [$old, $new];
            }
            $this->events->record($shipmentId, 'details_updated', $s->status, $s->status, $actorType, $actorUserId, ['changes' => $logged]);

            $driver = DB::table('assignments as a')->join('driver_profiles as dp', 'dp.id', '=', 'a.driver_profile_id')
                ->where('a.shipment_id', $shipmentId)->whereIn('a.status', ['assigned', 'accepted', 'en_route', 'active'])->value('dp.user_id');

            return [$changes, $driver ? (int) $driver : null, $s->public_id, DB::table('orders')->where('id', $s->order_id)->value('order_number')];
        });

        if ($changes && $driverUserId) {
            $this->notify->notify($driverUserId, 'delivery.job_updated', ['order' => $order], "/driver/jobs/{$publicId}");
        }

        return $changes;
    }
}
