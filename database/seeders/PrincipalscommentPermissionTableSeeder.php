<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PrincipalscommentPermissionTableSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'View principals-comment',
            'Show principals-comment',
            'Create principals-comment',
            'Update principals-comment',
            'Delete principals-comment',
  
        ];

        foreach ($permissions as $permission) {
            $title = 'principal comment Management';

            if (str_contains($permission, 'principals-comment')) {
                $title = 'principal comment Management  Management';
            }
            Permission::updateOrCreate(
                ['name' => $permission, 'guard_name' => 'web'], // Match by name and guard
                ['title' => $title] // Update or set the title
            );
        }
    }
}
