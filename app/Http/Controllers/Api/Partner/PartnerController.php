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
            // The merchant's own delivery team, when linked: deliveries sent with "provider": "own" go to it.
            'own_provider' => ($t = app(MerchantDeliveryService::class)->ownProvider($m)) ? ['id' => $t->public_id, 'name' => $t->display_name] : null,
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

    /**
     * POST /v1/partner/quotes/options — every eligible provider and speed with its own price, for the merchant's
     * customer to choose at checkout. Send the chosen option_id to POST /deliveries.
     */
    public function options(Request $request, MerchantDeliveryService $svc)
    {
        try {
            return response()->json($svc->options($request->attributes->get('api_client'), $this->validated($request)));
        } catch (RuntimeException $e) {
            return response()->json(['error' => 'unavailable', 'message' => $e->getMessage()], 422);
        }
    }

    /** GET /v1/partner/catalog — service types, live cities and vehicle types, so a merchant can fill in ids. */
    public function catalog()
    {
        return response()->json([
            'service_types' => DB::table('service_types')->where('active', true)->orderBy('id')
                ->get(['id', 'code', 'name', 'sla_minutes', 'allows_cod', 'allows_multi_stop'])
                ->map(fn ($t) => ['id' => (int) $t->id, 'code' => $t->code, 'name' => $t->name, 'sla_minutes' => $t->sla_minutes !== null ? (int) $t->sla_minutes : null,
                    'allows_cod' => (bool) $t->allows_cod, 'allows_multi_stop' => (bool) $t->allows_multi_stop]),
            'cities' => DB::table('cities')->orderBy('name')
                ->selectRaw('id, name, region, launch_status, ST_Y(centre::geometry) AS lat, ST_X(centre::geometry) AS lng')->get()
                ->map(fn ($c) => ['id' => (int) $c->id, 'name' => $c->name, 'region' => $c->region, 'launch_status' => $c->launch_status, 'lat' => (float) $c->lat, 'lng' => (float) $c->lng]),
            'vehicle_types' => DB::table('vehicle_types')->where('active', true)->orderBy('id')->get(['id', 'code', 'name', 'max_weight_g'])
                ->map(fn ($v) => ['id' => (int) $v->id, 'code' => $v->code, 'name' => $v->name, 'max_weight_g' => (int) $v->max_weight_g]),
            'fee_payers' => MerchantDeliveryService::FEE_PAYERS,
            'amount_unit' => 'kobo',
        ]);
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

    /** Up to BATCH_MAX deliveries at once; each one is checked and created on its own. */
    public function batch(Request $request, MerchantDeliveryService $svc)
    {
        $request->validate(['deliveries' => 'required|array|min:1|max:'.MerchantDeliveryService::BATCH_MAX]);
        $rules = $this->rules() + ['external_order_id' => 'required|string|max:80'];
        $items = [];
        $seen = [];
        foreach (array_values((array) $request->input('deliveries')) as $i => $raw) {
            $v = \Illuminate\Support\Facades\Validator::make(is_array($raw) ? $raw : [], $rules);
            $errors = $v->fails() ? $v->errors()->all() : [];
            $ref = is_array($raw) ? ($raw['external_order_id'] ?? null) : null;
            if (! $errors && isset($seen[$ref])) {
                $errors[] = "external_order_id {$ref} appears twice in this batch.";
            }
            $seen[$ref] = true;
            $items[$i] = ['data' => $errors ? (is_array($raw) ? $raw : []) : $v->validated(), 'errors' => $errors];
        }
        $results = $svc->createMany($request->attributes->get('api_client'), $items);
        $count = fn ($r) => count(array_filter($results, fn ($x) => $x['result'] === $r));

        return response()->json(['results' => $results, 'created' => $count('created'), 'existing' => $count('existing'),
            'failed' => $count('failed') + $count('invalid')], 200);
    }

    public function show(Request $request, string $externalOrderId, MerchantDeliveryService $svc)
    {
        $d = $svc->find($request->attributes->get('api_client'), $externalOrderId);
        abort_unless($d, 404);

        return response()->json($d);
    }

    /** GET /deliveries/{externalOrderId}/tracking: live rider, stops, ETA and drop-off code, for the merchant's own app. */
    public function tracking(Request $request, string $externalOrderId, MerchantDeliveryService $svc)
    {
        $d = $svc->tracking($request->attributes->get('api_client'), $externalOrderId);
        abort_unless($d, 404);

        return response()->json($d);
    }

    /** POST /deliveries/{externalOrderId}/reply: the receiver tells the rider they are ready, need time, etc. */
    public function reply(Request $request, string $externalOrderId, MerchantDeliveryService $svc)
    {
        $d = $request->validate(['reply' => 'required|string|in:'.implode(',', array_keys(\App\Modules\Eta\ReceiverReplies::OPTIONS)), 'note' => 'nullable|string|max:140']);
        try {
            $ok = $svc->reply($request->attributes->get('api_client'), $externalOrderId, $d['reply'], $d['note'] ?? null);
        } catch (RuntimeException $e) {
            return response()->json(['error' => 'cannot_reply', 'message' => $e->getMessage()], 422);
        }
        abort_unless($ok, 404);

        return response()->json(['message' => 'The rider has been told.']);
    }

    public function index(Request $request, MerchantDeliveryService $svc)
    {
        $d = $request->validate(['status' => 'nullable|string|max:24', 'since' => 'nullable|date', 'page' => 'nullable|integer|min:1|max:10000']);

        return response()->json($svc->list($request->attributes->get('api_client'), $d['status'] ?? null,
            isset($d['since']) ? \Carbon\CarbonImmutable::parse($d['since'])->toIso8601String() : null, (int) ($d['page'] ?? 1)));
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
        return $request->validate($this->rules());
    }

    /** POST /deliveries/{externalOrderId}/return: bring a delivered parcel back to the pickup address. */
    public function returnDelivery(Request $request, string $externalOrderId, MerchantDeliveryService $svc)
    {
        $d = $request->validate(['reason' => 'required|in:'.implode(',', array_keys(\App\Modules\Returns\ReturnService::REASONS))]);
        try {
            $r = $svc->createReturn($request->attributes->get('api_client'), $externalOrderId, $d['reason']);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return $r ? response()->json($r, 201) : response()->json(['message' => 'No delivery with that external_order_id.'], 404);
    }

    private function rules(): array
    {
        return [
            // city_id may be left out: it is found from the drop-off position. service_type_id too (same-day by default).
            'service_type_id' => 'nullable|integer', 'city_id' => 'nullable|integer', 'vehicle_type_id' => 'nullable|integer',
            // From POST /quotes/options: book with the provider the customer chose. max_price (kobo) caps a re-price after expiry.
            'option_id' => 'nullable|string|max:40', 'max_price' => 'nullable|integer|min:0',
            // merchant (default: paid through the merchant's settlement mode) or receiver (cash to the rider at the door).
            'fee_payer' => 'nullable|in:'.implode(',', MerchantDeliveryService::FEE_PAYERS),
            // "own": the merchant's own delivery team takes it; price (kobo) is what its customer paid, else the team's price list.
            'provider' => 'nullable|in:own', 'price' => 'nullable|integer|min:0|max:1000000000',
            'pickup.line1' => 'required|string|max:255', 'pickup.lat' => 'required|numeric|between:-90,90', 'pickup.lng' => 'required|numeric|between:-180,180',
            'pickup.contact_name' => 'nullable|string|max:120', 'pickup.contact_phone' => 'nullable|string|max:24',
            'dropoff.line1' => 'required|string|max:255', 'dropoff.lat' => 'required|numeric|between:-90,90', 'dropoff.lng' => 'required|numeric|between:-180,180',
            'dropoff.contact_name' => 'nullable|string|max:120', 'dropoff.contact_phone' => 'nullable|string|max:24',
            'weight_g' => 'nullable|integer|min:0', 'fragile' => 'nullable|boolean', 'declared_value' => 'nullable|integer|min:0',
            'cod_amount' => 'nullable|integer|min:0|max:10000000000',
            'packages' => 'nullable|array|max:20', 'external_order_id' => 'nullable|string|max:80', 'rush' => 'nullable|boolean',
        ];
    }
}
