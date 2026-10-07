<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SendPlainEmail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Forgotten password for app users, by a 6-digit code emailed to the account (phone reset needs an SMS provider).
 * The code is stored hashed, lasts 30 minutes, works once, and is locked after 5 wrong tries.
 */
class PasswordResetController extends Controller
{
    private const TTL_MINUTES = 30;

    /** Always answers the same, so the endpoint cannot be used to find out which emails have accounts. */
    public function forgot(Request $request)
    {
        $d = $request->validate(['email' => 'required|email:rfc|max:190']);
        $email = strtolower($d['email']);
        $reply = response()->json(['ok' => true, 'message' => 'If that email has an account, we have sent a code to it.']);

        $key = 'pwd-forgot:'.$email;
        if (RateLimiter::tooManyAttempts($key, 3)) {
            return $reply;
        }
        RateLimiter::hit($key, 3600);

        $user = User::where('email', $email)->first();
        if (! $user || $user->is_disabled) {
            return $reply;
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        DB::table('password_reset_tokens')->updateOrInsert(['email' => $email], ['token' => Hash::make($code), 'created_at' => now()]);
        RateLimiter::clear('pwd-reset:'.$email);
        SendPlainEmail::dispatch($email, 'Your password reset code', "Your code is {$code}.\n\nIt works once and expires in ".self::TTL_MINUTES." minutes. If you did not ask for it, ignore this email; your password has not changed.")->afterCommit();

        return $reply;
    }

    public function reset(Request $request)
    {
        $d = $request->validate(['email' => 'required|email:rfc|max:190', 'code' => 'required|digits:6', 'password' => 'required|string|min:8|max:100']);
        $email = strtolower($d['email']);
        $key = 'pwd-reset:'.$email;
        $bad = response()->json(['error' => 'invalid_code', 'message' => 'That code is invalid or has expired.'], 422);

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json(['error' => 'too_many_attempts', 'message' => 'Too many tries. Ask for a new code.'], 429);
        }
        $row = DB::table('password_reset_tokens')->where('email', $email)->first();
        $fresh = $row && $row->created_at && now()->diffInMinutes($row->created_at, true) <= self::TTL_MINUTES;
        if (! $fresh || ! Hash::check($d['code'], $row->token)) {
            RateLimiter::hit($key, 900);

            return $bad;
        }
        $user = User::where('email', $email)->first();
        if (! $user || $user->is_disabled) {
            return $bad;
        }

        DB::transaction(function () use ($user, $d, $email) {
            DB::table('users')->where('id', $user->id)->update(['password' => Hash::make($d['password']), 'must_change_password' => false, 'updated_at' => now()]);
            DB::table('password_reset_tokens')->where('email', $email)->delete();
            $user->tokens()->delete();   // every device signs in again with the new password
        });
        RateLimiter::clear($key);

        return response()->json(['ok' => true]);
    }
}
