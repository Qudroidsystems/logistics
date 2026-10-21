<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Marketplace\FailedDeliveryPolicy;
use App\Modules\Marketplace\ShoppingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * The few numbers staff tune without a developer: the platform fee, how long a customer has to confirm delivery,
 * how much of an errand's goods budget a shopper may take up front, and how often tracking refreshes.
 * Only settings the code actually reads are shown here. Changes apply to new agreements; ones already signed keep their terms.
 */
class SettingsController extends Controller
{
    private const KEYS = [
        'window' => 'escrow.default_confirmation_window_hours',
        'advance' => 'shopper.advance_max_bp',
        'tracking' => 'tracking.location_interval_seconds',
        'offer_seconds' => 'dispatch.offer_timeout_seconds',
        'max_attempts' => 'dispatch.max_offer_attempts',
    ];

    public function index(Request $request)
    {
        $this->needAny($request);

        $fee = $this->defaultRule();

        return view('ops.settings', [
            'fee' => $fee,
            'feePercent' => $fee ? rtrim(rtrim(number_format($fee->rate_bp / 100, 2, '.', ''), '0'), '.') : '',
            'feeMin' => $fee ? $fee->min_fee / 100 : 0,
            'window' => (int) ($this->get(self::KEYS['window']) ?? 24),
            'advance' => ((int) ($this->get(self::KEYS['advance']) ?? ShoppingService::DEFAULT_ADVANCE_MAX_BP)) / 100,
            'tracking' => (int) ($this->get(self::KEYS['tracking']) ?? 8),
            'offerSeconds' => (int) ($this->get(self::KEYS['offer_seconds']) ?? 30),
            'maxAttempts' => (int) ($this->get(self::KEYS['max_attempts']) ?? 5),
            'failed' => FailedDeliveryPolicy::current(),
            'otherRules' => DB::table('commission_rules as r')->leftJoin('operators as o', 'o.id', '=', 'r.operator_id')
                ->leftJoin('cities as c', 'c.id', '=', 'r.city_id')->leftJoin('service_types as t', 't.id', '=', 'r.service_type_id')
                ->when($fee, fn ($q) => $q->where('r.id', '!=', $fee->id))->orderByDesc('r.active')->orderByDesc('r.id')->limit(60)
                ->get(['r.*', 'o.display_name as provider', 'c.name as city', 't.name as service']),
            'ruleProviders' => DB::table('operators')->whereIn('type', \App\Support\Workspaces::providerTypes())->whereIn('status', ['active', 'pending', 'suspended'])
                ->orderBy('display_name')->limit(500)->get(['id', 'display_name', 'type']),
            'ruleTypes' => \App\Support\Workspaces::providerTypes(),
            'ruleServices' => DB::table('service_types')->where('active', true)->orderBy('id')->get(['id', 'name']),
            'ruleCities' => DB::table('cities')->orderBy('name')->get(['id', 'name']),
            'canFee' => $request->user()->can('Manage commission'),
            'canRules' => $request->user()->can('Manage pricing rules'),
            'pagetitle' => 'Settings',
        ]);
    }

    public function saveFee(Request $request)
    {
        abort_unless($request->user()->can('Manage commission'), 403);
        $d = $request->validate(['percent' => 'required|numeric|min:0|max:50', 'min_naira' => 'required|numeric|min:0|max:100000']);

        $values = ['basis' => 'percent_of_delivery_fee', 'rate_bp' => (int) round($d['percent'] * 100), 'min_fee' => (int) round($d['min_naira'] * 100), 'updated_at' => now()];
        $rule = $this->defaultRule();
        if ($rule) {
            DB::table('commission_rules')->where('id', $rule->id)->update($values + ['active' => true]);
        } else {
            DB::table('commission_rules')->insert($values + ['priority' => 0, 'active' => true, 'created_at' => now()]);
        }

        return back()->with('success', 'Platform fee saved. It applies to agreements made from now on.');
    }

    /**
     * A special fee for one provider, or for every provider of one kind (optionally only in one city or for one service).
     * The most specific matching rule wins over the platform fee (CommissionResolver).
     */
    public function saveRule(Request $request)
    {
        abort_unless($request->user()->can('Manage commission'), 403);
        $d = $request->validate([
            'applies_to' => 'required|in:provider,type',
            'operator_id' => 'required_if:applies_to,provider|nullable|integer|exists:operators,id',
            'operator_type' => 'required_if:applies_to,type|nullable|in:'.implode(',', \App\Support\Workspaces::providerTypes()),
            'service_type_id' => 'nullable|integer|exists:service_types,id',
            'city_id' => 'nullable|integer|exists:cities,id',
            'percent' => 'required|numeric|min:0|max:50',
            'min_naira' => 'nullable|numeric|min:0|max:100000',
        ]);
        $where = [
            'operator_id' => $d['applies_to'] === 'provider' ? (int) $d['operator_id'] : null,
            'operator_type' => $d['applies_to'] === 'type' ? $d['operator_type'] : null,
            'service_type_id' => $d['service_type_id'] ?? null,
            'city_id' => $d['city_id'] ?? null,
        ];
        $values = ['basis' => 'percent_of_delivery_fee', 'rate_bp' => (int) round($d['percent'] * 100), 'fixed_amount' => 0,
            'min_fee' => (int) round(((float) ($d['min_naira'] ?? 0)) * 100), 'active' => true, 'updated_at' => now()];
        $same = DB::table('commission_rules');
        foreach ($where as $k => $v) {
            $v === null ? $same->whereNull($k) : $same->where($k, $v);
        }
        if ($id = $same->value('id')) {
            DB::table('commission_rules')->where('id', $id)->update($values);
        } else {
            DB::table('commission_rules')->insert($where + $values + ['priority' => 0, 'created_at' => now()]);
        }

        return back()->with('success', 'Special fee saved. It applies to agreements made from now on; signed ones keep their fee.');
    }

    /** Switch a special fee off (or back on). The platform-wide fee cannot be switched off here. */
    public function toggleRule(Request $request, int $rule)
    {
        abort_unless($request->user()->can('Manage commission'), 403);
        $r = DB::table('commission_rules')->find($rule);
        abort_unless($r && ($r->operator_id || $r->operator_type || $r->service_type_id || $r->city_id), 404);
        DB::table('commission_rules')->where('id', $r->id)->update(['active' => ! $r->active, 'updated_at' => now()]);

        return back()->with('success', $r->active ? 'Special fee switched off. The platform fee applies again.' : 'Special fee switched on.');
    }

    public function saveRules(Request $request)
    {
        abort_unless($request->user()->can('Manage pricing rules'), 403);
        $d = $request->validate([
            'window_hours' => 'required|integer|min:1|max:168',
            'advance_percent' => 'required|numeric|min:0|max:100',
            'tracking_seconds' => 'required|integer|min:3|max:60',
            'offer_seconds' => 'sometimes|required|integer|min:10|max:300',
            'max_attempts' => 'sometimes|required|integer|min:1|max:20',
            'failed_fee_percent' => 'sometimes|required|numeric|min:0|max:100',
            'failed_return' => 'sometimes|required|in:yes,no',
            'failed_return_fee_percent' => 'sometimes|required|numeric|min:0|max:100',
            'failed_driver_paid' => 'sometimes|required|in:yes,no',
            'failed_wait_minutes' => 'sometimes|required|integer|min:0|max:120',
        ]);

        $this->put(self::KEYS['window'], (int) $d['window_hours'], $request->user()->id);
        $this->put(self::KEYS['advance'], (int) round($d['advance_percent'] * 100), $request->user()->id);
        $this->put(self::KEYS['tracking'], (int) $d['tracking_seconds'], $request->user()->id);
        if (isset($d['offer_seconds'])) {
            $this->put(self::KEYS['offer_seconds'], (int) $d['offer_seconds'], $request->user()->id);
            $this->put(self::KEYS['max_attempts'], (int) $d['max_attempts'], $request->user()->id);
        }
        if (isset($d['failed_fee_percent'])) {
            $uid = $request->user()->id;
            $this->put(FailedDeliveryPolicy::KEYS['customer_fee_bp'], (int) round($d['failed_fee_percent'] * 100), $uid);
            $this->put(FailedDeliveryPolicy::KEYS['return_to_sender'], $d['failed_return'] === 'yes', $uid);
            $this->put(FailedDeliveryPolicy::KEYS['return_fee_bp'], (int) round(($d['failed_return_fee_percent'] ?? 0) * 100), $uid);
            $this->put(FailedDeliveryPolicy::KEYS['driver_paid'], $d['failed_driver_paid'] === 'yes', $uid);
            $this->put(FailedDeliveryPolicy::KEYS['wait_minutes'], (int) $d['failed_wait_minutes'], $uid);
        }

        return back()->with('success', 'Settings saved. The confirmation window, advance cap and failed-delivery terms apply to new agreements; signed ones keep theirs.');
    }

    // ----------------------------------------------------------------

    private function needAny(Request $request): void
    {
        abort_unless($request->user()->hasAnyPermission(['Manage commission', 'Manage pricing rules']), 403);
    }

    /** The platform-wide rule: no provider, type, service or city attached. */
    private function defaultRule(): ?object
    {
        return DB::table('commission_rules')->whereNull('operator_id')->whereNull('operator_type')->whereNull('service_type_id')->whereNull('city_id')->orderBy('id')->first();
    }

    private function get(string $key): mixed
    {
        $v = DB::table('platform_settings')->whereNull('operator_id')->where('scope', 'global')->where('key', $key)->value('value');

        return $v === null ? null : (json_decode($v, true) ?? $v);
    }

    private function put(string $key, mixed $value, int $userId): void
    {
        DB::table('platform_settings')->updateOrInsert(
            ['operator_id' => null, 'scope' => 'global', 'scope_id' => null, 'key' => $key],
            ['value' => json_encode($value), 'updated_by' => $userId, 'updated_at' => now()]
        );
    }
}
