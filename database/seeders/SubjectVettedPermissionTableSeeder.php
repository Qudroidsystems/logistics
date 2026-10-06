<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class SubjectVettedPermissionTableSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'View subject-vetted',
        ];

        foreach ($permissions as $permission) {
            $title = 'Subject Vetted Management';

            if (str_contains($permission, 'subject-vetted')) {
                $title = 'Subject Vetted Management';
            }
            Permission::updateOrCreate(
                ['name' => $permission, 'guard_name' => 'web'], // Match by name and guard
                ['title' => $title] // Update or set the title
            );
        }
    }
}
