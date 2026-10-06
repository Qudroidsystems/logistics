<?php
// database/seeders/FinancePermissionSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class FinancePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Bank Reconciliation
            'View reconciliation',
            'Perform reconciliation',

            // Petty Cash
            'View petty cash',
            'Create petty cash',
            'Approve petty cash',
            'Reimburse petty cash',

            // Audit Trail
            'View audit trail',
            'Export audit trail',

            // Chart of Accounts
            'View chart of accounts',
            'Create account',
            'Update account',
            'Delete account',

            // Journal Entries
            'View journal entries',
            'Create journal entry',
            'Post journal entry',
            'Reverse journal entry',
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission, 'guard_name' => 'web'],
                ['title' => 'Finance Management']  // Removed 'module' field
            );
        }

        $this->command->info('✅ Finance permissions seeded successfully!');
    }
}
