<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PaymentGatewayPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'Manage payment gateways', // enter/test gateway keys, switch Sandbox/Live
        ];

        foreach ($permissions as $permission) {
            $title = 'Payment Gateway Settings';

            if (str_contains($permission, 'payment gateways')) {
                $title = 'Payment Gateway Settings';
            }
            Permission::updateOrCreate(
                ['name' => $permission, 'guard_name' => 'web'], // Match by name and guard
                ['title' => $title] // Update or set the title
            );
        }
    }
}
