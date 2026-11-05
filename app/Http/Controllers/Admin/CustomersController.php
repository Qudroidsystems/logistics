<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Payments\Ledger\AccountResolver;
use App\Modules\Payments\WalletService;
use App\Modules\Recurring\RecurringDeliveryService;
use App\Modules\Subscriptions\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Operations > Customers: find a customer, then everything about them on one page. */
class CustomersController extends Controller
{
    private function check(Request $request): void
    {
        abort_unless($request->user()->canAny(['View customer', 'Reply support ticket']), 403);
    }

    public function index(Request $request)
    {
        $this->check($request);
        $q = trim((string) $request->query('q', ''));
        $rows = DB::table('customer_profiles as c')->join('users as u', 'u.id', '=', 'c.user_id')->whereNull('u.deleted_at')
            ->when($q !== '', function ($w) use ($q) {
                $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $q).'%';
                $digits = preg_replace('/\D/', '', $q);
                $w->where(fn ($x) => $x->where('u.name', 'ilike', $like)->orWhere('u.email', 'ilike', $like)
                    ->when(strlen($digits) >= 4, fn ($y) => $y->orWhere('u.phone_number', 'like', '%'.$digits.'%'))
                    ->orWhereExists(fn ($o) => $o->select(DB::raw(1))->from('orders as o')->leftJoin('shipments as s', 's.order_id', '=', 'o.id')
                        ->whereColumn('o.customer_id', 'u.id')->where(fn ($z) => $z->where('o.order_number', 'ilike', $like)->orWhere('s.tracking_code', 'ilike', $like))));
            })
            ->selectRaw("c.public_id, u.id as user_id, u.name, u.email, u.phone_number, u.is_disabled, u.created_at,
                (SELECT count(*) FROM orders o WHERE o.customer_id = u.id) AS orders,
                (SELECT max(o.created_at) FROM orders o WHERE o.customer_id = u.id) AS last_order,
                (SELECT p.name FROM subscriptions s JOIN subscription_plans p ON p.id = s.plan_id WHERE s.user_id = u.id AND s.status IN ('active','past_due') LIMIT 1) AS plan")
            ->orderByRaw($q === '' ? 'last_order DESC NULLS LAST' : 'u.name')->limit(100)->get();

        return view('ops.customers', ['rows' => $rows, 'q' => $q, 'pagetitle' => 'Customers']);
    }

    public function show(Request $request, string $customer, WalletService $wallet, AccountResolver $accounts, SubscriptionService $plans, RecurringDeliveryService $recurring)
    {
        $this->check($request);
        $c = DB::table('customer_profiles as c')->join('users as u', 'u.id', '=', 'c.user_id')->where('c.public_id', $customer)
            ->first(['c.*', 'u.id as uid', 'u.name', 'u.email', 'u.phone_number', 'u.is_disabled', 'u.created_at as joined', 'u.last_login_at', 'u.deleted_at']);
        abort_unless($c, 404);
        $uid = (int) $c->uid;
        $orders = DB::table('orders as o')->leftJoin('shipments as s', 's.order_id', '=', 'o.id')->leftJoin('operators as p', 'p.id', '=', 'o.operator_id')
            ->where('o.customer_id', $uid)->orderByDesc('o.id')->limit(25)
            ->get(['o.public_id', 'o.order_number', 'o.total', 'o.payment_status', 'o.created_at', 's.status', 's.tracking_code', 'p.display_name as provider']);
        $stats = DB::table('orders')->where('customer_id', $uid)->selectRaw("count(*) AS n, coalesce(sum(total) FILTER (WHERE payment_status = 'paid'), 0) AS spent")->first();
        $cancelled = DB::table('orders as o')->join('shipments as s', 's.order_id', '=', 'o.id')->where('o.customer_id', $uid)->where('s.status', 'cancelled')->count();
        $canWallet = $request->user()->can('View customer wallet');

        return view('ops.customer', [
            'c' => $c, 'orders' => $orders, 'stats' => $stats, 'cancelled' => $cancelled,
            'balance' => $canWallet ? $wallet->balance($uid) : null,
            'ledger' => $canWallet ? DB::table('ledger_entries as e')->join('ledger_transactions as t', 't.id', '=', 'e.transaction_id')
                ->where('e.account_id', $accounts->wallet('customer', $uid, 1))->orderByDesc('e.created_at')->limit(15)
                ->get(['e.direction', 'e.amount', 'e.balance_after', 'e.created_at', 't.kind', 't.description']) : collect(),
            'plan' => $plans->summary($uid)['current'],
            'schedules' => $recurring->list($uid),
            'tickets' => DB::table('support_tickets')->where('requester_id', $uid)->orderByDesc('created_at')->limit(10)->get(['public_id', 'number', 'subject', 'status', 'created_at']),
            'requests' => DB::table('service_requests')->where('customer_id', $uid)->orderByDesc('id')->limit(10)->get(['public_id', 'type', 'status', 'reference_price', 'created_at']),
            'notes' => DB::table('customer_notes as n')->leftJoin('users as a', 'a.id', '=', 'n.author_id')->where('n.user_id', $uid)
                ->orderByDesc('n.pinned')->orderByDesc('n.created_at')->limit(50)->get(['n.id', 'n.body', 'n.pinned', 'n.created_at', 'a.name as author']),
            'canSuspend' => $request->user()->can('Suspend customer'),
            'pagetitle' => $c->name,
        ]);
    }

    public function note(Request $request, string $customer)
    {
        $this->check($request);
        $d = $request->validate(['body' => 'required|string|max:2000', 'pinned' => 'nullable|boolean']);
        $uid = DB::table('customer_profiles')->where('public_id', $customer)->value('user_id');
        abort_unless($uid, 404);
        DB::table('customer_notes')->insert(['user_id' => $uid, 'author_id' => $request->user()->id, 'body' => trim($d['body']), 'pinned' => (bool) ($d['pinned'] ?? false), 'created_at' => now()]);

        return back()->with('success', 'Note added.');
    }

    public function suspend(Request $request, string $customer)
    {
        abort_unless($request->user()->can('Suspend customer'), 403);
        $d = $request->validate(['suspend' => 'required|boolean', 'reason' => 'required_if:suspend,1|nullable|string|max:300']);
        $uid = DB::table('customer_profiles')->where('public_id', $customer)->value('user_id');
        abort_unless($uid, 404);
        abort_if(\App\Models\User::find($uid)?->can('dashboard'), 422, 'Staff accounts are managed under Users.');
        DB::transaction(function () use ($uid, $d, $request) {
            // Clearing the remember-me token ends "keep me signed in" cookies too.
            DB::table('users')->where('id', $uid)->update(['is_disabled' => (bool) $d['suspend'], 'updated_at' => now()] + ($d['suspend'] ? ['remember_token' => null] : []));
            if ($d['suspend']) {
                DB::table('personal_access_tokens')->where('tokenable_type', \App\Models\User::class)->where('tokenable_id', $uid)->delete();
                DB::table('sessions')->where('user_id', $uid)->delete();
            }
            DB::table('customer_notes')->insert(['user_id' => $uid, 'author_id' => $request->user()->id, 'pinned' => (bool) $d['suspend'],
                'body' => $d['suspend'] ? 'Suspended: '.$d['reason'] : 'Suspension lifted.', 'created_at' => now()]);
        });

        return back()->with('success', $d['suspend'] ? 'Customer suspended and signed out everywhere.' : 'Suspension lifted.');
    }
}
