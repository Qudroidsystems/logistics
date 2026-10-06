<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class MyClassMySubjectPermissionTableSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
           'View my-class',
           'View my-subject',

        ];

        foreach ($permissions as $permission) {
            $title = 'Student Management';

            if (str_contains($permission, 'my-class')) {
                $title = 'Staff Class  Management';
            } elseif (str_contains($permission, 'my-subject')) {
                $title = 'Staff Subject  Management';
            }

            Permission::updateOrCreate(
                ['name' => $permission, 'guard_name' => 'web'], // Match by name and guard
                ['title' => $title] // Update or set the title
            );
        }
    }
}
