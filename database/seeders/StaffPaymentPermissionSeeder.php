<?php
// database/seeders/StaffPaymentPermissionSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class StaffPaymentPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'View staff payments',
            'Create staff payment',
            'Reverse staff payment',
            'View own payments',
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission, 'guard_name' => 'web'],
                ['title' => 'Staff Payment Management']
            );
        }

        $this->command->info('✅ Staff payment permissions seeded successfully!');
    }
}
