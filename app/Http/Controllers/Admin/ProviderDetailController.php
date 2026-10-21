<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Payments\Ledger\AccountResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** One provider seen by the platform owner: standing, money, commission, team and riders, jobs, payouts and documents. */
class ProviderDetailController extends Controller
{
    public const ACTIVE = ['assigned', 'heading_to_pickup', 'at_pickup', 'picked_up', 'in_transit', 'at_dropoff'];

    public function show(Request $request, string $operator, AccountResolver $accounts)
    {
        abort_unless($request->user()->hasAnyPermission(['View vendor', 'View driver', 'View shopper']), 403);
        $op = DB::table('operators')->where('public_id', $operator)->whereIn('type', \App\Support\Workspaces::providerTypes())->first();
        abort_unless($op, 404);
        $month = now()->startOfMonth();
        $ship = fn () => DB::table('shipments')->where('operator_id', $op->id)->where('is_test', false);
        $comm = fn () => DB::table('commissions')->where('operator_id', $op->id);

        $rule = app(\App\Modules\Marketplace\CommissionResolver::class)->resolve((int) $op->id, (string) $op->type, 0, null, 1_000_000);
        $ruleRow = $rule['rule_id'] ? DB::table('commission_rules')->find($rule['rule_id']) : null;

        return view('ops.provider', [
            'op' => $op,
            'profile' => DB::table('provider_profiles')->where('operator_id', $op->id)->first(),
            'score' => DB::table('provider_scores')->where('operator_id', $op->id)->first(),
            'app' => DB::table('provider_applications')->where('operator_id', $op->id)->first(),
            'k' => [
                'in_progress' => $ship()->whereIn('status', self::ACTIVE)->count(),
                'waiting' => $ship()->whereIn('status', ['awaiting_dispatch', 'offered'])->count(),
                'delivered' => $ship()->where('status', 'delivered')->count(),
                'jobs_month' => $comm()->where('created_at', '>=', $month)->count(),
                'sales_month' => (int) $comm()->where('created_at', '>=', $month)->sum('gross_amount'),
                'fee_month' => (int) $comm()->where('created_at', '>=', $month)->sum('platform_fee'),
                'fee_all' => (int) $comm()->sum('platform_fee'),
                'wallet' => (int) DB::table('ledger_accounts')->where('id', $accounts->wallet('operator', (int) $op->id, (int) $op->id))->value('balance'),
                'payouts_waiting' => (int) DB::table('payout_requests')->where('operator_id', $op->id)->where('status', 'requested')->sum('amount'),
            ],
            'commission' => $ruleRow ? [
                'scope' => $ruleRow->operator_id ? 'Special fee for this provider' : ($ruleRow->operator_type ? 'Fee for every '.str_replace('_', ' ', $ruleRow->operator_type) : 'Platform fee'),
                'percent' => rtrim(rtrim(number_format($ruleRow->rate_bp / 100, 2), '0'), '.'), 'min' => (int) $ruleRow->min_fee,
            ] : null,
            'members' => DB::table('operator_members as m')->join('users as u', 'u.id', '=', 'm.user_id')->where('m.operator_id', $op->id)
                ->orderByRaw("CASE m.role WHEN 'owner' THEN 0 WHEN 'admin' THEN 1 ELSE 2 END")->orderBy('u.name')
                ->get(['u.name', 'u.email', 'u.phone_number', 'm.role', 'm.status', 'm.joined_at']),
            'riders' => DB::table('driver_profiles as d')->join('users as u', 'u.id', '=', 'd.user_id')->where('d.operator_id', $op->id)->whereNull('d.deleted_at')
                ->orderByRaw("CASE d.availability WHEN 'on_job' THEN 0 WHEN 'online' THEN 1 ELSE 2 END")->orderBy('u.name')
                ->get(['u.name', 'd.status', 'd.availability', 'd.last_seen_at', 'd.rating_avg', 'd.rating_count']),
            'jobs' => DB::table('shipments as s')->join('orders as o', 'o.id', '=', 's.order_id')->leftJoin('merchants as mc', 'mc.id', '=', 'o.merchant_id')
                ->where('s.operator_id', $op->id)->orderByDesc('s.id')->limit(15)
                ->get(['s.tracking_code', 's.status', 's.is_test', 's.created_at', 'o.public_id as order_id', 'o.order_number', 'o.total', 'mc.display_name as shop']),
            'payouts' => DB::table('payout_requests')->where('operator_id', $op->id)->orderByDesc('id')->limit(8)->get(['amount', 'status', 'created_at', 'failure_reason']),
            'documents' => DB::table('kyc_documents')->where('operator_id', $op->id)->orderByDesc('id')->limit(20)->get(['public_id', 'doc_type', 'status', 'expires_on', 'created_at']),
            'shops' => DB::table('merchants')->where('own_provider_operator_id', $op->id)->get(['id', 'display_name']),
            'pagetitle' => $op->display_name,
        ]);
    }
}
