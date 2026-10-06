<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class CertificatePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'Manage certificate templates', // design/create/edit/delete templates
            'Generate certificates',        // create/print a student's certificate
            'Approve certificates',         // approve a draft for issuing
            'Revoke certificates',          // void an issued certificate
            'Override certificate limit',   // exceed the per-student generation lock
            'View certificate audit',       // see logs / all certificates
        ];
        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission, 'guard_name' => 'web'],
                ['title' => 'Certificates']
            );
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
