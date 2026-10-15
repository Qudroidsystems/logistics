<?php

namespace App\Http\Middleware;

use App\Modules\Identity\Security\Audit;
use Closure;
use Illuminate\Http\Request;

/** Tells the database who is making this request, for the data-change audit trail. */
class AuditContext
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user() ?? ($request->bearerToken() ? auth('sanctum')->user() : null);
        Audit::forRequest($request, $user?->id, $user?->current_operator_id ? (int) $user->current_operator_id : null);

        return $next($request);
    }
}
