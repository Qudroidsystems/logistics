<?php

namespace App\Modules\Booking;

use App\Modules\DropOff\DropOffService;
use App\Modules\Marketplace\AgreementService;
use App\Modules\Partner\RouteEstimator;
use App\Modules\Pricing\NoPricingPlan;
use App\Modules\Pricing\QuoteService;
use App\Modules\Routing\RoutingEngine;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * A price on the spot, without signing in, and a booking at that price (config/booking.php).
 *
 *   quote()  pickup and drop-off, service (same-day, next-day, scheduled), item size, fragile and vehicle -> the price
 *            from the instant provider's price list, with each line explained. Saved as a quote for 10 minutes.
 *   book()   the signed-in customer adds contacts and what is being sent; an agreement at the quoted price is made and
 *            signed on both sides, and the customer goes straight to payment (card, e-Transfer or wallet).
 *
 * Everything after payment (order, dispatch, tracking) is the same as any other booking.
 */
class InstantQuoteService
{
    public const LINE_LABELS = [
        'base' => 'Base fare', 'per_km' => 'Distance', 'per_minute' => 'Time', 'per_kg' => 'Weight', 'per_stop' => 'Extra stops',
        'waiting' => 'Waiting', 'fragile' => 'Fragile handling', 'insurance' => 'Insurance', 'cod_fee' => 'Cash collection',
        'remote_area' => 'Outside the main area', 'night' => 'Night', 'peak_hour' => 'Busy hours', 'weekend' => 'Weekend',
        'rush' => 'Rush', 'fuel_surcharge' => 'Fuel surcharge', 'item_size' => 'Item size', 'vehicle' => 'Vehicle', 'store_dropoff' => 'Store drop-off saving',
        'surge' => 'High demand', 'minimum_fee' => 'Minimum charge', 'maximum_fee' => 'Price cap', 'rounding' => 'Rounding',
    ];

    public function __construct(
        private QuoteService $quotes,
        private RouteEstimator $routes,
        private AgreementService $agreements,
        private DropOffService $dropoffs,
    ) {}

    // ---------------------------------------------------------------- options

    /** @return array<string, array{label:string, hint:string, weight_g:int}> */
    public static function sizes(): array
    {
        return (array) config('booking.sizes', []);
    }

    /** What the form offers, for the page, the widget and the apps. */
    public function options(): array
    {
        $services = DB::table('service_types')->where('active', true)->whereIn('code', array_keys((array) config('booking.services')))->pluck('id', 'code');
        $vehicles = DB::table('vehicle_types')->where('active', true)->whereIn('code', (array) config('booking.vehicles'))->get(['code', 'name', 'max_weight_g'])->keyBy('code');

        return [
            'enabled' => self::operatorId() !== null,
            'services' => collect((array) config('booking.services'))->filter(fn ($v, $k) => isset($services[$k]))
                ->map(fn ($v, $k) => ['code' => $k] + $v)->values()->all(),
            'sizes' => collect(self::sizes())->map(fn ($v, $k) => ['code' => $k, 'label' => $v['label'], 'hint' => $v['hint']])->values()->all(),
            'vehicles' => collect((array) config('booking.vehicles'))->filter(fn ($c) => isset($vehicles[$c]))
                ->map(fn ($c) => ['code' => $c, 'name' => $vehicles[$c]->name, 'max_kg' => (int) round($vehicles[$c]->max_weight_g / 1000)])->values()->all(),
            'currency' => Money::forClient(),
            // Partner stores a sender can hand the parcel in at instead of waiting for a pickup.
            'centres' => $this->dropoffs->list(),
        ];
    }

    public static function operatorId(): ?int
    {
        $id = (int) config('booking.operator_id');
        if ($id < 1) {
            return null;
        }

        return DB::table('operators')->where('id', $id)->where('status', 'active')->exists() ? $id : null;
    }

    // ---------------------------------------------------------------- quote

    /**
     * @param  array{pickup:array{lat:float,lng:float,line1?:string}, dropoff:array{lat:float,lng:float,line1?:string}, service:string,
     *               size:string, fragile?:bool, vehicle?:?string, pickup_at?:?string}  $d
     */
    public function quote(array $d, ?int $customerId = null): array
    {
        $operatorId = self::operatorId();
        if (! $operatorId) {
            throw new RuntimeException('Instant prices are not set up yet. Ask for a quote instead.');
        }
        $sizes = self::sizes();
        if (! isset($sizes[$d['size'] ?? ''])) {
            throw new RuntimeException('Choose the item size.');
        }
        if (! isset(((array) config('booking.services'))[$d['service'] ?? ''])) {
            throw new RuntimeException('Choose a service.');
        }
        $serviceTypeId = DB::table('service_types')->where('code', $d['service'])->where('active', true)->value('id');
        if (! $serviceTypeId) {
            throw new RuntimeException('That service is not offered.');
        }
        $vehicle = null;
        if (! empty($d['vehicle'])) {
            if (! in_array($d['vehicle'], (array) config('booking.vehicles'), true)) {
                throw new RuntimeException('Choose one of the vehicles offered.');
            }
            $vehicle = DB::table('vehicle_types')->where('code', $d['vehicle'])->where('active', true)->first(['id', 'code', 'max_weight_g']);
        }
        $weight = (int) $sizes[$d['size']]['weight_g'];
        if ($vehicle && $vehicle->max_weight_g && $weight > (int) $vehicle->max_weight_g) {
            throw new RuntimeException('That item is too big for the vehicle you chose. Pick a bigger vehicle.');
        }
        // "I'll drop it at a store": the trip starts at the partner store.
        $centre = ! empty($d['dropoff_centre']) ? $this->dropoffs->active((string) $d['dropoff_centre']) : null;
        if ($centre) {
            $d['pickup'] = ['lat' => (float) $centre->lat, 'lng' => (float) $centre->lng, 'line1' => $centre->name.', '.$centre->line1];
        }
        if (empty($d['pickup']['lat']) || empty($d['pickup']['lng'])) {
            throw new RuntimeException('Choose the pickup address or a store to drop it at.');
        }
        [$lat1, $lng1, $lat2, $lng2] = [(float) $d['pickup']['lat'], (float) $d['pickup']['lng'], (float) $d['dropoff']['lat'], (float) $d['dropoff']['lng']];
        $city = $this->cityFor($lat1, $lng1);
        $pickupAt = self::pickupAt((string) $d['service'], $d['pickup_at'] ?? null, CarbonImmutable::now(config('app.timezone')));
        $route = $this->routes->estimate($lat1, $lng1, $lat2, $lng2, RoutingEngine::profileFor($vehicle->code ?? null));
        if ($route['distance_m'] < 100) {
            throw new RuntimeException('The pickup and drop-off are the same place.');
        }

        $request = [
            'operator_id' => $operatorId, 'service_type_id' => (int) $serviceTypeId, 'city_id' => (int) $city->id, 'customer_id' => $customerId,
            'vehicle_type_id' => $vehicle ? (int) $vehicle->id : null, 'weight_g' => $weight, 'size' => $d['size'], 'fragile' => (bool) ($d['fragile'] ?? false),
            'store_dropoff' => $centre !== null,
            'stops' => [['type' => 'pickup'], ['type' => 'dropoff']], 'scheduled_for' => $pickupAt?->toIso8601String(),
            // Kept with the quote so the booking is made from exactly what was priced.
            'instant' => [
                'service' => $d['service'], 'size' => $d['size'], 'vehicle' => $vehicle->code ?? null, 'pickup_at' => $pickupAt?->toIso8601String(),
                'dropoff_centre' => $centre?->public_id,
                'pickup' => ['lat' => $lat1, 'lng' => $lng1, 'line1' => mb_substr(trim((string) ($d['pickup']['line1'] ?? '')), 0, 200)],
                'dropoff' => ['lat' => $lat2, 'lng' => $lng2, 'line1' => mb_substr(trim((string) ($d['dropoff']['line1'] ?? '')), 0, 200)],
            ],
        ] + $route;
        try {
            $q = $this->quotes->quote($request);
        } catch (NoPricingPlan) {
            throw new RuntimeException('We do not have a price for this service in '.$city->name.' yet. Ask for a quote instead.');
        }

        return $this->present((int) $q['quote_id']);
    }

    /** A saved quote as the form shows it. */
    public function present(int $quoteId): array
    {
        $q = DB::table('quotes')->where('id', $quoteId)->first();
        $r = json_decode($q->request, true);
        $b = json_decode($q->breakdown, true);
        $lines = [];
        foreach ((array) ($b['lines'] ?? []) as $l) {
            $label = self::LINE_LABELS[$l['label']] ?? ucfirst(str_replace('_', ' ', (string) $l['label']));
            $lines[$label] = ($lines[$label] ?? 0) + (int) $l['amount'];
        }
        $tax = \App\Support\Region::taxFor(DB::table('cities')->where('id', $r['city_id'])->value('region'));

        return [
            'quote' => $q->public_id, 'expires_at' => \Illuminate\Support\Carbon::parse($q->expires_at)->toIso8601String(),
            'total' => (int) $q->total, 'subtotal' => (int) $q->subtotal, 'tax' => (int) $q->tax, 'discount' => (int) $q->discount,
            'total_text' => Money::format((int) $q->total, true), 'tax_name' => $tax['name'],
            'lines' => collect($lines)->map(fn ($amount, $label) => ['label' => $label, 'amount' => $amount, 'text' => Money::format($amount, true)])->values()->all(),
            'distance_km' => round(((int) $q->route_distance_m) / 1000, 1), 'duration_min' => (int) ceil(((int) $q->route_duration_s) / 60),
            'service' => $r['instant']['service'] ?? null, 'size' => $r['instant']['size'] ?? null, 'vehicle' => $r['instant']['vehicle'] ?? null,
            'fragile' => (bool) ($r['fragile'] ?? false), 'pickup_at' => $r['instant']['pickup_at'] ?? null,
            'pickup' => $r['instant']['pickup'] ?? null, 'dropoff' => $r['instant']['dropoff'] ?? null,
            'centre' => ! empty($r['instant']['dropoff_centre'])
                ? (array) DB::table('dropoff_centres')->where('public_id', $r['instant']['dropoff_centre'])->first(['public_id as id', 'name', 'line1 as address', 'opening_hours as hours'])
                : null,
            'city' => DB::table('cities')->where('id', $r['city_id'])->value('name'),
            'booked' => $q->accepted_order_id !== null,
        ];
    }

    // ---------------------------------------------------------------- book

    /**
     * Books a quote for a signed-in customer and returns the agreement's public id (the pay page takes it from there).
     * An expired quote is priced again; if the price changed, PriceChanged carries the new quote for the customer to see.
     *
     * @param  array{pickup_name:string, pickup_phone:string, dropoff_name:string, dropoff_phone:string, description:string,
     *               pickup_instructions?:?string, dropoff_instructions?:?string, pickup_at?:?string}  $d
     */
    public function book(int $customerId, string $quotePublicId, array $d): string
    {
        $q = DB::table('quotes')->where('public_id', $quotePublicId)->first();
        if (! $q || ! ($r = json_decode($q->request, true)) || empty($r['instant'])) {
            throw new RuntimeException('That price is not available any more. Get a new one.');
        }
        if ($q->customer_id && (int) $q->customer_id !== $customerId) {
            throw new RuntimeException('That price belongs to someone else. Get a new one.');
        }
        if ($q->accepted_order_id) {
            throw new RuntimeException('That price has already been booked.');
        }
        // Booking the same price twice (a double tap, the back button) returns the booking already made.
        $made = DB::table('agreements')->where('customer_id', $customerId)->whereIn('status', ['draft', 'locked'])
            ->whereRaw("(terms->>'quote_id') = ?", [(string) $q->id])->value('public_id');
        if ($made) {
            return (string) $made;
        }
        $i = $r['instant'];
        // A scheduled time the customer changes on the details page, or an expired quote: price it again.
        $newTime = ($i['service'] === 'scheduled' && ! empty($d['pickup_at'])) ? $d['pickup_at'] : null;
        if (CarbonImmutable::parse($q->expires_at)->isPast() || ($newTime && ! self::sameTime($newTime, $i['pickup_at'] ?? null))) {
            $fresh = $this->quote(['pickup' => $i['pickup'], 'dropoff' => $i['dropoff'], 'service' => $i['service'], 'size' => $i['size'],
                'fragile' => (bool) ($r['fragile'] ?? false), 'vehicle' => $i['vehicle'], 'pickup_at' => $newTime ?? ($i['pickup_at'] ?? null),
                'dropoff_centre' => $i['dropoff_centre'] ?? null], $customerId);
            if ($fresh['total'] !== (int) $q->total) {
                throw new PriceChanged($fresh);
            }

            return $this->book($customerId, $fresh['quote'], $d);
        }

        $phone = fn (string $p) => \App\Support\Phone::store($p) ?? throw new RuntimeException('Check the phone number '.$p.'. For example: '.config('region.phone_example').'.');
        $stop = fn (array $point, string $name, string $tel, ?string $notes) => array_filter([
            'line1' => $point['line1'] ?: 'Pinned location', 'lat' => (float) $point['lat'], 'lng' => (float) $point['lng'],
            'contact_name' => mb_substr(trim($name), 0, 120), 'contact_phone' => $phone($tel), 'instructions' => $notes ? mb_substr(trim($notes), 0, 300) : null,
        ], fn ($v) => $v !== null && $v !== '');

        // From a partner store, the driver deals with the store; the sender's name goes in the note for the counter.
        $centre = ! empty($i['dropoff_centre']) ? DB::table('dropoff_centres')->where('public_id', $i['dropoff_centre'])->first(['id', 'name', 'phone', 'status']) : null;
        if (! empty($i['dropoff_centre']) && (! $centre || $centre->status !== 'active')) {
            throw new RuntimeException('That store is not taking parcels right now. Get a new price with another store.');
        }
        if (! $centre && (trim((string) ($d['pickup_name'] ?? '')) === '' || trim((string) ($d['pickup_phone'] ?? '')) === '')) {
            throw new RuntimeException('Add the pickup contact name and phone.');
        }
        $pickupStop = $centre
            ? array_filter([
                'line1' => $i['pickup']['line1'], 'lat' => (float) $i['pickup']['lat'], 'lng' => (float) $i['pickup']['lng'],
                'contact_name' => $centre->name, 'contact_phone' => $centre->phone,
                'instructions' => 'Collect from the counter. Sender: '.mb_substr(trim((string) (($d['pickup_name'] ?? '') ?: 'the customer')), 0, 120).'.',
            ])
            : $stop($i['pickup'], $d['pickup_name'], $d['pickup_phone'], $d['pickup_instructions'] ?? null);

        $agreementId = DB::transaction(function () use ($customerId, $q, $r, $i, $d, $stop, $pickupStop, $centre) {
            $id = $this->agreements->propose($customerId, (int) $q->operator_id, [
                'terms' => array_filter([
                    'pickup' => $pickupStop,
                    'dropoff_centre_id' => $centre?->id,
                    'dropoff' => $stop($i['dropoff'], $d['dropoff_name'], $d['dropoff_phone'], $d['dropoff_instructions'] ?? null),
                    'packages' => [array_filter(['description' => mb_substr(trim($d['description']), 0, 200), 'weight_g' => (int) $r['weight_g'], 'fragile' => (bool) ($r['fragile'] ?? false), 'size' => $i['size']], fn ($v) => $v !== null)],
                    'vehicle_type_id' => $r['vehicle_type_id'] ?? null, 'quote_id' => (int) $q->id, 'channel' => 'instant',
                    'pickup_at' => $i['pickup_at'] ?? null, 'size' => $i['size'], 'instant_service' => $i['service'],
                ], fn ($v) => $v !== null),
                'price' => (int) $q->total, 'distance_m' => (int) $q->route_distance_m,
                'service_type_id' => (int) $q->service_type_id, 'city_id' => (int) $r['city_id'],
            ], $customerId);
            $v = (int) DB::table('agreements')->where('id', $id)->value('terms_version');
            // The provider accepted these terms in advance by publishing the price list.
            $this->agreements->accept($id, 'customer', $v);
            $this->agreements->accept($id, 'provider', $v);
            DB::table('quotes')->where('id', $q->id)->update(['customer_id' => $customerId, 'updated_at' => now()]);

            return $id;
        });

        return (string) DB::table('agreements')->where('id', $agreementId)->value('public_id');
    }

    // ---------------------------------------------------------------- rules

    /** Pure: when the pickup is, by service. Same-day = now (null); next-day = tomorrow at the set hour; scheduled = the chosen time. */
    public static function pickupAt(string $service, ?string $chosen, CarbonImmutable $now): ?CarbonImmutable
    {
        return match ($service) {
            'next_day' => $now->addDay()->setTime((int) config('booking.next_day_hour', 9), 0),
            'scheduled' => (function () use ($chosen, $now) {
                if (! $chosen) {
                    throw new RuntimeException('Choose the pickup date and time.');
                }
                $t = CarbonImmutable::parse($chosen, $now->getTimezone());
                if ($t->lt($now->addMinutes(60))) {
                    throw new RuntimeException('A scheduled pickup must be at least an hour from now.');
                }
                if ($t->gt($now->addDays(30))) {
                    throw new RuntimeException('You can book up to 30 days ahead.');
                }

                return $t;
            })(),
            default => null,
        };
    }

    private static function sameTime(?string $a, ?string $b): bool
    {
        return $a && $b && CarbonImmutable::parse($a, config('app.timezone'))->equalTo(CarbonImmutable::parse($b));
    }

    /** The live city nearest the pickup, within the service radius. */
    private function cityFor(float $lat, float $lng): object
    {
        $c = DB::selectOne(
            "SELECT id, name, ST_Distance(centre, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography) AS d FROM cities
             WHERE launch_status IN ('live', 'beta') ORDER BY d LIMIT 1",
            [$lng, $lat]
        );
        if (! $c || $c->d > ((int) config('booking.max_city_km', 80)) * 1000) {
            throw new RuntimeException('We do not deliver from there yet.');
        }

        return $c;
    }
}
