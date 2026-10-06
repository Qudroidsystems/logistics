<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PayrollPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'View payroll',               // payroll periods, runs, reports
            'Process payroll',            // calculate a month
            'Approve payroll',            // approve / lock a month
            'Manage salary structures',
            'Manage payroll settings',    // tax bands, pension/NHF rates, limits
            'Manage staff pay profiles',  // bank, TIN, pension details, holds
            'Manage remittances',         // PAYE / pension / NHF payments to government
            'View payslip',
            'Download payslip',
        ];

        foreach ($permissions as $permission) {
            $title = 'Payroll';

            if (str_contains($permission, 'payslip')) {
                $title = 'Payslips';
            }
            Permission::updateOrCreate(
                ['name' => $permission, 'guard_name' => 'web'], // Match by name and guard
                ['title' => $title] // Update or set the title
            );
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
