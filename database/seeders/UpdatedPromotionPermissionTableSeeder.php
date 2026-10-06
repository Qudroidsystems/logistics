<?php
// database/seeders/UpdatedPromotionPermissionTableSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class UpdatedPromotionPermissionTableSeeder extends Seeder
{
    public function run(): void
    {
        // ONLY NEW permissions from PromotionSettingController
        $newPermissions = [
            'View promotion' => 'View Promotion Settings',
            'Update promotion' => 'Update Promotion Settings',
        ];

        $this->command->info('Adding new promotion permissions from PromotionSettingController...');

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

        $this->command->info('✅ UpdatedPromotionPermissionTableSeeder completed!');
    }
}