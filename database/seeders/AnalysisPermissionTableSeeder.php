<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class AnalysisPermissionTableSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'View analysis',
            'Export analysis',
        ];

        foreach ($permissions as $permission) {
            $title = 'Analysis Management';

            Permission::updateOrCreate(
                ['name' => $permission, 'guard_name' => 'web'],
                ['title' => $title]
            );
        }
    }
}