<?php
// database/seeders/UpdatedScholarshipPermissionTableSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class UpdatedScholarshipPermissionTableSeeder extends Seeder
{
    public function run(): void
    {
        // ONLY NEW permissions from ScholarshipController, DiscountController, SiblingGroupController
        $newPermissions = [
            // From ScholarshipController
            'Approve scholarship' => 'Approve Scholarship',
            'Revoke scholarship' => 'Revoke Scholarship',

            // From SiblingGroupController (NEW)
            'View sibling groups' => 'Sibling Group Management',
            'Create sibling group' => 'Sibling Group Management',
            'Update sibling group' => 'Sibling Group Management',
            'Delete sibling group' => 'Sibling Group Management',
            'Apply sibling discount' => 'Apply Sibling Discount',

            // From DiscountController
            'Approve discount' => 'Approve Discount',
        ];

        $this->command->info('Adding new scholarship/discount permissions from controllers...');

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

        $this->command->info('✅ UpdatedScholarshipPermissionTableSeeder completed!');
    }
}