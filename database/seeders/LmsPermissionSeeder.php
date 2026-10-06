<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class LmsPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'Manage courses',   // full CRUD over any course, content and enrolment
            'Grade coursework', // teachers: manage own course, grade, gradebook
        ];
        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission, 'guard_name' => 'web'],
                ['title' => 'E-Learning']
            );
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
