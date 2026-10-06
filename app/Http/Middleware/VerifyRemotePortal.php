<?php

namespace App\Http\Middleware;

use App\Models\FeatureSync;
use App\Services\FeatureSyncService;
use Closure;
use Illuminate\Http\Request;

/**
 * Guards the feature-flag API. The remote portal must present the shared key
 * as a bearer token; write calls must also carry a matching HMAC signature of
 * the raw body in X-Signature. Rejects everything otherwise.
 */
class VerifyRemotePortal
{
    public function handle(Request $request, Closure $next)
    {
        $key = FeatureSync::current()->apiKey();
        if (!$key) {
            return response()->json(['message' => 'Feature-flag API is not configured.'], 503);
        }

        $presented = (string) ($request->bearerToken() ?: $request->header('X-Api-Key'));
        if ($presented === '' || !hash_equals($key, $presented)) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        // Writes must be signed.
        if (!$request->isMethod('GET')) {
            $sig = (string) $request->header('X-Signature');
            $expected = FeatureSyncService::sign($request->getContent(), $key);
            if ($sig === '' || !hash_equals($expected, $sig)) {
                return response()->json(['message' => 'Bad or missing signature.'], 401);
            }
        }

        return $next($request);
    }
}
