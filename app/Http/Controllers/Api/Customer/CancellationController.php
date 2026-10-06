<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Modules\Marketplace\CancellationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CancellationController extends Controller
{
    /** What it would cost to cancel right now, so the app can show the fee before the customer confirms. */
    public function preview(Request $request, string $shipment, CancellationService $svc)
    {
        $row = $this->owned($request, $shipment);
        $stage = $svc->stage($row->status);
        $a = DB::table('agreements')->find($row->agreement_id);

        return response()->json([
            'cancellable' => $stage !== 'not_cancellable', 'stage' => $stage,
            'fee' => $stage === 'not_cancellable' ? 0 : $svc->fee('customer', $stage, (int) $a->price, $a->cancellation_policy ? json_decode($a->cancellation_policy, true) : null),
        ]);
    }

    public function cancel(Request $request, string $shipment, CancellationService $svc)
    {
        $row = $this->owned($request, $shipment);
        $d = $request->validate(['reason' => 'required|string|max:40']);
        try {
            return response()->json($svc->cancelShipment((int) $row->id, 'customer', $request->user()->id, $d['reason']));
        } catch (RuntimeException $e) {
            return response()->json(['error' => 'cannot_cancel', 'message' => $e->getMessage()], 422);
        }
    }

    private function owned(Request $request, string $publicId): object
    {
        $row = DB::table('shipments as s')->join('orders as o', 'o.id', '=', 's.order_id')->where('s.public_id', $publicId)
            ->where('o.customer_id', $request->user()->id)->select('s.id', 's.status', 'o.agreement_id')->first();
        abort_unless($row, 404);

        return $row;
    }
}
