<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class ResultSendPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'View result-sends',   // see sending history and delivery reports
            'Create result-sends', // send report cards to parents
        ];

        foreach ($permissions as $permission) {
            $title = 'Send Results to Parents';

            if (str_contains($permission, 'result-sends')) {
                $title = 'Send Results to Parents';
            }
            Permission::updateOrCreate(
                ['name' => $permission, 'guard_name' => 'web'], // Match by name and guard
                ['title' => $title] // Update or set the title
            );
        }
    }
}
