<?php
// database/seeders/TimetablePermissionTableSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class TimetablePermissionTableSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Core Timetable Permissions
            'View timetable',
            'Create timetable',
            'Edit timetable',
            'Delete timetable',
            'Generate timetable',

            // Teacher Specific
            'View my timetable',

            // Management Permissions
            'Manage timetable settings',
            'Manage timetable constraints',
            'Manage teacher availability',

            // Substitute Permissions
            'Request substitute',
            'Approve substitute',
            'View substitute requests',

            // Report Permissions
            'View timetable reports',
            'Export timetable',

            // Conflict & Notification
            'Check timetable conflicts',
            'Send timetable notifications',
        ];

        foreach ($permissions as $permission) {
            $title = 'Timetable Management';

            if (str_contains($permission, 'my timetable')) {
                $title = 'My Timetable';
            } elseif (str_contains($permission, 'timetable settings')) {
                $title = 'Timetable Settings';
            } elseif (str_contains($permission, 'timetable constraints')) {
                $title = 'Timetable Constraints';
            } elseif (str_contains($permission, 'substitute')) {
                $title = 'Substitute Management';
            } elseif (str_contains($permission, 'reports') || str_contains($permission, 'Export')) {
                $title = 'Timetable Reports';
            } elseif (str_contains($permission, 'conflicts')) {
                $title = 'Conflict Checking';
            } elseif (str_contains($permission, 'notifications')) {
                $title = 'Timetable Notifications';
            } elseif (str_contains($permission, 'teacher availability')) {
                $title = 'Teacher Availability';
            } elseif (str_contains($permission, 'Generate')) {
                $title = 'Timetable Generation';
            }

            Permission::updateOrCreate(
                ['name' => $permission, 'guard_name' => 'web'],
                ['title' => $title]
            );
        }
    }
}
