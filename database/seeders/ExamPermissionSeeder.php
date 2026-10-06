<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Phase 4 — Exams permissions.
 *   • Write exam papers  — teachers build & submit papers, enter scores.
 *   • Vet exam papers    — HOD / Exam Officer review, approve, lock.
 *   • Manage exam bank   — curate the reusable question bank.
 */
class ExamPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'Write exam papers',
            'Vet exam papers',
            'Manage exam bank',
        ];
        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission, 'guard_name' => 'web'],
                ['title' => 'Exams']
            );
        }

        // Super Admin keeps every permission.
        try {
            $super = Role::where('name', 'Super Admin')->where('guard_name', 'web')->first();
            if ($super) {
                $super->givePermissionTo($permissions);
            }
        } catch (\Throwable $e) {
            // non-fatal
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
