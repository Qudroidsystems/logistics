<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class ViewClassPermissionTableSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
           'View my-class-student',
        ];

        foreach ($permissions as $permission) {
            $title = 'Student Management';

            if (str_contains($permission, 'my-class-student')) {
                $title = 'View Staff Class  Management';
            }
            Permission::updateOrCreate(
                ['name' => $permission, 'guard_name' => 'web'], // Match by name and guard
                ['title' => $title] // Update or set the title
            );
        }
    }
}
