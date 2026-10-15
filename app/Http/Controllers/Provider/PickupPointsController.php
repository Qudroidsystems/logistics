<?php

namespace App\Http\Controllers\Provider;

use App\Modules\Backoffice\ManualBookingService;
use App\Modules\Marketplace\FailedDeliveryService;
use App\Modules\Notifications\NotificationService;
use App\Modules\Returns\PickupPointService;
use App\Modules\Returns\ReturnService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Pickup points (where parcels wait for the receiver) and returns. Dispatch roles run both: they set up the points,
 * hand parcels over to receivers who come to collect, close parcels nobody collected, and book returns.
 */
class PickupPointsController extends WorkspaceController
{
    public function index(Request $request, PickupPointService $points)
    {
        [$op] = $this->ctx($request, 'jobs');

        return $this->view('provider.pickup-points', $request, [
            'points' => $points->all((int) $op->id), 'waiting' => $points->waiting((int) $op->id),
            'cities' => DB::table('cities')->orderBy('name')->get(['id', 'name']), 'pagetitle' => 'Pickup points',
        ], 'jobs');
    }

    public function store(Request $request, PickupPointService $points, ?string $point = null)
    {
        [$op] = $this->ctx($request, 'jobs');
        $d = $request->validate([
            'code' => 'required|string|max:12', 'name' => 'required|string|max:120', 'line1' => 'required|string|max:255',
            'lat' => 'required|numeric|between:-90,90', 'lng' => 'required|numeric|between:-180,180', 'landmark' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:24', 'opening_hours' => 'nullable|string|max:160', 'hold_days' => 'nullable|integer|min:1|max:30',
            'city_id' => 'nullable|integer|exists:cities,id',
        ]);
        try {
            $points->save((int) $op->id, $d, $point);
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('provider.points')->with('success', $point ? 'Pickup point saved.' : 'Pickup point added. You can now send deliveries to it.');
    }

    public function status(Request $request, string $point, PickupPointService $points)
    {
        [$op] = $this->ctx($request, 'jobs');
        $active = $request->boolean('active');
        $points->setStatus((int) $op->id, $point, $active);

        return back()->with('success', $active ? 'The point is taking parcels again.' : 'Paused. New bookings cannot be sent to it; parcels already there stay.');
    }

    /** The receiver is at the counter with their code. */
    public function collect(Request $request, string $shipment, PickupPointService $points)
    {
        [$op] = $this->ctx($request, 'jobs');
        $d = $request->validate(['code' => 'required|string|max:12', 'receiver_name' => 'nullable|string|max:120']);
        try {
            $points->collect((int) $op->id, $request->user()->id, $shipment, $d['code'], $d['receiver_name'] ?? null);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Handed over. The delivery is complete.');
    }

    /** Past its hold and nobody came: close it under the agreement's failed-delivery terms. */
    public function notCollected(Request $request, string $shipment, FailedDeliveryService $failed)
    {
        [$op] = $this->ctx($request, 'jobs');
        try {
            $r = $failed->notCollected((int) $op->id, $shipment, $request->user()->id);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
        $money = $r['money'] ? ' The customer paid '.NotificationService::naira($r['charged']).' under the agreement and was refunded '.NotificationService::naira($r['refunded']).'.' : '';

        return redirect()->route('provider.job', $shipment)->with('success', 'Closed as not collected.'.$money.' Book a return below to send the parcel back.');
    }

    public function bookReturn(Request $request, string $shipment, ReturnService $returns)
    {
        [$op] = $this->ctx($request, 'jobs');
        $d = $request->validate([
            'reason' => 'required|in:'.implode(',', array_keys(ReturnService::REASONS)),
            'payment' => 'required|in:'.implode(',', array_keys(ManualBookingService::PAYMENTS)),
            'price_naira' => 'nullable|numeric|min:1|max:100000000', 'to_point' => 'nullable|string|max:40', 'note' => 'nullable|string|max:300',
        ]);
        try {
            $r = $returns->book((int) $op->id, $shipment, [
                'reason' => $d['reason'], 'payment' => $d['payment'], 'to_point' => $d['to_point'] ?? null, 'note' => $d['note'] ?? null,
                'price' => isset($d['price_naira']) ? (int) round(((float) $d['price_naira']) * 100) : null,
            ], $request->user()->id);
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return $r['shipment']
            ? redirect()->route('provider.job', $r['shipment'])->with('success', 'Return booked. '.$r['message'])
            : back()->with('success', 'Return booked. '.$r['message'])->with('payment_url', $r['payment_url']);
    }
}
