<?php
// database/seeders/SiblingGroupPermissionSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class SiblingGroupPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'View sibling groups',
            'Create sibling group',
            'Update sibling group',
            'Delete sibling group',
            'Apply sibling discount',
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission, 'guard_name' => 'web'],
                ['title' => 'Sibling Management']
            );
        }

        $this->command->info('✅ Sibling group permissions seeded successfully!');
    }
}
