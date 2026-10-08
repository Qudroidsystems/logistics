<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Partner\ApiClientService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Staff pages for the stores and apps that order deliveries through the partner API (GozakMart and others):
 * register a merchant, issue and revoke its API keys, and set up where its delivery updates are sent.
 * A new key or webhook secret is shown once, straight after it is made, and never again.
 */
class MerchantsController extends Controller
{
    private const STATUSES = ['pending', 'verified', 'restricted', 'suspended'];

    private const SETTLEMENT = ['pay_at_checkout' => 'Customer pays at checkout', 'prepaid_wallet' => 'Merchant prepaid wallet', 'monthly_invoice' => 'Monthly invoice'];

    /** Delivery updates a merchant can subscribe to. '*' means all of them. */
    private const EVENTS = ['delivery.created', 'delivery.assigned', 'delivery.driver_arrived', 'delivery.picked_up', 'delivery.delivered', 'delivery.confirmed', 'delivery.failed', 'delivery.returned', 'delivery.disputed', 'delivery.dispute_resolved', 'delivery.cancelled'];

    public function index()
    {
        $rows = DB::table('merchants as m')
            ->selectRaw('m.id, m.merchant_code, m.display_name, m.status, m.settlement_mode, m.created_at,
                (select count(*) from api_clients c where c.merchant_id = m.id and c.revoked_at is null) as keys_active,
                (select count(*) from orders o where o.merchant_id = m.id) as orders,
                (select count(*) from live_key_requests r where r.merchant_id = m.id and r.status = \'pending\') as live_pending')
            ->orderByDesc('m.id')->limit(200)->get();

        return view('ops.merchants', ['rows' => $rows, 'settlement' => self::SETTLEMENT, 'pagetitle' => 'Merchants']);
    }

    public function store(Request $request)
    {
        $d = $request->validate([
            'display_name' => ['required', 'string', 'max:120'],
            'website' => ['nullable', 'url', 'max:200'],
            'support_email' => ['nullable', 'email', 'max:160'],
            'support_phone' => ['nullable', 'string', 'max:24'],
            'settlement_mode' => ['required', Rule::in(array_keys(self::SETTLEMENT))],
        ]);

        $id = DB::transaction(function () use ($d) {
            $slug = Str::slug($d['display_name']).'-'.Str::lower(Str::random(4));
            $op = DB::table('operators')->insertGetId([
                'public_id' => (string) Str::ulid(), 'type' => 'merchant', 'legal_name' => $d['display_name'], 'display_name' => $d['display_name'],
                'slug' => $slug, 'status' => 'active', 'approved_at' => now(), 'created_at' => now(), 'updated_at' => now(),
            ]);
            $id = DB::table('merchants')->insertGetId([
                'public_id' => (string) Str::ulid(), 'operator_id' => $op, 'merchant_code' => 'TMP-'.Str::upper(Str::random(12)),
                'display_name' => $d['display_name'], 'website' => $d['website'] ?? null, 'support_email' => $d['support_email'] ?? null,
                'support_phone' => $d['support_phone'] ?? null, 'status' => 'verified', 'settlement_mode' => $d['settlement_mode'],
                'verified_at' => now(), 'created_at' => now(), 'updated_at' => now(),
            ]);
            $prefix = (string) (json_decode((string) DB::table('platform_settings')->whereNull('operator_id')->where('key', 'merchant.code_prefix')->value('value'), true) ?: 'MC');
            DB::table('merchants')->where('id', $id)->update(['merchant_code' => $prefix.'-'.str_pad((string) $id, 6, '0', STR_PAD_LEFT)]);
            DB::table('merchant_branding')->insert(['merchant_id' => $id, 'marketplace_display_name' => config('app.name'), 'created_at' => now(), 'updated_at' => now()]);

            return $id;
        });

        return redirect()->route('ops.merchant', $id)->with('success', 'Merchant registered. Issue an API key below so they can connect.');
    }

    public function show(int $merchant)
    {
        $m = DB::table('merchants')->where('id', $merchant)->first();
        abort_unless($m, 404);

        return view('ops.merchant', [
            'm' => $m,
            'keys' => DB::table('api_clients')->where('merchant_id', $m->id)->orderByDesc('id')->get(['id', 'name', 'environment', 'key_prefix', 'scopes', 'last_used_at', 'revoked_at', 'created_at']),
            'hooks' => DB::table('webhook_endpoints')->where('operator_id', $m->operator_id)->orderByDesc('id')->get(['id', 'url', 'events', 'active', 'is_test', 'created_at']),
            'owner' => DB::table('operator_members as om')->join('users as u', 'u.id', '=', 'om.user_id')->where('om.operator_id', $m->operator_id)->where('om.role', 'owner')->where('om.status', 'active')->first(['u.name', 'u.email']),
            'liveRequest' => DB::table('live_key_requests as r')->join('users as u', 'u.id', '=', 'r.requested_by')->where('r.merchant_id', $m->id)->orderByDesc('r.id')->first(['r.id', 'r.status', 'r.note', 'r.decision_note', 'r.created_at', 'u.name as by']),
            'recent' => DB::table('orders')->where('merchant_id', $m->id)->orderByDesc('id')->limit(10)->get(['order_number', 'external_order_id', 'total', 'payment_status', 'created_at']),
            'statuses' => self::STATUSES, 'settlement' => self::SETTLEMENT, 'events' => self::EVENTS,
            'newKey' => session('new_key'), 'newSecret' => session('new_secret'),
            'pagetitle' => $m->display_name,
        ]);
    }

    public function update(Request $request, int $merchant)
    {
        $m = $this->find($merchant);
        $d = $request->validate([
            'status' => ['required', Rule::in(self::STATUSES)],
            'settlement_mode' => ['required', Rule::in(array_keys(self::SETTLEMENT))],
            'support_email' => ['nullable', 'email', 'max:160'],
            'support_phone' => ['nullable', 'string', 'max:24'],
        ]);
        if ($d['status'] !== $m->status && in_array($d['status'], ['restricted', 'suspended'], true)) {
            abort_unless($request->user()->can('Suspend vendor'), 403);
        }

        DB::transaction(function () use ($m, $d) {
            DB::table('merchants')->where('id', $m->id)->update([
                'status' => $d['status'], 'settlement_mode' => $d['settlement_mode'], 'support_email' => $d['support_email'] ?? null,
                'support_phone' => $d['support_phone'] ?? null, 'verified_at' => $d['status'] === 'verified' ? ($m->verified_at ?? now()) : $m->verified_at, 'updated_at' => now(),
            ]);
            DB::table('operators')->where('id', $m->operator_id)->update([
                'status' => ['verified' => 'active', 'pending' => 'pending', 'restricted' => 'restricted', 'suspended' => 'suspended'][$d['status']], 'updated_at' => now(),
            ]);
        });

        return back()->with('success', 'Merchant saved.');
    }

    /** Gives an existing account ownership of the merchant: they sign in to the merchant page, and deliveries are booked and paid under their account. */
    public function setOwner(Request $request, int $merchant)
    {
        $m = $this->find($merchant);
        $d = $request->validate(['email' => ['required', 'email', 'max:160']]);
        $u = DB::table('users')->where('email', $d['email'])->first();
        if (! $u) {
            return back()->withInput()->with('error', 'No account has that email. Ask them to sign up first, then add them here.');
        }

        DB::transaction(function () use ($m, $u, $request) {
            DB::table('operator_members')->where('operator_id', $m->operator_id)->where('role', 'owner')->where('user_id', '!=', $u->id)->update(['role' => 'admin', 'updated_at' => now()]);
            $row = DB::table('operator_members')->where(['operator_id' => $m->operator_id, 'user_id' => $u->id])->first();
            if ($row) {
                DB::table('operator_members')->where('id', $row->id)->update(['role' => 'owner', 'status' => 'active', 'updated_at' => now()]);
            } else {
                DB::table('operator_members')->insert([
                    'operator_id' => $m->operator_id, 'user_id' => $u->id, 'role' => 'owner', 'status' => 'active',
                    'invited_by' => $request->user()->id, 'joined_at' => now(), 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
            if (! $u->current_operator_id) {
                DB::table('users')->where('id', $u->id)->update(['current_operator_id' => $m->operator_id, 'updated_at' => now()]);
            }
        });

        return back()->with('success', $u->name.' is now the owner of this merchant.');
    }

    /** Approve or decline a merchant's request for a live key. Approval only allows it; the merchant makes the key themselves. */
    public function decideLive(Request $request, int $merchant, int $req)
    {
        $m = $this->find($merchant);
        $d = $request->validate(['decision' => ['required', Rule::in(['approve', 'decline'])], 'note' => ['nullable', 'string', 'max:300']]);
        if ($d['decision'] === 'approve' && $m->status !== 'verified') {
            return back()->with('error', 'Only a verified merchant can have a live key. Verify the merchant first.');
        }
        if ($d['decision'] === 'decline' && empty($d['note'])) {
            return back()->withInput()->with('error', 'Tell them why, so they know what to fix.');
        }

        $r = DB::table('live_key_requests')->where('id', $req)->where('merchant_id', $m->id)->where('status', 'pending')->first();
        if (! $r) {
            return back()->with('error', 'That request has already been decided.');
        }
        DB::table('live_key_requests')->where('id', $r->id)->update([
            'status' => $d['decision'] === 'approve' ? 'approved' : 'declined', 'decided_by' => $request->user()->id, 'decided_at' => now(),
            'decision_note' => $d['note'] ?? null, 'updated_at' => now(),
        ]);

        $message = $d['decision'] === 'approve'
            ? 'Your live key request was approved. Open API and webhooks to create your live key.'
            : 'Your live key request was declined: '.$d['note'];
        $notify = app(\App\Modules\Notifications\NotificationService::class);
        foreach (DB::table('operator_members')->where('operator_id', $m->operator_id)->where('status', 'active')->whereIn('role', ['owner', 'admin'])->pluck('user_id') as $uid) {
            $notify->notify((int) $uid, 'merchant.live_key_decided', ['message' => $message], '/merchant/api');
        }

        return back()->with('success', $d['decision'] === 'approve' ? 'Approved. They can now create their live key.' : 'Declined, and they have been told why.');
    }

    public function issueKey(Request $request, int $merchant, ApiClientService $keys)
    {
        $m = $this->find($merchant);
        $d = $request->validate(['name' => ['required', 'string', 'max:80'], 'environment' => ['required', Rule::in(['sandbox', 'live'])]]);
        if ($d['environment'] === 'live' && $m->status !== 'verified') {
            return back()->withInput()->with('error', 'Only a verified merchant can have a live key.');
        }

        $issued = $keys->issue((int) $m->operator_id, (int) $m->id, $d['name'], $d['environment']);

        return back()->with('success', 'Key created. Copy it now: it will not be shown again.')->with('new_key', $issued['key']);
    }

    public function revokeKey(int $merchant, int $client, ApiClientService $keys)
    {
        $m = $this->find($merchant);
        $c = DB::table('api_clients')->where('id', $client)->where('merchant_id', $m->id)->first();
        abort_unless($c, 404);
        $keys->revoke((int) $c->id);

        return back()->with('success', 'Key revoked. It stops working straight away.');
    }

    public function addWebhook(Request $request, int $merchant)
    {
        $m = $this->find($merchant);
        $d = $request->validate([
            'url' => ['required', 'url', 'max:500', 'starts_with:https://,http://'],
            'environment' => ['required', Rule::in(['sandbox', 'live'])],
            'events' => ['nullable', 'array'], 'events.*' => [Rule::in(self::EVENTS)],
        ]);
        if ($d['environment'] === 'live' && ! str_starts_with($d['url'], 'https://')) {
            return back()->withInput()->with('error', 'A live webhook address must start with https://.');
        }

        $secret = 'whsec_'.Str::random(40);
        DB::table('webhook_endpoints')->insert([
            'public_id' => (string) Str::ulid(), 'operator_id' => $m->operator_id, 'url' => $d['url'], 'secret' => $secret,
            'events' => json_encode($d['events'] ?? []), 'active' => true, 'is_test' => $d['environment'] !== 'live',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return back()->with('success', 'Webhook added. Copy the signing secret now: it will not be shown again.')->with('new_secret', $secret);
    }

    public function toggleWebhook(int $merchant, int $hook)
    {
        $m = $this->find($merchant);
        $h = DB::table('webhook_endpoints')->where('id', $hook)->where('operator_id', $m->operator_id)->first();
        abort_unless($h, 404);
        DB::table('webhook_endpoints')->where('id', $h->id)->update(['active' => ! $h->active, 'updated_at' => now()]);

        return back()->with('success', $h->active ? 'Webhook switched off.' : 'Webhook switched on.');
    }

    // ----------------------------------------------------------------

    private function find(int $id): object
    {
        $m = DB::table('merchants')->where('id', $id)->first();
        abort_unless($m, 404);

        return $m;
    }
}
