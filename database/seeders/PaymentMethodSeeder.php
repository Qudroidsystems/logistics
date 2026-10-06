<?php
// database/seeders/PaymentMethodSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        // Check if table exists (you may not have a dedicated payment_methods table)
        // If you don't have this table, we can skip or use a different approach

        $paymentMethods = [
            // Traditional Methods
            ['code' => 'CASH', 'name' => 'Cash', 'category' => 'offline', 'requires_approval' => false, 'sort_order' => 1, 'is_active' => true],
            ['code' => 'POS', 'name' => 'POS / Card', 'category' => 'offline', 'requires_approval' => false, 'sort_order' => 2, 'is_active' => true],
            ['code' => 'BANK_DEPOSIT', 'name' => 'Bank Deposit', 'category' => 'offline', 'requires_approval' => true, 'sort_order' => 3, 'is_active' => true],
            ['code' => 'BANK_TRANSFER', 'name' => 'Bank Transfer', 'category' => 'offline', 'requires_approval' => true, 'sort_order' => 4, 'is_active' => true],
            ['code' => 'CHEQUE', 'name' => 'Cheque', 'category' => 'offline', 'requires_approval' => true, 'sort_order' => 5, 'is_active' => true],

            // Online Payment Gateways
            ['code' => 'PAYSTACK', 'name' => 'Paystack', 'category' => 'online', 'requires_approval' => false, 'sort_order' => 10, 'is_active' => true],
            ['code' => 'REMITA', 'name' => 'Remita', 'category' => 'online', 'requires_approval' => false, 'sort_order' => 11, 'is_active' => false],
            ['code' => 'FLUTTERWAVE', 'name' => 'Flutterwave', 'category' => 'online', 'requires_approval' => false, 'sort_order' => 12, 'is_active' => false],
            ['code' => 'INTERSWITCH', 'name' => 'Interswitch', 'category' => 'online', 'requires_approval' => false, 'sort_order' => 13, 'is_active' => false],

            // Mobile Money
            ['code' => 'USSD', 'name' => 'USSD Banking', 'category' => 'online', 'requires_approval' => false, 'sort_order' => 20, 'is_active' => true],
            ['code' => 'OPAY', 'name' => 'Opay', 'category' => 'online', 'requires_approval' => false, 'sort_order' => 21, 'is_active' => false],
            ['code' => 'PALMPAY', 'name' => 'PalmPay', 'category' => 'online', 'requires_approval' => false, 'sort_order' => 22, 'is_active' => false],
            ['code' => 'MONIEPOINT', 'name' => 'Moniepoint', 'category' => 'online', 'requires_approval' => false, 'sort_order' => 23, 'is_active' => false],

            // Other
            ['code' => 'SCHOLARSHIP', 'name' => 'Scholarship', 'category' => 'adjustment', 'requires_approval' => true, 'sort_order' => 30, 'is_active' => true],
            ['code' => 'DISCOUNT', 'name' => 'Discount', 'category' => 'adjustment', 'requires_approval' => true, 'sort_order' => 31, 'is_active' => true],
            ['code' => 'WAIVER', 'name' => 'Fee Waiver', 'category' => 'adjustment', 'requires_approval' => true, 'sort_order' => 32, 'is_active' => true],
        ];

        // If you have a dedicated payment_methods table, use this:
        if (Schema::hasTable('payment_methods')) {
            foreach ($paymentMethods as $method) {
                $exists = DB::table('payment_methods')
                    ->where('code', $method['code'])
                    ->exists();

                if (!$exists) {
                    DB::table('payment_methods')->insert([
                        'code' => $method['code'],
                        'name' => $method['name'],
                        'category' => $method['category'],
                        'requires_approval' => $method['requires_approval'],
                        'sort_order' => $method['sort_order'],
                        'is_active' => $method['is_active'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
            $this->command->info('✅ Payment methods seeded successfully! (' . count($paymentMethods) . ' records)');
        } else {
            // If no dedicated table, create a config file instead
            $this->createPaymentMethodsConfig($paymentMethods);
            $this->command->info('✅ Payment methods configuration created successfully!');
        }
    }

    private function createPaymentMethodsConfig($paymentMethods)
    {
        $configPath = config_path('payment_methods.php');

        $configContent = "<?php\n\nreturn " . var_export($paymentMethods, true) . ";\n";

        file_put_contents($configPath, "<?php\n\nreturn " . var_export($paymentMethods, true) . ";\n");

        $this->command->info('📁 Configuration file created at: ' . $configPath);
    }
}
