<?php

namespace App\Modules\Marketplace;

use App\Modules\Partner\RouteEstimator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

/**
 * The in-app bargaining room. A customer posts a request (route fixed), providers answer with a price,
 * either side counters, and when one side accepts the other's latest offer the terms are locked into an
 * agreement. Locations and distance cannot change inside a thread: a different route is a new request.
 */
class NegotiationService
{
    public const OPEN_REQUEST_FANOUT = 20;
    public const REQUEST_TTL_HOURS = 24;

    public function __construct(private RouteEstimator $routes, private AgreementService $agreements)
    {
    }

    /** @param array $d type, service_type_id, city_id, pickup{line1,lat,lng,..}, dropoff{..}, packages[], budget_min/max, needed_by, visibility, operator_ids[], errand{list,market_id,budget_cap,substitution_policy} */
    public function createRequest(int $customerId, array $d, bool $isTest = false): int
    {
        $route = $this->routes->estimate($d['pickup']['lat'], $d['pickup']['lng'], $d['dropoff']['lat'], $d['dropoff']['lng']);
        $visibility = $d['visibility'] ?? 'open';
        $operatorIds = array_map('intval', $d['operator_ids'] ?? []);
        if ($visibility !== 'open' && ! $operatorIds) {
            throw new InvalidArgumentException('Choose at least one provider.');
        }
        if ($visibility === 'direct' && count($operatorIds) !== 1) {
            throw new InvalidArgumentException('A direct request goes to exactly one provider.');
        }

        return DB::transaction(function () use ($customerId, $d, $route, $visibility, $operatorIds, $isTest) {
            $id = DB::table('service_requests')->insertGetId([
                'public_id' => (string) Str::ulid(), 'customer_id' => $customerId, 'type' => $d['type'], 'service_type_id' => $d['service_type_id'],
                'stops' => json_encode(['pickup' => $d['pickup'], 'dropoff' => $d['dropoff'], 'city_id' => $d['city_id']]),
                'packages' => json_encode(['items' => $d['packages'] ?? [], 'errand' => $d['errand'] ?? null, 'vehicle_type_id' => $d['vehicle_type_id'] ?? null]),
                'distance_m' => $route['distance_m'], 'budget_min' => $d['budget_min'] ?? null, 'budget_max' => $d['budget_max'] ?? null,
                'needed_by' => $d['needed_by'] ?? null, 'visibility' => $visibility, 'status' => 'open', 'is_test' => $isTest,
                'expires_at' => now()->addHours(self::REQUEST_TTL_HOURS), 'created_at' => now(), 'updated_at' => now(),
            ]);

            $targets = $visibility === 'open' ? $this->matchProviders((int) $d['service_type_id'], self::OPEN_REQUEST_FANOUT) : $operatorIds;
            foreach (array_unique($targets) as $op) {
                DB::table('request_invitations')->insertOrIgnore(['request_id' => $id, 'operator_id' => $op, 'status' => 'sent', 'created_at' => now(), 'updated_at' => now()]);
            }

            return $id;
        });
    }

    /** Listed, accepting providers for the service, best tier and rating first. */
    public function matchProviders(int $serviceTypeId, int $limit): array
    {
        return DB::table('provider_profiles as p')->join('operators as o', 'o.id', '=', 'p.operator_id')
            ->where('p.listed', true)->where('p.availability_status', 'accepting')->where('o.status', 'active')
            ->whereRaw('p.service_types @> ?::jsonb', [json_encode([$serviceTypeId])])
            ->orderByRaw("CASE p.tier WHEN 'preferred' THEN 0 WHEN 'trusted' THEN 1 WHEN 'verified' THEN 2 ELSE 3 END")->orderByDesc('p.rating_avg')
            ->limit($limit)->pluck('p.operator_id')->map(fn ($i) => (int) $i)->all();
    }

    /** Provider answers an invitation with an offer. Opens the thread on first reply. */
    public function providerOffer(int $requestId, int $operatorId, int $userId, array $terms, ?string $message = null): int
    {
        return DB::transaction(function () use ($requestId, $operatorId, $userId, $terms, $message) {
            $req = $this->openRequest($requestId);
            $inv = DB::table('request_invitations')->where(['request_id' => $requestId, 'operator_id' => $operatorId])->lockForUpdate()->first();
            if (! $inv) {
                throw new RuntimeException('You were not invited to this request.');
            }
            $thread = $this->thread($requestId, $operatorId, (int) $req->customer_id);
            $this->addOffer((int) $thread->id, $userId, $terms, $message);
            DB::table('request_invitations')->where('id', $inv->id)->update(['status' => 'countered', 'updated_at' => now()]);
            DB::table('service_requests')->where('id', $requestId)->update(['status' => 'negotiating', 'updated_at' => now()]);

            return (int) $thread->id;
        });
    }

    public function customerCounter(int $threadId, int $customerId, array $terms, ?string $message = null): void
    {
        $t = $this->ownedThread($threadId, $customerId);
        $this->addOffer($threadId, $customerId, $terms, $message);
        DB::table('negotiation_threads')->where('id', $t->id)->update(['updated_at' => now()]);
    }

    public function providerCounter(int $threadId, int $operatorId, int $userId, array $terms, ?string $message = null): void
    {
        $t = DB::table('negotiation_threads')->where(['id' => $threadId, 'operator_id' => $operatorId, 'status' => 'open'])->first();
        if (! $t) {
            throw new RuntimeException('Negotiation not found or closed.');
        }
        $this->addOffer($threadId, $userId, $terms, $message);
    }

    public function say(int $threadId, int $userId, string $text): void
    {
        DB::table('negotiation_messages')->insert(['thread_id' => $threadId, 'sender_id' => $userId, 'kind' => 'text', 'body' => mb_substr($text, 0, 2000), 'created_at' => now()]);
    }

    /**
     * Accept the other side's latest offer. $offerId must still be the newest offer, so nobody can accept
     * a price that has just been replaced. Returns the locked agreement id.
     *
     * @param 'customer'|'provider' $role the accepter's side (the caller has already checked they belong to it)
     */
    public function accept(int $threadId, string $role, int $userId, int $offerId): int
    {
        return DB::transaction(function () use ($threadId, $role, $userId, $offerId) {
            $t = DB::table('negotiation_threads')->where('id', $threadId)->where('status', 'open')->lockForUpdate()->first();
            if (! $t) {
                throw new RuntimeException('Negotiation not found or closed.');
            }
            $req = $this->openRequest((int) $t->request_id);
            $latest = DB::table('negotiation_messages')->where('thread_id', $threadId)->where('kind', 'counter_offer')->orderByDesc('id')->first();
            if (! $latest || (int) $latest->id !== $offerId) {
                throw new RuntimeException('That offer has been replaced. Review the latest one.');
            }
            $offerRole = (int) $latest->sender_id === (int) $t->customer_id ? 'customer' : 'provider';
            if ($offerRole === $role) {
                throw new RuntimeException('You cannot accept your own offer.');
            }

            $o = json_decode($latest->terms, true);
            $stops = json_decode($req->stops, true);
            $pk = json_decode($req->packages, true);
            $errand = $pk['errand'] ?? null;
            $goods = (int) ($o['goods_budget'] ?? ($errand['budget_cap'] ?? 0));

            $terms = [
                'pickup' => $stops['pickup'], 'dropoff' => $stops['dropoff'], 'packages' => $pk['items'] ?? [], 'vehicle_type_id' => $o['vehicle_type_id'] ?? $pk['vehicle_type_id'] ?? null,
                'note' => $o['note'] ?? null,
            ] + ($errand ? ['errand' => 'shopping', 'list' => $errand['list'] ?? [], 'market_id' => $errand['market_id'] ?? null, 'substitution_policy' => $errand['substitution_policy'] ?? 'ask'] : []);

            $agreementId = $this->agreements->propose((int) $t->customer_id, (int) $t->operator_id, [
                'terms' => $terms, 'price' => (int) $o['price'], 'goods_budget' => $goods, 'tip' => (int) ($o['tip'] ?? 0),
                'distance_m' => $req->distance_m, 'service_type_id' => (int) $req->service_type_id, 'city_id' => $stops['city_id'] ?? null,
            ], (int) $latest->sender_id, $threadId, (int) $req->id);
            DB::table('agreements')->where('id', $agreementId)->update(['is_test' => (bool) $req->is_test]);

            $version = (int) DB::table('agreements')->where('id', $agreementId)->value('terms_version');
            $this->agreements->accept($agreementId, $offerRole, $version);
            $this->agreements->accept($agreementId, $role, $version);

            DB::table('negotiation_messages')->insert(['thread_id' => $threadId, 'sender_id' => $userId, 'kind' => 'accept', 'terms' => json_encode(['offer_id' => $offerId]), 'created_at' => now()]);
            DB::table('negotiation_threads')->where('id', $threadId)->update(['status' => 'agreed', 'updated_at' => now()]);
            DB::table('negotiation_threads')->where('request_id', $req->id)->where('id', '!=', $threadId)->update(['status' => 'closed', 'updated_at' => now()]);
            DB::table('request_invitations')->where('request_id', $req->id)->where('operator_id', '!=', $t->operator_id)->update(['status' => 'declined', 'updated_at' => now()]);
            DB::table('service_requests')->where('id', $req->id)->update(['status' => 'agreed', 'updated_at' => now()]);

            return $agreementId;
        });
    }

    public function reject(int $threadId, string $role, int $userId): void
    {
        $n = DB::table('negotiation_threads')->where('id', $threadId)->where('status', 'open')->update(['status' => 'closed', 'updated_at' => now()]);
        if (! $n) {
            throw new RuntimeException('Negotiation not found or closed.');
        }
        DB::table('negotiation_messages')->insert(['thread_id' => $threadId, 'sender_id' => $userId, 'kind' => 'reject', 'created_at' => now()]);
    }

    /** Pure rule: an offer needs a positive price and sane optional amounts. */
    public function validOffer(array $t): bool
    {
        return isset($t['price']) && is_int($t['price']) && $t['price'] > 0
            && (! isset($t['tip']) || (is_int($t['tip']) && $t['tip'] >= 0))
            && (! isset($t['goods_budget']) || (is_int($t['goods_budget']) && $t['goods_budget'] >= 0));
    }

    private function addOffer(int $threadId, int $userId, array $terms, ?string $message): void
    {
        if (! $this->validOffer($terms)) {
            throw new InvalidArgumentException('An offer needs a positive whole-kobo price.');
        }
        DB::table('negotiation_messages')->insert([
            'thread_id' => $threadId, 'sender_id' => $userId, 'kind' => 'counter_offer', 'body' => $message,
            'terms' => json_encode(array_intersect_key($terms, array_flip(['price', 'tip', 'goods_budget', 'vehicle_type_id', 'note']))), 'created_at' => now(),
        ]);
    }

    private function openRequest(int $id): object
    {
        $r = DB::table('service_requests')->where('id', $id)->whereIn('status', ['open', 'negotiating'])->where('expires_at', '>', now())->lockForUpdate()->first();
        if (! $r) {
            throw new RuntimeException('This request is closed or has expired.');
        }

        return $r;
    }

    private function thread(int $requestId, int $operatorId, int $customerId): object
    {
        DB::table('negotiation_threads')->insertOrIgnore(['public_id' => (string) Str::ulid(), 'request_id' => $requestId, 'operator_id' => $operatorId, 'customer_id' => $customerId, 'status' => 'open', 'created_at' => now(), 'updated_at' => now()]);
        $t = DB::table('negotiation_threads')->where(['request_id' => $requestId, 'operator_id' => $operatorId])->first();
        if ($t->status !== 'open') {
            throw new RuntimeException('This negotiation is closed.');
        }

        return $t;
    }

    private function ownedThread(int $threadId, int $customerId): object
    {
        $t = DB::table('negotiation_threads')->where(['id' => $threadId, 'customer_id' => $customerId, 'status' => 'open'])->first();
        if (! $t) {
            throw new RuntimeException('Negotiation not found or closed.');
        }

        return $t;
    }
}
