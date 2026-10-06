<?php

namespace App\Http\Middleware;

use App\Models\FeatureFlag;
use Closure;
use Illuminate\Http\Request;

/**
 * Blocks a module's pages when its feature flag is OFF, even if the URL is
 * typed directly. Matches the current route name against a prefix → flag-key
 * map. Fail-open: a flag that is missing or has never been set never blocks,
 * and core routes (login, dashboard, self-service, maintenance, this module's
 * own admin page) are always allowed so no one can be stranded.
 */
class FeatureRouteGuard
{
    /** Route-name prefix => feature key. First match wins. */
    protected array $map;

    /** Route-name prefix => feature key. Shared with the API catalog. */
    public static function moduleRouteMap(): array
    {
        return [
        // route-name prefix => feature key (modules are added as they are built)
        'deliveries.' => 'deliveries',
        'dispatch.' => 'dispatch',
        'tracking.' => 'tracking',
        'shopping.' => 'shopping',
        'stores.' => 'stores',
        'business.' => 'business',
        'vendors.' => 'vendors',
        'fleet.' => 'fleet',
        'warehouses.' => 'warehouses',
        'wallets.' => 'payments',
        'settlements.' => 'payments',
        'promotions.' => 'promotions',
        'support.' => 'support',
        'reports.' => 'reports',
        ];
    }

    /** Route names that are never blocked (prefix match). */
    protected array $allow;

    public static function allowList(): array
    {
        return [
        'login', 'logout', 'password', 'dashboard', 'home',
        'maintenance.', 'feature-flags.',
        'profile.', 'users.', 'roles.', 'permissions.', 'notifications.',
        'admin.payment-gateways.', 'activity.',
        ];
    }

    public function __construct()
    {
        $this->map = self::moduleRouteMap();
        $this->allow = self::allowList();
    }

    public function handle(Request $request, Closure $next)
    {
        $name = optional($request->route())->getName();
        if (!$name) {
            return $next($request);
        }

        foreach ($this->allow as $a) {
            if ($name === $a || str_starts_with($name, $a)) {
                return $next($request);
            }
        }

        foreach ($this->map as $prefix => $key) {
            if ($name === $prefix || str_starts_with($name, $prefix)) {
                // Fail-open: only an explicit OFF blocks.
                if (!FeatureFlag::enabled($key)) {
                    if ($request->expectsJson()) {
                        return response()->json(['message' => 'This module is currently switched off.'], 403);
                    }
                    $to = \Illuminate\Support\Facades\Route::has('dashboard') ? route('dashboard') : url('/home');
                    return redirect($to)->with('error', 'That section is currently switched off for this platform.');
                }
                break;
            }
        }

        return $next($request);
    }
}
