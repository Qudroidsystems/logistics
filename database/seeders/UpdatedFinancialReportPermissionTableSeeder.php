<?php
// database/seeders/UpdatedFinancialReportPermissionTableSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class UpdatedFinancialReportPermissionTableSeeder extends Seeder
{
    public function run(): void
    {
        // ONLY NEW permissions from FinancialReportController that aren't in existing seeders
        $newPermissions = [
            'View financial reports' => 'Financial Reports',
            'Export financial reports' => 'Export Financial Reports',
        ];

        $this->command->info('Adding new financial report permissions from FinancialReportController...');

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

        $this->command->info('✅ UpdatedFinancialReportPermissionTableSeeder completed!');
    }
}