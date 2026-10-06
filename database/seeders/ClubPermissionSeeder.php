<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ClubPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Club Permissions
        $clubPermissions = [
            'View club',
            'Create club',
            'Update club',
            'Delete club'
        ];

        // Create club permissions
        foreach ($clubPermissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web'
            ]);
        }

        // Assign club permissions to admin role
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $adminRole->givePermissionTo($clubPermissions);

        // Also assign to super-admin if exists
        $superAdminRole = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        if ($superAdminRole->exists) {
            $superAdminRole->givePermissionTo($clubPermissions);
        }

        $this->command->info('✅ Club permissions seeded successfully.');
    }
}