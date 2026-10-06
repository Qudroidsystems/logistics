<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class ActivityPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'View activity log', // see the staff activity log
            'View online staff', // see who is online and get sign-in notices
        ];

        foreach ($permissions as $permission) {
            $title = 'Staff Activity';

            Permission::updateOrCreate(
                ['name' => $permission, 'guard_name' => 'web'], // Match by name and guard
                ['title' => $title] // Update or set the title
            );
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
