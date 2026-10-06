<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Users holding a temporary password (e.g. staff-created accounts) must set their
 * own password before using the portal.
 */
class ForcePasswordChange
{
    protected array $allowed = ['password.change', 'password.change.update', 'logout'];

    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if ($user && !empty($user->must_change_password) && !$request->routeIs(...$this->allowed)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Please change your temporary password first.', 'redirect' => route('password.change')], 403);
            }
            return redirect()->route('password.change')->with('warning', 'Please choose your own password to continue.');
        }

        return $next($request);
    }
}
