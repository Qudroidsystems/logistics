<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DeviceAuthMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $deviceKey = $request->header('X-Device-Key');

        $expectedKey = config('services.device.key');

        if (!$deviceKey || !$expectedKey || !hash_equals($expectedKey, $deviceKey)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized device.',
            ], 401);
        }

        return $next($request);
    }
}