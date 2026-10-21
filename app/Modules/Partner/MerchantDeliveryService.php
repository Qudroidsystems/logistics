<?php

namespace App\Modules\Partner;

use App\Modules\Verification\VerificationPolicy;
use App\Modules\Dispatch\DispatchService;
use App\Modules\Marketplace\AgreementService;
use App\Modules\Marketplace\OrderFromAgreement;
use App\Modules\Payments\Escrow\EscrowService;
use App\Modules\Payments\PaymentService;
use App\Modules\Pricing\NoPricingPlan;
use App\Modules\Pricing\QuoteService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * A merchant's store asks for a delivery over the API. We pick a provider by the merchant's preferences,
 * quote at that provider's prices, lock an agreement on the merchant's behalf, then take payment.
 */
class MerchantDeliveryService
{
    public function __construct(
        private QuoteService $quotes,
        private RouteEstimator $routes,
        private AgreementService $agreements,
        private EscrowService $escrow,
        private OrderFromAgreement $orders,
        private DispatchService $dispatch,
        private PaymentService $payments,
        private \App\Modules\Billing\BusinessAccountService $accounts,
    ) {
    }

    /** Who pays the delivery fee: the merchant (its settlement mode) or the receiver, in cash to the rider at the door. */
    public const FEE_PAYERS = ['merchant', 'receiver'];

    /** Operator types that can be a merchant's own delivery team. */
    public const OWN_PROVIDER_TYPES = ['company', 'franchise', 'independent_driver'];

    /** Who prices the shop's own deliveries: the price the shop sends (its own checkout fee), or the team's price list. */
    public const OWN_PRICE_MODES = ['shop' => 'My shop sets the fee (sent with each delivery)', 'team' => "Always use my team's price list here"];

    /** How an own-team delivery is recorded on the order: the merchant and the team are one business, so no money moves. */
    public const OWN_PAYMENT_METHOD = 'own_delivery';

    /** Service types offered when the merchant does not name one: fastest first. */
    public const DEFAULT_SERVICE_CODES = ['instant', 'same_day', 'next_day'];

    /** Price a delivery with the best eligible provider without committing to anything. */
    public function quote(object $client, array $d): array
    {
        $merchant = $this->merchant($client);
        $d = $this->normalise($d);
        $route = $this->routes->estimate($d['pickup']['lat'], $d['pickup']['lng'], $d['dropoff']['lat'], $d['dropoff']['lng']);

        $best = null;
        foreach ($this->candidateOperators($merchant, (int) $d['service_type_id']) as $operatorId) {
            $q = $this->priceWith($client, $merchant, $d, $route, $operatorId);
            if ($q && (! $best || $q['total'] < $best['total'])) {
                $best = $q;
            }
        }
        if (! $best) {
            throw new RuntimeException('No provider can serve this delivery right now.');
        }

        // The terms a delivery created now would be bound by, so the merchant can show them to its customer.
        return $best + ['failed_delivery_terms' => \App\Modules\Marketplace\FailedDeliveryPolicy::current()];
    }

    /**
     * Every eligible provider (and service speed) with its own price, so the merchant's customer can choose.
     * Each option carries an option_id (the quote's public id) to send back when creating the delivery.
     */
    public function options(object $client, array $d): array
    {
        $merchant = $this->merchant($client);
        $d = $this->normalise($d, false);
        $route = $this->routes->estimate($d['pickup']['lat'], $d['pickup']['lng'], $d['dropoff']['lat'], $d['dropoff']['lng']);
        $branding = DB::table('merchant_branding')->where('merchant_id', $merchant->id)->first();
        $showProvider = (bool) ($branding->show_provider_name ?? true);

        $types = ! empty($d['service_type_id'])
            ? DB::table('service_types')->where('id', $d['service_type_id'])->where('active', true)->get()
            : DB::table('service_types')->whereIn('code', self::DEFAULT_SERVICE_CODES)->where('active', true)->get()
                ->sortBy(fn ($t) => array_search($t->code, self::DEFAULT_SERVICE_CODES, true))->values();

        // The merchant's own delivery team is always offered (priced from its own list) and flagged "own": true,
        // so the shop can show it as its own delivery service.
        $team = $this->ownProvider($merchant);
        $options = [];
        foreach ($types as $type) {
            if (! empty($d['cod_amount']) && ! $type->allows_cod) {
                continue;
            }
            $operators = $this->candidateOperators($merchant, (int) $type->id);
            if ($team && ! in_array((int) $team->id, $operators, true)) {
                $operators[] = (int) $team->id;
            }
            foreach ($operators as $operatorId) {
                $q = $this->priceWith($client, $merchant, ['service_type_id' => $type->id] + $d, $route, $operatorId);
                if (! $q) {
                    continue;
                }
                $mine = $team && (int) $team->id === $operatorId;
                $opt = $this->option($q, $type, $route, $showProvider || $mine) + ['own' => $mine];
                if ($mine) {
                    // The shop's own fee, when its team lets the shop set it; otherwise the team's price list.
                    $shopPrice = $this->shopPrice($merchant, $d['own_price'] ?? null);
                    $opt['price_source'] = $shopPrice === null ? 'team' : 'shop';
                    if ($shopPrice !== null) {
                        $opt['price'] = $shopPrice;
                    }
                }
                $options[] = $opt;
            }
        }
        if (! $options) {
            throw new RuntimeException('No provider can serve this delivery right now.');
        }
        usort($options, fn ($a, $b) => [$a['price'], $a['eta']['high_minutes']] <=> [$b['price'], $b['eta']['high_minutes']]);
        $cheapest = $options[0]['option_id'];
        $fastest = collect($options)->sortBy([['eta.high_minutes', 'asc'], ['price', 'asc']])->first()['option_id'];
        foreach ($options as &$o) {
            $o['tags'] = array_values(array_filter([$o['option_id'] === $cheapest ? 'cheapest' : null, $o['option_id'] === $fastest ? 'fastest' : null]));
        }
        unset($o);

        return [
            'marketplace' => ['name' => $branding->marketplace_display_name ?? config('app.name'), 'badge_text' => $branding->badge_text ?? null, 'badge_logo' => $this->fileUrl($branding->badge_logo ?? null)],
            'city_id' => (int) $d['city_id'],
            'distance_m' => (int) $route['distance_m'],
            'currency' => \App\Support\Money::code(),
            'amount_unit' => 'kobo',
            'expires_at' => now()->addMinutes(\App\Modules\Pricing\QuoteService::QUOTE_TTL_MINUTES)->toIso8601String(),
            'fee_payers' => self::FEE_PAYERS,
            'options' => $options,
            'failed_delivery_terms' => \App\Modules\Marketplace\FailedDeliveryPolicy::current(),
        ];
    }

    /** One provider's price, or null when it has no price list here or is above the merchant's ceiling. */
    private function priceWith(object $client, object $merchant, array $d, array $route, int $operatorId): ?array
    {
        try {
            $q = $this->quotes->quote([
                'operator_id' => $operatorId, 'service_type_id' => $d['service_type_id'], 'city_id' => $d['city_id'],
                'vehicle_type_id' => $d['vehicle_type_id'] ?? null, 'weight_g' => $d['weight_g'] ?? 0, 'fragile' => $d['fragile'] ?? false,
                'declared_value' => $d['declared_value'] ?? 0, 'is_test' => $client->environment !== 'live',
                'rush' => (bool) ($d['rush'] ?? false), 'merchant_id' => (int) $merchant->id,
                'cod_amount' => (int) ($d['cod_amount'] ?? 0),
            ] + $route);
        } catch (NoPricingPlan) {
            return null;
        }
        $pref = DB::table('merchant_provider_preferences')->where('merchant_id', $merchant->id)->first();
        if ($pref && $pref->max_price !== null && $q['total'] > $pref->max_price) {
            return null;
        }

        return $q + ['operator_id' => $operatorId, 'route' => $route];
    }

    private function option(array $q, object $type, array $route, bool $showProvider): array
    {
        $op = DB::table('operators as o')->leftJoin('provider_profiles as p', 'p.operator_id', '=', 'o.id')->where('o.id', $q['operator_id'])
            ->first(['o.public_id', 'o.display_name', 'o.branding', 'p.public_slug', 'p.logo', 'p.tier', 'p.rating_avg', 'p.rating_count', 'p.on_time_rate', 'p.jobs_completed']);
        $branding = $op && $op->branding ? (json_decode($op->branding, true) ?: []) : [];
        $drive = (int) ceil(((int) $route['duration_s']) / 60);
        $low = max(20, $drive + 20);
        $high = max($low + 20, $type->sla_minutes ? (int) $type->sla_minutes : $drive + 60);

        return [
            'option_id' => (string) DB::table('quotes')->where('id', $q['quote_id'])->value('public_id'),
            'price' => (int) $q['total'],
            'service' => ['id' => (int) $type->id, 'code' => $type->code, 'name' => $type->name, 'allows_cod' => (bool) $type->allows_cod],
            'provider' => $showProvider && $op ? [
                'id' => $op->public_id, 'name' => $op->display_name, 'slug' => $op->public_slug,
                'logo' => $this->fileUrl($op->logo ?? ($branding['logo'] ?? null)), 'tier' => $op->tier,
                'rating' => $op->rating_avg !== null ? round((float) $op->rating_avg, 1) : null, 'rating_count' => (int) ($op->rating_count ?? 0),
                'on_time_rate' => $op->on_time_rate !== null ? (float) $op->on_time_rate : null, 'jobs_completed' => (int) ($op->jobs_completed ?? 0),
            ] : ['id' => $op->public_id ?? null, 'name' => 'Delivery partner', 'slug' => null, 'logo' => null, 'tier' => null, 'rating' => null, 'rating_count' => 0, 'on_time_rate' => null, 'jobs_completed' => 0],
            'eta' => ['low_minutes' => $low, 'high_minutes' => $high, 'label' => $this->etaLabel($low, $high)],
            'breakdown' => $q['lines'] ?? [],
        ];
    }

    private function etaLabel(int $low, int $high): string
    {
        if ($high >= 1440 && $low >= 720) {
            return 'By '.now()->addMinutes($high)->setTimezone(config('app.timezone', 'Africa/Lagos'))->format('D j M');
        }
        if ($high < 120) {
            return (int) (round($low / 5) * 5).'–'.(int) (round($high / 5) * 5).' min';
        }
        $l = max(1, (int) floor($low / 60));
        $h = max($l + 1, (int) ceil($high / 60));

        return "{$l}–{$h} h";
    }

    private function fileUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        return preg_match('#^https?://#', $path) ? $path : Storage::disk('public')->url($path);
    }

    /**
     * Fill in what a merchant may leave out: the city (from the drop-off position) and the service type.
     *
     * @param  bool  $needType  quote/create need one service type; options may span several
     */
    public function normalise(array $d, bool $needType = true): array
    {
        if (empty($d['city_id'])) {
            $d['city_id'] = $this->cityAt((float) $d['dropoff']['lat'], (float) $d['dropoff']['lng'])
                ?? throw new RuntimeException('We do not deliver to that location yet.');
        }
        if ($needType && empty($d['service_type_id'])) {
            $d['service_type_id'] = (int) DB::table('service_types')->whereIn('code', self::DEFAULT_SERVICE_CODES)->where('active', true)
                ->orderByRaw("CASE code WHEN 'same_day' THEN 0 WHEN 'instant' THEN 1 ELSE 2 END")->value('id')
                ?: throw new RuntimeException('No delivery service is available.');
        }

        return $d;
    }

    /** The city whose boundary holds the point; without boundaries, the nearest city centre within 80 km. */
    public function cityAt(float $lat, float $lng): ?int
    {
        $pt = 'ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography';
        $inside = DB::selectOne("SELECT id FROM cities WHERE bounds IS NOT NULL AND ST_Covers(bounds, {$pt}) ORDER BY ST_Area(bounds) LIMIT 1", [$lng, $lat]);
        if ($inside) {
            return (int) $inside->id;
        }
        $near = DB::selectOne("SELECT id FROM cities WHERE ST_DWithin(centre, {$pt}, 80000) ORDER BY ST_Distance(centre, {$pt}) LIMIT 1", [$lng, $lat, $lng, $lat]);

        return $near ? (int) $near->id : null;
    }

    /** The merchant's own delivery team on the marketplace, when one is linked and active. */
    public function ownProvider(object $merchant): ?object
    {
        if (empty($merchant->own_provider_operator_id)) {
            return null;
        }

        return DB::table('operators')->where('id', $merchant->own_provider_operator_id)->where('status', 'active')
            ->whereIn('type', self::OWN_PROVIDER_TYPES)->first(['id', 'public_id', 'display_name']);
    }

    /**
     * The price the shop asked for its own delivery, when its team lets the shop set it (null: use the team's price list).
     * A free delivery (0) from the shop is still priced from the team's list: the customer pays nothing, the job keeps its cost.
     */
    public function shopPrice(object $merchant, $price): ?int
    {
        if ($price === null || $price === '' || ! is_numeric($price) || (int) $price <= 0) {
            return null;
        }

        return ($merchant->own_team_price_mode ?? 'shop') === 'shop' ? (int) $price : null;
    }

    /**
     * A delivery for the merchant's own team. The merchant may send the price its customer paid (kobo); without one,
     * the team's own price list is used.
     */
    private function own(object $client, object $merchant, array $d): array
    {
        $team = $this->ownProvider($merchant)
            ?? throw new RuntimeException('No own delivery team is linked to this merchant. Link your provider account on the API page first.');
        if (empty($d['service_type_id'])) {
            // The team's own kind of service, when it has a price list for one.
            $d['service_type_id'] = DB::table('service_types as t')->join('pricing_plans as p', 'p.service_type_id', '=', 't.id')
                ->where('p.operator_id', $team->id)->where('p.status', 'active')->where('t.active', true)
                ->orderByRaw("CASE t.code WHEN 'same_day' THEN 0 WHEN 'instant' THEN 1 WHEN 'next_day' THEN 2 ELSE 3 END")->value('t.id');
        }
        $d = $this->normalise($d);
        $route = $this->routes->estimate($d['pickup']['lat'], $d['pickup']['lng'], $d['dropoff']['lat'], $d['dropoff']['lng']);
        if (($p = $this->shopPrice($merchant, $d['price'] ?? null)) !== null && $p > 0) {
            return [$d, ['quote_id' => null, 'total' => $p, 'operator_id' => (int) $team->id, 'route' => $route]];
        }
        $q = $this->priceWith($client, $merchant, $d, $route, (int) $team->id)
            ?? throw new RuntimeException('Your delivery team has no price list for this delivery. Send a price, or add a price list for the team.');

        return [$d, $q];
    }

    /**
     * The quote behind an option the merchant's customer chose. A fresh one keeps its price; an expired one is priced
     * again with the same provider, and refused when the new price is above max_price (when the merchant sends one).
     */
    private function chosen(object $client, object $merchant, array $d): array
    {
        $quote = DB::table('quotes')->where('public_id', $d['option_id'])->first();
        $req = $quote ? (json_decode($quote->request, true) ?: []) : [];
        if (! $quote || (int) ($req['merchant_id'] ?? 0) !== (int) $merchant->id) {
            throw new RuntimeException('Unknown option_id. Ask for delivery options again.');
        }
        $team = $this->ownProvider($merchant);
        if (! ($team && (int) $team->id === (int) $quote->operator_id)
            && ! in_array((int) $quote->operator_id, $this->candidateOperators($merchant, (int) $quote->service_type_id), true)) {
            throw new RuntimeException('That provider is no longer available. Ask for delivery options again.');
        }
        $d['service_type_id'] = (int) $quote->service_type_id;
        $d['vehicle_type_id'] = $d['vehicle_type_id'] ?? $quote->vehicle_type_id;
        $d['city_id'] = $d['city_id'] ?? ($req['city_id'] ?? null);
        $d = $this->normalise($d);

        if ($quote->expires_at && now()->lt($quote->expires_at)) {
            $q = ['quote_id' => (int) $quote->id, 'total' => (int) $quote->total, 'operator_id' => (int) $quote->operator_id,
                'route' => ['distance_m' => (int) $quote->route_distance_m, 'duration_s' => (int) $quote->route_duration_s]];
        } else {
            $route = $this->routes->estimate($d['pickup']['lat'], $d['pickup']['lng'], $d['dropoff']['lat'], $d['dropoff']['lng']);
            $q = $this->priceWith($client, $merchant, $d, $route, (int) $quote->operator_id)
                ?? throw new RuntimeException('That provider can no longer price this delivery. Ask for delivery options again.');
        }
        if (isset($d['max_price']) && $q['total'] > (int) $d['max_price']) {
            throw new RuntimeException('The price has gone up to '.$q['total'].' kobo, above max_price. Ask for delivery options again.');
        }

        return [$d, $q];
    }

    /** Create the delivery. Safe to retry: external_order_id is the idempotency key. */
    public function create(object $client, array $d): array
    {
        $merchant = $this->merchant($client);
        $dupe = DB::table('agreements')->where('merchant_id', $merchant->id)->whereRaw("terms->>'external_order_id' = ?", [$d['external_order_id']])->first();
        if ($dupe) {
            return $this->present($dupe, null);
        }

        // "provider": "own": the merchant's own delivery team (its linked provider account) takes the job.
        $own = ($d['provider'] ?? null) === 'own';
        if (! empty($d['option_id'])) {
            // An option priced for the merchant's own team (from /quotes/options) books that team.
            $team = $this->ownProvider($merchant);
            $mineOption = $team && (int) DB::table('quotes')->where('public_id', $d['option_id'])->value('operator_id') === (int) $team->id;
            if ($mineOption && $this->shopPrice($merchant, $d['price'] ?? null) !== null) {
                unset($d['max_price']); // the shop names the price itself
            }
            [$d, $q] = $this->chosen($client, $merchant, $d);
            $own = $team && (int) $team->id === (int) $q['operator_id'];
            if ($own && ($p = $this->shopPrice($merchant, $d['price'] ?? null)) !== null) {
                $q['total'] = $p;
            }
        } elseif ($own) {
            [$d, $q] = $this->own($client, $merchant, $d);
        } else {
            $d = $this->normalise($d);
            $q = $this->quote($client, $d);
        }
        $feePayer = in_array($d['fee_payer'] ?? null, self::FEE_PAYERS, true) ? $d['fee_payer'] : 'merchant';
        $owner = DB::table('operator_members')->where('operator_id', $merchant->operator_id)->where('role', 'owner')->value('user_id');
        if (! $owner) {
            throw new RuntimeException('Merchant has no owner account.');
        }
        // A merchant on monthly invoicing with a business account at this provider books on credit.
        $account = ! $own && $feePayer === 'merchant' && $merchant->settlement_mode === 'monthly_invoice'
            ? DB::table('business_accounts')->where('operator_id', $q['operator_id'])->where('merchant_id', $merchant->id)->first()
            : null;
        if ($account) {
            $this->accounts->check($account, (int) $q['total']);
        }

        $agreementId = DB::transaction(function () use ($d, $q, $merchant, $owner, $client, $feePayer, $own) {
            $id = $this->agreements->propose($owner, $q['operator_id'], [
                'terms' => [
                    'external_order_id' => $d['external_order_id'], 'pickup' => $d['pickup'], 'dropoff' => $d['dropoff'],
                    'packages' => $d['packages'] ?? [], 'vehicle_type_id' => $d['vehicle_type_id'] ?? null, 'quote_id' => $q['quote_id'],
                    'verification' => VerificationPolicy::agree($d['verification'] ?? null, app(VerificationPolicy::class)->forProvider((int) $q['operator_id'], (int) $d['service_type_id'])),
                ] + array_filter(['own_delivery' => $own ?: null, 'return_of' => $d['return_of'] ?? null, 'return_reason' => $d['return_reason'] ?? null, 'cod_amount' => (int) ($d['cod_amount'] ?? 0) ?: null,
                    // The receiver pays the delivery fee to the rider at the door.
                    'fee_payer' => $feePayer === 'receiver' ? 'receiver' : null, 'fee_on_delivery' => $feePayer === 'receiver' ? (int) $q['total'] : null]),
                'price' => $q['total'], 'distance_m' => $q['route']['distance_m'],
                'service_type_id' => $d['service_type_id'], 'city_id' => $d['city_id'],
            ], $owner);
            DB::table('agreements')->where('id', $id)->update(['merchant_id' => $merchant->id, 'is_test' => $client->environment !== 'live']);
            // The merchant's contract with the platform pre-authorises provider terms, so both sides sign now.
            $v = (int) DB::table('agreements')->where('id', $id)->value('terms_version');
            $this->agreements->accept($id, 'customer', $v);
            $this->agreements->accept($id, 'provider', $v);

            return $id;
        });

        $agreement = DB::table('agreements')->find($agreementId);
        if ($own && $feePayer === 'merchant') {
            // The merchant's own team: nothing to pay. The team "holds" the fee itself, so when the job settles only the
            // platform's commission is owed (EscrowService::settleOffline), as with a cash booking.
            $made = DB::transaction(function () use ($agreementId, $owner) {
                $this->escrow->hold($agreementId, 'offline', $owner);
                $made = $this->orders->create($agreementId);
                DB::table('orders')->where('id', $made['order_id'])->update(['payment_method' => self::OWN_PAYMENT_METHOD, 'updated_at' => now()]);
                DB::table('shipments')->where('id', $made['shipment_id'])->update(['status' => 'awaiting_dispatch', 'updated_at' => now()]);

                return $made;
            });
            $this->dispatch->start($made['shipment_id']);

            return $this->present(DB::table('agreements')->find($agreementId), null);
        }
        if ($feePayer === 'receiver') {
            // Same path as a cash booking taken by provider staff: the provider holds the fee once the rider collects it,
            // and the platform's commission is set against that cash when the job settles (EscrowService::settleOffline).
            $made = DB::transaction(function () use ($agreementId, $owner) {
                $this->escrow->hold($agreementId, 'offline', $owner);
                $made = $this->orders->create($agreementId);
                DB::table('orders')->where('id', $made['order_id'])->update(['payment_method' => 'cash', 'updated_at' => now()]);
                DB::table('shipments')->where('id', $made['shipment_id'])->update(['status' => 'awaiting_dispatch', 'updated_at' => now()]);

                return $made;
            });
            $this->dispatch->start($made['shipment_id']);

            return $this->present(DB::table('agreements')->find($agreementId), null);
        }
        if ($account) {
            $made = DB::transaction(function () use ($agreementId, $account, $owner, $q) {
                $this->accounts->chargeable((int) $q['operator_id'], (string) $account->public_id, (int) $q['total']);
                $this->escrow->hold($agreementId, 'offline', $owner);
                $made = $this->orders->create($agreementId);
                DB::table('orders')->where('id', $made['order_id'])->update(['payment_method' => 'account', 'business_account_id' => $account->id, 'updated_at' => now()]);
                DB::table('shipments')->where('id', $made['shipment_id'])->update(['status' => 'awaiting_dispatch', 'updated_at' => now()]);

                return $made;
            });
            $this->dispatch->start($made['shipment_id']);

            return $this->present(DB::table('agreements')->find($agreementId), null);
        }
        if ($merchant->settlement_mode === 'prepaid_wallet') {
            $this->escrow->hold($agreementId, 'wallet', $owner);
            $made = $this->orders->create($agreementId);
            DB::table('shipments')->where('id', $made['shipment_id'])->update(['status' => 'awaiting_dispatch', 'updated_at' => now()]);
            $this->dispatch->start($made['shipment_id']);

            return $this->present(DB::table('agreements')->find($agreementId), null);
        }

        return $this->present($agreement, $this->payments->initiateForAgreement($agreementId, $owner)['authorization_url']);
    }

    /**
     * A return of one of the merchant's delivered parcels: picked up from the receiver and brought back to the
     * original pickup address. It is a delivery of its own with external_order_id "<original>-return", so sending
     * the call again returns the same return.
     */
    public function createReturn(object $client, string $externalOrderId, string $reason): ?array
    {
        $a = DB::table('agreements')->where('merchant_id', $client->merchant_id)->whereRaw("terms->>'external_order_id' = ?", [$externalOrderId])->first();
        $s = $a ? DB::table('shipments as s')->join('orders as o', 'o.id', '=', 's.order_id')->where('o.agreement_id', $a->id)->first(['s.*']) : null;
        if (! $s) {
            return null;
        }
        $ref = mb_substr($externalOrderId, 0, 73).'-return';
        if ($existing = $this->find($client, $ref)) {
            return $existing;
        }
        $returns = app(\App\Modules\Returns\ReturnService::class);
        if ($why = $returns->blocked($s)) {
            throw new RuntimeException($why);
        }
        $d = $returns->reversed($s);
        $this->create($client, $d + ['external_order_id' => $ref, 'return_of' => (int) $s->id, 'return_reason' => $reason]);

        return $this->find($client, $ref);
    }

    /** Most deliveries in one call. */
    public const BATCH_MAX = 50;

    /**
     * Several deliveries in one call. Each is created on its own, so one bad delivery does not stop the rest, and each
     * is idempotent on its external_order_id, so the whole batch can be sent again after a timeout.
     *
     * @param  array<int, array{data:array, errors:array}>  $items  validated items, or their validation errors
     */
    public function createMany(object $client, array $items): array
    {
        $out = [];
        foreach ($items as $i => $item) {
            $ref = $item['data']['external_order_id'] ?? null;
            if ($item['errors']) {
                $out[] = ['index' => $i, 'external_order_id' => $ref, 'result' => 'invalid', 'errors' => $item['errors']];

                continue;
            }
            $existed = DB::table('agreements')->where('merchant_id', $client->merchant_id)->whereRaw("terms->>'external_order_id' = ?", [$ref])->exists();
            try {
                $out[] = ['index' => $i, 'external_order_id' => $ref, 'result' => $existed ? 'existing' : 'created'] + $this->create($client, $item['data']);
            } catch (RuntimeException $e) {
                $out[] = ['index' => $i, 'external_order_id' => $ref, 'result' => 'failed', 'message' => $e->getMessage()];
            }
        }

        return $out;
    }

    /** Where a delivery is, by the merchant's own order id; null when it is not theirs. */
    public function find(object $client, string $externalOrderId): ?array
    {
        $a = DB::table('agreements')->where('merchant_id', $client->merchant_id)->whereRaw("terms->>'external_order_id' = ?", [$externalOrderId])->first();

        return $a ? $this->detail($a) : null;
    }

    /**
     * Live view of one delivery for the merchant to show its customer in its own app: status, stops, the rider
     * (first name, vehicle, position, ETA) and, while the parcel is on its way, the drop-off code the receiver
     * gives the rider. Null when it is not the merchant's.
     */
    public function tracking(object $client, string $externalOrderId): ?array
    {
        $a = DB::table('agreements')->where('merchant_id', $client->merchant_id)->whereRaw("terms->>'external_order_id' = ?", [$externalOrderId])->first();
        if (! $a) {
            return null;
        }
        $provider = DB::table('operators')->where('id', $a->provider_operator_id ?? 0)->first(['public_id', 'display_name']);
        $s = DB::table('shipments as s')->join('orders as o', 'o.id', '=', 's.order_id')->where('o.agreement_id', $a->id)->first(['s.id', 's.status']);
        $out = [
            'external_order_id' => $externalOrderId,
            'provider' => $provider ? ['id' => $provider->public_id, 'name' => $provider->display_name] : null,
            'delivery_status' => $s->status ?? 'awaiting_payment',
            'tracking_code' => null, 'delivered_at' => null, 'stops' => [], 'driver' => null,
            'delivery_code' => null, 'reply' => null, 'can_reply' => false, 'reply_options' => \App\Modules\Eta\ReceiverReplies::OPTIONS,
            'poll_after_seconds' => 30,
        ];
        if (! $s) {
            return $out;
        }
        $snap = app(\App\Modules\Tracking\TrackingService::class)->snapshotFor((int) $s->id) ?? [];
        $out = array_merge($out, \Illuminate\Support\Arr::only($snap, ['tracking_code', 'delivered_at', 'stops', 'driver', 'reply', 'can_reply', 'reply_options', 'poll_after_seconds']));
        $out['delivery_status'] = $s->status;
        if ($out['delivered_at']) {
            $out['delivered_at'] = \Carbon\CarbonImmutable::parse($out['delivered_at'])->toIso8601String();
        }
        // The merchant is the job giver, so it may hand the code to its own logged-in customer.
        $open = ! in_array($s->status, ['delivered', 'cancelled', 'returned', 'failed'], true);
        if ($open && in_array('code', app(\App\Modules\Verification\VerificationPolicy::class)->forShipment((int) $s->id)['dropoff'], true)) {
            $out['delivery_code'] = app(\App\Modules\Verification\DeliveryCodes::class)->current((int) $s->id)['code'] ?? null;
        }

        return $out;
    }

    /** The receiver's answer to the rider ("I'm ready", "Call me when you arrive"…), sent from the merchant's app. */
    public function reply(object $client, string $externalOrderId, string $reply, ?string $note): bool
    {
        $a = DB::table('agreements')->where('merchant_id', $client->merchant_id)->whereRaw("terms->>'external_order_id' = ?", [$externalOrderId])->first();
        $id = $a ? DB::table('shipments as s')->join('orders as o', 'o.id', '=', 's.order_id')->where('o.agreement_id', $a->id)->value('s.id') : null;
        if (! $id) {
            return false;
        }
        app(\App\Modules\Eta\ReceiverReplies::class)->reply((int) $id, $reply, $note);

        return true;
    }

    /** The merchant's deliveries, newest first, optionally only those in a state or changed since a time. */
    public function list(object $client, ?string $status, ?string $since, int $page, int $perPage = 50): array
    {
        $q = DB::table('agreements as a')->leftJoin('orders as o', 'o.agreement_id', '=', 'a.id')->leftJoin('shipments as s', 's.order_id', '=', 'o.id')
            ->where('a.merchant_id', $client->merchant_id)
            ->when($status, fn ($q) => $q->where('s.status', $status))
            ->when($since, fn ($q) => $q->where(fn ($q) => $q->where('s.updated_at', '>=', $since)->orWhere('a.updated_at', '>=', $since)));
        $total = (clone $q)->count();
        $rows = $q->orderByDesc('a.id')->offset(($page - 1) * $perPage)->limit($perPage)->get(['a.*']);

        return ['data' => $rows->map(fn ($a) => $this->detail($a))->all(), 'page' => $page, 'per_page' => $perPage, 'total' => $total,
            'has_more' => $page * $perPage < $total];
    }

    private function detail(object $a): array
    {
        $terms = json_decode($a->terms, true) ?: [];
        $s = DB::table('shipments as s')->join('orders as o', 'o.id', '=', 's.order_id')->where('o.agreement_id', $a->id)
            ->first(['s.id', 's.status', 's.tracking_code', 's.delivered_at', 's.updated_at']);
        $eta = $s ? app(\App\Modules\Eta\EtaService::class)->forShipment((int) $s->id) : null;

        return ['external_order_id' => $terms['external_order_id'] ?? null] + $this->present($a, null) + [
            'delivery_status' => $s->status ?? 'awaiting_payment', 'tracking_code' => $s->tracking_code ?? null,
            'delivered_at' => $s && $s->delivered_at ? \Carbon\CarbonImmutable::parse($s->delivered_at)->toIso8601String() : null,
            'cod' => $s ? app(\App\Modules\Cod\CodService::class)->forShipment((int) $s->id) : null,
            'eta' => $eta ? ['to' => $eta['stop_type'], 'label' => $eta['label'], 'low_minutes' => $eta['low'], 'high_minutes' => $eta['high'], 'stale' => $eta['stale']] : null,
            'updated_at' => \Carbon\CarbonImmutable::parse($s->updated_at ?? $a->updated_at)->toIso8601String(),
        ];
    }

    private function present(object $a, ?string $payUrl): array
    {
        $order = DB::table('orders')->where('agreement_id', $a->id)->first();
        $track = $order ? DB::table('tracking_links')->join('shipments', 'shipments.id', '=', 'tracking_links.shipment_id')
            ->where('shipments.order_id', $order->id)->where('audience', 'customer')->value('token') : null;

        $terms = json_decode($a->terms ?? '[]', true) ?: [];
        $provider = DB::table('operators')->where('id', $a->provider_operator_id ?? 0)->first(['public_id', 'display_name']);

        return [
            'agreement' => $a->number, 'status' => $a->status, 'price' => (int) $a->price, 'currency' => \App\Support\Money::code(),
            'provider' => $provider ? ['id' => $provider->public_id, 'name' => $provider->display_name] : null,
            'fee_payer' => $terms['fee_payer'] ?? 'merchant', 'fee_on_delivery' => (int) ($terms['fee_on_delivery'] ?? 0),
            'payment_url' => $payUrl, 'order' => $order->order_number ?? null, 'tracking_url' => $track ? url("/track/{$track}") : null,
            'failed_delivery_terms' => \App\Modules\Marketplace\FailedDeliveryPolicy::normalize($a->failed_delivery_policy ? json_decode($a->failed_delivery_policy, true) : null),
        ];
    }

    private function merchant(object $client): object
    {
        $m = $client->merchant_id ? DB::table('merchants')->find($client->merchant_id) : null;
        if (! $m || $m->status !== 'verified') {
            throw new RuntimeException('Merchant is not verified.');
        }

        return $m;
    }

    /** @return int[] */
    private function candidateOperators(object $merchant, int $serviceTypeId): array
    {
        $pref = DB::table('merchant_provider_preferences')->where('merchant_id', $merchant->id)->first();
        $exclude = $pref && $pref->exclude_operator_ids ? json_decode($pref->exclude_operator_ids, true) : [];
        $list = $pref && in_array($pref->mode, ['preferred_list', 'contracted'], true) && $pref->preferred_operator_ids ? json_decode($pref->preferred_operator_ids, true) : [];
        if ($list) {
            return array_values(array_diff($list, $exclude));
        }

        return DB::table('operators')->where('status', 'active')->whereIn('type', ['company', 'franchise', 'independent_driver'])
            ->whereExists(fn ($q) => $q->select(DB::raw(1))->from('pricing_plans')->whereColumn('pricing_plans.operator_id', 'operators.id')
                ->where('pricing_plans.service_type_id', $serviceTypeId)->where('pricing_plans.status', 'active'))
            ->whereNotIn('id', $exclude)->limit(20)->pluck('id')->map(fn ($i) => (int) $i)->all();
    }
}
