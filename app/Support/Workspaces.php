<?php

namespace App\Support;

use App\Models\User;
use App\Modules\Partner\MerchantDeliveryService;
use App\Modules\Providers\ProviderOnboardingService;
use Illuminate\Support\Facades\DB;

/**
 * The places one login can work in: the platform's own back office (staff), each provider business,
 * each merchant (shop) account, the driver pages and the person's own customer account.
 * Used by the switcher at the top of every page and to pick the right provider for /provider.
 */
class Workspaces
{
    /** Operator types that run a provider workspace. */
    public static function providerTypes(): array
    {
        return array_values(array_unique(array_merge(ProviderOnboardingService::TYPES, MerchantDeliveryService::OWN_PROVIDER_TYPES)));
    }

    /**
     * @return array<int, array{key:string, kind:string, label:string, sub:string, icon:string, url:string, operator_id:?int}>
     */
    public static function for(?User $user): array
    {
        if (! $user) {
            return [];
        }
        $out = [];
        if ($user->can('dashboard')) {
            $out[] = ['key' => 'platform', 'kind' => 'platform', 'label' => config('app.name'), 'sub' => 'Platform owner · back office',
                'icon' => 'ri-building-4-line', 'url' => route('dashboard'), 'operator_id' => null];
        }

        $rows = DB::table('operator_members as m')->join('operators as o', 'o.id', '=', 'm.operator_id')
            ->leftJoin('merchants as mc', 'mc.operator_id', '=', 'o.id')
            ->where('m.user_id', $user->id)->where('m.status', 'active')->whereNull('o.deleted_at')
            ->orderBy('o.display_name')->get(['o.id', 'o.type', 'o.display_name', 'o.status', 'm.role', 'mc.id as merchant_id']);
        $driver = false;
        foreach ($rows as $r) {
            $role = str_replace('_', ' ', $r->role);
            if ($r->merchant_id) {
                $out[] = ['key' => "shop:{$r->id}", 'kind' => 'merchant', 'label' => $r->display_name, 'sub' => "Shop (merchant) · {$role}",
                    'icon' => 'ri-store-3-line', 'url' => route('merchant.home'), 'operator_id' => (int) $r->id];
            }
            // A business can be both a shop and a delivery provider: it then shows twice.
            if (in_array($r->type, self::providerTypes(), true)) {
                if ($r->role === 'driver') {
                    $driver = true;

                    continue;
                }
                $out[] = ['key' => "op:{$r->id}", 'kind' => 'provider', 'label' => $r->display_name,
                    'sub' => 'Delivery provider · '.$role.($r->status !== 'active' ? " · {$r->status}" : ''),
                    'icon' => 'ri-truck-line', 'url' => route('provider.dashboard'), 'operator_id' => (int) $r->id];
            }
        }
        $driver = $driver || DB::table('driver_profiles')->where('user_id', $user->id)->where('status', 'active')->whereNull('deleted_at')->exists();
        if ($driver && \Illuminate\Support\Facades\Route::has('driver.home')) {
            $out[] = ['key' => 'driver', 'kind' => 'driver', 'label' => 'Driver', 'sub' => 'My delivery jobs', 'icon' => 'ri-steering-2-line',
                'url' => route('driver.home'), 'operator_id' => null];
        }
        $out[] = ['key' => 'account', 'kind' => 'account', 'label' => 'My account', 'sub' => 'Send parcels and track them', 'icon' => 'ri-user-3-line',
            'url' => route('account.dashboard'), 'operator_id' => null];

        return $out;
    }

    /** Which workspace the current page belongs to. */
    public static function current(?User $user): ?string
    {
        $r = request();
        $op = (int) ($user->current_operator_id ?? 0);

        return match (true) {
            $r->routeIs('provider.*') => "op:{$op}",
            $r->routeIs('merchant.*') => ($m = self::merchantOperatorOf($user)) ? "shop:{$m}" : null,
            $r->routeIs('driver.*') => 'driver',
            $r->routeIs('account.*') => 'account',
            default => $user && $user->can('dashboard') ? 'platform' : null,
        };
    }

    private static function merchantOperatorOf(?User $user): ?int
    {
        if (! $user) {
            return null;
        }
        $id = DB::table('operator_members as om')->join('merchants as m', 'm.operator_id', '=', 'om.operator_id')
            ->where('om.user_id', $user->id)->where('om.status', 'active')
            ->orderByRaw('om.operator_id = ? desc', [(int) $user->current_operator_id])->value('om.operator_id');

        return $id ? (int) $id : null;
    }

    /**
     * Make sure the user's current account is a provider they run, switching to one when it is not (for example after
     * they were made owner of a merchant). Returns the provider operator id, or null when they run none.
     */
    public static function ensureProvider(User $user): ?int
    {
        $types = self::providerTypes();
        $mine = fn () => DB::table('operator_members as m')->join('operators as o', 'o.id', '=', 'm.operator_id')
            ->where('m.user_id', $user->id)->where('m.status', 'active')->whereIn('o.type', $types)->whereNull('o.deleted_at');
        $current = (int) $user->current_operator_id;
        if ($current && $mine()->where('m.operator_id', $current)->exists()) {
            return $current;
        }
        $pick = $mine()->orderByRaw("CASE m.role WHEN 'owner' THEN 0 WHEN 'admin' THEN 1 WHEN 'driver' THEN 9 ELSE 5 END")->orderBy('o.id')->value('o.id');
        if (! $pick) {
            return null;
        }
        DB::table('users')->where('id', $user->id)->update(['current_operator_id' => $pick]);
        $user->forceFill(['current_operator_id' => (int) $pick])->syncOriginalAttribute('current_operator_id');

        return (int) $pick;
    }
}
