<?php

namespace App\Modules\Partner;

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

        $agreementId = DB::transaction(function () use ($d, $q, $merchant, $owner, $client) {
            $id = $this->agreements->propose($owner, $q['operator_id'], [
                'terms' => [
                    'external_order_id' => $d['external_order_id'], 'pickup' => $d['pickup'], 'dropoff' => $d['dropoff'],
                    'packages' => $d['packages'] ?? [], 'vehicle_type_id' => $d['vehicle_type_id'] ?? null, 'quote_id' => $q['quote_id'],
                ],
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
        if ($merchant->settlement_mode === 'prepaid_wallet') {
            $this->escrow->hold($agreementId, 'wallet', $owner);
            $made = $this->orders->create($agreementId);
            DB::table('shipments')->where('id', $made['shipment_id'])->update(['status' => 'awaiting_dispatch', 'updated_at' => now()]);
            $this->dispatch->start($made['shipment_id']);

            return $this->present(DB::table('agreements')->find($agreementId), null);
        }

        return $this->present($agreement, $this->payments->initiateForAgreement($agreementId, $owner)['authorization_url']);
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
