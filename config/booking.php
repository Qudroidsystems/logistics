<?php

/*
| Instant prices and booking without negotiation (the homepage price form, the embeddable widget, /api/v1/public/quote
| and the customer app's "Get a price"). Prices come from the provider's price lists (Operations > Price lists): make
| one per service (same-day, next-day, scheduled) and add item size, vehicle, fragile, rush, weekend and fuel rules.
*/
$presets = require __DIR__.'/region-presets.php';
$p = $presets[strtoupper((string) env('REGION', 'NG'))] ?? $presets['NG'];

return [
    // "Talk to us" on the Enterprise plan and the business pages.
    'sales_email' => env('SALES_EMAIL', env('MAIL_FROM_ADDRESS')),
    'sales_phone' => env('SALES_PHONE'),

    // Contact buttons on the website: a floating WhatsApp chat button (number in international form, e.g. 12265550123)
    // and the phone and email in the footer.
    'whatsapp' => env('WHATSAPP_NUMBER'),
    'whatsapp_message' => env('WHATSAPP_MESSAGE', 'Hi! I have a question about a delivery.'),
    'contact_phone' => env('CONTACT_PHONE', env('SALES_PHONE')),
    'contact_email' => env('CONTACT_EMAIL', env('SALES_EMAIL', env('MAIL_FROM_ADDRESS'))),

    // "Drive with us": approved applicants join this operator's fleet (defaults to the instant-price operator).
    'driver_operator_id' => (int) (env('DRIVE_OPERATOR_ID') ?: env('INSTANT_OPERATOR_ID') ?: env('PLATFORM_HOUSE_OPERATOR_ID') ?: 0) ?: null,

    // The provider whose price lists give instant prices and who takes instant bookings. Defaults to the house operator.
    'operator_id' => (int) (env('INSTANT_OPERATOR_ID') ?: env('PLATFORM_HOUSE_OPERATOR_ID') ?: 0) ?: null,

    // service_types codes offered, in order.
    'services' => [
        'same_day' => ['label' => 'Same-day', 'hint' => 'Picked up and delivered today'],
        'next_day' => ['label' => 'Next-day', 'hint' => 'Picked up tomorrow morning'],
        'scheduled' => ['label' => 'Scheduled', 'hint' => 'Pick a date and time'],
    ],

    // Item sizes: the weight used for per-kg rules and vehicle checks.
    'sizes' => [
        'small' => ['label' => 'Small', 'hint' => 'Envelopes, documents, a shoebox', 'weight_g' => 2_000],
        'medium' => ['label' => 'Medium', 'hint' => 'Boxes that fit on a car seat, up to about 15 kg', 'weight_g' => 10_000],
        'large' => ['label' => 'Large', 'hint' => 'Furniture, appliances, several boxes', 'weight_g' => 40_000],
    ],

    'vehicles' => array_values(array_filter(explode(',', (string) env('INSTANT_VEHICLES', implode(',', $p['quote_vehicles'] ?? ['car', 'van']))))),

    // Next-day pickups start at this hour (local time).
    'next_day_hour' => (int) env('INSTANT_NEXT_DAY_HOUR', 9),

    // A pickup further than this from every live city is outside the service area.
    'max_city_km' => (int) env('INSTANT_MAX_CITY_KM', 80),
];
