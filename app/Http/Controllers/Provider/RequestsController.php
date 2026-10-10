<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Api\Marketplace\NegotiationController;
use App\Http\Controllers\Api\Provider\ProviderPayoutController;
use App\Http\Controllers\Api\Provider\TeamController;
use App\Modules\Marketplace\NegotiationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Customer requests as the provider sees them: the inbox of requests that invite this business, making an offer,
 * and the back-and-forth that follows. Pages call the same code as the provider JSON API, so the rules live in one place.
 * Anyone who may see jobs may make and discuss offers; only an owner or admin may accept one.
 */
class RequestsController extends WorkspaceController
{
    public function __construct(ProviderPayoutController $payouts, TeamController $team, private NegotiationController $neg)
    {
        parent::__construct($payouts, $team);
    }

    public function index(Request $request)
    {
        [$op] = $this->ctx($request, 'requests');

        $waiting = collect($this->neg->inbox($request)->getData(true))->map(function ($r) {
            $r['stops'] = is_string($r['stops'] ?? null) ? json_decode($r['stops'], true) : ($r['stops'] ?? []);

            return $r;
        });

        $threads = DB::table('negotiation_threads as t')->join('service_requests as r', 'r.id', '=', 't.request_id')
            ->where('t.operator_id', $op->id)->orderByDesc('t.updated_at')->limit(50)
            ->get(['t.public_id as thread', 't.status', 'r.public_id as request', 'r.type', 'r.distance_m', 't.updated_at']);

        return $this->view('provider.requests', $request, ['waiting' => $waiting, 'threads' => $threads, 'pagetitle' => 'Requests'], 'requests');
    }

    public function show(Request $request, string $serviceRequest)
    {
        [$op] = $this->ctx($request, 'requests');
        $req = DB::table('service_requests as r')->join('request_invitations as i', function ($j) use ($op) {
            $j->on('i.request_id', '=', 'r.id')->where('i.operator_id', '=', $op->id);
        })->where('r.public_id', $serviceRequest)->first(['r.*', 'i.status as invitation']);
        abort_unless($req, 404);

        if ($req->invitation === 'sent') {
            DB::table('request_invitations')->where(['request_id' => $req->id, 'operator_id' => $op->id])->update(['status' => 'viewed', 'updated_at' => now()]);
        }

        $thread = DB::table('negotiation_threads')->where(['request_id' => $req->id, 'operator_id' => $op->id])->value('public_id');
        $open = in_array($req->status, ['open', 'negotiating'], true) && ($req->expires_at === null || $req->expires_at > now()) && ! in_array($req->invitation, ['declined', 'accepted'], true);

        return $this->view('provider.request', $request, [
            'req' => $req, 'stops' => json_decode($req->stops, true) ?? [], 'packages' => json_decode($req->packages ?? '[]', true) ?? [],
            'thread' => $thread, 'canOffer' => $open && ! $thread, 'pagetitle' => 'Request',
            'costProfiles' => app(\App\Modules\Pricing\ProviderCostingService::class)->profiles($op->id),
        ], 'requests');
    }

    public function offer(Request $request, string $serviceRequest)
    {
        $this->ctx($request, 'requests');
        $request->validate(['price_naira' => 'required|numeric|min:1', 'tip_naira' => 'nullable|numeric|min:0', 'goods_budget_naira' => 'nullable|numeric|min:0']);
        $request->merge($this->terms($request));

        $res = $this->neg->offer($request, $serviceRequest, app(NegotiationService::class));
        if ($res->getStatusCode() >= 400) {
            return $this->back($res, '');
        }

        return redirect()->route('provider.thread', $res->getData(true)['thread'])->with('success', 'Your offer is sent.');
    }

    public function thread(Request $request, string $thread)
    {
        [$op, $role] = $this->ctx($request, 'requests');
        $t = DB::table('negotiation_threads as t')->join('service_requests as r', 'r.id', '=', 't.request_id')
            ->where('t.public_id', $thread)->where('t.operator_id', $op->id)->first(['t.public_id', 't.status', 't.customer_id', 'r.public_id as request', 'r.type', 'r.distance_m']);
        abort_unless($t, 404);

        $data = $this->neg->providerThread($request, $thread)->getData(true);
        $messages = array_map(fn ($m) => $m + ['offer' => is_string($m['terms'] ?? null) ? json_decode($m['terms'], true) : ($m['terms'] ?? null)], $data['messages']);
        $latest = collect($messages)->where('kind', 'counter_offer')->last();

        return $this->view('provider.thread', $request, [
            't' => $t, 'messages' => $messages, 'me' => $request->user()->id, 'latest' => $latest,
            'canAccept' => $t->status === 'open' && $latest && (int) $latest['sender_id'] === (int) $t->customer_id && in_array($role, ['owner', 'admin'], true),
            'pagetitle' => 'Negotiation',
        ], 'requests');
    }

    public function say(Request $request, string $thread)
    {
        $this->ctx($request, 'requests');

        return $this->back($this->neg->say($request, $thread, app(NegotiationService::class)), 'Sent.');
    }

    public function counter(Request $request, string $thread)
    {
        $this->ctx($request, 'requests');
        $request->validate(['price_naira' => 'required|numeric|min:1', 'tip_naira' => 'nullable|numeric|min:0', 'goods_budget_naira' => 'nullable|numeric|min:0']);
        $request->merge($this->terms($request));

        return $this->back($this->neg->providerCounter($request, $thread, app(NegotiationService::class)), 'Your counter-offer is sent.');
    }

    public function accept(Request $request, string $thread)
    {
        $this->ctx($request, 'requests');
        $res = $this->neg->providerAccept($request, $thread, app(NegotiationService::class));
        if ($res->getStatusCode() >= 400) {
            return $this->back($res, '');
        }

        return redirect()->route('provider.requests')->with('success', 'Agreed. The customer has been asked to pay; the job appears under Jobs once they have.');
    }

    // ----------------------------------------------------------------

    /** Naira typed in the form becomes whole kobo for the shared API code. */
    private function terms(Request $request): array
    {
        $kobo = fn ($v) => ($v === null || $v === '') ? null : (int) round(((float) $v) * 100);

        return array_filter([
            'price' => $kobo($request->input('price_naira')),
            'tip' => $kobo($request->input('tip_naira')),
            'goods_budget' => $kobo($request->input('goods_budget_naira')),
            'note' => $request->input('note'),
            'message' => $request->input('message'),
        ], fn ($v) => $v !== null);
    }
}
