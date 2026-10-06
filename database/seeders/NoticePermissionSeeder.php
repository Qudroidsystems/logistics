<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class NoticePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'View notices',                  // list notices + delivery reports
            'Create notices',                // create, send, schedule, resend, cancel
            'Manage notification settings',  // SMS / WhatsApp / email provider keys
        ];

        foreach ($permissions as $permission) {
            $title = 'School Notices';

            if (str_contains($permission, 'notification settings')) {
                $title = 'School Notices';
            }
            Permission::updateOrCreate(
                ['name' => $permission, 'guard_name' => 'web'], // Match by name and guard
                ['title' => $title] // Update or set the title
            );
        }
    }
}
