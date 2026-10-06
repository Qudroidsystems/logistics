<?php
// database/seeders/ExpenseCategorySeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class ExpenseCategorySeeder extends Seeder
{
    public function run(): void
    {
        // Check if table exists
        if (!Schema::hasTable('expense_categories')) {
            $this->command->error('❌ expense_categories table does not exist. Run migrations first.');
            return;
        }

        $categories = [
            // Utilities
            ['code' => 'UTIL-001', 'name' => 'Electricity', 'description' => 'Electricity bills and power supply', 'account_code' => '5010', 'is_active' => true],
            ['code' => 'UTIL-002', 'name' => 'Water', 'description' => 'Water bills and supply', 'account_code' => '5010', 'is_active' => true],
            ['code' => 'UTIL-003', 'name' => 'Internet', 'description' => 'Internet service provider bills', 'account_code' => '5010', 'is_active' => true],
            ['code' => 'UTIL-004', 'name' => 'Telephone', 'description' => 'Phone and communication bills', 'account_code' => '5010', 'is_active' => true],

            // Maintenance
            ['code' => 'MAINT-001', 'name' => 'Building Maintenance', 'description' => 'Repairs and maintenance of school buildings', 'account_code' => '5020', 'is_active' => true],
            ['code' => 'MAINT-002', 'name' => 'Equipment Maintenance', 'description' => 'Maintenance of school equipment and machines', 'account_code' => '5020', 'is_active' => true],
            ['code' => 'MAINT-003', 'name' => 'Vehicle Maintenance', 'description' => 'School vehicle repairs and maintenance', 'account_code' => '5020', 'is_active' => true],
            ['code' => 'MAINT-004', 'name' => 'Generator Maintenance', 'description' => 'Generator servicing and repairs', 'account_code' => '5020', 'is_active' => true],

            // Teaching Materials
            ['code' => 'TEACH-001', 'name' => 'Textbooks', 'description' => 'Purchase of textbooks and reference materials', 'account_code' => '5030', 'is_active' => true],
            ['code' => 'TEACH-002', 'name' => 'Laboratory Equipment', 'description' => 'Science lab equipment and supplies', 'account_code' => '5030', 'is_active' => true],
            ['code' => 'TEACH-003', 'name' => 'Stationery', 'description' => 'Office and classroom stationery', 'account_code' => '5030', 'is_active' => true],
            ['code' => 'TEACH-004', 'name' => 'Teaching Aids', 'description' => 'Visual aids, charts, and teaching materials', 'account_code' => '5030', 'is_active' => true],
            ['code' => 'TEACH-005', 'name' => 'IT Equipment', 'description' => 'Computers, projectors, and IT supplies', 'account_code' => '5030', 'is_active' => true],

            // Staff Related
            ['code' => 'STAFF-001', 'name' => 'Staff Salaries', 'description' => 'Monthly staff salary payments', 'account_code' => '5000', 'is_active' => true],
            ['code' => 'STAFF-002', 'name' => 'Staff Training', 'description' => 'Training and development programs', 'account_code' => '5050', 'is_active' => true],
            ['code' => 'STAFF-003', 'name' => 'Staff Welfare', 'description' => 'Staff welfare and events', 'account_code' => '5050', 'is_active' => true],
            ['code' => 'STAFF-004', 'name' => 'Staff Uniforms', 'description' => 'Purchase of staff uniforms', 'account_code' => '5050', 'is_active' => true],

            // Marketing & Events
            ['code' => 'MKT-001', 'name' => 'Advertising', 'description' => 'Radio, TV, and online advertising', 'account_code' => '5060', 'is_active' => true],
            ['code' => 'MKT-002', 'name' => 'Printing Materials', 'description' => 'Brochures, flyers, and banners', 'account_code' => '5060', 'is_active' => true],
            ['code' => 'MKT-003', 'name' => 'School Events', 'description' => 'Graduation, sports day, and events', 'account_code' => '5060', 'is_active' => true],
            ['code' => 'MKT-004', 'name' => 'Open Day', 'description' => 'Open day and parent engagement events', 'account_code' => '5060', 'is_active' => true],

            // Professional Services
            ['code' => 'PROF-001', 'name' => 'Legal Services', 'description' => 'Legal fees and consultations', 'account_code' => '5130', 'is_active' => true],
            ['code' => 'PROF-002', 'name' => 'Audit Services', 'description' => 'External audit fees', 'account_code' => '5130', 'is_active' => true],
            ['code' => 'PROF-003', 'name' => 'Consulting', 'description' => 'Professional consulting services', 'account_code' => '5130', 'is_active' => true],

            // Security & Safety
            ['code' => 'SEC-001', 'name' => 'Security Guards', 'description' => 'Security guard services', 'account_code' => '5100', 'is_active' => true],
            ['code' => 'SEC-002', 'name' => 'CCTV Equipment', 'description' => 'CCTV cameras and installation', 'account_code' => '5100', 'is_active' => true],
            ['code' => 'SEC-003', 'name' => 'Fire Safety', 'description' => 'Fire extinguishers and safety equipment', 'account_code' => '5100', 'is_active' => true],

            // Cleaning & Sanitation
            ['code' => 'CLN-001', 'name' => 'Cleaning Supplies', 'description' => 'Detergents, brooms, and cleaning materials', 'account_code' => '5090', 'is_active' => true],
            ['code' => 'CLN-002', 'name' => 'Waste Disposal', 'description' => 'Waste collection and disposal services', 'account_code' => '5090', 'is_active' => true],
            ['code' => 'CLN-003', 'name' => 'Sanitation', 'description' => 'Toiletries and sanitation supplies', 'account_code' => '5090', 'is_active' => true],

            // Transportation
            ['code' => 'TRANS-001', 'name' => 'Fuel', 'description' => 'Fuel for school vehicles and generators', 'account_code' => '5120', 'is_active' => true],
            ['code' => 'TRANS-002', 'name' => 'School Bus', 'description' => 'School bus operations and maintenance', 'account_code' => '5120', 'is_active' => true],
            ['code' => 'TRANS-003', 'name' => 'Staff Transport', 'description' => 'Staff transportation allowance', 'account_code' => '5120', 'is_active' => true],

            // Medical
            ['code' => 'MED-001', 'name' => 'Clinic Supplies', 'description' => 'Medical supplies for school clinic', 'account_code' => '5170', 'is_active' => true],
            ['code' => 'MED-002', 'name' => 'Staff Medical', 'description' => 'Staff medical expenses', 'account_code' => '5170', 'is_active' => true],
            ['code' => 'MED-003', 'name' => 'Student Medical', 'description' => 'Student medical emergencies', 'account_code' => '5170', 'is_active' => true],

            // Insurance
            ['code' => 'INS-001', 'name' => 'Building Insurance', 'description' => 'School building insurance', 'account_code' => '5110', 'is_active' => true],
            ['code' => 'INS-002', 'name' => 'Vehicle Insurance', 'description' => 'School vehicle insurance', 'account_code' => '5110', 'is_active' => true],
            ['code' => 'INS-003', 'name' => 'Liability Insurance', 'description' => 'Public liability insurance', 'account_code' => '5110', 'is_active' => true],

            // Software & Subscriptions
            ['code' => 'SOFT-001', 'name' => 'School Software', 'description' => 'School management software subscriptions', 'account_code' => '5140', 'is_active' => true],
            ['code' => 'SOFT-002', 'name' => 'Domain & Hosting', 'description' => 'Website domain and hosting fees', 'account_code' => '5140', 'is_active' => true],
            ['code' => 'SOFT-003', 'name' => 'Online Services', 'description' => 'SaaS and online service subscriptions', 'account_code' => '5140', 'is_active' => true],

            // Miscellaneous
            ['code' => 'MISC-001', 'name' => 'Bank Charges', 'description' => 'Bank transaction fees and charges', 'account_code' => '5040', 'is_active' => true],
            ['code' => 'MISC-002', 'name' => 'Postage & Courier', 'description' => 'Postage and courier services', 'account_code' => '5040', 'is_active' => true],
            ['code' => 'MISC-003', 'name' => 'Subscriptions', 'description' => 'Magazine and journal subscriptions', 'account_code' => '5040', 'is_active' => true],
            ['code' => 'MISC-004', 'name' => 'Contingency', 'description' => 'Miscellaneous and contingency expenses', 'account_code' => '5040', 'is_active' => true],
        ];

        foreach ($categories as $category) {
            // Get account ID from chart of accounts
            $accountId = null;
            if (Schema::hasTable('chart_of_accounts')) {
                $account = DB::table('chart_of_accounts')
                    ->where('account_code', $category['account_code'])
                    ->first();
                $accountId = $account->id ?? null;
            }

            $exists = DB::table('expense_categories')
                ->where('code', $category['code'])
                ->exists();

            if (!$exists) {
                DB::table('expense_categories')->insert([
                    'code' => $category['code'],
                    'name' => $category['name'],
                    'description' => $category['description'],
                    'account_id' => $accountId,
                    'is_active' => $category['is_active'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $this->command->info('✅ Expense categories seeded successfully! (' . count($categories) . ' records)');
    }
}
