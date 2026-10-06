<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class IdCardPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'View id card',
            'Generate id card',
            'Print id card',
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission, 'guard_name' => 'web'],
                ['title' => 'Student ID Card Management']
            );
        }

        // $this->command->info('✅ Student ID Card permissions seeded successfully!');
    }
}
