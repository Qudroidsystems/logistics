<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class OnlineFeePaymentPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'View online-fee-payments',   // bursary list + receipts
            'Create online-fee-payments', // pay online on a student's behalf
            'Update online-fee-payments', // re-check a transaction with Paystack
        ];

        foreach ($permissions as $permission) {
            $title = 'Online Fee Payments';

            if (str_contains($permission, 'online-fee-payments')) {
                $title = 'Online Fee Payments';
            }
            Permission::updateOrCreate(
                ['name' => $permission, 'guard_name' => 'web'], // Match by name and guard
                ['title' => $title] // Update or set the title
            );
        }
    }
}
