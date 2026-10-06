<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Auth;

class HandleTokenMismatch
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        try {
            return $next($request);
        } catch (TokenMismatchException $e) {
            // Store the intended URL
            $intendedUrl = $request->fullUrl();

            // For AJAX/API requests
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Session expired. Please login again.',
                    'redirect' => route('login', [], false)
                ], 419);
            }

            // Log out the user
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
    }
}