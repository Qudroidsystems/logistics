<?php
// database/seeders/UpdatedFinancePermissionTableSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class UpdatedFinancePermissionTableSeeder extends Seeder
{
    public function run(): void
    {
        // ONLY NEW permissions from PaymentController that aren't in existing seeders
        $newPermissions = [
            'View payment' => 'View Payment',
            'Create payment' => 'Create Payment',
            'Process payment' => 'Process Payment',
            'Reverse payment' => 'Reverse Payment',
            'View invoice' => 'View Invoice',
            'Generate invoice' => 'Generate Invoice',
        ];

        $this->command->info('Adding new finance/payment permissions from controllers...');

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

        $this->command->info('✅ UpdatedFinancePermissionTableSeeder completed!');
    }
}