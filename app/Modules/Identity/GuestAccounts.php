<?php

namespace App\Modules\Identity;

use App\Jobs\SendPlainEmail;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Booking without signing up first: an account is made quietly from the name, email and phone given at checkout,
 * and an email with a link to choose a password follows. An email that already has an account must sign in instead
 * (a booking is never added to someone else's account).
 */
class GuestAccounts
{
    public function create(string $name, string $email, ?string $phone, bool $offers = false): User
    {
        $email = strtolower(trim($email));
        if (User::where('email', $email)->exists()) {
            throw new RuntimeException('You already have an account with this email. Sign in to book.');
        }
        $stored = $phone ? \App\Support\Phone::store($phone) : null;
        $user = DB::transaction(function () use ($name, $email, $stored, $offers) {
            $u = User::create([
                'name' => mb_substr(trim($name), 0, 120), 'email' => $email,
                'phone_number' => $stored && ! User::where('phone_number', $stored)->exists() ? $stored : null,
                'password' => Hash::make(Str::random(40)),
            ]);
            DB::table('customer_profiles')->insert([
                'public_id' => (string) Str::ulid(), 'user_id' => $u->id, 'referral_code' => strtoupper(Str::random(8)),
                'marketing_opt_in' => $offers, 'marketing_changed_at' => $offers ? now() : null, 'created_at' => now(), 'updated_at' => now(),
            ]);

            return $u;
        });

        $link = route('password.reset', ['token' => Password::broker()->createToken($user), 'email' => $user->email]);
        SendPlainEmail::dispatch($user->email, 'Your '.config('app.name').' account',
            "Hi {$user->name},\n\nThanks for booking with us. We made you an account with this email so you can track your delivery and book again quickly.\n\nChoose a password here (the link works for ".config('auth.passwords.users.expire', 60)." minutes; after that use \"Forgot password\" on the sign-in page):\n{$link}\n\nIf you didn't book a delivery, ignore this email.");

        return $user;
    }
}
