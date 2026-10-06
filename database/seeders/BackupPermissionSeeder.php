<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class BackupPermissionSeeder extends Seeder
{
    public function run(): void
    {
        Permission::updateOrCreate(
            ['name' => 'Manage backups', 'guard_name' => 'web'],
            ['title' => 'System']
        );
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
