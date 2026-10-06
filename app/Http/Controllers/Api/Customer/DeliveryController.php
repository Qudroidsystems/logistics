<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Modules\Marketplace\DeliveryService;
use App\Modules\Ratings\RatingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use RuntimeException;

/** The customer's answer to "was it delivered properly?": confirm and pay, or report a problem and hold the money. */
class DeliveryController extends Controller
{
    public function confirm(Request $request, string $shipment, DeliveryService $delivery, RatingService $ratings)
    {
        $id = $this->owned($request, $shipment);
        $d = $request->validate(['rating' => 'nullable|integer|min:1|max:5', 'tags' => 'array|max:5', 'tags.*' => 'string|max:24', 'comment' => 'nullable|string|max:500']);

        try {
            $result = $delivery->confirm($id, $request->user()->id, $d['rating'] ?? null);
        } catch (RuntimeException $e) {
            return response()->json(['error' => 'cannot_confirm', 'message' => $e->getMessage()], 422);
        }

        // The payment is already released; a rating problem must never undo or hide that.
        $rated = false;
        if (! empty($d['rating'])) {
            try {
                $ratings->rate($id, $request->user()->id, 'customer', (int) $d['rating'], $d['tags'] ?? [], $d['comment'] ?? null);
                $rated = true;
            } catch (RuntimeException) {
            }
        }

        return response()->json(['confirmed' => true, 'rated' => $rated] + (is_array($result) ? ['payment' => array_intersect_key($result, array_flip(['provider_net', 'fee', 'refunded']))] : []));
    }

    public function object(Request $request, string $shipment, DeliveryService $delivery)
    {
        $id = $this->owned($request, $shipment);
        $d = $request->validate([
            'type' => ['required', Rule::in(['price', 'quality', 'damage', 'lost', 'late', 'driver_conduct', 'fraud'])],
            'reason' => 'required|string|min:10|max:1000',
            'evidence' => 'array|max:10', 'evidence.*' => 'string|max:300',
        ]);

        try {
            $disputeId = $delivery->object($id, $request->user()->id, $d['type'], $d['reason'], ['photos' => $d['evidence'] ?? []]);
        } catch (RuntimeException $e) {
            return response()->json(['error' => 'cannot_object', 'message' => $e->getMessage()], 422);
        }

        return response()->json(['dispute' => DB::table('disputes')->where('id', $disputeId)->first(['public_id', 'type', 'status'])], 201);
    }

    /** Rating after the fact, for customers who confirmed without one (or whose delivery auto-released). */
    public function rate(Request $request, string $shipment, RatingService $ratings)
    {
        $id = $this->owned($request, $shipment);
        $d = $request->validate(['rating' => 'required|integer|min:1|max:5', 'tags' => 'array|max:5', 'tags.*' => 'string|max:24', 'comment' => 'nullable|string|max:500']);

        try {
            $ratings->rate($id, $request->user()->id, 'customer', (int) $d['rating'], $d['tags'] ?? [], $d['comment'] ?? null);
        } catch (RuntimeException $e) {
            return response()->json(['error' => 'cannot_rate', 'message' => $e->getMessage()], 422);
        }

        return response()->json(['rated' => true], 201);
    }

    private function owned(Request $request, string $publicId): int
    {
        $id = DB::table('shipments as s')->join('orders as o', 'o.id', '=', 's.order_id')->where('s.public_id', $publicId)->where('o.customer_id', $request->user()->id)->value('s.id');
        abort_unless($id, 404);

        return (int) $id;
    }
}
