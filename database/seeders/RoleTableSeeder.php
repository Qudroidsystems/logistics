<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Back-office roles from the platform spec (section 71). Each role gets a
 * starting permission set by group; admins fine-tune it in the role editor.
 * Re-running adds missing permissions but never removes ones an admin granted.
 */
class RoleTableSeeder extends Seeder
{
    /** role => [badge class, permission groups, extra individual permissions] */
    protected function roles(): array
    {
        return [
            'Super Admin'        => ['badge bg-success',   '*', []],
            'Operations Manager' => ['badge bg-primary',   ['Dashboard', 'Customers', 'Drivers', 'Shoppers', 'Deliveries', 'Shopping', 'Dispatch', 'Tracking', 'Fleet', 'Warehouses', 'Zones & Pricing', 'Reports'], ['View activity log', 'View online staff']],
            'Dispatcher'         => ['badge bg-info',      ['Dispatch', 'Tracking'], ['dashboard', 'View delivery', 'Update delivery', 'Cancel delivery', 'Reschedule delivery', 'View driver', 'View driver locations', 'View customer', 'View support ticket']],
            'Support Agent'      => ['badge bg-warning',   ['Support'], ['dashboard', 'View delivery', 'View tracking', 'View customer', 'View driver', 'View shopper', 'View payment']],
            'Finance Manager'    => ['badge bg-success',   ['Payments'], ['dashboard', 'View financial report', 'Export reports', 'View delivery', 'View driver earnings', 'View vendor']],
            'Vendor Manager'     => ['badge bg-secondary', ['Vendors', 'Stores', 'Businesses'], ['dashboard', 'View delivery', 'View kyc', 'View vendor']],
            'Driver Manager'     => ['badge bg-secondary', ['Drivers', 'Fleet'], ['dashboard', 'View kyc', 'Approve kyc', 'Reject kyc', 'View live map', 'View delivery']],
            'Shopper Manager'    => ['badge bg-secondary', ['Shoppers'], ['dashboard', 'View kyc', 'Approve kyc', 'Reject kyc', 'View shopping request', 'Approve substitution', 'View delivery']],
            'Compliance Officer' => ['badge bg-danger',    ['Compliance'], ['dashboard', 'View driver', 'View shopper', 'View vendor', 'View store', 'View dispute']],
            'Warehouse Manager'  => ['badge bg-dark',      ['Warehouses'], ['dashboard', 'View delivery', 'View tracking', 'View driver']],
            'Analyst'            => ['badge bg-light text-dark', ['Reports'], ['dashboard', 'View delivery', 'View payment', 'View live map']],
        ];
    }

    public function run(): void
    {
        $catalogue = PermissionTableSeeder::catalogue();

        foreach ($this->roles() as $name => [$badge, $groups, $extra]) {
            $role = Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
            if (\Illuminate\Support\Facades\Schema::hasColumn('roles', 'badge') && !$role->badge) {
                $role->forceFill(['badge' => $badge])->save();
            }

            $perms = $groups === '*'
                ? Permission::pluck('name')->all()
                : array_merge($extra, ...array_map(fn ($g) => $catalogue[$g] ?? [], $groups));

            $role->givePermissionTo(Permission::whereIn('name', array_unique($perms))->get());
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
