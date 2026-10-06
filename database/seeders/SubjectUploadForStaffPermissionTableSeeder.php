<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class SubjectUploadForStaffPermissionTableSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
           'Update subject-upload-for-staff',
        ];

        foreach ($permissions as $permission) {
            $title = 'Subject Upload For Staff';

            if (str_contains($permission, 'subject-upload-for-staff')) {
                $title = 'Subject Upload For Staff';
            }
            Permission::updateOrCreate(
                ['name' => $permission, 'guard_name' => 'web'], // Match by name and guard
                ['title' => $title] // Update or set the title
            );
        }
    }
}
