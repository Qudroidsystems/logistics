<?php
// database/seeders/UpdatedTimetableReportsPermissionTableSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class UpdatedTimetableReportsPermissionTableSeeder extends Seeder
{
    public function run(): void
    {
        // NEW permissions that exist in the controller but NOT in the original seeder
        $newPermissions = [
            'View own timetable reports' => 'View Own Timetable Reports',
            'Generate own timetable reports' => 'Generate Own Timetable Reports',
        ];

        $this->command->info('Adding new timetable reports permissions...');

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

        $this->command->info('✅ UpdatedTimetableReportsPermissionTableSeeder completed!');
    }
}