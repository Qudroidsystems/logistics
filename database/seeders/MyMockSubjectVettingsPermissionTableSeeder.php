<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class MyMockSubjectVettingsPermissionTableSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'View my-mock-subject-vettings',
        ];

        foreach ($permissions as $permission) {
            $title = 'My my-mock-subject-vettings';

            if (str_contains($permission, 'my-subject-vettings')) {
                $title = 'my-mock-subject-vettings';
            }
            Permission::updateOrCreate(
                ['name' => $permission, 'guard_name' => 'web'], // Match by name and guard
                ['title' => $title] // Update or set the title
            );
        }
    }
}
