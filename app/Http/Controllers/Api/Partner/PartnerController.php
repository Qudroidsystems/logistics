<?php

namespace App\Http\Controllers\Api\Partner;

use App\Http\Controllers\Controller;
use App\Modules\Partner\MerchantDeliveryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PartnerController extends Controller
{
    /** Lets the merchant's app show the marketplace name and its own registered ID. */
    public function me(Request $request)
    {
        $c = $request->attributes->get('api_client');
        $m = DB::table('merchants')->find($c->merchant_id);
        $b = DB::table('merchant_branding')->where('merchant_id', $m->id)->first();

        return response()->json([
            'merchant_code' => ($b->show_merchant_code ?? true) ? $m->merchant_code : null,
            'merchant_name' => $m->display_name, 'status' => $m->status, 'environment' => $c->environment,
            'marketplace' => ['name' => $b->marketplace_display_name ?? config('app.name'), 'badge_text' => $b->badge_text ?? null, 'badge_logo' => $b->badge_logo ?? null],
        ]);
    }

    public function quote(Request $request, MerchantDeliveryService $svc)
    {
        try {
            $q = $svc->quote($request->attributes->get('api_client'), $this->validated($request));
        } catch (RuntimeException $e) {
            return response()->json(['error' => 'unavailable', 'message' => $e->getMessage()], 422);
        }

        return response()->json(['quote_id' => $q['quote_id'], 'total' => $q['total'], 'currency' => 'NGN', 'distance_m' => $q['route']['distance_m'], 'breakdown' => $q['lines'], 'failed_delivery_terms' => $q['failed_delivery_terms']]);
    }

    public function store(Request $request, MerchantDeliveryService $svc)
    {
        $d = $this->validated($request) + $request->validate(['external_order_id' => 'required|string|max:80']);
        try {
            return response()->json($svc->create($request->attributes->get('api_client'), $d), 201);
        } catch (RuntimeException $e) {
            return response()->json(['error' => 'cannot_create', 'message' => $e->getMessage()], 422);
        }
    }

    /** Merchant cancels a delivery by its own order id. Refund rules are the customer's. */
    public function cancel(Request $request, string $externalOrderId, \App\Modules\Marketplace\CancellationService $svc)
    {
        $c = $request->attributes->get('api_client');
        $d = $request->validate(['reason' => 'nullable|string|max:40']);
        $o = DB::table('orders')->where('merchant_id', $c->merchant_id)->where('external_order_id', $externalOrderId)->first();
        $a = $o ? null : DB::table('agreements')->where('merchant_id', $c->merchant_id)->whereRaw("terms->>'external_order_id' = ?", [$externalOrderId])->first();
        abort_unless($o || $a, 404);
        try {
            if ($o) {
                $shipmentId = DB::table('shipments')->where('order_id', $o->id)->value('id');

                return response()->json($svc->cancelShipment((int) $shipmentId, 'customer', (int) $o->customer_id, $d['reason'] ?? 'merchant_cancelled'));
            }
            $svc->cancelUnpaid((int) $a->id, (int) $a->customer_id);

            return response()->json(['stage' => 'before_payment', 'fee' => 0, 'refunded' => 0]);
        } catch (RuntimeException $e) {
            return response()->json(['error' => 'cannot_cancel', 'message' => $e->getMessage()], 422);
        }
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'service_type_id' => 'required|integer', 'city_id' => 'required|integer', 'vehicle_type_id' => 'nullable|integer',
            'pickup.line1' => 'required|string|max:255', 'pickup.lat' => 'required|numeric|between:-90,90', 'pickup.lng' => 'required|numeric|between:-180,180',
            'pickup.contact_name' => 'nullable|string|max:120', 'pickup.contact_phone' => 'nullable|string|max:24',
            'dropoff.line1' => 'required|string|max:255', 'dropoff.lat' => 'required|numeric|between:-90,90', 'dropoff.lng' => 'required|numeric|between:-180,180',
            'dropoff.contact_name' => 'nullable|string|max:120', 'dropoff.contact_phone' => 'nullable|string|max:24',
            'weight_g' => 'nullable|integer|min:0', 'fragile' => 'nullable|boolean', 'declared_value' => 'nullable|integer|min:0',
            'packages' => 'nullable|array|max:20', 'external_order_id' => 'nullable|string|max:80',
        ]);
    }
}
