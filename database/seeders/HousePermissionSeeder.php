<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class HousePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'Award house points', // sports masters / staff who add or deduct house points
        ];

        foreach ($permissions as $permission) {
            $title = 'School House';

            if (str_contains($permission, 'house')) {
                $title = 'School House';
            }
            Permission::updateOrCreate(
                ['name' => $permission, 'guard_name' => 'web'], // Match by name and guard
                ['title' => $title] // Update or set the title
            );
        }
    }
}
