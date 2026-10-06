<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Order matters: permissions -> roles (which attach permissions) -> first admin.
     * Every seeder is idempotent, so `php artisan db:seed` is safe to re-run.
     */
    public function run(): void
    {
        $this->call([
            PermissionTableSeeder::class,
            RoleTableSeeder::class,
            UserTableSeeder::class,
            LogisticsFoundationSeeder::class,
        ]);
    }
}
