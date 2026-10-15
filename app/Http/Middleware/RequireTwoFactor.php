<?php

namespace App\Http\Middleware;

use App\Modules\Identity\Security\TwoFactorService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/** People who must use two-step sign-in (staff or team rule) are sent to set it up before anything else. */
class RequireTwoFactor
{
    protected array $allowed = ['security.*', 'two-factor.*', 'logout', 'password.change', 'password.change.update', 'csrf.refresh', 'notifications.feed'];

    public function __construct(private TwoFactorService $tf)
    {
    }

    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (! $user || $this->tf->enabled($user) || $request->routeIs(...$this->allowed)) {
            return $next($request);
        }
        // Remembered for a minute per person, and forgotten as soon as any rule changes.
        $why = Cache::remember(TwoFactorService::requiredCacheKey($user->id), 60, fn () => $this->tf->requiredBecause($user) ?? '');
        if ($why === '') {
            return $next($request);
        }
        if ($request->expectsJson()) {
            return response()->json(['message' => $why.' Set it up first.', 'redirect' => route('security.index')], 403);
        }

        return redirect()->route('security.index')->with('warning', $why.' Set it up to continue.');
    }
}
