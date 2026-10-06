<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class StudentMockReportPermissionTableSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'View student-mock-report',
            'Show student-mock-report',
            'Create student-mock-report',
            'Update student-mock-report',
            'Delete student-mock-report',
        ];

        foreach ($permissions as $permission) {
            $title = 'Student Mock Report Management';

            if (str_contains($permission, 'student-mock-report')) {
                $title = 'Student Mock Report Management';
            }
            Permission::updateOrCreate(
                ['name' => $permission, 'guard_name' => 'web'], // Match by name and guard
                ['title' => $title] // Update or set the title
            );
        }
    }
}
