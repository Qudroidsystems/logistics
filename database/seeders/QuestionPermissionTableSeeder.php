<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class QuestionPermissionTableSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'View question',
            'Create question',
            'Update question',
            'Delete question',
        ];

        foreach ($permissions as $permission) {
            $title = 'Question Management';

            Permission::updateOrCreate(
                ['name' => $permission, 'guard_name' => 'web'],
                ['title' => $title]
            );
        }
    }
}