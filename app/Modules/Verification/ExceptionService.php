<?php

namespace App\Modules\Verification;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * When a driver cannot meet one of the agreed proofs (the receiver's phone is dead, a parcel label is torn), they
 * ask for an exception with a reason. Someone at the provider approves or declines it; only an approved exception
 * lets the stop complete without that proof. Both the request and the decision go into the custody trail.
 */
class ExceptionService
{
    public const MIN_REASON = 10;

    public function __construct(private VerificationPolicy $policy, private CustodyLog $custody)
    {
    }

    public function request(int $driverProfileId, int $stopId, string $method, string $reason): array
    {
        $reason = trim($reason);
        if (mb_strlen($reason) < self::MIN_REASON) {
            throw new InvalidArgumentException('Say what happened in a few words so your dispatcher can decide.');
        }

        return DB::transaction(function () use ($driverProfileId, $stopId, $method, $reason) {
            $stop = DB::table('shipment_stops')->where('id', $stopId)->first();
            $owns = $stop && DB::table('assignments')->where('shipment_id', $stop->shipment_id)->where('driver_profile_id', $driverProfileId)
                ->whereIn('status', ['accepted', 'en_route', 'active'])->exists();
            if (! $owns || $stop->status === 'completed') {
                throw new RuntimeException('This stop is not yours to complete.');
            }
            if (! in_array($method, $this->policy->forStop($stop), true)) {
                throw new RuntimeException('That proof is not required at this stop.');
            }
            if ($open = DB::table('verification_exceptions')->where(['stop_id' => $stopId, 'method' => $method])->whereIn('status', ['pending', 'approved'])->first()) {
                return $this->row($open);
            }

            $userId = DB::table('driver_profiles')->where('id', $driverProfileId)->value('user_id');
            $id = DB::table('verification_exceptions')->insertGetId([
                'public_id' => (string) Str::ulid(), 'shipment_id' => $stop->shipment_id, 'stop_id' => $stopId, 'method' => $method,
                'reason' => mb_substr($reason, 0, 1000), 'requested_by' => $userId, 'driver_profile_id' => $driverProfileId,
                'status' => 'pending', 'created_at' => now(), 'updated_at' => now(),
            ]);
            $this->custody->append((int) $stop->shipment_id, 'exception', 'verification_exception', $id, ['stop_id' => $stopId, 'method' => $method, 'status' => 'pending', 'by' => 'driver']);
            $this->tellProvider((int) $stop->shipment_id, $method);

            return $this->row(DB::table('verification_exceptions')->find($id));
        });
    }

    public function decide(int $operatorId, string $publicId, bool $approve, int $userId, ?string $note = null): array
    {
        return DB::transaction(function () use ($operatorId, $publicId, $approve, $userId, $note) {
            $e = DB::table('verification_exceptions as e')->join('shipments as s', 's.id', '=', 'e.shipment_id')
                ->where('e.public_id', $publicId)->where('s.operator_id', $operatorId)->lockForUpdate()->first(['e.*']);
            if (! $e) {
                throw new RuntimeException('Exception not found.');
            }
            if ($e->status !== 'pending') {
                throw new RuntimeException('This exception was already decided.');
            }
            $status = $approve ? 'approved' : 'declined';
            DB::table('verification_exceptions')->where('id', $e->id)->update([
                'status' => $status, 'decided_by' => $userId, 'decided_at' => now(), 'decision_note' => $note ? mb_substr($note, 0, 255) : null, 'updated_at' => now(),
            ]);
            $this->custody->append((int) $e->shipment_id, 'exception', 'verification_exception', (int) $e->id, ['stop_id' => (int) $e->stop_id, 'method' => $e->method, 'status' => $status, 'by' => 'provider:'.$userId]);

            return $this->row(DB::table('verification_exceptions')->find($e->id));
        });
    }

    /** @return list<string> methods waived for this stop */
    public function waived(int $stopId): array
    {
        return DB::table('verification_exceptions')->where('stop_id', $stopId)->where('status', 'approved')->pluck('method')->unique()->values()->all();
    }

    /** @return list<array> */
    public function forShipment(int $shipmentId): array
    {
        return DB::table('verification_exceptions')->where('shipment_id', $shipmentId)->orderBy('id')->get()->map(fn ($e) => $this->row($e))->all();
    }

    public function pendingForOperator(int $operatorId): int
    {
        return DB::table('verification_exceptions as e')->join('shipments as s', 's.id', '=', 'e.shipment_id')
            ->where('s.operator_id', $operatorId)->where('e.status', 'pending')->count();
    }

    private function row(object $e): array
    {
        return ['id' => $e->public_id, 'stop_id' => (int) $e->stop_id, 'method' => $e->method, 'label' => VerificationPolicy::LABELS[$e->method] ?? $e->method,
            'reason' => $e->reason, 'status' => $e->status, 'decision_note' => $e->decision_note, 'created_at' => $e->created_at, 'decided_at' => $e->decided_at];
    }

    private function tellProvider(int $shipmentId, string $method): void
    {
        try {
            $s = DB::table('shipments')->where('id', $shipmentId)->first(['operator_id', 'public_id']);
            app(\App\Modules\Notifications\NotificationService::class)->exceptionRequested((int) $s->operator_id, (string) $s->public_id, VerificationPolicy::LABELS[$method] ?? $method);
        } catch (Throwable $e) {
            Log::warning("Exception notice for shipment {$shipmentId} failed: {$e->getMessage()}");
        }
    }
}
