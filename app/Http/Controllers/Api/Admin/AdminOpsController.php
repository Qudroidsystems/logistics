<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Marketplace\DisputeService;
use App\Modules\Ratings\ProviderScoreService;
use App\Modules\Settlements\SettlementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

/** Staff read and decision endpoints for the admin screens. Permissions are enforced on the routes. */
class AdminOpsController extends Controller
{
    // ------------------------------------------------------------ dashboard

    public function dashboard()
    {
        $today = now()->startOfDay();
        $money = DB::table('ledger_accounts')->where('operator_id', '>', 0)->selectRaw("
            COALESCE(SUM(balance) FILTER (WHERE purpose = 'order_escrow'), 0) AS escrow_held,
            COALESCE(SUM(balance) FILTER (WHERE purpose = 'platform_commission'), 0) AS commission_earned,
            COALESCE(SUM(balance) FILTER (WHERE purpose = 'payout_in_transit'), 0) AS payouts_in_transit")->first();

        return response()->json([
            'orders_today' => DB::table('orders')->where('created_at', '>=', $today)->count(),
            'paid_orders_today' => DB::table('orders')->where('created_at', '>=', $today)->where('payment_status', 'paid')->count(),
            'gmv_today' => (int) DB::table('orders')->where('created_at', '>=', $today)->where('payment_status', 'paid')->sum('total'),
            'active_shipments' => DB::table('shipments')->whereIn('status', ['assigned', 'heading_to_pickup', 'at_pickup', 'picked_up', 'in_transit', 'at_dropoff'])->count(),
            'escrow_held' => (int) $money->escrow_held,
            'commission_earned' => (int) $money->commission_earned,
            'payouts_in_transit' => (int) $money->payouts_in_transit,
            'open_disputes' => DB::table('disputes')->whereIn('status', ['open', 'evidence'])->count(),
            'payouts_waiting_approval' => DB::table('payout_requests')->where('status', 'requested')->count(),
            'kyc_waiting' => DB::table('provider_applications')->where('status', 'submitted')->count(),
            'open_risk_events' => DB::table('risk_events')->where('status', 'open')->count(),
            'active_providers' => DB::table('operators')->whereIn('type', ['company', 'independent_driver', 'market_shopper'])->where('status', 'active')->count(),
        ]);
    }

    // ------------------------------------------------------------ orders

    public function orders(Request $request)
    {
        $q = DB::table('orders as o')->leftJoin('shipments as s', 's.order_id', '=', 'o.id')->leftJoin('operators as p', 'p.id', '=', 's.operator_id')
            ->when($request->query('status'), fn ($q, $v) => $q->where('s.status', $v))
            ->when($request->query('payment_status'), fn ($q, $v) => $q->where('o.payment_status', $v))
            ->when($request->query('q'), fn ($q, $v) => $q->where(fn ($w) => $w->where('o.order_number', 'ilike', "%{$v}%")->orWhere('s.tracking_code', 'ilike', "%{$v}%")))
            ->orderByDesc('o.id')->limit(min((int) $request->query('limit', 50), 200));

        return response()->json($q->get(['o.public_id as order_id', 'o.order_number', 'o.total', 'o.payment_status', 's.public_id as shipment_id', 's.status', 's.tracking_code', 'p.display_name as provider', 'o.created_at']));
    }

    public function order(string $order)
    {
        $o = DB::table('orders')->where('public_id', $order)->first();
        abort_unless($o, 404);
        $s = DB::table('shipments')->where('order_id', $o->id)->first();

        return response()->json([
            'order' => ['public_id' => $o->public_id, 'order_number' => $o->order_number, 'total' => $o->total, 'payment_status' => $o->payment_status, 'status' => $o->status, 'customer' => DB::table('users')->where('id', $o->customer_id)->first(['name', 'email'])],
            'shipment' => $s ? ['public_id' => $s->public_id, 'status' => $s->status, 'tracking_code' => $s->tracking_code, 'provider' => DB::table('operators')->where('id', $s->operator_id)->value('display_name')] : null,
            'timeline' => $s ? DB::table('shipment_events')->where('shipment_id', $s->id)->orderBy('seq')->get(['seq', 'type', 'from_status', 'to_status', 'actor_type', 'created_at']) : [],
            'escrow' => $o->agreement_id ? DB::table('escrow_holds')->where('agreement_id', $o->agreement_id)->first(['amount', 'status', 'released_amount', 'refunded_amount']) : null,
            'refunds' => DB::table('refunds')->where('order_id', $o->id)->get(['public_id', 'amount', 'status', 'reason_code', 'created_at']),
        ]);
    }

    // ------------------------------------------------------------ disputes

    public function disputes(Request $request)
    {
        $status = $request->query('status', 'open');

        return response()->json(DB::table('disputes as d')->join('shipments as s', 's.id', '=', 'd.shipment_id')->join('orders as o', 'o.id', '=', 's.order_id')
            ->join('operators as p', 'p.id', '=', 's.operator_id')
            ->when($status === 'open', fn ($q) => $q->whereIn('d.status', ['open', 'evidence']))
            ->when(! in_array($status, ['open', 'all'], true), fn ($q) => $q->where('d.status', $status))
            ->orderBy('d.created_at')->limit(100)
            ->get(['d.public_id as dispute_id', 'd.type', 'd.status', 'd.decision', 'o.order_number', 'p.display_name as provider', 'd.created_at']));
    }

    public function dispute(string $dispute)
    {
        $d = DB::table('disputes')->where('public_id', $dispute)->first();
        abort_unless($d, 404);
        $agreement = DB::table('agreements')->find($d->agreement_id);

        return response()->json([
            'dispute' => ['public_id' => $d->public_id, 'type' => $d->type, 'status' => $d->status, 'decision' => $d->decision, 'evidence' => json_decode($d->evidence ?? '{}', true), 'created_at' => $d->created_at],
            'agreement' => $agreement ? ['price' => $agreement->price, 'status' => $agreement->status] : null,
            'escrow' => DB::table('escrow_holds')->where('id', $d->escrow_hold_id)->first(['amount', 'status', 'frozen_reason']),
            'receipts' => $agreement ? DB::table('shopping_receipts as r')->join('shopping_requests as q', 'q.id', '=', 'r.shopping_request_id')->where('q.agreement_id', $agreement->id)->get(['r.vendor_name', 'r.amount', 'r.verified_by_customer', 'r.uploaded_at']) : [],
            'timeline' => DB::table('shipment_events')->where('shipment_id', $d->shipment_id)->orderBy('seq')->get(['type', 'actor_type', 'created_at']),
        ]);
    }

    /** decision: full_release | partial (needs provider_share, in kobo) | full_refund. Moves real money, so it is one atomic call. */
    public function decideDispute(Request $request, string $dispute, DisputeService $svc)
    {
        $data = $request->validate(['decision' => 'required|in:full_release,partial,full_refund', 'provider_share' => 'required_if:decision,partial|nullable|integer|min:0']);
        $id = DB::table('disputes')->where('public_id', $dispute)->value('id');
        abort_unless($id, 404);

        try {
            $svc->decide((int) $id, $data['decision'], $request->user()->id, isset($data['provider_share']) ? (int) $data['provider_share'] : null);
        } catch (InvalidArgumentException|RuntimeException $e) {
            return response()->json(['error' => 'cannot_decide', 'message' => $e->getMessage()], 422);
        }

        return response()->json(['ok' => true]);
    }

    // ------------------------------------------------------------ money

    public function refunds(Request $request)
    {
        return response()->json(DB::table('refunds as r')->join('orders as o', 'o.id', '=', 'r.order_id')
            ->when($request->query('status'), fn ($q, $v) => $q->where('r.status', $v))
            ->orderByDesc('r.id')->limit(100)->get(['r.public_id', 'o.order_number', 'r.amount', 'r.status', 'r.reason_code', 'r.created_at']));
    }

    public function settlements(Request $request)
    {
        return response()->json(DB::table('settlements as s')->join('operators as o', 'o.id', '=', 's.operator_id')
            ->when($request->query('status'), fn ($q, $v) => $q->where('s.status', $v))
            ->orderByDesc('s.period_end')->orderBy('o.display_name')->limit(200)
            ->get(['s.public_id', 'o.display_name as provider', 's.period_start', 's.period_end', 's.gross', 's.commission', 's.adjustments', 's.net', 's.status']));
    }

    public function approveSettlement(string $settlement, SettlementService $svc)
    {
        $id = DB::table('settlements')->where('public_id', $settlement)->value('id');
        abort_unless($id, 404);
        $svc->approve((int) $id);

        return response()->json(['ok' => true]);
    }

    /** Builds last week's draft statements on demand (the Monday job does the same). */
    public function runSettlements(SettlementService $svc)
    {
        [$start, $end] = $svc->lastWeek();
        $n = 0;
        foreach (DB::table('operators')->whereIn('type', ['company', 'independent_driver', 'market_shopper'])->where('status', 'active')->pluck('id') as $op) {
            $svc->generate((int) $op, $start, $end);
            $n++;
        }

        return response()->json(['period' => [$start, $end], 'providers' => $n]);
    }

    // ------------------------------------------------------------ providers, ratings, risk

    public function providers(Request $request)
    {
        return response()->json(DB::table('operators as o')->join('provider_profiles as p', 'p.operator_id', '=', 'o.id')->leftJoin('provider_scores as sc', 'sc.operator_id', '=', 'o.id')
            ->whereIn('o.type', ['company', 'independent_driver', 'market_shopper'])
            ->when($request->query('status'), fn ($q, $v) => $q->where('o.status', $v))
            ->when($request->query('q'), fn ($q, $v) => $q->where('o.display_name', 'ilike', "%{$v}%"))
            ->orderByDesc('sc.score')->limit(100)
            ->get(['o.public_id as operator_id', 'o.display_name', 'o.type', 'o.status', 'p.tier', 'p.listed', 'p.rating_avg', 'p.rating_count', 'p.jobs_completed', 'sc.score', 'sc.dispute_rate', 'sc.completion_rate']));
    }

    public function refreshProvider(string $operator, ProviderScoreService $scores)
    {
        $id = DB::table('operators')->where('public_id', $operator)->value('id');
        abort_unless($id, 404);

        return response()->json($scores->refresh((int) $id));
    }

    /** Ratings with low scores or comments, for moderation. Hiding one removes it from the provider's page and score. */
    public function ratings(Request $request)
    {
        return response()->json(DB::table('ratings as r')->join('shipments as s', 's.id', '=', 'r.shipment_id')
            ->when($request->query('max_score'), fn ($q, $v) => $q->where('r.score', '<=', (int) $v))
            ->when($request->query('status'), fn ($q, $v) => $q->where('r.moderation_status', $v))
            ->orderByDesc('r.id')->limit(100)
            ->get(['r.id', 's.tracking_code', 'r.rater_type', 'r.ratee_type', 'r.ratee_id', 'r.score', 'r.tags', 'r.comment', 'r.moderation_status', 'r.created_at']));
    }

    public function moderateRating(Request $request, int $rating, ProviderScoreService $scores)
    {
        $d = $request->validate(['status' => 'required|in:approved,hidden']);
        $r = DB::table('ratings')->find($rating);
        abort_unless($r, 404);
        DB::table('ratings')->where('id', $rating)->update(['moderation_status' => $d['status'], 'updated_at' => now()]);
        if ($r->ratee_type === 'operator') {
            $scores->refresh((int) $r->ratee_id);
        }

        return response()->json(['ok' => true]);
    }

    public function riskEvents(Request $request)
    {
        return response()->json(DB::table('risk_events')->when($request->query('status', 'open') !== 'all', fn ($q) => $q->where('status', $request->query('status', 'open')))
            ->orderByRaw("CASE severity WHEN 'high' THEN 0 WHEN 'medium' THEN 1 ELSE 2 END")->orderByDesc('id')->limit(100)
            ->get(['id', 'subject_type', 'subject_id', 'type', 'severity', 'evidence', 'status', 'created_at']));
    }

    public function reviewRiskEvent(Request $request, int $event)
    {
        $d = $request->validate(['status' => 'required|in:reviewed,dismissed,actioned']);
        $n = DB::table('risk_events')->where('id', $event)->update(['status' => $d['status'], 'reviewed_by' => $request->user()->id, 'updated_at' => now()]);
        abort_unless($n, 404);

        return response()->json(['ok' => true]);
    }
}
