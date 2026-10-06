<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SportPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Sport Permissions
        $sportPermissions = [
            'View sport',
            'Create sport',
            'Update sport',
            'Delete sport'
        ];

        // Create sport permissions
        foreach ($sportPermissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web'
            ]);
        }

        // Assign sport permissions to admin role
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $adminRole->givePermissionTo($sportPermissions);

        // Also assign to super-admin if exists
        $superAdminRole = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        if ($superAdminRole->exists) {
            $superAdminRole->givePermissionTo($sportPermissions);
        }

        $this->command->info('✅ Sport permissions seeded successfully.');
    }
}