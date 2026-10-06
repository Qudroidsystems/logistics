<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class StudentPaymentPermissionTableSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'View student payments',
        ];

        foreach ($permissions as $permission) {
            $title = 'Student Payment Management';

            Permission::updateOrCreate(
                ['name' => $permission, 'guard_name' => 'web'], // Match by name and guard
                ['title' => $title] // Update or set the title
            );
        }
    }
}
