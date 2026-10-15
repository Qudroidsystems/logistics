<?php

namespace App\Modules\Verification;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * One-time receiver codes for the drop-off.
 *
 *   - random 6 digits, never derived from the shipment, so knowing the order number gives nothing away
 *   - stored as a keyed hash for checking, and encrypted so the job giver can open it again in their app
 *   - expire after CODE_TTL_HOURS; opening an expired code in the app issues a fresh one
 *   - MAX_ATTEMPTS wrong tries lock the code for LOCK_MINUTES; failed tries are recorded even when the
 *     completion itself is rolled back
 *   - the job giver can ask for a new code at most MAX_REISSUES_PER_HOUR times an hour
 *   - used once: a completed drop-off marks the code used
 */
class DeliveryCodes
{
    public const LENGTH = 6;
    public const CODE_TTL_HOURS = 72;
    public const MAX_ATTEMPTS = 5;
    public const LOCK_MINUTES = 15;
    public const MAX_REISSUES_PER_HOUR = 3;

    /**
     * Creates a new code for the shipment's drop-off and retires any earlier one. Returns the plain code.
     * $expires overrides the usual CODE_TTL_HOURS (a parcel held at a pickup point keeps its code for the whole hold).
     */
    public function issue(int $shipmentId, ?\DateTimeInterface $expires = null): ?string
    {
        $stopId = $this->dropoff($shipmentId);
        if (! $stopId) {
            return null;
        }
        $code = str_pad((string) random_int(0, 10 ** self::LENGTH - 1), self::LENGTH, '0', STR_PAD_LEFT);
        DB::table('delivery_codes')->where('stop_id', $stopId)->whereNull('revoked_at')->whereNull('used_at')->update(['revoked_at' => self::at(now())]);
        DB::table('delivery_codes')->insert([
            'shipment_id' => $shipmentId, 'stop_id' => $stopId, 'code_hash' => $this->hash($stopId, $code),
            'code_secret' => Crypt::encryptString($code), 'expires_at' => self::at($expires ?? now()->addHours(self::CODE_TTL_HOURS)), 'created_at' => self::at(now()),
        ]);

        return $code;
    }

    /**
     * The code to show the job giver: the live one, or a fresh one when the last has expired.
     *
     * @return array{code:string, expires_at:string, locked_until:?string}|null
     */
    public function current(int $shipmentId): ?array
    {
        $row = $this->active($this->dropoff($shipmentId) ?? 0);
        if (! $row || CarbonImmutable::parse($row->expires_at)->isPast()) {
            if (! $this->issue($shipmentId)) {
                return null;
            }
            $row = $this->active((int) $this->dropoff($shipmentId));
        }

        return ['code' => Crypt::decryptString($row->code_secret), 'expires_at' => CarbonImmutable::parse($row->expires_at)->toIso8601String(),
            'locked_until' => $row->locked_until ? CarbonImmutable::parse($row->locked_until)->toIso8601String() : null];
    }

    /** The job giver asks for a new code (lost it, or shared it with the wrong person). Rate limited. */
    public function reissue(int $shipmentId): array
    {
        $recent = DB::table('delivery_codes')->where('shipment_id', $shipmentId)->where('created_at', '>', self::at(now()->subHour()))->count();
        if ($recent >= self::MAX_REISSUES_PER_HOUR) {
            throw new RuntimeException('You have asked for several new codes. Try again in an hour, or use the code you have.');
        }
        $this->issue($shipmentId);

        return $this->current($shipmentId);
    }

    /**
     * Checks the code the driver typed. A wrong code throws WrongDeliveryCode; whoever owns the outer transaction
     * calls recordFailure() after the rollback so the try still counts.
     */
    public function verify(int $stopId, string $code): void
    {
        $row = $this->active($stopId);
        if (! $row) {
            throw new RuntimeException('There is no delivery code for this stop yet. Ask the receiver to open their app.');
        }
        if ($row->locked_until && CarbonImmutable::parse($row->locked_until)->isFuture()) {
            throw new RuntimeException('Too many wrong codes. Try again after '.CarbonImmutable::parse($row->locked_until)->format('H:i').', or ask for an exception.');
        }
        if (CarbonImmutable::parse($row->expires_at)->isPast()) {
            throw new RuntimeException('This delivery code has expired. Ask the receiver to open their app for a new one.');
        }
        if (! hash_equals($row->code_hash, $this->hash($stopId, preg_replace('/\D/', '', $code)))) {
            $left = max(0, self::MAX_ATTEMPTS - (int) $row->attempts - 1);
            throw new WrongDeliveryCode($stopId, $left);
        }
        DB::table('delivery_codes')->where('id', $row->id)->update(['used_at' => self::at(now())]);
    }

    /** Counts one wrong try; the fifth locks the code for a while. Never throws. */
    public function recordFailure(int $stopId): void
    {
        try {
            $row = $this->active($stopId);
            if (! $row) {
                return;
            }
            $attempts = (int) $row->attempts + 1;
            $lock = $attempts >= self::MAX_ATTEMPTS;
            DB::table('delivery_codes')->where('id', $row->id)->update([
                'attempts' => $lock ? 0 : $attempts,
                'locked_until' => $lock ? self::at(now()->addMinutes(self::LOCK_MINUTES)) : $row->locked_until,
            ]);
        } catch (Throwable $e) {
            Log::warning("Could not record a wrong delivery code for stop {$stopId}: {$e->getMessage()}");
        }
    }

    public function markSent(int $shipmentId): void
    {
        DB::table('delivery_codes')->where('shipment_id', $shipmentId)->whereNull('revoked_at')->whereNull('used_at')->update(['sent_at' => self::at(now())]);
    }

    private function active(int $stopId): ?object
    {
        return DB::table('delivery_codes')->where('stop_id', $stopId)->whereNull('revoked_at')->whereNull('used_at')->orderByDesc('id')->first();
    }

    private function dropoff(int $shipmentId): ?int
    {
        $id = DB::table('shipment_stops')->where('shipment_id', $shipmentId)->where('type', 'dropoff')->orderByDesc('seq')->value('id');

        return $id ? (int) $id : null;
    }

    /**
     * Times are written with their UTC offset. The app runs on Lagos time while the database session may not, and an
     * offset-less timestamp would shift expiry and lock times by the difference.
     */
    private static function at(\DateTimeInterface $t): string
    {
        return CarbonImmutable::instance($t)->toIso8601String();
    }

    /** Keyed with the app key and the stop, so a leaked table cannot be checked against all million codes at once. */
    private function hash(int $stopId, string $code): string
    {
        return hash_hmac('sha256', $stopId.':'.$code, (string) config('app.key'));
    }
}
