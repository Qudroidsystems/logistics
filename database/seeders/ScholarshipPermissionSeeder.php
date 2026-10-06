<?php
// database/seeders/ScholarshipPermissionSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class ScholarshipPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Scholarship Permissions
            'View scholarship',
            'Create scholarship',
            'Update scholarship',
            'Delete scholarship',
            'Approve scholarship',
            'Revoke scholarship',

            // Discount Permissions
            'View discount',
            'Create discount',
            'Update discount',
            'Delete discount',
            'Approve discount',

            // Payment Permissions
            'View payment',
            'Create payment',
            'Process payment',
            'Reverse payment',
            'View invoice',
            'Generate invoice',

            // Financial Report Permissions
            'View financial reports',
            'Export financial reports',
            'View balance sheet',
            'View income statement',
            'View trial balance',
            'View cash flow',
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission, 'guard_name' => 'web'],
                ['title' => 'Scholarship & Payment Management']
            );
        }

        $this->command->info('✅ Scholarship permissions seeded successfully!');
    }
}
