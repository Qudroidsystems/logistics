<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Modules\Tracking\TrackingService;
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

    /** The paying customer re-reads the code to hand to the driver. Nobody else can see it. */
    public function deliveryCode(Request $request, string $shipment, TrackingService $tracking)
    {
        $row = DB::table('shipments')->join('orders', 'orders.id', '=', 'shipments.order_id')
            ->where('shipments.public_id', $shipment)->where('orders.customer_id', $request->user()->id)
            ->select('shipments.public_id', 'shipments.status')->first();
        abort_unless($row, 404);

        return response()->json(['code' => $tracking->deliveryCode($row->public_id)]);
    }
}
