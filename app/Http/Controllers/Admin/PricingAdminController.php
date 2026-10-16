<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Platform;
use App\Modules\Marketing\ReferralService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Staff pages for prices customers see: promo codes, corporate discounts, and price lists (pricing plans and their
 * rules, including weekend, rush and fuel surcharges). Price lists drive instant quotes for business customers and
 * the partner API; negotiated jobs are priced by the provider's offer, and promos and corporate discounts apply to both.
 */
class PricingAdminController extends Controller
{
    public const PROMO_TYPES = ['percent' => 'Percent off', 'fixed' => 'Fixed amount off', 'free_delivery' => 'Free delivery', 'first_order' => 'Percent off the first delivery'];

    /** Rule kinds staff can add, with what amount and rate mean for each. */
    public const RULE_KINDS = [
        'base' => 'Base fee (amount)', 'per_km' => 'Per km (amount; optional from/to km band)', 'per_minute' => 'Per minute of driving (amount)',
        'per_kg' => 'Per kg (amount)', 'per_stop' => 'Per extra stop (amount)', 'waiting' => 'Per waiting minute (amount)',
        'fragile' => 'Fragile item (amount)', 'insurance' => 'Insurance (rate % of declared value)', 'cod_fee' => 'Cash on delivery (amount and/or rate %)',
        'night' => 'Night surcharge (rate %, hours)', 'peak_hour' => 'Peak-hour surcharge (rate %, hours)',
        'weekend' => 'Weekend surcharge (rate % and/or amount; Sat and Sun)', 'rush' => 'Rush delivery surcharge (rate % and/or amount)',
        'fuel_surcharge' => 'Fuel surcharge (rate % and/or amount)',
    ];

    // ---------------------------------------------------------------- promo codes and corporate discounts

    public function promos()
    {
        return view('ops.promos', [
            'promos' => DB::table('promo_codes as p')->leftJoin('operators as o', 'o.id', '=', 'p.operator_id')
                ->selectRaw('p.*, o.display_name as provider, (select count(*) from promo_redemptions r where r.promo_code_id = p.id) as used')
                ->orderByDesc('p.id')->limit(200)->get(),
            'corporate' => DB::table('corporate_discounts as c')->leftJoin('merchants as m', 'm.id', '=', 'c.merchant_id')->leftJoin('users as u', 'u.id', '=', 'c.user_id')
                ->leftJoin('operators as o', 'o.id', '=', 'c.operator_id')
                ->orderByDesc('c.id')->get(['c.*', 'm.display_name as merchant', 'u.email as user_email', 'o.display_name as provider']),
            'merchants' => DB::table('merchants')->orderBy('display_name')->get(['id', 'display_name as name']),
            'operators' => $this->operators(), 'types' => self::PROMO_TYPES, 'pagetitle' => 'Promo codes and discounts',
            'referral' => ReferralService::settings(),
            'referrals' => DB::table('referrals as r')->join('users as a', 'a.id', '=', 'r.referrer_id')->join('users as f', 'f.id', '=', 'r.friend_id')
                ->orderByDesc('r.id')->limit(50)->get(['r.status', 'r.reward', 'r.note', 'r.created_at', 'a.email as referrer', 'f.email as friend']),
            'referralTotals' => DB::table('referrals')->selectRaw("count(*) as invited, count(*) filter (where status = 'rewarded') as rewarded, coalesce(sum(reward), 0) as paid")->first(),
            'loyalty' => \App\Modules\Marketing\LoyaltyService::settings(),
            'loyaltyTotals' => DB::table('loyalty_points')->selectRaw("coalesce(sum(points) filter (where kind = 'earn'), 0) as earned, coalesce(-sum(points) filter (where kind = 'redeem'), 0) as spent, coalesce(sum(points), 0) as outstanding")->first(),
            'giftCards' => DB::table('gift_cards as g')->leftJoin('users as b', 'b.id', '=', 'g.bought_by')->orderByDesc('g.id')->limit(50)
                ->get(['g.id', 'g.last4', 'g.initial_value', 'g.balance', 'g.source', 'g.status', 'g.recipient_name', 'g.expires_at', 'g.created_at', 'b.email as buyer']),
            'giftTotals' => DB::table('gift_cards')->where('status', 'active')->selectRaw('count(*) as cards, coalesce(sum(balance), 0) as unspent')->first(),
        ]);
    }

    public function saveReferrals(Request $request)
    {
        $d = $request->validate([
            'enabled' => 'required|in:0,1', 'reward_naira' => 'required|numeric|min:0|max:100000', 'friend_naira' => 'required|numeric|min:0|max:100000',
            'min_price_naira' => 'required|numeric|min:0|max:1000000', 'monthly_cap' => 'required|integer|min:0|max:1000',
        ]);
        ReferralService::save([
            'enabled' => $d['enabled'] === '1', 'reward' => self::kobo($d['reward_naira']), 'friend_discount' => self::kobo($d['friend_naira']),
            'min_price' => self::kobo($d['min_price_naira']), 'monthly_cap' => (int) $d['monthly_cap'],
        ], $request->user()->id);

        return back()->with('success', $d['enabled'] === '1' ? 'Invites are on with the new amounts.' : 'Invites are off. Codes already shared stop giving anything.');
    }

    public function saveLoyalty(Request $request)
    {
        $d = $request->validate([
            'enabled' => 'required|in:0,1', 'earn_per_100' => 'required|integer|min:0|max:100', 'point_value_naira' => 'required|numeric|min:0.01|max:1000',
            'min_redeem' => 'required|integer|min:0|max:1000000', 'max_share_percent' => 'required|integer|min:1|max:100',
        ]);
        \App\Modules\Marketing\LoyaltyService::save([
            'enabled' => $d['enabled'] === '1', 'earn_per_100' => (int) $d['earn_per_100'], 'point_value' => self::kobo($d['point_value_naira']),
            'min_redeem' => (int) $d['min_redeem'], 'max_share_bp' => (int) $d['max_share_percent'] * 100,
        ], $request->user()->id);

        return back()->with('success', $d['enabled'] === '1' ? 'Loyalty points are on with the new rates.' : 'Loyalty points are off. Customers keep their points but cannot earn or spend them.');
    }

    /** A card given away by staff. The code is shown once, here. */
    public function issueGiftCard(Request $request, \App\Modules\Marketing\GiftCardService $cards)
    {
        $d = $request->validate([
            'amount_naira' => 'required|numeric|min:1', 'recipient_name' => 'nullable|string|max:120', 'recipient_phone' => 'nullable|string|max:24',
            'recipient_email' => 'nullable|email:rfc|max:190', 'message' => 'nullable|string|max:300', 'expires_at' => 'nullable|date|after:today',
        ]);
        try {
            $card = $cards->issue($request->user()->id, self::kobo($d['amount_naira']), [
                'name' => $d['recipient_name'] ?? null, 'phone' => $d['recipient_phone'] ?? null, 'email' => $d['recipient_email'] ?? null, 'message' => $d['message'] ?? null,
            ], $d['expires_at'] ?? null);
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return back()->with('success', "Gift card {$card['code']} created. Copy the code now; it is not shown again.");
    }

    public function toggleGiftCard(Request $request, int $card, \App\Modules\Marketing\GiftCardService $cards)
    {
        $row = DB::table('gift_cards')->where('id', $card)->first();
        abort_unless($row, 404);
        $cards->setActive($card, $row->status !== 'active');

        return back()->with('success', $row->status === 'active' ? 'Gift card stopped. It can no longer pay.' : 'Gift card can pay again.');
    }

    public function storePromo(Request $request)
    {
        $d = $request->validate([
            'code' => ['required', 'string', 'max:32', 'regex:/^[A-Za-z0-9_-]+$/'], 'type' => ['required', Rule::in(array_keys(self::PROMO_TYPES))],
            'value' => 'nullable|numeric|min:0', 'cap_naira' => 'nullable|numeric|min:0', 'min_order_naira' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1', 'per_user_limit' => 'nullable|integer|min:1|max:100', 'budget_naira' => 'nullable|numeric|min:0',
            'operator_id' => 'nullable|integer|exists:operators,id', 'valid_from' => 'nullable|date', 'valid_to' => 'nullable|date|after:valid_from',
        ]);
        $code = strtoupper($d['code']);
        if (DB::table('promo_codes')->where('code', $code)->exists()) {
            return back()->withInput()->with('error', "The code {$code} already exists.");
        }
        // Percent types store basis points; a fixed amount stores kobo.
        $value = match ($d['type']) {
            'fixed' => self::kobo($d['value'] ?? 0),
            'free_delivery' => 0,
            default => (int) round(min(100, (float) ($d['value'] ?? 0)) * 100),
        };
        if ($d['type'] !== 'free_delivery' && $value <= 0) {
            return back()->withInput()->with('error', 'Give the discount: a percentage, or an amount in naira.');
        }
        DB::table('promo_codes')->insert([
            'code' => $code, 'type' => $d['type'], 'value' => $value, 'cap' => self::kobo($d['cap_naira'] ?? null), 'min_order' => self::kobo($d['min_order_naira'] ?? null),
            'usage_limit' => $d['usage_limit'] ?? null, 'per_user_limit' => $d['per_user_limit'] ?? 1, 'budget' => self::kobo($d['budget_naira'] ?? null),
            'operator_id' => Platform::companyOnly() ? null : ($d['operator_id'] ?? null), 'funded_by' => 'platform',
            'valid_from' => $d['valid_from'] ?? null, 'valid_to' => $d['valid_to'] ?? null, 'active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);

        return back()->with('success', "Promo code {$code} is live.");
    }

    public function togglePromo(int $promo)
    {
        $p = DB::table('promo_codes')->where('id', $promo)->first();
        abort_unless($p, 404);
        DB::table('promo_codes')->where('id', $promo)->update(['active' => ! $p->active, 'updated_at' => now()]);

        return back()->with('success', $p->code.($p->active ? ' paused.' : ' switched on.'));
    }

    public function storeCorporate(Request $request)
    {
        $d = $request->validate([
            'name' => 'required|string|max:120', 'rate' => 'required|numeric|min:0.01|max:100', 'cap_naira' => 'nullable|numeric|min:0',
            'merchant_id' => 'nullable|integer|exists:merchants,id', 'user_email' => 'nullable|email', 'operator_id' => 'nullable|integer|exists:operators,id',
            'valid_to' => 'nullable|date|after:today',
        ]);
        $userId = ! empty($d['user_email']) ? DB::table('users')->where('email', $d['user_email'])->value('id') : null;
        if (! empty($d['user_email']) && ! $userId) {
            return back()->withInput()->with('error', 'No customer has that email address.');
        }
        if ((bool) $userId === (bool) ($d['merchant_id'] ?? null)) {
            return back()->withInput()->with('error', 'Choose a business account or a customer email, not both.');
        }
        DB::table('corporate_discounts')->insert([
            'name' => $d['name'], 'rate_bp' => (int) round((float) $d['rate'] * 100), 'cap' => self::kobo($d['cap_naira'] ?? null),
            'merchant_id' => $d['merchant_id'] ?? null, 'user_id' => $userId, 'operator_id' => Platform::companyOnly() ? null : ($d['operator_id'] ?? null),
            'valid_to' => $d['valid_to'] ?? null, 'active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);

        return back()->with('success', 'Corporate discount added.');
    }

    public function toggleCorporate(int $discount)
    {
        $c = DB::table('corporate_discounts')->where('id', $discount)->first();
        abort_unless($c, 404);
        DB::table('corporate_discounts')->where('id', $discount)->update(['active' => ! $c->active, 'updated_at' => now()]);

        return back()->with('success', $c->name.($c->active ? ' paused.' : ' switched on.'));
    }

    // ---------------------------------------------------------------- price lists

    public function plans()
    {
        return view('ops.price-lists', [
            'plans' => DB::table('pricing_plans as p')->join('operators as o', 'o.id', '=', 'p.operator_id')->join('service_types as s', 's.id', '=', 'p.service_type_id')
                ->leftJoin('cities as c', 'c.id', '=', 'p.city_id')->leftJoin('vehicle_types as v', 'v.id', '=', 'p.vehicle_type_id')
                ->selectRaw('p.*, o.display_name as provider, s.name as service, c.name as city, v.name as vehicle, (select count(*) from pricing_rules r where r.plan_id = p.id) as rules')
                ->orderByDesc('p.id')->get(),
            'operators' => $this->operators(), 'services' => DB::table('service_types')->orderBy('id')->get(['id', 'name']),
            'cities' => DB::table('cities')->orderBy('name')->get(['id', 'name']), 'vehicles' => DB::table('vehicle_types')->orderBy('id')->get(['id', 'name']),
            'pagetitle' => 'Price lists',
        ]);
    }

    public function storePlan(Request $request)
    {
        $d = $request->validate([
            'name' => 'required|string|max:120', 'operator_id' => 'required|integer|exists:operators,id', 'service_type_id' => 'required|integer|exists:service_types,id',
            'city_id' => 'nullable|integer|exists:cities,id', 'vehicle_type_id' => 'nullable|integer|exists:vehicle_types,id',
            'min_fee_naira' => 'required|numeric|min:0', 'max_fee_naira' => 'nullable|numeric|min:0', 'rounding_naira' => 'nullable|numeric|min:0.01',
        ]);
        $id = DB::table('pricing_plans')->insertGetId([
            'public_id' => (string) Str::ulid(), 'operator_id' => Platform::companyOnly() ? Platform::houseOperatorId() : $d['operator_id'],
            'service_type_id' => $d['service_type_id'], 'city_id' => $d['city_id'] ?? null, 'vehicle_type_id' => $d['vehicle_type_id'] ?? null,
            'name' => $d['name'], 'min_fee' => self::kobo($d['min_fee_naira']), 'max_fee' => self::kobo($d['max_fee_naira'] ?? null),
            'rounding_kobo' => self::kobo($d['rounding_naira'] ?? 50) ?: 5000, 'status' => 'draft', 'created_at' => now(), 'updated_at' => now(),
        ]);

        return redirect()->route('ops.price-list', $id)->with('success', 'Price list created as a draft. Add its rules, then switch it on.');
    }

    public function plan(int $plan)
    {
        $p = DB::table('pricing_plans as p')->join('operators as o', 'o.id', '=', 'p.operator_id')->join('service_types as s', 's.id', '=', 'p.service_type_id')
            ->leftJoin('cities as c', 'c.id', '=', 'p.city_id')->where('p.id', $plan)->first(['p.*', 'o.display_name as provider', 's.name as service', 'c.name as city']);
        abort_unless($p, 404);

        return view('ops.price-list', [
            'p' => $p, 'rules' => DB::table('pricing_rules')->where('plan_id', $plan)->orderBy('priority')->orderBy('id')->get(),
            'kinds' => self::RULE_KINDS, 'pagetitle' => 'Price list',
        ]);
    }

    public function storeRule(Request $request, int $plan)
    {
        abort_unless(DB::table('pricing_plans')->where('id', $plan)->exists(), 404);
        $d = $request->validate([
            'kind' => ['required', Rule::in(array_keys(self::RULE_KINDS))], 'amount_naira' => 'nullable|numeric|min:0', 'rate' => 'nullable|numeric|min:0|max:500',
            'from_km' => 'nullable|numeric|min:0', 'to_km' => 'nullable|numeric|gt:from_km', 'hours' => 'nullable|string|max:80',
            'days' => 'nullable|array', 'days.*' => 'integer|between:1,7', 'priority' => 'nullable|integer|min:0|max:100',
        ]);
        $condition = array_filter([
            'from_km' => isset($d['from_km']) ? (float) $d['from_km'] : null, 'to_km' => isset($d['to_km']) ? (float) $d['to_km'] : null,
            'hours' => ! empty($d['hours']) ? array_values(array_unique(array_filter(array_map('intval', preg_split('/[\s,]+/', $d['hours'])), fn ($h) => $h >= 0 && $h <= 23))) : null,
            'days' => ! empty($d['days']) && $d['kind'] === 'weekend' ? array_map('intval', $d['days']) : null,
        ], fn ($v) => $v !== null && $v !== []);
        $amount = self::kobo($d['amount_naira'] ?? null);
        $rate = isset($d['rate']) && $d['rate'] !== '' ? (int) round((float) $d['rate'] * 100) : null;
        if (! $amount && ! $rate) {
            return back()->withInput()->with('error', 'Give the rule an amount, a rate, or both.');
        }
        DB::transaction(function () use ($plan, $d, $condition, $amount, $rate) {
            DB::table('pricing_rules')->insert([
                'plan_id' => $plan, 'kind' => $d['kind'], 'condition' => $condition ? json_encode($condition) : null, 'amount' => $amount, 'rate_bp' => $rate,
                'priority' => $d['priority'] ?? 0, 'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('pricing_plans')->where('id', $plan)->increment('version', 1, ['updated_at' => now()]);
        });

        return back()->with('success', 'Rule added.');
    }

    public function deleteRule(int $plan, int $rule)
    {
        $n = DB::table('pricing_rules')->where(['id' => $rule, 'plan_id' => $plan])->delete();
        abort_unless($n, 404);
        DB::table('pricing_plans')->where('id', $plan)->increment('version', 1, ['updated_at' => now()]);

        return back()->with('success', 'Rule removed.');
    }

    public function setPlanStatus(Request $request, int $plan)
    {
        $d = $request->validate(['status' => 'required|in:draft,active,retired']);
        $n = DB::table('pricing_plans')->where('id', $plan)->update(['status' => $d['status'], 'updated_at' => now()]);
        abort_unless($n, 404);

        return back()->with('success', 'Price list is now '.$d['status'].'.');
    }

    private function operators()
    {
        $q = DB::table('operators')->where('status', 'active')->orderBy('display_name');
        if (Platform::companyOnly()) {
            $q->where('id', Platform::houseOperatorId());
        }

        return $q->get(['id', 'display_name']);
    }

    private static function kobo($naira): ?int
    {
        return ($naira === null || $naira === '') ? null : (int) round(((float) $naira) * 100);
    }
}
