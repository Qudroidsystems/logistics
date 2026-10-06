<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PromotionPermissionTableSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'View promotion',
            'Update promotion',
        ];

        foreach ($permissions as $permission) {
            $title = 'Student Promotion Management';

            if (str_contains($permission, 'promotion')) {
                $title = 'Student Promotion Management';
            }
            Permission::updateOrCreate(
                ['name' => $permission, 'guard_name' => 'web'], // Match by name and guard
                ['title' => $title] // Update or set the title
            );
        }
    }
}