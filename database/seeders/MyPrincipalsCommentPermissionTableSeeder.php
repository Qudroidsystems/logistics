<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class MyPrincipalsCommentPermissionTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // Staff / Principal side - "My" permissions
            'View my-principals-comment',
            'Update my-principals-comment',
        ];

        // Optional: You can give them a nice display title
        $titles = [
            'View my-principals-comment'    => 'My Principal\'s Comment Assignments',
            'Update my-principals-comment'  => 'My Principal\'s Comment Assignments',
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission, 'guard_name' => 'web'],
                [
                    'title' => $titles[$permission] ?? 'My Principal\'s Comment Management',
                ]
            );
        }
    }
}