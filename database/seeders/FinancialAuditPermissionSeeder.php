<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class FinancialAuditPermissionSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['View financial audit', 'Clear audit exceptions'] as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission, 'guard_name' => 'web'],
                ['title' => 'Financial Audit']
            );
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
