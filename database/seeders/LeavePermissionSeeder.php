<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class LeavePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'Recommend leave', // HOD - recommend leave for own department
            'Approve leave', // principal - final leave approval
            'View leave records', // see everyone's leave
            'Manage leave types', // leave types and balance adjustments
        ];

        foreach ($permissions as $permission) {
            $title = 'Staff Leave';

            Permission::updateOrCreate(
                ['name' => $permission, 'guard_name' => 'web'], // Match by name and guard
                ['title' => $title] // Update or set the title
            );
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
