<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

/**
 * Creates the first Super Admin from env values — nothing is hard-coded:
 *   ADMIN_NAME, ADMIN_EMAIL, ADMIN_PASSWORD
 * If ADMIN_PASSWORD is not set, a random one is generated and printed once.
 * An existing user's password is never reset.
 */
class UserTableSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('admin.email');
        if (!$email) {
            $this->command?->warn('ADMIN_EMAIL is not set — skipping Super Admin creation.');
            return;
        }

        $user = User::where('email', $email)->first();
        if (!$user) {
            $password = config('admin.password') ?: \Illuminate\Support\Str::password(14, symbols: false);
            $user = User::create([
                'name'     => config('admin.name'),
                'email'    => $email,
                'password' => Hash::make($password),
            ]);
            $user->forceFill(['email_verified_at' => now(), 'must_change_password' => !config('admin.password')])->save();

            if (!config('admin.password')) {
                $this->command?->warn("Generated Super Admin password (shown once): {$password}");
            }
        }

        $role = Role::where('name', 'Super Admin')->where('guard_name', 'web')->first();
        if ($role && !$user->hasRole($role->name)) {
            $user->assignRole($role);
        }
    }
}
