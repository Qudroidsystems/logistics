<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class SchoolPaymentPermissionTableSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'View school-payment',
            'Create school-payment',
            'Delete school-payment',
        ];

        foreach ($permissions as $permission) {
            $title = 'School Payment Management';

            Permission::updateOrCreate(
                ['name' => $permission, 'guard_name' => 'web'],
                ['title' => $title]
            );
        }
    }
}