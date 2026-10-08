<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Notifications\Sms\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Confirms that a signed-in user owns the phone number on their account, by a 6-digit text (hashed in cache,
 * 10 minutes, one use, 5 wrong tries). Changing the number clears the verified flag.
 */
class PhoneVerificationController extends Controller
{
    private const TTL_MINUTES = 10;

    /** Save or change the number (does not verify it). */
    public function set(Request $request)
    {
        $d = $request->validate(['phone' => 'required|string|max:24']);
        $phone = SmsService::normalize($d['phone']);
        if (! $phone) {
            return response()->json(['error' => 'invalid_phone', 'message' => 'Enter a valid Nigerian mobile number.'], 422);
        }
        $user = $request->user();
        $local = '0'.substr($phone, 3);
        $taken = DB::table('users')->where('id', '!=', $user->id)->where('phone_number', $local)->exists();
        if ($taken) {
            return response()->json(['error' => 'phone_taken', 'message' => 'That number is already used by another account.'], 422);
        }
        $changed = SmsService::normalize($user->phone_number) !== $phone;
        DB::table('users')->where('id', $user->id)->update([
            'phone_number' => $local, 'phone_verified_at' => $changed ? null : $user->phone_verified_at, 'updated_at' => now(),
        ]);

        return response()->json(['ok' => true, 'phone' => $local, 'verified' => ! $changed && (bool) $user->phone_verified_at]);
    }

    public function send(Request $request)
    {
        $user = $request->user();
        $phone = SmsService::normalize($user->phone_number);
        if (! $phone) {
            return response()->json(['error' => 'no_phone', 'message' => 'Add your phone number first.'], 422);
        }
        if ($user->phone_verified_at) {
            return response()->json(['ok' => true, 'verified' => true]);
        }
        $key = 'phone-verify-send:'.$user->id;
        if (RateLimiter::tooManyAttempts($key, 3)) {
            return response()->json(['error' => 'too_many_requests', 'message' => 'Please wait before asking for another code.'], 429);
        }
        RateLimiter::hit($key, 3600);

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        Cache::put('phone-verify:'.$user->id, ['hash' => Hash::make($code), 'phone' => $phone], now()->addMinutes(self::TTL_MINUTES));
        RateLimiter::clear('phone-verify-try:'.$user->id);
        $sent = app(SmsService::class)->toNumber($phone, "Your verification code is {$code}. It expires in ".self::TTL_MINUTES.' minutes. Do not share it.', $user->id, 'phone.verify');

        return response()->json(['ok' => $sent, 'verified' => false], $sent ? 200 : 503);
    }

    public function verify(Request $request)
    {
        $d = $request->validate(['code' => 'required|digits:6']);
        $user = $request->user();
        if ($user->phone_verified_at) {
            return response()->json(['ok' => true, 'verified' => true]);
        }
        $key = 'phone-verify-try:'.$user->id;
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json(['error' => 'too_many_attempts', 'message' => 'Too many tries. Ask for a new code.'], 429);
        }
        $entry = Cache::get('phone-verify:'.$user->id);
        // The code is tied to the number it was sent to, so changing the number cannot reuse an old code.
        if (! $entry || $entry['phone'] !== SmsService::normalize($user->phone_number) || ! Hash::check($d['code'], $entry['hash'])) {
            RateLimiter::hit($key, 900);

            return response()->json(['error' => 'invalid_code', 'message' => 'That code is invalid or has expired.'], 422);
        }
        DB::table('users')->where('id', $user->id)->update(['phone_verified_at' => now(), 'updated_at' => now()]);
        Cache::forget('phone-verify:'.$user->id);
        RateLimiter::clear($key);

        return response()->json(['ok' => true, 'verified' => true]);
    }
}
