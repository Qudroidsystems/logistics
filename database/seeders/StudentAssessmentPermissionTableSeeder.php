<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class StudentAssessmentPermissionTableSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'View student assessments',
        ];

        foreach ($permissions as $permission) {
            $title = 'Student Assessment Management';

            Permission::updateOrCreate(
                ['name' => $permission, 'guard_name' => 'web'],
                ['title' => $title]
            );
        }
    }
}