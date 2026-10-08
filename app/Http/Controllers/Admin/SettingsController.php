<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
            'otherRules' => DB::table('commission_rules')->when($fee, fn ($q) => $q->where('id', '!=', $fee->id))->where('active', true)->orderByDesc('id')->limit(30)->get(),
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

    public function saveRules(Request $request)
    {
        abort_unless($request->user()->can('Manage pricing rules'), 403);
        $d = $request->validate([
            'window_hours' => 'required|integer|min:1|max:168',
            'advance_percent' => 'required|numeric|min:0|max:100',
            'tracking_seconds' => 'required|integer|min:3|max:60',
        ]);

        $this->put(self::KEYS['window'], (int) $d['window_hours'], $request->user()->id);
        $this->put(self::KEYS['advance'], (int) round($d['advance_percent'] * 100), $request->user()->id);
        $this->put(self::KEYS['tracking'], (int) $d['tracking_seconds'], $request->user()->id);

        return back()->with('success', 'Settings saved. The confirmation window and advance cap apply to new agreements.');
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
