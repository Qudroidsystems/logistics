<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * "Manage broadsheet ranking" — configure the unofficial best-student ranking
 * per section (junior/senior). Does not affect official positions.
 */
class BroadsheetRankingPermissionSeeder extends Seeder
{
    public function run(): void
    {
        Permission::updateOrCreate(
            ['name' => 'Manage broadsheet ranking', 'guard_name' => 'web'],
            ['title' => 'Broadsheet']
        );

        try {
            $super = Role::where('name', 'Super Admin')->where('guard_name', 'web')->first();
            if ($super) {
                $super->givePermissionTo('Manage broadsheet ranking');
            }
        } catch (\Throwable $e) {
            // non-fatal
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
