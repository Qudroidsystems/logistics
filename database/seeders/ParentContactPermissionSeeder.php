<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class ParentContactPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'Manage parent contacts', // parent contact clean-up page, CSV import/export
        ];

        foreach ($permissions as $permission) {
            $title = 'Parent Contacts';

            if (str_contains($permission, 'parent contacts')) {
                $title = 'Parent Contacts';
            }
            Permission::updateOrCreate(
                ['name' => $permission, 'guard_name' => 'web'], // Match by name and guard
                ['title' => $title] // Update or set the title
            );
        }
    }
}
