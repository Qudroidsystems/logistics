<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class StudentPermissionTableSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'View student',
            'Show student',
            'Create student',
            'Update student',
            'Delete student',
            'student-edit-edit',
            'student-Delete-Delete',
            'Create student-bulk-upload',
            'Create student-bulk-uploadsave',
            'View student-results',
        ];

        foreach ($permissions as $permission) {
            $title = 'Student Management';

            if (str_contains($permission, 'bulk')) {
                $title = 'Student bulk upload Management';
            } elseif (str_contains($permission, 'results')) {
                $title = 'Student result list Management';
            }

            Permission::updateOrCreate(
                ['name' => $permission, 'guard_name' => 'web'], // Match by name and guard
                ['title' => $title] // Update or set the title
            );
        }
    }
}
