<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Bot check for the public price form (Cloudflare Turnstile). Off until TURNSTILE_SITE_KEY and TURNSTILE_SECRET_KEY are set.
 * Signed-in people are never asked; a visitor gets FREE_PER_HOUR prices from one network address, then must pass the check.
 */
class BotCheck
{
    public const FREE_PER_HOUR = 5;

    public static function enabled(): bool
    {
        return (bool) config('services.turnstile.site_key') && (bool) config('services.turnstile.secret_key');
    }

    /** True when this request may go ahead; counts it. */
    public static function allows(Request $request, ?int $userId): bool
    {
        if (! self::enabled() || $userId) {
            return true;
        }
        $key = 'price-check:'.$request->ip();
        $needed = RateLimiter::attempts($key) >= self::FREE_PER_HOUR;
        if ($needed && ! self::verify((string) $request->input('cf_token'), $request->ip())) {
            return false;
        }
        RateLimiter::hit($key, 3600);

        return true;
    }

    public static function verify(string $token, ?string $ip): bool
    {
        if ($token === '') {
            return false;
        }
        try {
            $r = Http::asForm()->timeout(5)->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'secret' => (string) config('services.turnstile.secret_key'), 'response' => $token, 'remoteip' => $ip,
            ]);

            return (bool) $r->json('success');
        } catch (\Throwable $e) {
            Log::warning('Turnstile check failed: '.$e->getMessage());

            return true; // Cloudflare unreachable: don't lock real customers out.
        }
    }
}
