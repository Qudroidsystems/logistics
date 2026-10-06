<?php
// database/seeders/UpdatedAdminScoreEntryPermissionTableSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class UpdatedAdminScoreEntryPermissionTableSeeder extends Seeder
{
    public function run(): void
    {
        // ONLY NEW permissions that exist in AdminScoreEntryController but NOT in AdminScoreEntryPermissionSeeder
        $newPermissions = [
            'View teacher-subject-list' => 'View Teacher Subject List',
            'Manage admin-student-results' => 'Manage Student Results',
        ];

        $this->command->info('Adding new admin score entry permissions from AdminScoreEntryController...');

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

        $this->command->info('✅ UpdatedAdminScoreEntryPermissionTableSeeder completed!');
    }
}