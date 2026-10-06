<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class ReportApprovalPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'View report-approvals', // see the approval board for all classes
            'Submit report cards',   // class teachers submit their own class
            'Approve report cards',  // principal: approve / return / settings
        ];

        foreach ($permissions as $permission) {
            $title = 'Report Card Approval';

            if (str_contains($permission, 'report')) {
                $title = 'Report Card Approval';
            }
            Permission::updateOrCreate(
                ['name' => $permission, 'guard_name' => 'web'], // Match by name and guard
                ['title' => $title] // Update or set the title
            );
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
