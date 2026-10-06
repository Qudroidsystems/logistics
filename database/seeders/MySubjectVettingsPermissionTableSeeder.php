<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class MySubjectVettingsPermissionTableSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'View my-subject-vettings',
        ];

        foreach ($permissions as $permission) {
            $title = 'My Subject Vettings Management';

            if (str_contains($permission, 'my-subject-vettings')) {
                $title = 'My Subject Vettings Management';
            }
            Permission::updateOrCreate(
                ['name' => $permission, 'guard_name' => 'web'], // Match by name and guard
                ['title' => $title] // Update or set the title
            );
        }
    }
}
