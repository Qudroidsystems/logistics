<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class StaffAttendancePermissionTableSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
          

            // Device PIN mappings (biometric device integration)
            'View device-mappings',
            'Create device-mappings',
            'Update device-mappings',
            'Delete device-mappings',

            // Staff attendance (device-driven, read-only — no manual write path)
            'View staff-attendance',
            'View staff-attendance-report',
            'View staff-attendance-school-report',

            // Device outage management (kept separate from general staff-attendance access)
            'View device-outages',
            'Create device-outages',
            'Delete device-outages',
        ];

        foreach ($permissions as $permission) {
            $title = 'Attendance Management';

            if (str_contains($permission, 'device-mappings')) {
                $title = 'Device Mapping Management';
            } elseif (str_contains($permission, 'device-outages')) {
                $title = 'Device Outage Management';
            } elseif (str_contains($permission, 'staff-attendance')) {
                $title = 'Staff Attendance Management';
            }

            Permission::updateOrCreate(
                ['name' => $permission, 'guard_name' => 'web'],
                ['title' => $title]
            );
        }
    }
}
