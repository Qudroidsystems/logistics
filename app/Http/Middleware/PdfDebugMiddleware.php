<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PdfDebugMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Log request details
        Log::info('PDF Request started', [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'user_agent' => $request->userAgent(),
            'ip' => $request->ip(),
            'memory_before' => memory_get_usage(true),
            'time_before' => microtime(true)
        ]);
        
        // Check for any existing output
        $outputBefore = ob_get_length();
        if ($outputBefore > 0) {
            Log::warning('Output buffer not empty before request', [
                'buffer_length' => $outputBefore
            ]);
        }
        
        try {
            $response = $next($request);
            
            // Log response details
            Log::info('PDF Response generated', [
                'status_code' => method_exists($response, 'getStatusCode') ? $response->getStatusCode() : 'unknown',
                'content_type' => method_exists($response, 'headers') ? $response->headers->get('Content-Type') : 'unknown',
                'content_length' => method_exists($response, 'headers') ? $response->headers->get('Content-Length') : 'unknown',
                'memory_after' => memory_get_usage(true),
                'time_taken' => microtime(true) - microtime(true),
                'memory_peak' => memory_get_peak_usage(true)
            ]);
            
            return $response;
            
        } catch (\Exception $e) {
            Log::error('PDF Middleware caught exception', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'memory_at_error' => memory_get_usage(true)
            ]);
            
            throw $e;
        }
    }
}