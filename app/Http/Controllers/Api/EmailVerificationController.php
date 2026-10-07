<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SendPlainEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Confirms that a signed-in user owns their email, by a 6-digit code (hashed in cache, 30 minutes, one use,
 * 5 wrong tries). Nothing is blocked on it yet; the flag is there for apps and staff to rely on.
 */
class EmailVerificationController extends Controller
{
    private const TTL_MINUTES = 30;

    public function send(Request $request)
    {
        $user = $request->user();
        if ($user->email_verified_at) {
            return response()->json(['ok' => true, 'verified' => true]);
        }
        $key = 'email-verify-send:'.$user->id;
        if (RateLimiter::tooManyAttempts($key, 3)) {
            return response()->json(['error' => 'too_many_requests', 'message' => 'Please wait before asking for another code.'], 429);
        }
        RateLimiter::hit($key, 3600);

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        Cache::put('email-verify:'.$user->id, ['hash' => Hash::make($code), 'email' => $user->email], now()->addMinutes(self::TTL_MINUTES));
        RateLimiter::clear('email-verify-try:'.$user->id);
        SendPlainEmail::dispatch($user->email, 'Confirm your email', "Your code is {$code}.\n\nIt works once and expires in ".self::TTL_MINUTES.' minutes.')->afterCommit();

        return response()->json(['ok' => true, 'verified' => false]);
    }

    public function verify(Request $request)
    {
        $d = $request->validate(['code' => 'required|digits:6']);
        $user = $request->user();
        if ($user->email_verified_at) {
            return response()->json(['ok' => true, 'verified' => true]);
        }
        $key = 'email-verify-try:'.$user->id;
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json(['error' => 'too_many_attempts', 'message' => 'Too many tries. Ask for a new code.'], 429);
        }
        $entry = Cache::get('email-verify:'.$user->id);
        // The code is tied to the email it was sent to, so changing the address cannot reuse an old code.
        if (! $entry || $entry['email'] !== $user->email || ! Hash::check($d['code'], $entry['hash'])) {
            RateLimiter::hit($key, 900);

            return response()->json(['error' => 'invalid_code', 'message' => 'That code is invalid or has expired.'], 422);
        }
        DB::table('users')->where('id', $user->id)->update(['email_verified_at' => now(), 'updated_at' => now()]);
        Cache::forget('email-verify:'.$user->id);
        RateLimiter::clear($key);

        return response()->json(['ok' => true, 'verified' => true]);
    }
}
