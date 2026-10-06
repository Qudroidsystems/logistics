<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as BaseVerifier;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Auth;

class CustomVerifyCsrfToken extends BaseVerifier
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     *
     * @throws \Illuminate\Session\TokenMismatchException
     */
    public function handle($request, Closure $next)
    {
        // Check if this route should be excluded from CSRF verification
        if ($this->isReading($request) || $this->tokensMatch($request) || $this->shouldSkipCsrf($request)) {
            return $this->addCookieToResponse($request, $next($request));
        }

        // ============================================
        // TOKEN MISMATCH - HANDLE IT HERE
        // ============================================
        
        // Store the URL they were trying to access
        $intendedUrl = $request->fullUrl();

        // For AJAX/API requests
        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Session expired. Please login again.',
                'redirect' => route('login', [], false)
            ], 419);
        }

        // Log out the user if they were logged in
        if (Auth::check()) {
            Auth::logout();
        }

        // Clear and regenerate session
        $request->session()->flush();
        $request->session()->regenerate();

        // Redirect to login with flash data
        return redirect()->route('login')
            ->with('session_expired', true)
            ->with('error', 'Your session has expired. Please login again.')
            ->with('intended', $intendedUrl);
    }

    /**
     * Check if the request should skip CSRF verification.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function shouldSkipCsrf($request)
    {
        foreach ($this->getExcludedPaths() as $uri) {
            if ($request->is($uri) || $request->is($uri . '/*')) {
                return true;
            }
        }
        return false;
    }
}