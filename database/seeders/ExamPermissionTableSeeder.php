<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class ExamPermissionTableSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'View exam',
            'Create exam',
            'Update exam',
            'Delete exam',
        ];

        foreach ($permissions as $permission) {
            $title = 'Exam Management';

            Permission::updateOrCreate(
                ['name' => $permission, 'guard_name' => 'web'],
                ['title' => $title]
            );
        }
    }
}