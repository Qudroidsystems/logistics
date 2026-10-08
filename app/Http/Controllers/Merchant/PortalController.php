<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Jobs\DeliverWebhook;
use App\Modules\Partner\ApiClientService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * The merchant's own page for its integration: orders it has booked, test API keys, and where delivery updates are sent.
 * Live API keys are issued by staff after the merchant is verified. Owners and admins of the merchant's team can change things;
 * other members can look. A new key or signing secret is shown once, straight after it is made.
 */
class PortalController extends Controller
{
    /** Delivery updates a merchant can subscribe to. */
    public const EVENTS = ['delivery.created', 'delivery.assigned', 'delivery.driver_arrived', 'delivery.picked_up', 'delivery.delivered', 'delivery.confirmed', 'delivery.failed', 'delivery.returned', 'delivery.disputed', 'delivery.dispute_resolved', 'delivery.cancelled'];

    public function home(Request $request)
    {
        [$m, $role] = $this->ctx($request);
        $count = fn (array $in) => (int) DB::table('shipments as s')->join('orders as o', 'o.id', '=', 's.order_id')->where('o.merchant_id', $m->id)->whereIn('s.status', $in)->count();

        return view('merchant.home', [
            'm' => $m, 'role' => $role,
            'orders' => DB::table('orders as o')->leftJoin('shipments as s', 's.order_id', '=', 'o.id')->where('o.merchant_id', $m->id)->orderByDesc('o.id')->limit(25)
                ->get(['o.order_number', 'o.external_order_id', 'o.total', 'o.payment_status', 'o.created_at', 's.status as delivery', 's.public_id as shipment']),
            'stats' => [
                'orders' => (int) DB::table('orders')->where('merchant_id', $m->id)->count(),
                'on_road' => $count(['assigned', 'heading_to_pickup', 'at_pickup', 'picked_up', 'in_transit', 'at_dropoff', 'returning']),
                'done' => $count(['delivered', 'confirmed', 'completed']),
                'failed' => $count(['failed_attempt', 'returning', 'returned']),
            ],
            'hasKey' => DB::table('api_clients')->where('merchant_id', $m->id)->whereNull('revoked_at')->exists(),
            'hasHook' => DB::table('webhook_endpoints')->where('operator_id', $m->operator_id)->where('active', true)->exists(),
            'pagetitle' => $m->display_name,
        ]);
    }

    public function api(Request $request)
    {
        [$m, $role] = $this->ctx($request);

        return view('merchant.api', [
            'm' => $m, 'role' => $role, 'canEdit' => in_array($role, ['owner', 'admin'], true),
            'keys' => DB::table('api_clients')->where('merchant_id', $m->id)->orderByDesc('id')->get(['id', 'name', 'environment', 'key_prefix', 'last_used_at', 'revoked_at']),
            'hooks' => DB::table('webhook_endpoints')->where('operator_id', $m->operator_id)->orderByDesc('id')->get(['id', 'url', 'events', 'active', 'is_test']),
            'events' => self::EVENTS,
            'log' => DB::table('webhook_deliveries as d')->join('webhook_endpoints as e', 'e.id', '=', 'd.endpoint_id')->where('e.operator_id', $m->operator_id)
                ->orderByDesc('d.id')->limit(20)->get(['d.event', 'd.attempt', 'd.response_status', 'd.delivered_at', 'd.next_retry_at', 'd.created_at', 'e.url']),
            'live' => $this->liveState($m),
            'newKey' => session('new_key'), 'newSecret' => session('new_secret'),
            'pagetitle' => 'API and webhooks',
        ]);
    }

    /** Ask staff for a live key. Needs a verified account and at least one test key that has been used. */
    public function requestLive(Request $request)
    {
        [$m] = $this->managing($request);
        $d = $request->validate(['note' => ['required', 'string', 'min:10', 'max:600']]);
        $state = $this->liveState($m);
        if ($state['stage'] !== 'can_request') {
            return back()->with('error', $state['message'] ?? 'You cannot ask for a live key right now.');
        }
        try {
            DB::table('live_key_requests')->insert(['merchant_id' => $m->id, 'requested_by' => $request->user()->id, 'note' => $d['note'], 'status' => 'pending', 'created_at' => now(), 'updated_at' => now()]);
        } catch (\Illuminate\Database\QueryException) {
            return back()->with('error', 'You already have a request open.');
        }
        $this->tellStaff($m);

        return back()->with('success', 'Request sent. We will review it and tell you here.');
    }

    /** Once staff approve, the owner makes the live key themselves, so only they ever see it. */
    public function createLiveKey(Request $request, ApiClientService $keys)
    {
        [$m] = $this->managing($request);
        $d = $request->validate(['name' => ['required', 'string', 'max:80']]);

        $issued = DB::transaction(function () use ($m, $d, $keys) {
            $req = DB::table('live_key_requests')->where('merchant_id', $m->id)->where('status', 'approved')->lockForUpdate()->first();
            if (! $req || $m->status !== 'verified') {
                return null;
            }
            $issued = $keys->issue((int) $m->operator_id, (int) $m->id, $d['name'], 'live');
            $clientId = DB::table('api_clients')->where('key_prefix', $issued['prefix'])->value('id');
            DB::table('live_key_requests')->where('id', $req->id)->update(['status' => 'issued', 'api_client_id' => $clientId, 'updated_at' => now()]);

            return $issued;
        });
        if (! $issued) {
            return back()->with('error', 'There is no approved request to create a live key from.');
        }

        return back()->with('success', 'Live key created. Copy it now: it will not be shown again.')->with('new_key', $issued['key']);
    }

    /** A test key the merchant can make for itself. Live keys stay with staff. */
    public function issueKey(Request $request, ApiClientService $keys)
    {
        [$m] = $this->managing($request);
        $d = $request->validate(['name' => ['required', 'string', 'max:80']]);
        if (DB::table('api_clients')->where('merchant_id', $m->id)->where('environment', 'sandbox')->whereNull('revoked_at')->count() >= 5) {
            return back()->withInput()->with('error', 'You already have 5 active test keys. Revoke one first.');
        }
        $issued = $keys->issue((int) $m->operator_id, (int) $m->id, $d['name'], 'sandbox');

        return back()->with('success', 'Test key created. Copy it now: it will not be shown again.')->with('new_key', $issued['key']);
    }

    public function revokeKey(Request $request, int $client, ApiClientService $keys)
    {
        [$m] = $this->managing($request);
        $c = DB::table('api_clients')->where('id', $client)->where('merchant_id', $m->id)->first();
        abort_unless($c, 404);
        $keys->revoke((int) $c->id);

        return back()->with('success', 'Key revoked. It stops working straight away.');
    }

    public function addHook(Request $request)
    {
        [$m] = $this->managing($request);
        $d = $request->validate([
            'url' => ['required', 'url', 'max:500', 'starts_with:https://,http://'],
            'environment' => ['required', Rule::in(['sandbox', 'live'])],
            'events' => ['nullable', 'array'], 'events.*' => [Rule::in(self::EVENTS)],
        ]);
        if ($d['environment'] === 'live' && ! str_starts_with($d['url'], 'https://')) {
            return back()->withInput()->with('error', 'A live webhook address must start with https://.');
        }
        if (DB::table('webhook_endpoints')->where('operator_id', $m->operator_id)->count() >= 10) {
            return back()->withInput()->with('error', 'You can have up to 10 webhook addresses.');
        }

        $secret = 'whsec_'.Str::random(40);
        DB::table('webhook_endpoints')->insert([
            'public_id' => (string) Str::ulid(), 'operator_id' => $m->operator_id, 'url' => $d['url'], 'secret' => $secret,
            'events' => json_encode($d['events'] ?? []), 'active' => true, 'is_test' => $d['environment'] !== 'live',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return back()->with('success', 'Webhook added. Copy the signing secret now: it will not be shown again.')->with('new_secret', $secret);
    }

    public function toggleHook(Request $request, int $hook)
    {
        [$m] = $this->managing($request);
        $h = DB::table('webhook_endpoints')->where('id', $hook)->where('operator_id', $m->operator_id)->first();
        abort_unless($h, 404);
        DB::table('webhook_endpoints')->where('id', $h->id)->update(['active' => ! $h->active, 'updated_at' => now()]);

        return back()->with('success', $h->active ? 'Webhook switched off.' : 'Webhook switched on.');
    }

    /** Sends one signed example event so the merchant can check their receiver and signature code. */
    public function testHook(Request $request, int $hook)
    {
        [$m] = $this->managing($request);
        $h = DB::table('webhook_endpoints')->where('id', $hook)->where('operator_id', $m->operator_id)->first();
        abort_unless($h, 404);
        if (! $h->active) {
            return back()->with('error', 'Switch this webhook on first.');
        }
        $eventId = 'evt_'.Str::lower(Str::random(24));
        $id = DB::table('webhook_deliveries')->insertGetId([
            'endpoint_id' => $h->id, 'event' => 'delivery.test', 'event_id' => $eventId,
            'payload' => json_encode(['id' => $eventId, 'type' => 'delivery.test', 'created' => now()->toIso8601String(), 'data' => [
                'order_number' => 'TEST-000001', 'external_order_id' => 'example-order', 'merchant_code' => $m->merchant_code, 'tracking_code' => 'TESTTRACK', 'status' => 'delivered',
            ]]),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DeliverWebhook::dispatch($id);

        return back()->with('success', 'Test event sent. It shows in the log below in a moment.');
    }

    // ----------------------------------------------------------------

    /** Where the merchant is on the way to a live key. @return array{stage:string,message?:string,request?:object} */
    private function liveState(object $m): array
    {
        $hasLive = DB::table('api_clients')->where('merchant_id', $m->id)->where('environment', 'live')->whereNull('revoked_at')->exists();
        $req = DB::table('live_key_requests')->where('merchant_id', $m->id)->orderByDesc('id')->first();
        if ($req && in_array($req->status, ['pending', 'approved'], true)) {
            return ['stage' => $req->status, 'request' => $req];
        }
        if ($m->status !== 'verified') {
            return ['stage' => 'blocked', 'message' => 'Your account must be verified before you can go live.'];
        }
        if (! DB::table('api_clients')->where('merchant_id', $m->id)->where('environment', 'sandbox')->whereNotNull('last_used_at')->exists()) {
            return ['stage' => 'blocked', 'message' => 'Try the API with a test key first. Once a test key has been used, you can ask for a live one.'];
        }

        return ['stage' => 'can_request', 'has_live' => $hasLive, 'request' => $req && $req->status === 'declined' ? $req : null];
    }

    private function tellStaff(object $m): void
    {
        try {
            $notify = app(\App\Modules\Notifications\NotificationService::class);
            foreach (\App\Models\User::permission('Update vendor')->pluck('id') as $id) {
                $notify->notify((int) $id, 'merchant.live_key_requested', ['merchant' => $m->display_name], '/ops/merchants/'.$m->id);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Live key request notice failed: '.$e->getMessage());
        }
    }

    /** @return array{0:object,1:string} the merchant and the signed-in user's role in its team */
    private function ctx(Request $request): array
    {
        $row = DB::table('operator_members as om')->join('merchants as m', 'm.operator_id', '=', 'om.operator_id')
            ->where('om.user_id', $request->user()->id)->where('om.status', 'active')
            ->orderByRaw('om.operator_id = ? desc', [(int) $request->user()->current_operator_id])
            ->first(['m.*', 'om.role as member_role']);
        abort_unless($row, 403, 'You do not have a merchant account.');

        return [$row, $row->member_role];
    }

    private function managing(Request $request): array
    {
        [$m, $role] = $this->ctx($request);
        abort_unless(in_array($role, ['owner', 'admin'], true), 403, 'Only the owner or an admin can change this.');
        abort_unless(in_array($m->status, ['verified', 'pending'], true), 403, 'This merchant account is not active.');

        return [$m, $role];
    }
}
