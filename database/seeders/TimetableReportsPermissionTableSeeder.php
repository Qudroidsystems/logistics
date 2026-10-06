<?php
// database/seeders/TimetableReportsPermissionTableSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class TimetableReportsPermissionTableSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Report View Permissions
            'View timetable reports',
            'View report list',
            'View report details',

            // Report Generation Permissions
            'Generate timetable reports',
            'Schedule reports',
            'Export reports',

            // Report Management Permissions
            'Delete timetable reports',
            'Share reports',
            'Download reports',

            // Specific Report Types
            'View teacher workload report',
            'View room utilization report',
            'View class schedule report',
            'View conflict analysis report',
            'View subject distribution report',

            // Report Settings
            'Configure report settings',
            'Set report schedules',
        ];

        foreach ($permissions as $permission) {
            $title = 'Timetable Reports';

            if (str_contains($permission, 'teacher workload')) {
                $title = 'Teacher Workload Report';
            } elseif (str_contains($permission, 'room utilization')) {
                $title = 'Room Utilization Report';
            } elseif (str_contains($permission, 'class schedule')) {
                $title = 'Class Schedule Report';
            } elseif (str_contains($permission, 'conflict analysis')) {
                $title = 'Conflict Analysis Report';
            } elseif (str_contains($permission, 'subject distribution')) {
                $title = 'Subject Distribution Report';
            } elseif (str_contains($permission, 'Generate')) {
                $title = 'Generate Reports';
            } elseif (str_contains($permission, 'Export')) {
                $title = 'Export Reports';
            } elseif (str_contains($permission, 'Delete')) {
                $title = 'Delete Reports';
            } elseif (str_contains($permission, 'Schedule')) {
                $title = 'Schedule Reports';
            } elseif (str_contains($permission, 'View timetable reports')) {
                $title = 'View Reports';
            }

            Permission::updateOrCreate(
                ['name' => $permission, 'guard_name' => 'web'],
                ['title' => $title]
            );
        }
    }
}
