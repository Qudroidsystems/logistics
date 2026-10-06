<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class CurriculumPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'Manage topics', // build the syllabus / scheme, assign reps
            'Verify topics', // HOD verification & comments
            'Track topics',  // teachers mark delivery on their board
        ];
        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission, 'guard_name' => 'web'],
                ['title' => 'Curriculum']
            );
        }

        // A narrow role for class reps (confirmation flow is gated by class_reps membership).
        Role::firstOrCreate(['name' => 'Class Rep', 'guard_name' => 'web']);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
