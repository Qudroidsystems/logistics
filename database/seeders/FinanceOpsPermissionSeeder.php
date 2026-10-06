<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/** Permissions for salary payouts, loans, cooperative, duty claims, expenses, budgets, assets and accounting. */
class FinanceOpsPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $groups = [
            'Payroll' => ['Release salary payments', 'Approve duty claims'],
            'Staff Loans' => ['Manage staff loans', 'Approve staff loans', 'Manage cooperative'],
            'Expenses' => ['Create expenses', 'Approve expenses', 'Pay expenses', 'Approve purchase requests', 'Manage budgets', 'Manage assets'],
            'Accounting' => ['View financial reports', 'Manage chart of accounts', 'Post journal entries', 'Close accounting period'],
        ];

        foreach ($groups as $title => $permissions) {
            foreach ($permissions as $permission) {
                Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web'], ['title' => $title]);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
