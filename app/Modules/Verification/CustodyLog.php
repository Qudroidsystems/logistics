<?php

namespace App\Modules\Verification;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * A tamper-evident trail per shipment: every parcel scan, proof and exception decision is appended with the hash of
 * the event before it. Changing or deleting any past row breaks every hash after it, which verify() reports.
 */
class CustodyLog
{
    public const GENESIS = '0000000000000000000000000000000000000000000000000000000000000000';

    public function append(int $shipmentId, string $kind, ?string $refType, ?int $refId, array $payload): array
    {
        // Lock the shipment row so two events for one shipment never take the same place in the chain.
        DB::table('shipments')->where('id', $shipmentId)->lockForUpdate()->value('id');
        $last = DB::table('custody_events')->where('shipment_id', $shipmentId)->orderByDesc('seq')->first(['seq', 'hash']);
        $seq = $last ? (int) $last->seq + 1 : 1;
        $prev = $last->hash ?? self::GENESIS;
        $at = CarbonImmutable::now()->startOfSecond()->utc()->toIso8601String();
        $hash = self::hash($prev, $seq, $kind, $refType, $refId, $payload, $at);

        DB::table('custody_events')->insert([
            'shipment_id' => $shipmentId, 'seq' => $seq, 'kind' => $kind, 'ref_type' => $refType, 'ref_id' => $refId,
            'payload' => json_encode(self::canonical($payload)), 'prev_hash' => $prev, 'hash' => $hash, 'created_at' => $at,
        ]);

        return ['seq' => $seq, 'hash' => $hash];
    }

    /** @return array{ok:bool, events:int, broken_at:?int} */
    public function verify(int $shipmentId): array
    {
        $prev = self::GENESIS;
        $n = 0;
        foreach (DB::table('custody_events')->where('shipment_id', $shipmentId)->orderBy('seq')->cursor() as $e) {
            $n++;
            $at = CarbonImmutable::parse($e->created_at)->utc()->toIso8601String();
            $expected = self::hash($prev, (int) $e->seq, $e->kind, $e->ref_type, $e->ref_id === null ? null : (int) $e->ref_id, json_decode($e->payload, true), $at);
            if ((int) $e->seq !== $n || $e->prev_hash !== $prev || ! hash_equals($expected, $e->hash)) {
                return ['ok' => false, 'events' => $n, 'broken_at' => (int) $e->seq];
            }
            $prev = $e->hash;
        }

        return ['ok' => true, 'events' => $n, 'broken_at' => null];
    }

    /** @return list<array> the trail, oldest first, for the provider's job page */
    public function events(int $shipmentId): array
    {
        return DB::table('custody_events')->where('shipment_id', $shipmentId)->orderBy('seq')->get()
            ->map(fn ($e) => ['seq' => (int) $e->seq, 'kind' => $e->kind, 'payload' => json_decode($e->payload, true), 'at' => $e->created_at, 'hash' => $e->hash])->all();
    }

    private static function hash(string $prev, int $seq, string $kind, ?string $refType, ?int $refId, array $payload, string $at): string
    {
        return hash('sha256', implode('|', [$prev, $seq, $kind, $refType ?? '', $refId ?? '', json_encode(self::canonical($payload)), $at]));
    }

    /** Sorted keys at every level, so the same event always hashes the same after a round trip through jsonb. */
    private static function canonical(array $a): array
    {
        if (! array_is_list($a)) {
            ksort($a);
        }

        return array_map(fn ($v) => is_array($v) ? self::canonical($v) : $v, $a);
    }
}
