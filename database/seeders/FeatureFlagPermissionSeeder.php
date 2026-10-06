<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class FeatureFlagPermissionSeeder extends Seeder
{
    public function run(): void
    {
        Permission::updateOrCreate(
            ['name' => 'Manage feature flags', 'guard_name' => 'web'],
            ['title' => 'System']
        );

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
