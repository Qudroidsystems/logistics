<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class StudentReportPermissionTableSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'View student-report',
            'Show student-report',
            'Create student-report',
            'Update student-report',
            'Delete student-report',
        ];

        foreach ($permissions as $permission) {
            $title = 'Student Report Management';

            if (str_contains($permission, 'student-report')) {
                $title = 'Student Report Management';
            }
            Permission::updateOrCreate(
                ['name' => $permission, 'guard_name' => 'web'], // Match by name and guard
                ['title' => $title] // Update or set the title
            );
        }
    }
}
