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

    /** Price a delivery with the best eligible provider without committing to anything. */
    public function quote(object $client, array $d): array
    {
        $merchant = $this->merchant($client);
        $route = $this->routes->estimate($d['pickup']['lat'], $d['pickup']['lng'], $d['dropoff']['lat'], $d['dropoff']['lng']);

        $best = null;
        foreach ($this->candidateOperators($merchant, (int) $d['service_type_id']) as $operatorId) {
            try {
                $q = $this->quotes->quote([
                    'operator_id' => $operatorId, 'service_type_id' => $d['service_type_id'], 'city_id' => $d['city_id'],
                    'vehicle_type_id' => $d['vehicle_type_id'] ?? null, 'weight_g' => $d['weight_g'] ?? 0, 'fragile' => $d['fragile'] ?? false,
                    'declared_value' => $d['declared_value'] ?? 0, 'is_test' => $client->environment !== 'live',
                    'rush' => (bool) ($d['rush'] ?? false), 'merchant_id' => (int) $merchant->id,
                ] + $route);
            } catch (NoPricingPlan) {
                continue;
            }
            $pref = DB::table('merchant_provider_preferences')->where('merchant_id', $merchant->id)->first();
            if ($pref && $pref->max_price !== null && $q['total'] > $pref->max_price) {
                continue;
            }
            if (! $best || $q['total'] < $best['total']) {
                $best = $q + ['operator_id' => $operatorId, 'route' => $route];
            }
        }
        if (! $best) {
            throw new RuntimeException('No provider can serve this delivery right now.');
        }

        // The terms a delivery created now would be bound by, so the merchant can show them to its customer.
        return $best + ['failed_delivery_terms' => \App\Modules\Marketplace\FailedDeliveryPolicy::current()];
    }

    /** Create the delivery. Safe to retry: external_order_id is the idempotency key. */
    public function create(object $client, array $d): array
    {
        $merchant = $this->merchant($client);
        $dupe = DB::table('agreements')->where('merchant_id', $merchant->id)->whereRaw("terms->>'external_order_id' = ?", [$d['external_order_id']])->first();
        if ($dupe) {
            return $this->present($dupe, null);
        }

        $q = $this->quote($client, $d);
        $owner = DB::table('operator_members')->where('operator_id', $merchant->operator_id)->where('role', 'owner')->value('user_id');
        if (! $owner) {
            throw new RuntimeException('Merchant has no owner account.');
        }
        // A merchant on monthly invoicing with a business account at this provider books on credit.
        $account = $merchant->settlement_mode === 'monthly_invoice'
            ? DB::table('business_accounts')->where('operator_id', $q['operator_id'])->where('merchant_id', $merchant->id)->first()
            : null;
        if ($account) {
            $this->accounts->check($account, (int) $q['total']);
        }

        $agreementId = DB::transaction(function () use ($d, $q, $merchant, $owner, $client) {
            $id = $this->agreements->propose($owner, $q['operator_id'], [
                'terms' => [
                    'external_order_id' => $d['external_order_id'], 'pickup' => $d['pickup'], 'dropoff' => $d['dropoff'],
                    'packages' => $d['packages'] ?? [], 'vehicle_type_id' => $d['vehicle_type_id'] ?? null, 'quote_id' => $q['quote_id'],
                    'verification' => VerificationPolicy::agree($d['verification'] ?? null, app(VerificationPolicy::class)->forProvider((int) $q['operator_id'], (int) $d['service_type_id'])),
                ] + array_filter(['return_of' => $d['return_of'] ?? null, 'return_reason' => $d['return_reason'] ?? null]),
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
            'eta' => $eta ? ['to' => $eta['stop_type'], 'label' => $eta['label'], 'low_minutes' => $eta['low'], 'high_minutes' => $eta['high'], 'stale' => $eta['stale']] : null,
            'updated_at' => \Carbon\CarbonImmutable::parse($s->updated_at ?? $a->updated_at)->toIso8601String(),
        ];
    }

    private function present(object $a, ?string $payUrl): array
    {
        $order = DB::table('orders')->where('agreement_id', $a->id)->first();
        $track = $order ? DB::table('tracking_links')->join('shipments', 'shipments.id', '=', 'tracking_links.shipment_id')
            ->where('shipments.order_id', $order->id)->where('audience', 'customer')->value('token') : null;

        return [
            'agreement' => $a->number, 'status' => $a->status, 'price' => (int) $a->price, 'currency' => 'NGN',
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
