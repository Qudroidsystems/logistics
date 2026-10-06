<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class SubjectClassResultRoomOperationPermissionTableSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
           'View subject-operation',
           'Create subject-operation',
           'Update subject-operation',
           'Delete subject-operation',

           'View class-operation',
           'Create class-operation',
           'Update class-operation',
           'Delete class-operation',

           'View myresult-room',
           'Create myresult-room',
           'Update myresult-room',
           'Delete myresult-room',
        ];

        foreach ($permissions as $permission) {
            $title = 'Student Management';

            if (str_contains($permission, 'subject-operation')) {
                $title = 'Subject Operation  Management';
            }elseif (str_contains($permission, 'class-operation')) {
                $title = 'Class Operation  Management';
            }elseif (str_contains($permission, 'myresult-room')) {
                $title = 'Records Operation  Management';
            }

            Permission::updateOrCreate(
                ['name' => $permission, 'guard_name' => 'web'], // Match by name and guard
                ['title' => $title] // Update or set the title
            );
        }
    }
}
