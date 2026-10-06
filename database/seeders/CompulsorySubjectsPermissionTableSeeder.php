<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class CompulsorySubjectsPermissionTableSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'View compulsory-subject',
            'Show compulsory-subject',
            'Create compulsory-subject',
            'Update compulsory-subject',
            'Delete compulsory-subject',
        ];

        foreach ($permissions as $permission) {
            $title = 'Compulsory Subject Management';

            if (str_contains($permission, 'compulsory-subject')) {
                $title = 'Compulsory Subject Management';
            }
            Permission::updateOrCreate(
                ['name' => $permission, 'guard_name' => 'web'], // Match by name and guard
                ['title' => $title] // Update or set the title
            );
        }
    }
}
