<?php

namespace App\Modules\Dispatch;

use App\Modules\Dispatch\Jobs\ExpireDispatchOffer;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Finds drivers for a shipment and offers it to them one at a time.
 *
 *   start()    -> runs the candidate search, stores every candidate considered, sends the first offer
 *   respond()  -> a driver accepts or declines; accepting creates the assignment atomically
 *   expire()   -> called by the delayed job when a driver does not answer in time
 *
 * Every step writes to dispatch_runs / dispatch_candidates / dispatch_offers / shipment_events so a
 * dispatcher can see exactly why a driver was (or was not) chosen.
 */
class DispatchService
{
    public const OFFER_SECONDS = 30;
    public const MAX_ATTEMPTS = 5;
    public const RADIUS_STEPS_M = [2_000, 4_000, 8_000];

    /** How long a driver has to answer an offer. Staff can change it in platform settings; the constant is the fallback. */
    public function offerSeconds(): int
    {
        return $this->setting('dispatch.offer_timeout_seconds', self::OFFER_SECONDS, 10, 300);
    }

    public function maxAttempts(): int
    {
        return $this->setting('dispatch.max_offer_attempts', self::MAX_ATTEMPTS, 1, 20);
    }

    private function setting(string $key, int $default, int $min, int $max): int
    {
        $raw = DB::table('platform_settings')->whereNull('operator_id')->where('key', $key)->value('value');
        $v = $raw === null ? $default : (int) (json_decode($raw, true) ?? $raw);

        return max($min, min($max, $v ?: $default));
    }

    public function start(int $shipmentId, string $strategy = 'scored'): int
    {
        $shipment = DB::table('shipments')->find($shipmentId);
        if (! $shipment || ! in_array($shipment->status, ['created', 'awaiting_dispatch', 'offered'], true)) {
            throw new RuntimeException('Shipment is not waiting for a driver.');
        }

        $pickup = DB::table('shipment_stops')->where('shipment_id', $shipmentId)->where('type', 'pickup')->orderBy('seq')->first();
        if (! $pickup) {
            throw new RuntimeException('Shipment has no pickup stop.');
        }

        $runId = DB::table('dispatch_runs')->insertGetId([
            'shipment_id' => $shipmentId,
            'strategy' => $strategy,
            'config_snapshot' => json_encode(['offer_seconds' => $this->offerSeconds(), 'max_attempts' => $this->maxAttempts(), 'radius_steps_m' => self::RADIUS_STEPS_M]),
            'started_at' => now(),
        ]);

        DB::table('shipments')->where('id', $shipmentId)->update(['status' => 'awaiting_dispatch', 'needs_manual_dispatch' => false, 'updated_at' => now()]);

        $candidates = $this->findCandidates($shipment, $pickup);
        $found = 0;
        foreach ($candidates as $rank => $c) {
            $found += $c['filtered_out'] ? 0 : 1;
            DB::table('dispatch_candidates')->insert([
                'run_id' => $runId,
                'driver_profile_id' => $c['id'],
                'rank' => $c['filtered_out'] ? null : $rank + 1,
                'score' => $c['score'],
                'score_breakdown' => json_encode($c['breakdown']),
                'distance_m' => (int) $c['distance_m'],
                'filtered_out' => $c['filtered_out'],
                'filter_reason' => $c['reason'],
            ]);
        }
        DB::table('dispatch_runs')->where('id', $runId)->update(['candidates_found' => $found]);

        $this->offerNext($runId);

        return $runId;
    }

    /** Send the next offer in rank order, or give up to the manual queue. */
    public function offerNext(int $runId): ?int
    {
        return DB::transaction(function () use ($runId) {
            $run = DB::table('dispatch_runs')->where('id', $runId)->lockForUpdate()->first();
            if (! $run || $run->outcome !== null) {
                return null;
            }

            $shipment = DB::table('shipments')->where('id', $run->shipment_id)->lockForUpdate()->first();
            if (! in_array($shipment->status, ['awaiting_dispatch', 'offered'], true)) {
                return null;
            }

            $tried = DB::table('dispatch_offers')->where('run_id', $runId)->pluck('driver_profile_id')->all();
            $next = DB::table('dispatch_candidates')
                ->where('run_id', $runId)->where('filtered_out', false)
                ->whereNotIn('driver_profile_id', $tried ?: [0])
                ->orderBy('rank')->first();

            if (! $next || count($tried) >= $this->maxAttempts()) {
                $this->toManualQueue($run, $shipment, $next ? 'repeated_decline' : 'no_supply');

                return null;
            }

            $driverOperator = DB::table('driver_profiles')->where('id', $next->driver_profile_id)->value('operator_id');
            $offerId = DB::table('dispatch_offers')->insertGetId([
                'run_id' => $runId,
                'shipment_id' => $shipment->id,
                'driver_profile_id' => $next->driver_profile_id,
                'operator_id' => $driverOperator,
                'sequence' => count($tried) + 1,
                'offered_at' => now(),
                'expires_at' => now()->addSeconds($this->offerSeconds()),
                'channel' => 'push',
            ]);
            DB::table('dispatch_runs')->where('id', $runId)->increment('candidates_offered');
            DB::table('shipments')->where('id', $shipment->id)->update(['status' => 'offered', 'updated_at' => now()]);
            $this->event($shipment->id, 'offer_sent', $shipment->status, 'offered', 'system', null, ['driver_profile_id' => $next->driver_profile_id, 'offer_id' => $offerId]);

            // Fires after commit so the worker never sees an offer that was rolled back.
            ExpireDispatchOffer::dispatch($offerId)->delay(now()->addSeconds($this->offerSeconds() + 1))->afterCommit();

            return $offerId;
        });
    }

    /** A driver answers an offer. Returns the assignment id when accepted. */
    public function respond(int $offerId, int $driverProfileId, bool $accept, ?string $declineReason = null): ?int
    {
        $assignmentId = null;
        $runId = null;

        DB::transaction(function () use ($offerId, $driverProfileId, $accept, $declineReason, &$assignmentId, &$runId) {
            $offer = DB::table('dispatch_offers')->where('id', $offerId)->lockForUpdate()->first();
            if (! $offer || (int) $offer->driver_profile_id !== $driverProfileId) {
                throw new RuntimeException('Offer not found.');
            }
            if ($offer->response !== null || now()->greaterThan($offer->expires_at)) {
                throw new RuntimeException('This offer is no longer available.');
            }
            $runId = $offer->run_id;

            if (! $accept) {
                DB::table('dispatch_offers')->where('id', $offerId)->update(['response' => 'declined', 'decline_reason' => $declineReason, 'responded_at' => now()]);

                return;
            }

            $shipment = DB::table('shipments')->where('id', $offer->shipment_id)->lockForUpdate()->first();
            if ($shipment->status !== 'offered') {
                throw new RuntimeException('The job was already taken.');
            }

            $driver = DB::table('driver_profiles')->where('id', $driverProfileId)->lockForUpdate()->first();

            DB::table('dispatch_offers')->where('id', $offerId)->update(['response' => 'accepted', 'responded_at' => now()]);
            $assignmentId = DB::table('assignments')->insertGetId([
                'public_id' => (string) \Illuminate\Support\Str::ulid(),
                'shipment_id' => $shipment->id,
                'driver_profile_id' => $driverProfileId,
                'operator_id' => $driver->operator_id,
                'vehicle_id' => $driver->current_vehicle_id,
                'status' => 'accepted',
                'assigned_by' => 'system',
                'payout_amount' => $offer->payout_offered,
                'assigned_at' => now(),
                'accepted_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('shipments')->where('id', $shipment->id)->update(['status' => 'assigned', 'updated_at' => now()]);
            DB::table('driver_profiles')->where('id', $driverProfileId)->update(['availability' => 'on_job', 'updated_at' => now()]);
            DB::table('dispatch_runs')->where('id', $offer->run_id)->update([
                'outcome' => 'assigned', 'winner_driver_id' => $driverProfileId, 'finished_at' => now(),
            ]);
            $this->event($shipment->id, 'assigned', 'offered', 'assigned', 'driver', $driverProfileId, ['assignment_id' => $assignmentId]);
        });

        if (! $accept && $runId) {
            $this->offerNext($runId);
        }

        return $assignmentId;
    }

    /** The delayed job calls this; it is a no-op if the driver already answered. */
    public function expire(int $offerId): void
    {
        $runId = DB::transaction(function () use ($offerId) {
            $offer = DB::table('dispatch_offers')->where('id', $offerId)->lockForUpdate()->first();
            if (! $offer || $offer->response !== null) {
                return null;
            }
            DB::table('dispatch_offers')->where('id', $offerId)->update(['response' => 'timeout', 'responded_at' => now()]);

            return $offer->run_id;
        });

        if ($runId) {
            $this->offerNext($runId);
        }
    }

    /**
     * Candidate search: nearest eligible drivers around the pickup, widening the radius until some are found.
     *
     * @return array<int, array{id:int, distance_m:float, score:float, breakdown:array, filtered_out:bool, reason:?string}>
     */
    private function findCandidates(object $shipment, object $pickup): array
    {
        foreach (self::RADIUS_STEPS_M as $radius) {
            $rows = DB::select(<<<'SQL'
                SELECT dp.id, dp.rating_avg, dp.acceptance_rate, dp.completion_rate, dp.max_active_jobs,
                       dp.accepts_cod, dp.kyc_status, dp.status, dp.current_vehicle_id, v.vehicle_type_id,
                       ST_Distance(dp.last_point, s.point) AS distance_m,
                       EXTRACT(EPOCH FROM (now() - COALESCE(dp.last_trip_at, now() - interval '1 hour'))) AS idle_s,
                       (SELECT COUNT(*) FROM assignments a
                         WHERE a.driver_profile_id = dp.id AND a.status IN ('assigned','accepted','en_route','active')) AS active_jobs,
                       EXISTS (SELECT 1 FROM restrictions r
                                WHERE r.subject_type = 'driver' AND r.subject_id = dp.id
                                  AND r.type IN ('ban','suspension','new_job_block')
                                  AND r.starts_at <= now() AND (r.ends_at IS NULL OR r.ends_at > now())) AS restricted
                  FROM driver_profiles dp
                  JOIN shipment_stops s ON s.id = ?
                  LEFT JOIN vehicles v ON v.id = dp.current_vehicle_id
                 WHERE dp.operator_id = ?
                   AND dp.deleted_at IS NULL
                   AND dp.availability = 'online'
                   AND dp.last_point IS NOT NULL
                   AND dp.last_seen_at > now() - interval '2 minutes'
                   AND ST_DWithin(dp.last_point, s.point, ?)
                 ORDER BY distance_m
                 LIMIT 40
            SQL, [$pickup->id, $shipment->operator_id, $radius]);

            $out = [];
            foreach ($rows as $r) {
                $reason = $this->rejectReason($r, $shipment);
                [$score, $parts] = $this->score($r, $radius);
                $out[] = [
                    'id' => (int) $r->id,
                    'distance_m' => (float) $r->distance_m,
                    'score' => $score,
                    'breakdown' => $parts + ['radius_m' => $radius],
                    'filtered_out' => $reason !== null,
                    'reason' => $reason,
                ];
            }

            if (collect($out)->contains(fn ($c) => ! $c['filtered_out'])) {
                usort($out, fn ($a, $b) => [$a['filtered_out'], -$a['score']] <=> [$b['filtered_out'], -$b['score']]);

                return $out;
            }
        }

        return $out ?? [];
    }

    private function rejectReason(object $d, object $shipment): ?string
    {
        if ($d->status !== 'active') return 'driver_not_active';
        if ($d->kyc_status !== 'approved') return 'kyc_not_approved';
        if ($d->restricted) return 'restricted';
        if ((int) $d->active_jobs >= (int) $d->max_active_jobs) return 'at_capacity';
        if ($shipment->requires_cod && ! $d->accepts_cod) return 'cod_not_accepted';
        if ($shipment->vehicle_type_id && (int) $d->vehicle_type_id !== (int) $shipment->vehicle_type_id) return 'wrong_vehicle_type';

        return null;
    }

    /** Higher is better. Distance dominates; rating, acceptance and idle time break ties. */
    private function score(object $d, int $radius): array
    {
        $distance = 1 - min(1, $d->distance_m / $radius);
        $rating = $d->rating_avg !== null ? $d->rating_avg / 5 : 0.6;
        $acceptance = $d->acceptance_rate !== null ? $d->acceptance_rate / 100 : 0.7;
        $idle = min(1, $d->idle_s / 1800);

        $parts = ['distance' => round($distance * 50, 2), 'rating' => round($rating * 20, 2), 'acceptance' => round($acceptance * 20, 2), 'idle' => round($idle * 10, 2)];

        return [array_sum($parts), $parts];
    }

    private function toManualQueue(object $run, object $shipment, string $alert): void
    {
        DB::table('dispatch_runs')->where('id', $run->id)->update(['outcome' => $alert === 'no_supply' ? 'no_supply' : 'manual_queue', 'finished_at' => now()]);
        DB::table('shipments')->where('id', $shipment->id)->update(['status' => 'awaiting_dispatch', 'needs_manual_dispatch' => true, 'updated_at' => now()]);
        DB::table('dispatch_alerts')->insert(['shipment_id' => $shipment->id, 'kind' => $alert, 'severity' => 'high', 'raised_at' => now()]);
        $this->event($shipment->id, 'manual_queue', $shipment->status, 'awaiting_dispatch', 'system', null, ['reason' => $alert]);
    }

    private function event(int $shipmentId, string $type, ?string $from, ?string $to, string $actorType, ?int $actorId, array $meta = []): void
    {
        app(\App\Modules\Partner\ShipmentEvents::class)->record($shipmentId, $type, $from, $to, $actorType, $actorId, $meta);
    }
}
