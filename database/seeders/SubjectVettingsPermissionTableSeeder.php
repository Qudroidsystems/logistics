<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class SubjectVettingsPermissionTableSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'View subject-vettings',
            'Show subject-vettings',
            'Create subject-vettings',
            'Update subject-vettings',
            'Delete subject-vettings',
        ];

        foreach ($permissions as $permission) {
            $title = 'Subject Vettings Management';

            if (str_contains($permission, 'subject-vettings')) {
                $title = 'Subject Vettings Management';
            }
            Permission::updateOrCreate(
                ['name' => $permission, 'guard_name' => 'web'], // Match by name and guard
                ['title' => $title] // Update or set the title
            );
        }
    }
}
