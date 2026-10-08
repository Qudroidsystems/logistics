<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Modules\Marketplace\CancellationService;
use App\Modules\Marketplace\FailedDeliveryPolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/** The customer's own orders: a list for the app's home screen and one detail call with everything the order screen needs. */
class OrdersController extends Controller
{
    private const FINISHED = ['delivered', 'completed', 'cancelled', 'confirmed'];

    public function index(Request $request)
    {
        $q = DB::table('orders as o')->join('shipments as s', 's.order_id', '=', 'o.id')->join('operators as p', 'p.id', '=', 's.operator_id')
            ->where('o.customer_id', $request->user()->id)->orderByDesc('o.id')->limit(100)
            ->select('s.public_id as shipment', 's.status', 's.tracking_code', 'o.order_number', 'o.total', 'o.created_at', 'p.display_name as provider');

        $filter = $request->query('status', 'all');
        if ($filter === 'active') {
            $q->whereNotIn('s.status', self::FINISHED);
        } elseif ($filter === 'done') {
            $q->whereIn('s.status', self::FINISHED);
        }

        return response()->json($q->get()->map(fn ($r) => (array) $r + ['active' => ! in_array($r->status, self::FINISHED, true)])->all());
    }

    public function show(Request $request, string $shipment, CancellationService $cancel)
    {
        $s = DB::table('shipments as s')->join('orders as o', 'o.id', '=', 's.order_id')->join('operators as p', 'p.id', '=', 's.operator_id')
            ->where('s.public_id', $shipment)->where('o.customer_id', $request->user()->id)
            ->first(['s.id', 's.public_id', 's.status', 's.tracking_code', 's.delivered_at', 'o.order_number', 'o.total', 'o.agreement_id', 'p.display_name as provider', 's.created_at']);
        abort_unless($s, 404);

        $stops = DB::select('SELECT seq, type, line1, landmark, status, ST_Y(point::geometry) AS lat, ST_X(point::geometry) AS lng FROM shipment_stops WHERE shipment_id = ? ORDER BY seq', [$s->id]);
        $rated = DB::table('ratings')->where(['shipment_id' => $s->id, 'rater_type' => 'customer'])->exists();
        $token = DB::table('tracking_links')->where('shipment_id', $s->id)->where('audience', 'customer')->whereNull('revoked_at')
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))->value('token');

        $agreement = DB::table('agreements')->where('id', $s->agreement_id ?? 0)->first();
        $isShopping = $agreement && DB::table('shopping_requests')->where('agreement_id', $agreement->id)->exists();
        $stage = $cancel->stage($s->status);
        $policy = $agreement && $agreement->failed_delivery_policy ? json_decode($agreement->failed_delivery_policy, true) : null;

        return response()->json([
            'shipment' => $s->public_id, 'status' => $s->status, 'tracking_code' => $s->tracking_code, 'tracking_token' => $token,
            'order_number' => $s->order_number, 'total' => (int) $s->total, 'provider' => $s->provider, 'created_at' => $s->created_at, 'delivered_at' => $s->delivered_at,
            'stops' => array_map(fn ($x) => ['seq' => $x->seq, 'type' => $x->type, 'address' => $x->line1, 'landmark' => $x->landmark, 'status' => $x->status, 'lat' => (float) $x->lat, 'lng' => (float) $x->lng], $stops),
            'timeline' => DB::table('shipment_events')->where('shipment_id', $s->id)->orderBy('seq')->get(['type', 'created_at']),
            'can_cancel' => $stage !== 'not_cancellable', 'can_confirm' => $s->status === 'delivered',
            'can_rate' => in_array($s->status, ['confirmed', 'completed'], true) && ! $rated,
            'shows_code' => ! in_array($s->status, [...self::FINISHED, 'created', 'awaiting_dispatch'], true),
            'agreement' => $agreement->public_id ?? null, 'is_shopping' => $isShopping,
            'terms' => $this->terms($policy),
        ]);
    }

    private function terms(?array $policy): mixed
    {
        try {
            return FailedDeliveryPolicy::describe($policy);
        } catch (RuntimeException) {
            return null;
        }
    }
}
