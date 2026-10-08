<?php

namespace App\Http\Controllers\Api\Marketplace;

use App\Http\Controllers\Api\Concerns\ResolvesOperator;
use App\Http\Controllers\Controller;
use App\Modules\Marketplace\NegotiationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class NegotiationController extends Controller
{
    use ResolvesOperator;

    private const PROVIDER_ROLES = ['owner', 'admin', 'dispatcher'];

    // ---------- customer ----------

    public function providers(Request $request)
    {
        $d = $request->validate(['service_type_id' => 'required|integer', 'limit' => 'nullable|integer|min:1|max:50']);

        return response()->json(DB::table('provider_profiles as p')->join('operators as o', 'o.id', '=', 'p.operator_id')
            ->where('p.listed', true)->where('o.status', 'active')->whereRaw('p.service_types @> ?::jsonb', [json_encode([(int) $d['service_type_id']])])
            ->orderByRaw("CASE p.tier WHEN 'preferred' THEN 0 WHEN 'trusted' THEN 1 WHEN 'verified' THEN 2 ELSE 3 END")->orderByDesc('p.rating_avg')->limit($d['limit'] ?? 20)
            ->get(['p.operator_id as id', 'o.display_name as name', 'p.public_slug', 'p.headline', 'p.tier', 'p.rating_avg', 'p.rating_count', 'p.jobs_completed',
                'p.on_time_rate', 'p.starting_price_hint', 'p.availability_status', 'p.verified_badges']));
    }

    public function createRequest(Request $request, NegotiationService $svc)
    {
        $d = $request->validate([
            'type' => 'required|in:parcel,freight,errand,shopping,moving,bulk', 'service_type_id' => 'required|integer', 'city_id' => 'required|integer', 'vehicle_type_id' => 'nullable|integer',
            'pickup.line1' => 'required|string|max:255', 'pickup.lat' => 'required|numeric|between:-90,90', 'pickup.lng' => 'required|numeric|between:-180,180',
            'pickup.contact_name' => 'nullable|string|max:120', 'pickup.contact_phone' => 'nullable|string|max:24',
            'dropoff.line1' => 'required|string|max:255', 'dropoff.lat' => 'required|numeric|between:-90,90', 'dropoff.lng' => 'required|numeric|between:-180,180',
            'dropoff.contact_name' => 'nullable|string|max:120', 'dropoff.contact_phone' => 'nullable|string|max:24',
            'packages' => 'nullable|array|max:20', 'budget_min' => 'nullable|integer|min:0', 'budget_max' => 'nullable|integer|min:0', 'needed_by' => 'nullable|date|after:now',
            'visibility' => 'nullable|in:direct,invited,open', 'operator_ids' => 'nullable|array|max:20', 'operator_ids.*' => 'integer',
            'errand.list' => 'required_if:type,shopping|array', 'errand.budget_cap' => 'required_if:type,shopping|integer|min:1', 'errand.market_id' => 'nullable|integer',
            'errand.substitution_policy' => 'nullable|in:ask,allow,none',
        ]);

        return $this->run(fn () => ['request_id' => DB::table('service_requests')->where('id', $svc->createRequest($request->user()->id, $d))->value('public_id')], 201);
    }

    public function myRequests(Request $request)
    {
        return response()->json(DB::table('service_requests')->where('customer_id', $request->user()->id)->orderByDesc('id')->limit(50)
            ->get(['public_id', 'type', 'status', 'visibility', 'distance_m', 'expires_at', 'created_at']));
    }

    /** Offers on one request, with the provider's standing. */
    public function offers(Request $request, string $serviceRequest)
    {
        $req = DB::table('service_requests')->where('public_id', $serviceRequest)->where('customer_id', $request->user()->id)->first();
        abort_unless($req, 404);

        return response()->json(DB::table('negotiation_threads as t')->join('operators as o', 'o.id', '=', 't.operator_id')
            ->leftJoin('provider_profiles as p', 'p.operator_id', '=', 't.operator_id')->leftJoin('agreements as a', 'a.thread_id', '=', 't.id')->where('t.request_id', $req->id)
            ->selectRaw("t.public_id as thread, t.status, o.display_name as provider, p.rating_avg, p.tier, p.jobs_completed, p.public_slug as slug, a.public_id as agreement, a.status as agreement_status,
                (SELECT (m.terms::jsonb ->> 'price')::bigint FROM negotiation_messages m WHERE m.thread_id = t.id AND m.kind = 'counter_offer' ORDER BY m.id DESC LIMIT 1) AS latest_price")
            ->get());
    }

    public function customerThread(Request $request, string $thread)
    {
        $t = DB::table('negotiation_threads')->where('public_id', $thread)->where('customer_id', $request->user()->id)->first();
        abort_unless($t, 404);

        return $this->messages($t);
    }

    public function customerCounter(Request $request, string $thread, NegotiationService $svc)
    {
        $t = DB::table('negotiation_threads')->where('public_id', $thread)->where('customer_id', $request->user()->id)->first();
        abort_unless($t, 404);
        $d = $this->offerInput($request);

        return $this->run(function () use ($svc, $t, $request, $d) {
            $svc->customerCounter((int) $t->id, $request->user()->id, $d['terms'], $d['message']);

            return ['ok' => true];
        });
    }

    public function customerAccept(Request $request, string $thread, NegotiationService $svc)
    {
        $t = DB::table('negotiation_threads')->where('public_id', $thread)->where('customer_id', $request->user()->id)->first();
        abort_unless($t, 404);
        $d = $request->validate(['offer_id' => 'required|integer']);

        return $this->run(fn () => $this->agreementResult($svc->accept((int) $t->id, 'customer', $request->user()->id, (int) $d['offer_id'])), 201);
    }

    public function customerReject(Request $request, string $thread, NegotiationService $svc)
    {
        $t = DB::table('negotiation_threads')->where('public_id', $thread)->where('customer_id', $request->user()->id)->first();
        abort_unless($t, 404);

        return $this->run(function () use ($svc, $t, $request) {
            $svc->reject((int) $t->id, 'customer', $request->user()->id);

            return ['ok' => true];
        });
    }

    // ---------- provider ----------

    /** Requests waiting for this provider's offer. */
    public function inbox(Request $request)
    {
        $op = $this->operatorId($request, self::PROVIDER_ROLES);

        return response()->json(DB::table('request_invitations as i')->join('service_requests as r', 'r.id', '=', 'i.request_id')
            ->where('i.operator_id', $op)->whereIn('i.status', ['sent', 'viewed', 'countered'])->whereIn('r.status', ['open', 'negotiating'])->where('r.expires_at', '>', now())
            ->orderByDesc('r.id')->limit(50)
            ->get(['r.public_id', 'r.type', 'r.distance_m', 'r.budget_min', 'r.budget_max', 'r.needed_by', 'r.stops', 'i.status as invitation']));
    }

    public function offer(Request $request, string $serviceRequest, NegotiationService $svc)
    {
        $op = $this->operatorId($request, self::PROVIDER_ROLES);
        $req = DB::table('service_requests')->where('public_id', $serviceRequest)->first();
        abort_unless($req, 404);
        $d = $this->offerInput($request);

        return $this->run(fn () => ['thread' => DB::table('negotiation_threads')->where('id', $svc->providerOffer((int) $req->id, $op, $request->user()->id, $d['terms'], $d['message']))->value('public_id')], 201);
    }

    public function providerThread(Request $request, string $thread)
    {
        $op = $this->operatorId($request, self::PROVIDER_ROLES);
        $t = DB::table('negotiation_threads')->where('public_id', $thread)->where('operator_id', $op)->first();
        abort_unless($t, 404);

        return $this->messages($t);
    }

    public function providerCounter(Request $request, string $thread, NegotiationService $svc)
    {
        $op = $this->operatorId($request, self::PROVIDER_ROLES);
        $t = DB::table('negotiation_threads')->where('public_id', $thread)->where('operator_id', $op)->first();
        abort_unless($t, 404);
        $d = $this->offerInput($request);

        return $this->run(function () use ($svc, $t, $op, $request, $d) {
            $svc->providerCounter((int) $t->id, $op, $request->user()->id, $d['terms'], $d['message']);

            return ['ok' => true];
        });
    }

    public function providerAccept(Request $request, string $thread, NegotiationService $svc)
    {
        $op = $this->operatorId($request, ['owner', 'admin']);
        $t = DB::table('negotiation_threads')->where('public_id', $thread)->where('operator_id', $op)->first();
        abort_unless($t, 404);
        $d = $request->validate(['offer_id' => 'required|integer']);

        return $this->run(fn () => $this->agreementResult($svc->accept((int) $t->id, 'provider', $request->user()->id, (int) $d['offer_id'])), 201);
    }

    public function say(Request $request, string $thread, NegotiationService $svc)
    {
        $d = $request->validate(['text' => 'required|string|max:2000']);
        $t = DB::table('negotiation_threads')->where('public_id', $thread)->first();
        abort_unless($t, 404);
        $isCustomer = (int) $t->customer_id === $request->user()->id;
        if (! $isCustomer) {
            $this->operatorId($request, self::PROVIDER_ROLES);
            abort_unless((int) $request->user()->current_operator_id === (int) $t->operator_id, 403);
        }
        $svc->say((int) $t->id, $request->user()->id, $d['text']);

        return response()->json(['ok' => true], 201);
    }

    // ---------- helpers ----------

    private function offerInput(Request $request): array
    {
        $d = $request->validate(['price' => 'required|integer|min:1', 'tip' => 'nullable|integer|min:0', 'goods_budget' => 'nullable|integer|min:0', 'vehicle_type_id' => 'nullable|integer', 'note' => 'nullable|string|max:500', 'message' => 'nullable|string|max:1000']);

        $terms = array_diff_key($d, ['message' => 1, 'note' => 1]);
        foreach ($terms as $k => $v) {
            $terms[$k] = (int) $v;
        }
        if (isset($d['note'])) {
            $terms['note'] = $d['note'];
        }

        return ['terms' => $terms, 'message' => $d['message'] ?? null];
    }

    private function messages(object $t)
    {
        return response()->json([
            'status' => $t->status,
            'messages' => DB::table('negotiation_messages')->where('thread_id', $t->id)->orderBy('id')->get(['id', 'sender_id', 'kind', 'body', 'terms', 'created_at']),
        ]);
    }

    private function agreementResult(int $agreementId): array
    {
        $a = DB::table('agreements')->find($agreementId);

        return ['agreement' => $a->public_id, 'number' => $a->number, 'status' => $a->status, 'price' => (int) $a->price, 'platform_fee' => (int) $a->platform_fee, 'goods_budget' => (int) $a->goods_budget, 'tip' => (int) $a->tip];
    }

    private function run(callable $fn, int $status = 200)
    {
        try {
            return response()->json($fn(), $status);
        } catch (InvalidArgumentException|RuntimeException $e) {
            return response()->json(['error' => 'rejected', 'message' => $e->getMessage()], 422);
        }
    }
}
