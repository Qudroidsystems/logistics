<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class TranscriptPermissionTableSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'View student-transcript',
            'Download student-transcript',
            'Preview student-transcript',
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission, 'guard_name' => 'web'],
                ['title' => 'Transcript Management']
            );
        }
    }
}
