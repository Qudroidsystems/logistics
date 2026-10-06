<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class InstalmentPlanPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'View instalment-plans',   // see plans and who is behind
            'Manage instalment-plans', // create/edit plans, add/remove students
        ];

        foreach ($permissions as $permission) {
            $title = 'Instalment Plans';

            if (str_contains($permission, 'instalment-plans')) {
                $title = 'Instalment Plans';
            }
            Permission::updateOrCreate(
                ['name' => $permission, 'guard_name' => 'web'], // Match by name and guard
                ['title' => $title] // Update or set the title
            );
        }
    }
}
