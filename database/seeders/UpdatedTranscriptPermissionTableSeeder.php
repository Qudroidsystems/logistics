<?php
// database/seeders/UpdatedTranscriptPermissionTableSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class UpdatedTranscriptPermissionTableSeeder extends Seeder
{
    public function run(): void
    {
        // ONLY NEW permissions from TranscriptController
        $newPermissions = [
            'View student-transcript' => 'View Student Transcript',
        ];

        $this->command->info('Adding new transcript permissions from TranscriptController...');

        foreach ($newPermissions as $permission => $title) {
            $existing = Permission::where('name', $permission)
                ->where('guard_name', 'web')
                ->first();

            if ($existing) {
                $existing->update(['title' => $title]);
                $this->command->info("✓ Updated permission: {$permission}");
            } else {
                Permission::create([
                    'name' => $permission,
                    'guard_name' => 'web',
                    'title' => $title,
                ]);
                $this->command->info("✓ Created permission: {$permission}");
            }
        }

        $this->command->info('✅ UpdatedTranscriptPermissionTableSeeder completed!');
    }
}