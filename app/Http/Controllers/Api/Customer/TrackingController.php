<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Modules\Tracking\TrackingService;
use App\Modules\Verification\DeliveryCodes;
use App\Modules\Verification\VerificationPolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TrackingController extends Controller
{
    /** Public, token-guarded. Safe to share with the recipient. */
    public function show(string $token, TrackingService $tracking)
    {
        $snap = $tracking->snapshot($token);
        abort_unless($snap, 404);

        return response()->json($snap)->header('Cache-Control', 'no-store');
    }

    /** The receiver (or the job giver) tells the rider whether they are ready. Public, guarded by the link token. */
    public function reply(Request $request, string $token, TrackingService $tracking, \App\Modules\Eta\ReceiverReplies $replies)
    {
        $d = $request->validate(['reply' => 'required|string|in:'.implode(',', array_keys(\App\Modules\Eta\ReceiverReplies::OPTIONS)), 'note' => 'nullable|string|max:140']);
        $id = $tracking->linkShipment($token);
        abort_unless($id, 404);
        try {
            return response()->json($replies->reply($id, $d['reply'], $d['note'] ?? null));
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /** The paying customer re-reads the code to hand to the driver. Nobody else can see it. */
    public function deliveryCode(Request $request, string $shipment, TrackingService $tracking)
    {
        $row = $this->own($request, $shipment);
        // A pickup point parcel is collected with a code, but only once it is there (the receiver is texted a fresh one then).
        $point = app(\App\Modules\Returns\PickupPointService::class)->forCustomer((int) $row->id);
        if ($point && ! $point['waiting']) {
            return response()->json(['code' => null, 'expires_at' => null, 'message' => 'You get the collection code when the parcel reaches '.$point['name'].'.']);
        }
        if (! $point && ! in_array('code', app(VerificationPolicy::class)->forShipment((int) $row->id)['dropoff'], true)) {
            return response()->json(['code' => null, 'expires_at' => null, 'message' => 'This delivery is confirmed without a code.']);
        }
        $c = app(DeliveryCodes::class)->current((int) $row->id);

        return response()->json(['code' => $c['code'] ?? null, 'expires_at' => $c['expires_at'] ?? null, 'locked_until' => $c['locked_until'] ?? null])->header('Cache-Control', 'no-store');
    }

    /** A new code, for when the old one went to the wrong person. Texts the receiver again. Rate limited. */
    public function newDeliveryCode(Request $request, string $shipment)
    {
        $row = $this->own($request, $shipment);
        if ($row->status === 'at_pickup_point') {
            try {
                $c = app(\App\Modules\Returns\PickupPointService::class)->reissue((int) $row->id);
            } catch (\RuntimeException $e) {
                return response()->json(['message' => $e->getMessage()], 429);
            }

            return response()->json(['code' => $c['code'], 'expires_at' => $c['expires_at'], 'locked_until' => $c['locked_until']])->header('Cache-Control', 'no-store');
        }
        abort_unless(in_array('code', app(VerificationPolicy::class)->forShipment((int) $row->id)['dropoff'], true), 422, 'This delivery is confirmed without a code.');
        abort_if(in_array($row->status, ['delivered', 'confirmed', 'completed', 'cancelled', 'failed', 'returned'], true), 422, 'This delivery is already finished.');
        try {
            $c = app(DeliveryCodes::class)->reissue((int) $row->id);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 429);
        }
        if (DB::table('assignments')->where('shipment_id', $row->id)->whereIn('status', ['accepted', 'en_route', 'active'])->exists()) {
            app(\App\Modules\Notifications\NotificationService::class)->textRecipient((int) $row->id, (string) $row->public_id, (string) $row->order_number);
        }

        return response()->json(['code' => $c['code'], 'expires_at' => $c['expires_at'], 'locked_until' => $c['locked_until']])->header('Cache-Control', 'no-store');
    }

    private function own(Request $request, string $shipment): object
    {
        $row = DB::table('shipments')->join('orders', 'orders.id', '=', 'shipments.order_id')
            ->where('shipments.public_id', $shipment)->where('orders.customer_id', $request->user()->id)
            ->select('shipments.id', 'shipments.public_id', 'shipments.status', 'orders.order_number')->first();
        abort_unless($row, 404);

        return $row;
    }
}
