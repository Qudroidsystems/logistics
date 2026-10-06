<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class LessonNotePermissionSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            'Write lesson notes',   // teachers: create/edit/submit/deliver their own
            'Review lesson notes',  // HOD: approve / return
            'Manage teaching methods', // admin: methods library
        ] as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission, 'guard_name' => 'web'],
                ['title' => 'Lesson Notes']
            );
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
