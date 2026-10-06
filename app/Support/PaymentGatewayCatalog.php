<?php

namespace App\Support;

/**
 * The payment gateways the portal knows about and the credentials each one
 * needs, per mode ("test" = sandbox, "live"). Drives the admin settings page,
 * validation and the connection tests.
 *
 * 'supported' => true means the portal actually takes payments through it
 * today (Paystack, for online school fees). The others can be configured and
 * tested now, ready for when they are wired in.
 */
class PaymentGatewayCatalog
{
    public const PROVIDERS = [
        'paystack' => [
            'name'      => 'Paystack',
            'supported' => true,
            'used_for'  => 'Online school fees',
            'dashboard' => 'https://dashboard.paystack.com/#/settings/developers',
            'fields'    => [
                'secret_key' => ['label' => 'Secret key', 'secret' => true,  'required' => true,
                                 'prefix' => ['test' => 'sk_test_', 'live' => 'sk_live_']],
                'public_key' => ['label' => 'Public key', 'secret' => false, 'required' => true,
                                 'prefix' => ['test' => 'pk_test_', 'live' => 'pk_live_']],
            ],
        ],
        'opay' => [
            'name'      => 'OPay',
            'supported' => true,
            'used_for'  => 'Online school fees',
            'dashboard' => 'https://merchant.opaycheckout.com',
            'fields'    => [
                'merchant_id' => ['label' => 'Merchant ID', 'secret' => false, 'required' => true],
                'public_key'  => ['label' => 'Public key', 'secret' => false, 'required' => true],
                'secret_key'  => ['label' => 'Private (secret) key', 'secret' => true, 'required' => true],
            ],
        ],
        'flutterwave' => [
            'name'      => 'Flutterwave',
            'supported' => false,
            'dashboard' => 'https://app.flutterwave.com/dashboard/settings/apis',
            'fields'    => [
                'secret_key'     => ['label' => 'Secret key', 'secret' => true, 'required' => true,
                                     'prefix' => ['test' => 'FLWSECK_TEST-', 'live' => 'FLWSECK-']],
                'public_key'     => ['label' => 'Public key', 'secret' => false, 'required' => true,
                                     'prefix' => ['test' => 'FLWPUBK_TEST-', 'live' => 'FLWPUBK-']],
                'encryption_key' => ['label' => 'Encryption key', 'secret' => true, 'required' => false],
            ],
        ],
        'monnify' => [
            'name'      => 'Monnify',
            'supported' => false,
            'dashboard' => 'https://app.monnify.com/developer',
            'fields'    => [
                'api_key'       => ['label' => 'API key', 'secret' => false, 'required' => true,
                                    'prefix' => ['test' => 'MK_TEST_', 'live' => 'MK_PROD_']],
                'secret_key'    => ['label' => 'Secret key', 'secret' => true, 'required' => true],
                'contract_code' => ['label' => 'Contract code', 'secret' => false, 'required' => true],
            ],
        ],
        'remita' => [
            'name'      => 'Remita',
            'supported' => false,
            'dashboard' => 'https://remita.net',
            'fields'    => [
                'merchant_id'     => ['label' => 'Merchant ID', 'secret' => false, 'required' => true],
                'service_type_id' => ['label' => 'Service type ID', 'secret' => false, 'required' => true],
                'api_key'         => ['label' => 'API key', 'secret' => true, 'required' => true],
            ],
        ],
        'interswitch' => [
            'name'      => 'Interswitch',
            'supported' => false,
            'dashboard' => 'https://developer.interswitchgroup.com',
            'fields'    => [
                'client_id'     => ['label' => 'Client ID', 'secret' => false, 'required' => true],
                'secret_key'    => ['label' => 'Secret key', 'secret' => true, 'required' => true],
                'merchant_code' => ['label' => 'Merchant code', 'secret' => false, 'required' => true],
                'pay_item_id'   => ['label' => 'Pay item ID', 'secret' => false, 'required' => true],
            ],
        ],
        'stripe' => [
            'name'      => 'Stripe',
            'supported' => false,
            'dashboard' => 'https://dashboard.stripe.com/apikeys',
            'fields'    => [
                'public_key'     => ['label' => 'Publishable key', 'secret' => false, 'required' => true,
                                     'prefix' => ['test' => 'pk_test_', 'live' => 'pk_live_']],
                'secret_key'     => ['label' => 'Secret key', 'secret' => true, 'required' => true,
                                     'prefix' => ['test' => 'sk_test_', 'live' => 'sk_live_']],
                'webhook_secret' => ['label' => 'Webhook signing secret', 'secret' => true, 'required' => false,
                                     'prefix' => ['test' => 'whsec_', 'live' => 'whsec_']],
            ],
        ],
        'paypal' => [
            'name'      => 'PayPal',
            'supported' => false,
            'dashboard' => 'https://developer.paypal.com/dashboard/applications',
            'fields'    => [
                'client_id'  => ['label' => 'Client ID', 'secret' => false, 'required' => true],
                'secret_key' => ['label' => 'Secret', 'secret' => true, 'required' => true],
            ],
        ],
    ];

    public static function get(string $provider): ?array
    {
        return self::PROVIDERS[$provider] ?? null;
    }

    public static function fields(string $provider): array
    {
        return self::PROVIDERS[$provider]['fields'] ?? [];
    }

    /** "sandbox"/"live" (DB value) → "test"/"live" (credential set). */
    public static function set(?string $mode): string
    {
        return $mode === 'live' ? 'live' : 'test';
    }
}
