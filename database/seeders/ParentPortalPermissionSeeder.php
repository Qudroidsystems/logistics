<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class ParentPortalPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'Manage parent accounts', // create parent logins, send passwords, link children
        ];

        foreach ($permissions as $permission) {
            $title = 'Parent Portal';

            if (str_contains($permission, 'parent accounts')) {
                $title = 'Parent Portal';
            }
            Permission::updateOrCreate(
                ['name' => $permission, 'guard_name' => 'web'], // Match by name and guard
                ['title' => $title] // Update or set the title
            );
        }

        // Role every parent account gets (the portal pages check the role, not permissions).
        Role::firstOrCreate(['name' => 'Parent', 'guard_name' => 'web']);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
