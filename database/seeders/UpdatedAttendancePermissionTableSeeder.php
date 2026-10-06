<?php
// database/seeders/UpdatedAttendancePermissionTableSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class UpdatedAttendancePermissionTableSeeder extends Seeder
{
    public function run(): void
    {
        // ONLY NEW permissions that exist in controllers but NOT in AttendancePermissionTableSeeder
        $newPermissions = [
            'View attendance-report' => 'Attendance Reports',
            'View attendance-class-summary' => 'Attendance Class Summary',
            'View attendance-student-report' => 'Attendance Student Report',
            'Update attendance-register' => 'Attendance Register Management',
            'Delete attendance-register' => 'Attendance Register Management',
            'Update attendance-settings' => 'Attendance Admin Management',
            'Delete attendance-settings' => 'Attendance Admin Management',
            'Update attendance-holidays' => 'Attendance Admin Management',
            'Delete attendance-holidays' => 'Attendance Admin Management',
            'View attendance-school-report' => 'Attendance School Report',
        ];

        $this->command->info('Adding new attendance permissions from AttendanceController...');

        foreach ($newPermissions as $permission => $title) {
            $existing = Permission::where('name', $permission)
                ->where('guard_name', 'web')
                ->first();

            if ($existing) {
                $existing->update(['title' => $title]);
                $this->command->info("✓ Updated permission: {$permission}");
            } else {
                Permission::create([
                    'name' => $permission,
                    'guard_name' => 'web',
                    'title' => $title,
                ]);
                $this->command->info("✓ Created permission: {$permission}");
            }
        }

        $this->command->info('✅ UpdatedAttendancePermissionTableSeeder completed!');
    }
}