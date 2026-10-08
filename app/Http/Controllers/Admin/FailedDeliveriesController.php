<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Marketplace\FailedDeliveryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Read-only staff view of deliveries that failed because of the receiver: why, what the customer was charged and
 * refunded, and whether the parcel came back. Everything comes from the shipment timeline, so nothing here can change money.
 */
class FailedDeliveriesController extends Controller
{
    private const DAYS = [7 => 'Last 7 days', 30 => 'Last 30 days', 90 => 'Last 90 days', 365 => 'Last year'];

    public function index(Request $request)
    {
        $f = $request->validate(['days' => 'nullable|integer|in:7,30,90,365', 'reason' => 'nullable|string|max:30', 'provider' => 'nullable|string|max:80']);
        $days = (int) ($f['days'] ?? 30);

        $q = DB::table('shipment_events as e')
            ->join('shipments as s', 's.id', '=', 'e.shipment_id')
            ->join('orders as o', 'o.id', '=', 's.order_id')
            ->leftJoin('operators as p', 'p.id', '=', 's.operator_id')
            ->where('e.type', 'delivery_failed')->where('e.created_at', '>=', now()->subDays($days))
            ->when(! empty($f['reason']), fn ($w) => $w->whereRaw("e.meta->>'reason' = ?", [$f['reason']]))
            ->when(! empty($f['provider']), fn ($w) => $w->where('p.display_name', 'ilike', '%'.str_replace(['%', '_'], ['\%', '\_'], $f['provider']).'%'));

        $rows = (clone $q)->orderByDesc('e.id')->limit(200)->get([
            'e.created_at', 'e.meta', 's.status', 's.public_id as shipment', 'o.id as order_id', 'o.order_number', 'o.merchant_id',
            'p.display_name as provider',
        ])->map(function ($r) {
            $m = json_decode($r->meta, true) ?: [];

            return (object) [
                'at' => $r->created_at, 'order_id' => $r->order_id, 'order' => $r->order_number, 'provider' => $r->provider, 'merchant' => (bool) $r->merchant_id,
                'reason' => $m['reason'] ?? null, 'charged' => (int) ($m['charged'] ?? 0), 'refunded' => (int) ($m['refunded'] ?? 0),
                'returning' => (bool) ($m['returning'] ?? false), 'status' => $r->status,
            ];
        });

        $total = (clone $q)->count();
        $byReason = (clone $q)->selectRaw("e.meta->>'reason' as reason, count(*) as n")->groupBy('reason')->pluck('n', 'reason')->all();
        $sums = (clone $q)->selectRaw("coalesce(sum((e.meta->>'charged')::bigint),0) as charged, coalesce(sum((e.meta->>'refunded')::bigint),0) as refunded, count(*) filter (where (e.meta->>'returning')::boolean) as returned")->first();
        $deliveries = (int) DB::table('shipments')->where('created_at', '>=', now()->subDays($days))->count();

        return view('ops.failed-deliveries', [
            'rows' => $rows, 'total' => $total, 'byReason' => $byReason, 'reasons' => FailedDeliveryService::REASONS,
            'charged' => (int) $sums->charged, 'refunded' => (int) $sums->refunded, 'returnTrips' => (int) $sums->returned,
            'rate' => $deliveries > 0 ? round($total * 100 / $deliveries, 1) : 0, 'days' => $days, 'daysOptions' => self::DAYS, 'f' => $f,
            'pagetitle' => 'Failed deliveries',
        ]);
    }
}
