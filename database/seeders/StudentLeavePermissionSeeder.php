<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class StudentLeavePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'Recommend student leave',   // class teacher - recommend leave for own class
            'Approve student leave',     // principal / admin - final approval
            'View student leave records',// see all student leave
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission, 'guard_name' => 'web'],
                ['title' => 'Student Leave']
            );
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
