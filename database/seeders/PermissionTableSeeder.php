<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Permission catalogue. `title` is the group shown in the role editor.
 * Safe to re-run: permissions are matched by name and never duplicated.
 *
 * Naming convention: "<Verb> <noun>" (View / Create / Update / Delete / Manage /
 * Approve / Assign ...) — PermissionMeta turns these into plain-English text.
 */
class PermissionTableSeeder extends Seeder
{
    public static function catalogue(): array
    {
        $crud = fn (string $noun) => ["View $noun", "Create $noun", "Update $noun", "Delete $noun"];

        return [
            'Dashboard' => ['dashboard'],

            'Users & Access' => array_merge(
                $crud('user'),
                $crud('role'), ['Add user-role', 'Update user-role', 'Remove user-role'],
                $crud('permission')
            ),

            'Customers'  => array_merge($crud('customer'), ['Suspend customer', 'View customer wallet']),
            'Drivers'    => array_merge($crud('driver'), ['Approve driver', 'Suspend driver', 'View driver earnings', 'View driver locations']),
            'Shoppers'   => array_merge($crud('shopper'), ['Approve shopper', 'Suspend shopper']),
            'Vendors'    => array_merge($crud('vendor'), ['Approve vendor', 'Suspend vendor', 'Manage vendor pricing', 'Manage vendor commission']),
            'Stores'     => array_merge($crud('store'), ['Approve store', 'Manage store products']),
            'Businesses' => array_merge($crud('business'), ['Approve procurement request']),

            'Deliveries' => array_merge($crud('delivery'), ['Cancel delivery', 'Reschedule delivery', 'Override delivery price', 'Export deliveries']),
            'Shopping'   => array_merge($crud('shopping request'), ['Approve substitution', 'Cancel shopping request']),
            'Dispatch'   => ['View dispatch board', 'Assign driver', 'Reassign driver', 'Override automatic dispatch', 'Manage dispatch rules', 'Manage exceptions'],
            'Tracking'   => ['View live map', 'View tracking', 'Manage proof of delivery'],

            'Fleet'      => array_merge($crud('vehicle'), ['Manage vehicle maintenance', 'Manage fuel logs']),
            'Warehouses' => array_merge($crud('warehouse'), $crud('hub'), $crud('pickup point'), ['Scan packages']),

            'Zones & Pricing' => array_merge($crud('zone'), $crud('service area'), ['Manage pricing rules', 'Manage vehicle types', 'Manage delivery types']),

            'Payments'    => ['View payment', 'Refund payment', 'Manage payment gateways', 'View wallet', 'Adjust wallet', 'Approve withdrawal',
                              'View settlement', 'Run settlement', 'Approve refund', 'View commission', 'Manage commission'],
            'Promotions'  => array_merge($crud('promotion'), $crud('promo code'), ['Manage loyalty program', 'Manage referrals']),

            'Support'     => ['View support ticket', 'Reply support ticket', 'Assign support ticket', 'Close support ticket',
                              'View dispute', 'Resolve dispute', 'Manage chat', 'View rating', 'Moderate rating'],
            'Compliance'  => ['View kyc', 'Approve kyc', 'Reject kyc', 'View audit log', 'Manage fraud flags'],

            'Reports'     => ['View reports', 'View operations report', 'View financial report', 'View geographic report', 'Export reports'],

            'System'      => ['View activity log', 'View online staff', 'Manage maintenance mode', 'Manage feature flags',
                              'Manage backups', 'Manage notifications settings', 'Manage api keys', 'Manage webhooks'],
        ];
    }

    public function run(): void
    {
        foreach (self::catalogue() as $title => $names) {
            foreach ($names as $name) {
                Permission::updateOrCreate(
                    ['name' => $name, 'guard_name' => 'web'],
                    ['title' => $title]
                );
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
