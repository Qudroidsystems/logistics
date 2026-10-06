<?php
// database/seeders/ExamTimetablePermissionTableSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class ExamTimetablePermissionTableSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Exam Timetable View Permissions
            'View exam timetable',
            'View exam details',
            'View exam slots',

            // Exam Timetable CRUD Permissions
            'Create exam timetable',
            'Edit exam timetable',
            'Delete exam timetable',

            // Exam Slot Permissions
            'Add exam slots',
            'Edit exam slots',
            'Delete exam slots',

            // Exam Timetable Management
            'Publish exam timetable',
            'Archive exam timetable',
            'Clone exam timetable',

            // Exam Related Permissions
            'View exam results',
            'Enter exam results',
            'Edit exam results',

            // Exam Report Permissions
            'Export exam timetable',
            'Print exam timetable',
            'Generate exam reports',
        ];

        foreach ($permissions as $permission) {
            $title = 'Exam Timetable Management';

            if (str_contains($permission, 'slots')) {
                $title = 'Exam Slots';
            } elseif (str_contains($permission, 'results')) {
                $title = 'Exam Results';
            } elseif (str_contains($permission, 'reports')) {
                $title = 'Exam Reports';
            } elseif (str_contains($permission, 'Publish')) {
                $title = 'Publish Exam Timetable';
            } elseif (str_contains($permission, 'Archive')) {
                $title = 'Archive Exam Timetable';
            } elseif (str_contains($permission, 'Clone')) {
                $title = 'Clone Exam Timetable';
            } elseif (str_contains($permission, 'Create')) {
                $title = 'Create Exam Timetable';
            } elseif (str_contains($permission, 'Edit')) {
                $title = 'Edit Exam Timetable';
            } elseif (str_contains($permission, 'Delete')) {
                $title = 'Delete Exam Timetable';
            } elseif (str_contains($permission, 'View exam timetable')) {
                $title = 'View Exam Timetable';
            }

            Permission::updateOrCreate(
                ['name' => $permission, 'guard_name' => 'web'],
                ['title' => $title]
            );
        }
    }
}
