<?php
// database/seeders/RoomPermissionTableSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class RoomPermissionTableSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Room View Permissions
            'View rooms',
            'View room details',
            'View room bookings',

            // Room CRUD Permissions
            'Create rooms',
            'Edit rooms',
            'Delete rooms',

            // Room Booking Permissions
            'Manage room bookings',
            'Book rooms',
            'Cancel room bookings',
            'Approve room bookings',

            // Room Schedule Permissions
            'View room schedule',
            'Export room schedule',
        ];

        foreach ($permissions as $permission) {
            $title = 'Room Management';

            if (str_contains($permission, 'bookings')) {
                $title = 'Room Bookings';
            } elseif (str_contains($permission, 'schedule')) {
                $title = 'Room Schedule';
            } elseif (str_contains($permission, 'Create')) {
                $title = 'Create Rooms';
            } elseif (str_contains($permission, 'Edit')) {
                $title = 'Edit Rooms';
            } elseif (str_contains($permission, 'Delete')) {
                $title = 'Delete Rooms';
            } elseif (str_contains($permission, 'View rooms')) {
                $title = 'View Rooms';
            }

            Permission::updateOrCreate(
                ['name' => $permission, 'guard_name' => 'web'],
                ['title' => $title]
            );
        }
    }
}
