<?php
// database/seeders/UpdatedTimetablePermissionTableSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;

class UpdatedTimetablePermissionTableSeeder extends Seeder
{
    public function run(): void
    {
        // ============================================================
        // ONLY NEW PERMISSIONS THAT WERE ADDED TO THE CONTROLLER
        // ============================================================
        $newPermissions = [
            // ============================================================
            // PUBLISHING PERMISSIONS (NEW)
            // ============================================================
            'Publish timetable' => 'Publish Timetable',

            // ============================================================
            // BULK OPERATIONS (NEW)
            // ============================================================
            'Export whole school timetable' => 'Export Whole School Timetable',
            'Auto-generate whole school' => 'Auto-generate Whole School',

            // ============================================================
            // SUBJECT ASSIGNMENT VIEW (NEW)
            // ============================================================
            'View teacher assignments' => 'View Teacher Assignments',

            // ============================================================
            // ICS CALENDAR EXPORT (NEW - Public but kept for roles)
            // ============================================================
            'Export timetable ICS' => 'Export Timetable ICS',
        ];

        $this->command->info('Adding new timetable permissions...');

        foreach ($newPermissions as $permission => $title) {
            // Check if permission already exists
            $existing = Permission::where('name', $permission)
                ->where('guard_name', 'web')
                ->first();

            if ($existing) {
                // Update existing permission
                $existing->update(['title' => $title]);
                $this->command->info("✓ Updated permission: {$permission}");
            } else {
                // Create new permission
                Permission::create([
                    'name' => $permission,
                    'guard_name' => 'web',
                    'title' => $title,
                ]);
                $this->command->info("✓ Created permission: {$permission}");
            }
        }

        // ============================================================
        // ASSIGN NEW PERMISSIONS TO ROLES
        // ============================================================
        $this->assignNewPermissionsToRoles();

        $this->command->info('✅ UpdatedTimetablePermissionTableSeeder completed successfully!');
    }

    /**
     * Assign new permissions to appropriate roles
     */
    private function assignNewPermissionsToRoles(): void
    {
        // Define which roles get which new permissions
        $rolePermissions = [
            'admin' => [
                'Publish timetable',
                'Export whole school timetable',
                'Auto-generate whole school',
                'View teacher assignments',
                'Export timetable ICS',
            ],
            'principal' => [
                'Publish timetable',
                'Export whole school timetable',
                'View teacher assignments',
                'Export timetable ICS',
            ],
            'teacher' => [
                'Export timetable ICS', // Teachers can export their own ICS feed
            ],
            'exam_officer' => [
                'Export whole school timetable',
                'View teacher assignments',
                'Export timetable ICS',
            ],
        ];

        foreach ($rolePermissions as $roleName => $permissions) {
            $role = \Spatie\Permission\Models\Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);

            // Get the permission models
            $permissionModels = Permission::whereIn('name', $permissions)
                ->where('guard_name', 'web')
                ->get();

            if ($permissionModels->isNotEmpty()) {
                // Assign the new permissions to the role (without removing existing ones)
                $role->givePermissionTo($permissionModels);
                $this->command->info("✓ Assigned new permissions to role: {$roleName}");
            }
        }
    }

    /**
     * Display all timetable permissions (for reference)
     */
    public function getAllTimetablePermissions(): array
    {
        return [
            // ===== OLD PERMISSIONS (Already exist) =====
            'View timetable',
            'Create timetable',
            'Edit timetable',
            'Delete timetable',
            'Generate timetable',
            'View my timetable',
            'Manage timetable settings',
            'Manage timetable constraints',
            'Manage teacher availability',
            'Request substitute',
            'Approve substitute',
            'View substitute requests',
            'View timetable reports',
            'Export timetable',
            'Check timetable conflicts',
            'Send timetable notifications',

            // ===== NEW PERMISSIONS (Added by this seeder) =====
            'Publish timetable',              // NEW
            'Export whole school timetable',  // NEW
            'Auto-generate whole school',     // NEW
            'View teacher assignments',       // NEW
            'Export timetable ICS',           // NEW
        ];
    }
}