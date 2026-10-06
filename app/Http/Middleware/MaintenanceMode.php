<?php

namespace App\Http\Middleware;

use App\Models\MaintenanceSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Admin-controlled maintenance mode.
 *
 * When it is on, everyone except bypass users sees a branded "down for
 * maintenance" page. Bypass users (Super Admins, anyone who can manage
 * maintenance, and any roles the admin ticked) keep full access and see a
 * banner. Login/logout and the maintenance settings screen are always
 * reachable, so an admin can always turn it back off from the browser — there
 * is no command-line recovery step and no one is ever permanently locked out.
 *
 * An optional scheduled time only flips the same switch the admin controls;
 * clearing the schedule or the switch from the settings page always wins.
 */
class MaintenanceMode
{
    /** Route names that must work even while maintenance is on. */
    protected array $alwaysAllowNames = [
        'login', 'logout', 'password.request', 'password.email', 'password.reset', 'password.update', 'password.confirm',
        'maintenance.settings', 'maintenance.save', 'maintenance.page',
    ];

    /** Path prefixes that must stay reachable (assets, health, gateway callbacks). */
    protected array $alwaysAllowPaths = [
        'up', 'login', 'logout', 'password', 'maintenance',
        'build', 'assets', 'css', 'js', 'images', 'img', 'fonts', 'storage', 'favicon.ico',
        'webhook',   // payment gateways must still confirm payments
    ];

    public function handle(Request $request, Closure $next)
    {
        $settings = MaintenanceSetting::current();

        // A due schedule flips the same switch the admin controls (one-time).
        if ($settings->isScheduleDue()) {
            $settings->forceFill([
                'is_active' => true,
                'activated_at' => now(),
                'scheduled_at' => null,
            ])->save();
            $settings = MaintenanceSetting::current();
        }

        if (!$settings->is_active) {
            return $next($request);
        }

        if ($this->allowed($request)) {
            // Bypass users get through; a banner is shown via the view composer / shared flag.
            $request->attributes->set('maintenance_active', true);
            return $next($request);
        }

        $retry = ($settings->retry_after ?? 0) > 0 ? (int) $settings->retry_after * 60 : 3600;

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'maintenance',
                'message' => $settings->message ?: 'The portal is temporarily unavailable for maintenance.',
            ], 503, ['Retry-After' => $retry]);
        }

        return response()
            ->view('errors.maintenance', ['m' => $settings], 503)
            ->header('Retry-After', $retry);
    }

    protected function allowed(Request $request): bool
    {
        if ($this->onAllowedRoute($request)) {
            return true;
        }

        $user = Auth::user();
        if (!$user) {
            return false;
        }

        // Super Admins and anyone who can manage maintenance always keep access.
        try {
            if ($user->hasRole('Super Admin') || $user->can('Manage maintenance mode')) {
                return true;
            }
        } catch (\Throwable $e) {
            // permission tables not ready — fall through
        }

        // Roles the admin chose to keep working while maintenance is on.
        $allow = (array) (MaintenanceSetting::current()->allow_role_ids ?? []);
        if ($allow) {
            try {
                if ($user->roles()->whereIn('id', $allow)->exists()) {
                    return true;
                }
            } catch (\Throwable $e) {
            }
        }

        return false;
    }

    protected function onAllowedRoute(Request $request): bool
    {
        $name = optional($request->route())->getName();
        if ($name && in_array($name, $this->alwaysAllowNames, true)) {
            return true;
        }
        foreach ($this->alwaysAllowPaths as $prefix) {
            if ($request->is($prefix) || $request->is($prefix . '/*')) {
                return true;
            }
        }
        return false;
    }
}
