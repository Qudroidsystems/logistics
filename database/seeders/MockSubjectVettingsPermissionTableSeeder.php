<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class MockSubjectVettingsPermissionTableSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'View mock-subject-vettings',
            'Show mock-subject-vettings',
            'Create mock-subject-vettings',
            'Update mock-subject-vettings',
            'Delete mock-subject-vettings',
        ];

        foreach ($permissions as $permission) {
            $title = 'Mock Subject Vettings Management';

            if (str_contains($permission, 'mock-subject-vettings')) {
                $title = 'Mock Subject Vettings Management';
            }
            Permission::updateOrCreate(
                ['name' => $permission, 'guard_name' => 'web'], // Match by name and guard
                ['title' => $title] // Update or set the title
            );
        }
    }
}
