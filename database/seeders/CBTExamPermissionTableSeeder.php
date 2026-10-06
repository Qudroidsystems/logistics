<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class CBTExamPermissionTableSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'View cbt-exam',
            'Take cbt-exam',
            'Submit cbt-exam',
        ];

        foreach ($permissions as $permission) {
            $title = 'CBT Exam Management';

            Permission::updateOrCreate(
                ['name' => $permission, 'guard_name' => 'web'],
                ['title' => $title]
            );
        }
    }
}