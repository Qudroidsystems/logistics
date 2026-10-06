<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Users holding a temporary password (e.g. new parent accounts) must set their
 * own password before using the portal.
 */
class ForcePasswordChange
{
    protected array $allowed = ['parent.password', 'parent.password.update', 'logout'];

    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if ($user && !empty($user->must_change_password) && !$request->routeIs(...$this->allowed)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Please change your temporary password first.', 'redirect' => route('parent.password')], 403);
            }
            return redirect()->route('parent.password')->with('warning', 'Please choose your own password to continue.');
        }

        return $next($request);
    }
}
