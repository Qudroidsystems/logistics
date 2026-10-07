<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Sign-in for the mobile and partner apps. A person registers once as a customer and can later also register as a
 * provider (company, rider or shopper) under the same account; the app picks the role from /auth/me.
 *
 * Phone numbers are not verified yet (that needs an SMS provider), so a phone is a login handle only, never proof of identity.
 */
class AuthController extends Controller
{
    public function register(Request $request)
    {
        $d = $request->validate([
            'name' => 'required|string|min:2|max:120',
            'email' => 'required|email:rfc|max:190|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:8|max:100',
            'device_name' => 'nullable|string|max:60',
        ]);
        $phone = isset($d['phone']) ? self::normalizePhone($d['phone']) : null;
        if ($phone !== null && (strlen($phone) < 10 || DB::table('users')->where('phone_number', $phone)->exists())) {
            return response()->json(['message' => 'That phone number cannot be used.', 'errors' => ['phone' => ['That phone number cannot be used.']]], 422);
        }

        $user = DB::transaction(function () use ($d, $phone) {
            $u = User::create(['name' => $d['name'], 'email' => strtolower($d['email']), 'phone_number' => $phone, 'password' => Hash::make($d['password'])]);
            DB::table('customer_profiles')->insert([
                'public_id' => (string) Str::ulid(), 'user_id' => $u->id, 'referral_code' => strtoupper(Str::random(8)), 'created_at' => now(), 'updated_at' => now(),
            ]);

            return $u;
        });

        return response()->json($this->session($user, $d['device_name'] ?? 'app'), 201);
    }

    public function login(Request $request)
    {
        $d = $request->validate(['login' => 'required|string|max:190', 'password' => 'required|string|max:100', 'device_name' => 'nullable|string|max:60']);
        $key = 'api-login:'.Str::lower($d['login']).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json(['error' => 'too_many_attempts', 'message' => 'Too many attempts. Try again in '.RateLimiter::availableIn($key).' seconds.'], 429);
        }

        $login = trim($d['login']);
        $user = str_contains($login, '@')
            ? User::where('email', strtolower($login))->first()
            : User::where('phone_number', self::normalizePhone($login))->first();

        // One message for "no such user" and "wrong password" so the response does not reveal which accounts exist.
        if (! $user || ! Hash::check($d['password'], $user->password)) {
            RateLimiter::hit($key, 60);

            return response()->json(['error' => 'invalid_credentials', 'message' => 'Email, phone or password is wrong.'], 401);
        }
        if ($user->is_disabled || ! in_array($user->status ?? 'active', ['active'], true)) {
            return response()->json(['error' => 'account_disabled', 'message' => 'This account is not active. Contact support.'], 403);
        }
        RateLimiter::clear($key);
        DB::table('users')->where('id', $user->id)->update(['last_login_at' => now(), 'last_active_at' => now()]);

        return response()->json($this->session($user, $d['device_name'] ?? 'app'));
    }

    public function me(Request $request)
    {
        return response()->json($this->profile($request->user()));
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['ok' => true]);
    }

    /** Sign out everywhere, for a lost phone. */
    public function logoutAll(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json(['ok' => true]);
    }

    public function changePassword(Request $request)
    {
        $d = $request->validate(['current_password' => 'required|string', 'password' => 'required|string|min:8|max:100|different:current_password']);
        $user = $request->user();
        if (! Hash::check($d['current_password'], $user->password)) {
            return response()->json(['error' => 'invalid_credentials', 'message' => 'Your current password is wrong.'], 422);
        }
        DB::table('users')->where('id', $user->id)->update(['password' => Hash::make($d['password']), 'must_change_password' => false, 'updated_at' => now()]);
        // Every other device signs in again; this one stays signed in.
        $user->tokens()->where('id', '!=', $user->currentAccessToken()->id ?? 0)->delete();

        return response()->json(['ok' => true]);
    }

    // ----------------------------------------------------------------

    private function session(User $user, string $device): array
    {
        return ['token' => $user->createToken($device)->plainTextToken, 'token_type' => 'Bearer'] + $this->profile($user);
    }

    private function profile(User $user): array
    {
        $memberships = DB::table('operator_members as m')->join('operators as o', 'o.id', '=', 'm.operator_id')->where('m.user_id', $user->id)->where('m.status', 'active')
            ->get(['o.public_id as operator_id', 'o.type', 'o.display_name', 'o.status', 'm.role', 'o.id as _id']);
        $current = (int) $user->current_operator_id;

        return [
            'user' => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email, 'phone' => $user->phone_number, 'email_verified' => $user->email_verified_at !== null, 'must_change_password' => (bool) $user->must_change_password],
            'customer' => DB::table('customer_profiles')->where('user_id', $user->id)->exists(),
            'operators' => $memberships->map(fn ($m) => [
                'operator_id' => $m->operator_id, 'type' => $m->type, 'name' => $m->display_name, 'status' => $m->status, 'role' => $m->role, 'current' => (int) $m->_id === $current,
            ])->values(),
            'staff' => $user->getAllPermissions()->isNotEmpty() || $user->roles()->exists(),
        ];
    }

    /** Nigerian numbers are stored in local form: +2348012345678, 2348012345678 and 08012345678 are the same person. */
    public static function normalizePhone(string $raw): string
    {
        $p = preg_replace('/[^\d+]/', '', trim($raw));
        if (str_starts_with($p, '+234')) {
            $p = '0'.substr($p, 4);
        } elseif (str_starts_with($p, '234') && strlen($p) >= 13) {
            $p = '0'.substr($p, 3);
        }

        return ltrim($p, '+') === $p ? $p : ltrim($p, '+');
    }
}
