<?php
// database/seeders/AdminScoreEntryPermissionSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AdminScoreEntryPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Admin score entry permissions
        $permissions = [
            'View admin-score-entry',
            'Create admin-score-entry',
            'Update admin-score-entry',
            'Delete admin-score-entry',
            'View teacher-subject-list',
            'Manage admin-student-results', // ADD THIS NEW PERMISSION
        ];


        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission, 'guard_name' => 'web'],
                ['title' => $titles[$permission] ?? 'Admin Score Entry Management']
            );
        }


    }
}
