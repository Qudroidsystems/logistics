<?php

namespace App\Modules\Verification;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Which proofs a stop needs before the driver can complete it.
 *
 * The provider sets its standard proofs (per service type, or one default). The job giver can ask for more when
 * posting the job. Accepting an offer agrees the union of both: it is written into the signed agreement terms and
 * copied onto the shipment, so neither side can change it after the job is agreed.
 *
 * Shape: {"version":1, "pickup":["scan"], "dropoff":["code","photo"], "authorised_receivers":["Ada Obi"]}
 */
class VerificationPolicy
{
    public const PICKUP = ['scan', 'photo'];

    public const DROPOFF = ['code', 'scan', 'photo', 'signature', 'receiver_name', 'receiver_id'];

    public const LABELS = [
        'code' => 'One-time delivery code from the receiver',
        'scan' => 'Scan every parcel',
        'photo' => 'Photo of the hand-over',
        'signature' => "Receiver's signature",
        'receiver_name' => "Receiver's name",
        'receiver_id' => "Receiver's ID (type and last 4 digits)",
        'cod' => 'Cash collected',
    ];

    /** What every job had before policies existed: the receiver's code at drop-off. */
    public const DEFAULT = ['version' => 1, 'pickup' => [], 'dropoff' => ['code'], 'authorised_receivers' => []];

    public const MAX_RECEIVERS = 5;

    /** Cleans any input into a valid policy. Unknown methods are dropped, order is fixed. */
    public static function normalize(?array $p): array
    {
        $p ??= [];
        $pick = fn (array $allowed, $v) => array_values(array_intersect($allowed, is_array($v) ? $v : []));
        $names = array_values(array_unique(array_filter(array_map(
            fn ($n) => mb_substr(trim(preg_replace('/\s+/', ' ', (string) $n)), 0, 80),
            is_array($p['authorised_receivers'] ?? null) ? $p['authorised_receivers'] : []
        ))));

        return [
            'version' => 1,
            'pickup' => $pick(self::PICKUP, $p['pickup'] ?? []),
            'dropoff' => $pick(self::DROPOFF, $p['dropoff'] ?? []),
            'authorised_receivers' => array_slice($names, 0, self::MAX_RECEIVERS),
        ];
    }

    /** The job giver's extra asks plus the provider's standard: neither side's requirement is dropped. */
    public static function agree(?array $jobGiver, array $provider): array
    {
        $a = self::normalize($jobGiver);
        $b = self::normalize($provider);
        $p = self::normalize([
            'pickup' => array_merge($a['pickup'], $b['pickup']),
            'dropoff' => array_merge($a['dropoff'], $b['dropoff']),
            'authorised_receivers' => array_merge($a['authorised_receivers'], $b['authorised_receivers']),
        ]);
        // Authorised receivers only mean something when the driver records who took the parcel.
        if ($p['authorised_receivers'] && ! in_array('receiver_name', $p['dropoff'], true)) {
            $p['dropoff'] = self::normalize(['dropoff' => array_merge($p['dropoff'], ['receiver_name'])])['dropoff'];
        }

        return $p;
    }

    /** The provider's standard for a service type, else its default, else the platform default. */
    public function forProvider(int $operatorId, ?int $serviceTypeId = null): array
    {
        $row = DB::table('verification_policies')->where('operator_id', $operatorId)
            ->where(fn ($q) => $q->where('service_type_id', $serviceTypeId)->orWhereNull('service_type_id'))
            ->orderByRaw('service_type_id IS NULL')->first();

        return $row ? self::normalize(['pickup' => json_decode($row->pickup, true), 'dropoff' => json_decode($row->dropoff, true)]) : self::DEFAULT;
    }

    /** All of a provider's saved standards, keyed by service type id ('default' for the catch-all). */
    public function saved(int $operatorId): array
    {
        $out = [];
        foreach (DB::table('verification_policies')->where('operator_id', $operatorId)->get() as $r) {
            $out[$r->service_type_id ?? 'default'] = self::normalize(['pickup' => json_decode($r->pickup, true), 'dropoff' => json_decode($r->dropoff, true)]);
        }

        return $out;
    }

    public function save(int $operatorId, ?int $serviceTypeId, array $pickup, array $dropoff, ?int $userId): array
    {
        $p = self::normalize(['pickup' => $pickup, 'dropoff' => $dropoff]);
        if (! $p['dropoff']) {
            throw new InvalidArgumentException('Choose at least one way to confirm the drop-off.');
        }
        $q = DB::table('verification_policies')->where('operator_id', $operatorId);
        $serviceTypeId === null ? $q->whereNull('service_type_id') : $q->where('service_type_id', $serviceTypeId);
        $vals = ['pickup' => json_encode($p['pickup']), 'dropoff' => json_encode($p['dropoff']), 'updated_by' => $userId, 'updated_at' => now()];
        if ($q->exists()) {
            $q->update($vals);
        } else {
            DB::table('verification_policies')->insert($vals + ['operator_id' => $operatorId, 'service_type_id' => $serviceTypeId, 'created_at' => now()]);
        }

        return $p;
    }

    public function remove(int $operatorId, int $serviceTypeId): void
    {
        DB::table('verification_policies')->where(['operator_id' => $operatorId, 'service_type_id' => $serviceTypeId])->delete();
    }

    /** What the shipment agreed. Shipments made before policies existed keep the code-only rule. */
    public function forShipment(int $shipmentId): array
    {
        $s = DB::table('shipments')->where('id', $shipmentId)->first(['verification_policy', 'cod_amount']);
        $p = $s && $s->verification_policy ? self::normalize(json_decode($s->verification_policy, true)) : self::DEFAULT;
        if ($s && (int) $s->cod_amount > 0) {
            $p['dropoff'][] = 'cod';
        }

        return $p;
    }

    /** The proofs one stop needs. Return stops need none beyond being at the stop. */
    public function forStop(object $stop): array
    {
        $p = $this->forShipment((int) $stop->shipment_id);

        if ($stop->type === 'dropoff' && ! empty($stop->pickup_point_id)) {
            // Left at a pickup point: the receiver is not there, so the rider proves the hand-over to the point with a
            // photo (and the parcel scans, when agreed). The receiver's code is checked when they collect.
            return array_values(array_unique(['photo', ...array_intersect($p['dropoff'], ['scan'])]));
        }
        if ($stop->type === 'dropoff' && $this->isMidRoute($stop)) {
            // A drop-off before the last one: the delivery code and the cash belong to the final hand-over,
            // so a photo stands in for the code here.
            $mid = array_values(array_diff($p['dropoff'], ['code', 'cod']));

            return in_array('code', $p['dropoff'], true) && ! in_array('photo', $mid, true) ? [...$mid, 'photo'] : $mid;
        }

        return match ($stop->type) {
            'pickup' => $p['pickup'],
            'dropoff' => $p['dropoff'],
            default => [],
        };
    }

    private function isMidRoute(object $stop): bool
    {
        return DB::table('shipment_stops')->where('shipment_id', $stop->shipment_id)->where('type', 'dropoff')->where('seq', '>', $stop->seq)->exists();
    }

    /** @return list<string> plain sentences for screens and SMS */
    public static function describe(array $methods): array
    {
        return array_values(array_map(fn ($m) => self::LABELS[$m] ?? $m, $methods));
    }
}
