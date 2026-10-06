<?php
// database/seeders/HolidayPermissionTableSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class HolidayPermissionTableSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Holiday View Permissions
            'View holidays',
            'View holiday details',

            // Holiday CRUD Permissions
            'Create holidays',
            'Edit holidays',
            'Delete holidays',

            // Holiday Application Permissions
            'Apply holidays to timetable',
            'Bulk apply holidays',

            // Holiday Calendar Permissions
            'View holiday calendar',
            'Export holiday calendar',
        ];

        foreach ($permissions as $permission) {
            $title = 'Holiday Management';

            if (str_contains($permission, 'calendar')) {
                $title = 'Holiday Calendar';
            } elseif (str_contains($permission, 'Apply')) {
                $title = 'Apply Holidays';
            } elseif (str_contains($permission, 'Create')) {
                $title = 'Create Holidays';
            } elseif (str_contains($permission, 'Edit')) {
                $title = 'Edit Holidays';
            } elseif (str_contains($permission, 'Delete')) {
                $title = 'Delete Holidays';
            } elseif (str_contains($permission, 'View holidays')) {
                $title = 'View Holidays';
            }

            Permission::updateOrCreate(
                ['name' => $permission, 'guard_name' => 'web'],
                ['title' => $title]
            );
        }
    }
}
