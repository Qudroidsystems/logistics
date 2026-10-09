<?php

namespace App\Http\Middleware;

use App\Support\Platform;
use Closure;
use Illuminate\Http\Request;

/** Hides marketplace-only pages and endpoints (directory, provider sign-up, public provider pages) in company mode. */
class MarketplaceOnly
{
    public function handle(Request $request, Closure $next)
    {
        abort_unless(Platform::marketplace(), 404);

        return $next($request);
    }
}
