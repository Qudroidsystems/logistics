<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Booking\InstantQuoteService;
use App\Modules\Booking\PriceChanged;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Instant prices for the apps and for your own website:
 *   GET  /v1/public/quote-options      services, sizes and vehicles to offer
 *   POST /v1/public/quote              a price (no sign-in)
 *   POST /v1/customer/instant-bookings book a price (signed in); then pay with /v1/customer/agreements/{agreement}/pay
 */
class InstantQuoteApiController extends Controller
{
    public function __construct(private InstantQuoteService $instant) {}

    public function options()
    {
        return response()->json($this->instant->options());
    }

    /** Partner stores to drop a parcel at, nearest first when ?lat=&lng= are given. */
    public function centres(Request $request, \App\Modules\DropOff\DropOffService $dropoffs)
    {
        $d = $request->validate(['lat' => 'nullable|numeric|between:-90,90', 'lng' => 'nullable|numeric|between:-180,180']);

        return response()->json(['data' => $dropoffs->list(isset($d['lat'], $d['lng']) ? (float) $d['lat'] : null, isset($d['lat'], $d['lng']) ? (float) $d['lng'] : null, 30)]);
    }

    public function quote(Request $request)
    {
        $d = $request->validate([
            'dropoff_centre' => 'nullable|string|size:26',
            'pickup.lat' => 'required_without:dropoff_centre|nullable|numeric|between:-90,90', 'pickup.lng' => 'required_without:dropoff_centre|nullable|numeric|between:-180,180', 'pickup.line1' => 'nullable|string|max:200',
            'dropoff.lat' => 'required|numeric|between:-90,90', 'dropoff.lng' => 'required|numeric|between:-180,180', 'dropoff.line1' => 'nullable|string|max:200',
            'service' => 'required|string|max:24', 'size' => 'required|string|max:12', 'fragile' => 'nullable|boolean',
            'vehicle' => 'nullable|string|max:24', 'pickup_at' => 'nullable|date',
        ]);
        try {
            return response()->json($this->instant->quote($d, auth('sanctum')->id()));
        } catch (RuntimeException $e) {
            return response()->json(['error' => 'no_price', 'message' => $e->getMessage()], 422);
        }
    }

    public function book(Request $request)
    {
        $d = $request->validate([
            'quote' => 'required|string|size:26',
            'pickup_name' => 'nullable|string|max:120', 'pickup_phone' => 'nullable|string|max:24',
            'dropoff_name' => 'required|string|max:120', 'dropoff_phone' => 'required|string|max:24',
            'description' => 'required|string|max:200', 'pickup_instructions' => 'nullable|string|max:300', 'dropoff_instructions' => 'nullable|string|max:300',
            'pickup_at' => 'nullable|date',
        ]);
        try {
            return response()->json(['agreement' => $this->instant->book($request->user()->id, $d['quote'], $d)], 201);
        } catch (PriceChanged $e) {
            return response()->json(['error' => 'price_changed', 'message' => $e->getMessage(), 'quote' => $e->quote], 409);
        } catch (RuntimeException $e) {
            return response()->json(['error' => 'cannot_book', 'message' => $e->getMessage()], 422);
        }
    }
}
