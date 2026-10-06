<?php

namespace App\Http\Middleware;

use App\Modules\Partner\ApiClientService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class AuthenticateApiClient
{
    public function __construct(private ApiClientService $clients)
    {
    }

    public function handle(Request $request, Closure $next, string $scope = '')
    {
        $client = $this->clients->authenticate((string) $request->bearerToken(), $request->ip());
        if (! $client) {
            return response()->json(['error' => 'invalid_api_key'], 401);
        }
        if ($scope && ! in_array($scope, json_decode($client->scopes ?? '[]', true) ?: [], true)) {
            return response()->json(['error' => 'insufficient_scope', 'scope' => $scope], 403);
        }
        $key = "api-client:{$client->id}";
        if (RateLimiter::tooManyAttempts($key, (int) $client->rate_limit_per_minute)) {
            return response()->json(['error' => 'rate_limited'], 429)->header('Retry-After', RateLimiter::availableIn($key));
        }
        RateLimiter::hit($key, 60);

        $request->attributes->set('api_client', $client);

        return $next($request);
    }
}
