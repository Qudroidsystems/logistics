<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class SchoolInformationPermissionTableSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
           'View schoolinformation',
           'Create schoolinformation',
           'Update schoolinformation',
           'Delete schoolinformation',
        ];

        foreach ($permissions as $permission) {
            $title = 'Student Management';

            if (str_contains($permission, 'schoolinformation')) {
                $title = 'School Information Management';
                $desciption = 'Basic School Information which will appear on the portal and documents from the portal';
            }
            Permission::updateOrCreate(
                ['name' => $permission, 'guard_name' => 'web'], // Match by name and guard
                ['title' => $title], // Update or set the title
                ['description' => $title] // Update or set the title
            );
        }
    }
}
