<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class ManagementDashboardPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'View management dashboard', // principal / proprietor overview
        ];

        foreach ($permissions as $permission) {
            $title = 'Management Dashboard';

            if (str_contains($permission, 'management')) {
                $title = 'Management Dashboard';
            }
            Permission::updateOrCreate(
                ['name' => $permission, 'guard_name' => 'web'], // Match by name and guard
                ['title' => $title] // Update or set the title
            );
        }
    }
}
