<?php
// database/seeders/DefaultPaymentGatewaysSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DefaultPaymentGatewaysSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * This seeder creates default payment gateway configurations
     * for online payment processing.
     */
    public function run(): void
    {
        $this->command->info('');
        $this->command->info('  🌐 Seeding default payment gateways...');

        // Check if payment_gateways table exists
        if (!Schema::hasTable('payment_gateways')) {
            $this->command->warn('  ⚠️  payment_gateways table does not exist. Creating table...');
            $this->createPaymentGatewaysTable();
        }

        $gateways = $this->getGatewayConfigurations();

        $insertedCount = 0;
        $updatedCount = 0;

        foreach ($gateways as $gateway) {
            try {
                // Check if gateway already exists
                $existing = DB::table('payment_gateways')
                    ->where('provider_key', $gateway['provider_key'])
                    ->first();

                if ($existing) {
                    // Keys, mode and on/off are managed by the admin in Finance ›
                    // Payment Gateways. Re-running this seeder must never overwrite
                    // them, so only the name and non-key settings are refreshed.
                    $current = json_decode($existing->config ?? '[]', true) ?: [];
                    $defaults = array_filter(
                        $gateway['config'] ?? [],
                        fn ($k) => !preg_match('/(key|secret|merchant|contract|client|token)/i', $k),
                        ARRAY_FILTER_USE_KEY
                    );
                    DB::table('payment_gateways')
                        ->where('provider_key', $gateway['provider_key'])
                        ->update([
                            'name' => $gateway['name'],
                            'config' => json_encode(array_merge($defaults, $current)),
                            'updated_at' => now(),
                        ]);
                    $updatedCount++;
                    $this->command->info("  🔄 Updated: {$gateway['name']}");
                } else {
                    // Insert new gateway
                    DB::table('payment_gateways')->insert([
                        'name' => $gateway['name'],
                        'provider_key' => $gateway['provider_key'],
                        'secret_key' => null, // entered by the admin in Payment Gateways
                        'public_key' => null,
                        'mode' => 'sandbox',
                        'config' => json_encode(array_filter(
                            $gateway['config'] ?? [],
                            fn ($k) => !preg_match('/(key|secret|merchant|contract|client|token)/i', $k),
                            ARRAY_FILTER_USE_KEY
                        )),
                        'is_active' => false,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $insertedCount++;
                    $this->command->info("  ✅ Added: {$gateway['name']}");
                }
            } catch (\Exception $e) {
                $this->command->error("  ❌ Failed to seed {$gateway['name']}: " . $e->getMessage());
                Log::error("PaymentGatewaySeeder: Failed to seed {$gateway['name']} - " . $e->getMessage());
            }
        }

        $this->command->info('');
        $this->command->info("  📊 Payment Gateways Summary:");
        $this->command->info("  ✅ Inserted: {$insertedCount}");
        $this->command->info("  🔄 Updated: {$updatedCount}");
        $this->command->info("  📦 Total: " . ($insertedCount + $updatedCount));

        // Display active gateways
        $activeGateways = DB::table('payment_gateways')
            ->where('is_active', true)
            ->get();

        if ($activeGateways->count() > 0) {
            $this->command->info('');
            $this->command->info('  🟢 Active Payment Gateways:');
            foreach ($activeGateways as $gateway) {
                $this->command->info("     • {$gateway->name} ({$gateway->mode} mode)");
            }
        } else {
            $this->command->warn('  ⚠️  No active payment gateways. Please configure at least one.');
        }
    }

    /**
     * Get all payment gateway configurations
     */
    private function getGatewayConfigurations(): array
    {
        return [
            // ============================================
            // PAYSTACK - Primary Nigerian Gateway
            // ============================================
            [
                'name' => 'Paystack',
                'provider_key' => 'paystack',
                'secret_key' => env('PAYSTACK_SECRET_KEY', 'sk_test_xxxxxxxxxxxxx'),
                'public_key' => env('PAYSTACK_PUBLIC_KEY', 'pk_test_xxxxxxxxxxxxx'),
                'mode' => env('PAYSTACK_MODE', 'sandbox'), // sandbox or live
                'config' => [
                    'test_secret_key' => env('PAYSTACK_TEST_SECRET_KEY', 'sk_test_xxxxxxxxxxxxx'),
                    'test_public_key' => env('PAYSTACK_TEST_PUBLIC_KEY', 'pk_test_xxxxxxxxxxxxx'),
                    'live_secret_key' => env('PAYSTACK_LIVE_SECRET_KEY', ''),
                    'live_public_key' => env('PAYSTACK_LIVE_PUBLIC_KEY', ''),
                    'callback_url' => env('PAYSTACK_CALLBACK_URL', '/payment/callback'),
                    'webhook_url' => env('PAYSTACK_WEBHOOK_URL', '/payment/webhook/paystack'),
                    'transaction_charge' => 1.5, // 1.5% + ₦100
                    'minimum_amount' => 100,
                    'currency' => 'NGN',
                    'supported_cards' => ['Visa', 'Mastercard', 'Verve'],
                    'supports_ussd' => true,
                    'supports_bank_transfer' => true,
                    'supports_qr_code' => true,
                    'supports_mobile_money' => true,
                ],
                'is_active' => env('PAYSTACK_ACTIVE', true),
            ],

            // ============================================
            // REMITA - Government Payment Gateway
            // ============================================
            [
                'name' => 'Remita',
                'provider_key' => 'remita',
                'secret_key' => env('REMITA_SECRET_KEY', ''),
                'public_key' => env('REMITA_PUBLIC_KEY', ''),
                'mode' => env('REMITA_MODE', 'sandbox'),
                'config' => [
                    'test_merchant_id' => env('REMITA_TEST_MERCHANT_ID', '2547916'),
                    'test_api_key' => env('REMITA_TEST_API_KEY', ''),
                    'test_api_token' => env('REMITA_TEST_API_TOKEN', ''),
                    'live_merchant_id' => env('REMITA_LIVE_MERCHANT_ID', ''),
                    'live_api_key' => env('REMITA_LIVE_API_KEY', ''),
                    'live_api_token' => env('REMITA_LIVE_API_TOKEN', ''),
                    'callback_url' => env('REMITA_CALLBACK_URL', '/payment/callback/remita'),
                    'webhook_url' => env('REMITA_WEBHOOK_URL', '/payment/webhook/remita'),
                    'service_type_id' => env('REMITA_SERVICE_TYPE_ID', '4430731'),
                    'transaction_charge' => 0, // Configured by Remita
                    'minimum_amount' => 500,
                    'currency' => 'NGN',
                    'supports_rrr' => true, // Remita Retrieval Reference
                    'supports_bank_branch' => true,
                    'supports_ussd' => true,
                ],
                'is_active' => env('REMITA_ACTIVE', false),
            ],

            // ============================================
            // FLUTTERWAVE - Alternative Gateway
            // ============================================
            [
                'name' => 'Flutterwave',
                'provider_key' => 'flutterwave',
                'secret_key' => env('FLW_SECRET_KEY', ''),
                'public_key' => env('FLW_PUBLIC_KEY', ''),
                'mode' => env('FLW_MODE', 'sandbox'),
                'config' => [
                    'test_secret_key' => env('FLW_TEST_SECRET_KEY', 'FLWSECK_TEST-xxxxxxxxxxxxx'),
                    'test_public_key' => env('FLW_TEST_PUBLIC_KEY', 'FLWPUBK_TEST-xxxxxxxxxxxxx'),
                    'live_secret_key' => env('FLW_LIVE_SECRET_KEY', ''),
                    'live_public_key' => env('FLW_LIVE_PUBLIC_KEY', ''),
                    'encryption_key' => env('FLW_ENCRYPTION_KEY', ''),
                    'callback_url' => env('FLW_CALLBACK_URL', '/payment/callback/flutterwave'),
                    'webhook_url' => env('FLW_WEBHOOK_URL', '/payment/webhook/flutterwave'),
                    'transaction_charge' => 1.4, // 1.4% + ₦100
                    'minimum_amount' => 100,
                    'currency' => 'NGN',
                    'supported_cards' => ['Visa', 'Mastercard', 'Verve', 'American Express'],
                    'supports_ussd' => true,
                    'supports_bank_transfer' => true,
                    'supports_mobile_money' => true,
                    'supports_barter' => true, // Flutterwave Barter
                ],
                'is_active' => env('FLUTTERWAVE_ACTIVE', false),
            ],

            // ============================================
            // INTERSWITCH - WebPay
            // ============================================
            [
                'name' => 'Interswitch WebPay',
                'provider_key' => 'interswitch',
                'secret_key' => env('INTERSWITCH_SECRET_KEY', ''),
                'public_key' => env('INTERSWITCH_PUBLIC_KEY', ''),
                'mode' => env('INTERSWITCH_MODE', 'sandbox'),
                'config' => [
                    'test_merchant_code' => env('INTERSWITCH_TEST_MERCHANT_CODE', 'MX020101'),
                    'test_api_key' => env('INTERSWITCH_TEST_API_KEY', ''),
                    'test_iv_key' => env('INTERSWITCH_TEST_IV_KEY', ''),
                    'live_merchant_code' => env('INTERSWITCH_LIVE_MERCHANT_CODE', ''),
                    'live_api_key' => env('INTERSWITCH_LIVE_API_KEY', ''),
                    'live_iv_key' => env('INTERSWITCH_LIVE_IV_KEY', ''),
                    'callback_url' => env('INTERSWITCH_CALLBACK_URL', '/payment/callback/interswitch'),
                    'webhook_url' => env('INTERSWITCH_WEBHOOK_URL', '/payment/webhook/interswitch'),
                    'product_id' => env('INTERSWITCH_PRODUCT_ID', '101'),
                    'transaction_charge' => 1.5,
                    'minimum_amount' => 200,
                    'currency' => 'NGN',
                    'supported_cards' => ['Visa', 'Mastercard', 'Verve'],
                    'supports_quickteller' => true,
                    'supports_ussd' => false,
                ],
                'is_active' => env('INTERSWITCH_ACTIVE', false),
            ],

            // ============================================
            // MONNIFY - Account Provisioning
            // ============================================
            [
                'name' => 'Monnify',
                'provider_key' => 'monnify',
                'secret_key' => env('MONNIFY_SECRET_KEY', ''),
                'public_key' => env('MONNIFY_PUBLIC_KEY', ''),
                'mode' => env('MONNIFY_MODE', 'sandbox'),
                'config' => [
                    'test_api_key' => env('MONNIFY_TEST_API_KEY', 'MK_TEST_xxxxxxxxxxxxx'),
                    'test_secret_key' => env('MONNIFY_TEST_SECRET_KEY', 'MS_TEST_xxxxxxxxxxxxx'),
                    'test_contract_code' => env('MONNIFY_TEST_CONTRACT_CODE', ''),
                    'live_api_key' => env('MONNIFY_LIVE_API_KEY', ''),
                    'live_secret_key' => env('MONNIFY_LIVE_SECRET_KEY', ''),
                    'live_contract_code' => env('MONNIFY_LIVE_CONTRACT_CODE', ''),
                    'callback_url' => env('MONNIFY_CALLBACK_URL', '/payment/callback/monnify'),
                    'webhook_url' => env('MONNIFY_WEBHOOK_URL', '/payment/webhook/monnify'),
                    'transaction_charge' => 0, // No charge for Monnify
                    'minimum_amount' => 100,
                    'currency' => 'NGN',
                    'supports_account_provision' => true,
                    'supports_bank_transfer' => true,
                    'supports_card' => false,
                ],
                'is_active' => env('MONNIFY_ACTIVE', false),
            ],

            // ============================================
            // PAYPAL - International Payments
            // ============================================
            [
                'name' => 'PayPal',
                'provider_key' => 'paypal',
                'secret_key' => env('PAYPAL_SECRET_KEY', ''),
                'public_key' => env('PAYPAL_CLIENT_ID', ''),
                'mode' => env('PAYPAL_MODE', 'sandbox'),
                'config' => [
                    'test_client_id' => env('PAYPAL_TEST_CLIENT_ID', ''),
                    'test_secret' => env('PAYPAL_TEST_SECRET', ''),
                    'live_client_id' => env('PAYPAL_LIVE_CLIENT_ID', ''),
                    'live_secret' => env('PAYPAL_LIVE_SECRET', ''),
                    'callback_url' => env('PAYPAL_CALLBACK_URL', '/payment/callback/paypal'),
                    'webhook_url' => env('PAYPAL_WEBHOOK_URL', '/payment/webhook/paypal'),
                    'transaction_charge' => 3.9, // 3.9% + fixed fee
                    'minimum_amount' => 1,
                    'currency' => 'USD',
                    'supported_cards' => ['Visa', 'Mastercard', 'American Express', 'Discover'],
                    'supports_subscriptions' => true,
                    'supports_invoicing' => true,
                ],
                'is_active' => env('PAYPAL_ACTIVE', false),
            ],

            // ============================================
            // STRIPE - International Card Payments
            // ============================================
            [
                'name' => 'Stripe',
                'provider_key' => 'stripe',
                'secret_key' => env('STRIPE_SECRET_KEY', ''),
                'public_key' => env('STRIPE_PUBLIC_KEY', ''),
                'mode' => env('STRIPE_MODE', 'sandbox'),
                'config' => [
                    'test_secret_key' => env('STRIPE_TEST_SECRET_KEY', 'sk_test_xxxxxxxxxxxxx'),
                    'test_public_key' => env('STRIPE_TEST_PUBLIC_KEY', 'pk_test_xxxxxxxxxxxxx'),
                    'live_secret_key' => env('STRIPE_LIVE_SECRET_KEY', ''),
                    'live_public_key' => env('STRIPE_LIVE_PUBLIC_KEY', ''),
                    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET', ''),
                    'callback_url' => env('STRIPE_CALLBACK_URL', '/payment/callback/stripe'),
                    'webhook_url' => env('STRIPE_WEBHOOK_URL', '/payment/webhook/stripe'),
                    'transaction_charge' => 2.9, // 2.9% + $0.30
                    'minimum_amount' => 0.50,
                    'currency' => 'USD',
                    'supported_cards' => ['Visa', 'Mastercard', 'American Express', 'Discover', 'JCB'],
                    'supports_subscriptions' => true,
                    'supports_3d_secure' => true,
                    'supports_sepa' => true,
                ],
                'is_active' => env('STRIPE_ACTIVE', false),
            ],
        ];
    }

    /**
     * Create payment_gateways table if it doesn't exist
     */
    private function createPaymentGatewaysTable(): void
    {
        Schema::create('payment_gateways', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('provider_key')->unique();
            $table->string('secret_key')->nullable();
            $table->string('public_key')->nullable();
            $table->enum('mode', ['sandbox', 'live'])->default('sandbox');
            $table->json('config')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $this->command->info('  ✅ payment_gateways table created successfully!');
    }
}
