<?php

return [
    // Used when the customer does not pick a method: paystack | opay | monnify | stripe
    'default' => env('PAYMENTS_DEFAULT_GATEWAY', 'paystack'),

    // Stripe charges in this currency (it must be enabled on your Stripe account); the amount is the same kobo/minor figure.
    'stripe_currency' => strtolower(env('STRIPE_CURRENCY', 'ngn')),

    // Unconfirmed online payments are re-checked with the gateway for this long (minutes) after they start.
    'reconcile_window_minutes' => (int) env('PAYMENTS_RECONCILE_WINDOW', 2880),
];
