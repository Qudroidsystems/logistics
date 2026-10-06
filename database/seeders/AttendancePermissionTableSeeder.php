<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class AttendancePermissionTableSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Teacher-facing
            'View attendance-register',
            'Create attendance-register',
            'Update attendance-register',
            'Delete attendance-register',

            // Reports
            'View attendance-report',
            'View attendance-class-summary',
            'View attendance-student-report',

            // Admin settings
            'View attendance-settings',
            'Create attendance-settings',
            'Update attendance-settings',
            'Delete attendance-settings',

            // Holidays
            'View attendance-holidays',
            'Create attendance-holidays',
            'Update attendance-holidays',
            'Delete attendance-holidays',

            // School-wide report
            'View attendance-school-report',
        ];

        foreach ($permissions as $permission) {
            $title = 'Attendance Management';

            if (str_contains($permission, 'attendance-settings') || str_contains($permission, 'attendance-holidays')) {
                $title = 'Attendance Admin Management';
            } elseif (str_contains($permission, 'attendance-report') || str_contains($permission, 'attendance-school-report')) {
                $title = 'Attendance Reports Management';
            } elseif (str_contains($permission, 'attendance-register')) {
                $title = 'Attendance Register Management';
            }

            Permission::updateOrCreate(
                ['name' => $permission, 'guard_name' => 'web'],
                ['title' => $title]
            );
        }
    }
}
