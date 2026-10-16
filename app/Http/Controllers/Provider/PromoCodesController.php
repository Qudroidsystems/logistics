<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Admin\PricingAdminController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * The provider's own promo codes. They work only on the provider's jobs, and the provider pays for them: the
 * discount comes out of their share when the job pays out. Run by the money roles.
 */
class PromoCodesController extends WorkspaceController
{
    public function index(Request $request)
    {
        [$op] = $this->ctx($request, 'money');
        $promos = DB::table('promo_codes as p')->where('p.operator_id', $op->id)->where('p.funded_by', 'operator')
            ->selectRaw('p.*, (select count(*) from promo_redemptions r where r.promo_code_id = p.id) as used, (select coalesce(sum(amount), 0) from promo_redemptions r where r.promo_code_id = p.id) as given')
            ->orderByDesc('p.id')->get();

        return $this->view('provider.promo-codes', $request, ['promos' => $promos, 'types' => PricingAdminController::PROMO_TYPES, 'pagetitle' => 'Promo codes'], 'money');
    }

    public function store(Request $request)
    {
        [$op] = $this->ctx($request, 'money');
        $d = $request->validate([
            'code' => ['required', 'string', 'min:3', 'max:32', 'regex:/^[A-Za-z0-9_-]+$/'], 'type' => ['required', Rule::in(array_keys(PricingAdminController::PROMO_TYPES))],
            'value' => 'nullable|numeric|min:0', 'cap_naira' => 'nullable|numeric|min:0', 'min_order_naira' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1', 'per_user_limit' => 'nullable|integer|min:1|max:100', 'budget_naira' => 'nullable|numeric|min:0',
            'valid_from' => 'nullable|date', 'valid_to' => 'nullable|date|after:valid_from',
        ]);
        $code = strtoupper($d['code']);
        if (DB::table('promo_codes')->where('code', $code)->exists()) {
            return back()->withInput()->with('error', "The code {$code} is already taken. Try another.");
        }
        $value = match ($d['type']) {
            'fixed' => (int) round(((float) ($d['value'] ?? 0)) * 100),
            'free_delivery' => 0,
            default => (int) round(min(100, (float) ($d['value'] ?? 0)) * 100),
        };
        if ($d['type'] !== 'free_delivery' && $value <= 0) {
            return back()->withInput()->with('error', 'Give the discount: a percentage, or an amount in naira.');
        }
        $kobo = fn ($n) => ($n === null || $n === '') ? null : (int) round(((float) $n) * 100);
        DB::table('promo_codes')->insert([
            'code' => $code, 'type' => $d['type'], 'value' => $value, 'cap' => $kobo($d['cap_naira'] ?? null), 'min_order' => $kobo($d['min_order_naira'] ?? null),
            'usage_limit' => $d['usage_limit'] ?? null, 'per_user_limit' => $d['per_user_limit'] ?? 1, 'budget' => $kobo($d['budget_naira'] ?? null),
            'operator_id' => $op->id, 'funded_by' => 'operator',
            'valid_from' => $d['valid_from'] ?? null, 'valid_to' => $d['valid_to'] ?? null, 'active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);

        return back()->with('success', "{$code} is live. Share it with your customers.");
    }

    public function toggle(Request $request, int $promo)
    {
        [$op] = $this->ctx($request, 'money');
        $p = DB::table('promo_codes')->where('id', $promo)->where('operator_id', $op->id)->where('funded_by', 'operator')->first();
        abort_unless($p, 404);
        DB::table('promo_codes')->where('id', $p->id)->update(['active' => ! $p->active, 'updated_at' => now()]);

        return back()->with('success', $p->code.($p->active ? ' paused.' : ' switched on.'));
    }
}
