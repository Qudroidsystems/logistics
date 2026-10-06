<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class MaintenancePermissionSeeder extends Seeder
{
    public function run(): void
    {
        Permission::updateOrCreate(
            ['name' => 'Manage maintenance mode', 'guard_name' => 'web'],
            ['title' => 'System']
        );

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
