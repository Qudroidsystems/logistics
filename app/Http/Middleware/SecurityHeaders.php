<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Browser security headers on every web page: no MIME sniffing, no framing by other sites (except pages that set their
 * own frame-ancestors, like the embeddable price form), a strict referrer, limited device features, and HSTS over HTTPS.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $h = $response->headers;
        $h->set('X-Content-Type-Options', 'nosniff', false);
        $h->set('Referrer-Policy', 'strict-origin-when-cross-origin', false);
        $h->set('Permissions-Policy', 'geolocation=(self), camera=(self), microphone=(), payment=(self)', false);
        if (! str_contains((string) $h->get('Content-Security-Policy'), 'frame-ancestors')) {
            $h->set('X-Frame-Options', 'SAMEORIGIN', false);
            $csp = (string) $h->get('Content-Security-Policy');
            $h->set('Content-Security-Policy', trim($csp.($csp ? '; ' : '')."frame-ancestors 'self'"));
        }
        if ($request->isSecure()) {
            $h->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains', false);
        }

        return $response;
    }
}
