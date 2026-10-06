<?php
// database/seeders/FinancialReportPermissionSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class FinancialReportPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'View balance sheet',
            'View income statement',
            'View cash flow',
            'View trial balance',
            'Export financial reports',
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission, 'guard_name' => 'web'],
                ['title' => 'Financial Reports']
            );
        }

        $this->command->info('✅ Financial report permissions seeded successfully!');
    }
}
