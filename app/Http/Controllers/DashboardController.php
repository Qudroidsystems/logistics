<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Workspaces;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The platform owner's dashboard: the whole marketplace at a glance (sales, commission, deliveries, providers,
 * shops, customers, money held and paid out) and everything waiting for a decision. Providers have their own
 * dashboard at /provider; this one is for the company that runs the platform.
 */
class DashboardController extends Controller
{
    public const ACTIVE = ['assigned', 'heading_to_pickup', 'at_pickup', 'picked_up', 'in_transit', 'at_dropoff'];

    public function __construct()
    {
        $this->middleware('permission:dashboard');
    }

    public function index()
    {
        $today = now()->startOfDay();
        $month = now()->startOfMonth();
        $week = now()->subDays(7);
        $types = Workspaces::providerTypes();

        $orders = DB::table('orders')->where('is_test', false);
        $paidToday = (clone $orders)->where('created_at', '>=', $today)->where('payment_status', 'paid');
        $paidMonth = (clone $orders)->where('created_at', '>=', $month)->where('payment_status', 'paid');
        $ship = fn () => DB::table('shipments')->where('is_test', false);
        $comm = fn () => DB::table('commissions as c')->join('shipments as s', 's.id', '=', 'c.shipment_id')->where('s.is_test', false);

        $money = DB::table('ledger_accounts')->where('operator_id', '>', 0)->selectRaw("
            COALESCE(SUM(balance) FILTER (WHERE purpose = 'order_escrow'), 0) AS escrow,
            COALESCE(SUM(balance) FILTER (WHERE purpose = 'payout_in_transit'), 0) AS in_transit")->first();

        $k = [
            'orders_today' => (clone $orders)->where('created_at', '>=', $today)->count(),
            'paid_today' => (clone $paidToday)->count(),
            'sales_today' => (int) (clone $paidToday)->sum('total'),
            'sales_month' => (int) (clone $paidMonth)->sum('total'),
            'orders_month' => (clone $paidMonth)->count(),
            'commission_today' => (int) $comm()->where('c.created_at', '>=', $today)->sum('c.platform_fee'),
            'commission_month' => (int) $comm()->where('c.created_at', '>=', $month)->sum('c.platform_fee'),
            'in_progress' => $ship()->whereIn('status', self::ACTIVE)->count(),
            'waiting_rider' => $ship()->whereIn('status', ['awaiting_dispatch', 'offered'])->count(),
            'waiting_rider_long' => $ship()->whereIn('status', ['awaiting_dispatch', 'offered'])->where('updated_at', '<', now()->subMinutes(15))->count(),
            'late' => $ship()->whereIn('status', self::ACTIVE)->whereNotNull('deliver_by')->where('deliver_by', '<', now())->count(),
            'delivered_today' => $ship()->where('status', 'delivered')->where('updated_at', '>=', $today)->count(),
            'failed_today' => $ship()->where('status', 'failed_attempt')->where('updated_at', '>=', $today)->count(),
            'providers_active' => DB::table('operators')->whereIn('type', $types)->where('status', 'active')->count(),
            'providers_pending' => DB::table('provider_applications')->where('status', 'submitted')->count(),
            'providers_suspended' => DB::table('operators')->whereIn('type', $types)->where('status', 'suspended')->count(),
            'riders_online' => DB::table('driver_profiles')->whereIn('availability', ['online', 'on_job'])->where('status', 'active')->whereNull('deleted_at')
                ->where('last_seen_at', '>=', now()->subMinutes(15))->count(),
            'merchants' => DB::table('merchants')->where('status', 'verified')->count(),
            'merchant_orders_today' => (clone $orders)->whereNotNull('merchant_id')->where('created_at', '>=', $today)->count(),
            'customers' => DB::table('customer_profiles')->count(),
            'customers_new' => DB::table('customer_profiles')->where('created_at', '>=', $week)->count(),
            'escrow' => (int) $money->escrow,
            'payouts_in_transit' => (int) $money->in_transit,
            'payouts_waiting' => DB::table('payout_requests')->where('status', 'requested')->count(),
            'payouts_waiting_amount' => (int) DB::table('payout_requests')->where('status', 'requested')->sum('amount'),
            'disputes' => DB::table('disputes')->whereIn('status', ['open', 'evidence'])->count(),
            'risk' => DB::table('risk_events')->where('status', 'open')->count(),
            'live_keys' => Schema::hasTable('live_key_requests') ? DB::table('live_key_requests')->where('status', 'pending')->count() : 0,
            'settlements_draft' => DB::table('settlements')->where('status', 'draft')->count(),
        ];

        $topProviders = $comm()->join('operators as o', 'o.id', '=', 'c.operator_id')->where('c.created_at', '>=', $month)
            ->groupBy('o.id', 'o.display_name', 'o.public_id')->orderByRaw('SUM(c.gross_amount) DESC')->limit(6)
            ->get(['o.public_id', 'o.display_name', DB::raw('COUNT(*) AS jobs'), DB::raw('SUM(c.gross_amount) AS sales'), DB::raw('SUM(c.platform_fee) AS fee')]);
        $topMerchants = DB::table('orders as o')->join('merchants as m', 'm.id', '=', 'o.merchant_id')->where('o.is_test', false)->where('o.created_at', '>=', $month)
            ->groupBy('m.id', 'm.display_name')->orderByRaw('COUNT(*) DESC')->limit(6)
            ->get(['m.id', 'm.display_name', DB::raw('COUNT(*) AS orders'), DB::raw("COALESCE(SUM(o.total) FILTER (WHERE o.payment_status = 'paid'), 0) AS sales")]);

        $recent = Schema::hasTable('activity_logs')
            ? DB::table('activity_logs as a')->leftJoin('users as u', 'u.id', '=', 'a.user_id')
                ->orderByDesc('a.id')->limit(8)->get(['a.created_at', 'u.name', 'a.event', 'a.description'])
            : collect();

        return view('dashboards.dashboard', [
            'pagetitle' => 'Platform overview',
            'k' => $k,
            'topProviders' => $topProviders,
            'topMerchants' => $topMerchants,
            'recent' => $recent,
            'users' => ['total' => User::count(), 'online' => User::where('last_seen_at', '>=', now()->subMinutes(10))->count()],
        ]);
    }
}
