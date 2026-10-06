<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class ResultAccessPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'View result-access',
            'Update result-access',
        ];

        foreach ($permissions as $permission) {
            $title = 'Result Access Control';

            if (str_contains($permission, 'result-access')) {
                $title = 'Result Access Control';
            }
            Permission::updateOrCreate(
                ['name' => $permission, 'guard_name' => 'web'], // Match by name and guard
                ['title' => $title] // Update or set the title
            );
        }
    }
}