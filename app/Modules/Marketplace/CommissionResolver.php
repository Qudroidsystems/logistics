<?php

namespace App\Modules\Marketplace;

use Illuminate\Support\Facades\DB;

/**
 * Chooses the platform's fee for an agreement and freezes it.
 *
 * The most specific active rule wins: a rule tied to the operator beats one tied to the provider type,
 * which beats one tied to service type or city, which beats the global default.
 * Goods money (shopper budgets) is never commissionable; only the service price is.
 */
class CommissionResolver
{
    /** @return array{fee:int, rule_id:?int, rule:?array} */
    public function resolve(int $providerOperatorId, string $providerType, int $serviceTypeId, ?int $cityId, int $price): array
    {
        $rule = DB::table('commission_rules')
            ->where('active', true)
            ->where(fn ($q) => $q->whereNull('operator_id')->orWhere('operator_id', $providerOperatorId))
            ->where(fn ($q) => $q->whereNull('operator_type')->orWhere('operator_type', $providerType))
            ->where(fn ($q) => $q->whereNull('service_type_id')->orWhere('service_type_id', $serviceTypeId))
            ->where(fn ($q) => $q->whereNull('city_id')->orWhere('city_id', $cityId))
            ->where(fn ($q) => $q->whereNull('valid_from')->orWhere('valid_from', '<=', now()))
            ->where(fn ($q) => $q->whereNull('valid_to')->orWhere('valid_to', '>=', now()))
            ->orderByRaw('(operator_id IS NOT NULL) DESC, (operator_type IS NOT NULL) DESC, (service_type_id IS NOT NULL) DESC, (city_id IS NOT NULL) DESC, priority DESC, id DESC')
            ->first();

        if (! $rule) {
            return ['fee' => 0, 'rule_id' => null, 'rule' => null];
        }

        $arr = (array) $rule;
        $arr['tiers'] = $rule->tiers ? json_decode($rule->tiers, true) : null;

        return ['fee' => $this->fee($arr, $price), 'rule_id' => (int) $rule->id, 'rule' => $arr];
    }

    /** Pure calculation, covered by a unit test. The fee can never exceed the price. */
    public function fee(array $rule, int $price): int
    {
        $fee = match ($rule['basis']) {
            'percent_of_delivery_fee', 'percent_of_total' => intdiv($price * (int) $rule['rate_bp'], 10_000),
            'fixed_per_job' => (int) $rule['fixed_amount'],
            'tiered' => $this->tiered($rule['tiers'] ?? [], $price),
            default => 0,
        };

        $fee = max($fee, (int) ($rule['min_fee'] ?? 0));

        return min($fee, $price);
    }

    /** tiers: [{"up_to": 500000, "rate_bp": 1500}, {"up_to": null, "rate_bp": 1000}] applied to the whole price. */
    private function tiered(array $tiers, int $price): int
    {
        foreach ($tiers as $tier) {
            if ($tier['up_to'] === null || $price <= (int) $tier['up_to']) {
                return intdiv($price * (int) $tier['rate_bp'], 10_000);
            }
        }

        return 0;
    }
}
