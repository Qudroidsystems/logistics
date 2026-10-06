<?php

namespace App\Modules\Marketplace;

use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Turns a negotiation into a locked, priced contract.
 *
 * A change to the terms creates a new version and clears both signatures, so a customer can never
 * pay against terms the provider has not seen. The platform fee is computed and frozen at lock time.
 */
class AgreementService
{
    public function __construct(private CommissionResolver $commission)
    {
    }

    /** @param array{terms:array, price:int, goods_budget?:int, tip?:int, distance_m?:?int, service_type_id:int, city_id?:?int} $d */
    public function propose(int $customerId, int $providerOperatorId, array $d, int $proposedBy, ?int $threadId = null, ?int $requestId = null): int
    {
        return DB::transaction(function () use ($customerId, $providerOperatorId, $d, $proposedBy, $threadId, $requestId) {
            $id = DB::table('agreements')->insertGetId([
                'public_id' => (string) \Illuminate\Support\Str::ulid(),
                'number' => 'AG'.now()->format('ymd').strtoupper(\Illuminate\Support\Str::random(6)),
                'request_id' => $requestId,
                'thread_id' => $threadId,
                'customer_id' => $customerId,
                'provider_operator_id' => $providerOperatorId,
                'terms' => json_encode($d['terms'] + ['service_type_id' => $d['service_type_id'], 'city_id' => $d['city_id'] ?? null]),
                'terms_version' => 1,
                'distance_m' => $d['distance_m'] ?? null,
                'price' => $d['price'],
                'goods_budget' => $d['goods_budget'] ?? 0,
                'tip' => $d['tip'] ?? 0,
                'platform_fee' => 0,
                'provider_net' => $d['price'],
                'confirmation_window_hours' => (int) (DB::table('platform_settings')->whereNull('operator_id')->where('key', 'escrow.default_confirmation_window_hours')->value('value') ?? 24),
                'status' => 'draft',
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $this->snapshot($id, 1, $d['terms'], $d['price'], $proposedBy);

            return $id;
        });
    }

    /** Either side changes price or terms: bump the version, clear signatures, ask the other party. */
    public function revise(int $agreementId, array $terms, int $price, int $byUserId, string $byRole): void
    {
        DB::transaction(function () use ($agreementId, $terms, $price, $byUserId, $byRole) {
            $a = $this->lock($agreementId);
            $this->assertOpen($a);
            $version = $a->terms_version + 1;

            DB::table('agreements')->where('id', $agreementId)->update([
                'terms' => json_encode($terms), 'price' => $price, 'terms_version' => $version,
                'provider_net' => $price, 'customer_signed_at' => null, 'provider_signed_at' => null,
                'status' => $byRole === 'customer' ? 'awaiting_provider' : 'awaiting_customer',
                'updated_at' => now(),
            ]);
            $this->snapshot($agreementId, $version, $terms, $price, $byUserId);
        });
    }

    /** A party signs the CURRENT version. When both have, the agreement locks. */
    public function accept(int $agreementId, string $role, int $expectedVersion): string
    {
        if (! in_array($role, ['customer', 'provider'], true)) {
            throw new RuntimeException('Unknown role.');
        }

        return DB::transaction(function () use ($agreementId, $role, $expectedVersion) {
            $a = $this->lock($agreementId);
            $this->assertOpen($a);
            if ((int) $a->terms_version !== $expectedVersion) {
                throw new RuntimeException('The terms changed; review the latest version before accepting.');
            }

            $col = $role.'_signed_at';
            DB::table('agreements')->where('id', $agreementId)->update([$col => now(), 'updated_at' => now()]);
            DB::table('agreement_versions')->where('agreement_id', $agreementId)->where('version', $expectedVersion)
                ->update(["accepted_by_{$role}_at" => now()]);

            $a = $this->lock($agreementId);
            if ($a->customer_signed_at && $a->provider_signed_at) {
                return $this->lockTerms($a);
            }
            $status = $role === 'customer' ? 'awaiting_provider' : 'awaiting_customer';
            DB::table('agreements')->where('id', $agreementId)->update(['status' => $status]);

            return $status;
        });
    }

    private function lockTerms(object $a): string
    {
        $terms = json_decode($a->terms, true);
        $providerType = (string) DB::table('operators')->where('id', $a->provider_operator_id)->value('type');
        $r = $this->commission->resolve((int) $a->provider_operator_id, $providerType, (int) $terms['service_type_id'], $terms['city_id'] ?? null, (int) $a->price);

        DB::table('agreements')->where('id', $a->id)->update([
            'platform_fee' => $r['fee'],
            'fee_rule_id' => $r['rule_id'],
            'provider_net' => $a->price - $r['fee'],
            'status' => 'locked',
            'locked_at' => now(),
            'updated_at' => now(),
        ]);

        return 'locked';
    }

    private function snapshot(int $id, int $version, array $terms, int $price, ?int $by): void
    {
        DB::table('agreement_versions')->insert([
            'agreement_id' => $id, 'version' => $version, 'terms' => json_encode($terms),
            'price' => $price, 'proposed_by' => $by, 'created_at' => now(),
        ]);
    }

    private function lock(int $id): object
    {
        $a = DB::table('agreements')->where('id', $id)->lockForUpdate()->first();
        if (! $a) {
            throw new RuntimeException('Agreement not found.');
        }

        return $a;
    }

    private function assertOpen(object $a): void
    {
        if (! in_array($a->status, ['draft', 'awaiting_customer', 'awaiting_provider'], true)) {
            throw new RuntimeException("Agreement is {$a->status} and can no longer be changed.");
        }
    }
}
