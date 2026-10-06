<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class ParentPermissionTableSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'View parent',
            'Create parent',
            'Update parent',
            'Delete parent',
        ];

        foreach ($permissions as $permission) {
            $title = 'Parent Management';

            Permission::updateOrCreate(
                ['name' => $permission, 'guard_name' => 'web'],
                ['title' => $title]
            );
        }
    }
}