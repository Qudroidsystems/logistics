<?php

return [
    // Used when the customer does not pick a method: paystack | opay | monnify | stripe
    'default' => env('PAYMENTS_DEFAULT_GATEWAY', 'paystack'),

    // Stripe charges in this currency (it must be enabled on your Stripe account); the amount is the same kobo/minor figure.
    'stripe_currency' => strtolower((string) (env('STRIPE_CURRENCY') ?: env('REGION_CURRENCY') ?: ((require __DIR__.'/region-presets.php')[strtoupper((string) env('REGION', 'NG'))]['currency'] ?? 'ngn'))),

    // Unconfirmed online payments are re-checked with the gateway for this long (minutes) after they start.
    'reconcile_window_minutes' => (int) env('PAYMENTS_RECONCILE_WINDOW', 2880),

    // How wallet withdrawals reach people: paystack (bank transfer to Nigerian accounts, automatic) or etransfer (staff send an
    // Interac e-Transfer to the email or mobile the person gave, then mark it sent). Defaults from the REGION preset.
    'payout_method' => (string) (env('PAYOUT_METHOD') ?: ((require __DIR__.'/region-presets.php')[strtoupper((string) env('REGION', 'NG'))]['payout_method'] ?? 'paystack')),
];
