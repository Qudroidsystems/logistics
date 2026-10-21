<?php

/*
| Country presets for config/region.php (and the default time zone in config/app.php).
| Pick one with REGION=CA or REGION=NG in .env; any single value can still be overridden with its own REGION_* key.
|
|   currency        ISO code; amounts are stored in the minor unit (kobo, cents) everywhere
|   symbol          shown before amounts
|   locale          number formatting in the browser (JS Intl) and the apps
|   calling_code    country code for phone numbers, without "+"
|   trunk_prefix    the digit dialled before local numbers ("0" in Nigeria, none in Canada)
|   store_local     keep phone numbers in local form ("0803...") as older Nigerian installs do; otherwise +E.164
|   subdivisions    states / provinces offered in forms
|   tax             default sales tax name and rate (basis points) for new cities; per-subdivision overrides
*/
return [
    'NG' => [
        'country' => 'NG',
        'country_name' => 'Nigeria',
        'currency' => 'NGN',
        'symbol' => '₦',
        'locale' => 'en-NG',
        'calling_code' => '234',
        'trunk_prefix' => '0',
        'store_local' => true,
        'phone_example' => '0803 123 4567',
        'timezone' => 'Africa/Lagos',
        // Where maps open before anything is picked.
        'map' => ['lat' => 9.082, 'lng' => 8.6753, 'zoom' => 6],
        'subdivision_label' => 'State',
        'subdivisions' => [
            'AB' => 'Abia', 'AD' => 'Adamawa', 'AK' => 'Akwa Ibom', 'AN' => 'Anambra', 'BA' => 'Bauchi', 'BY' => 'Bayelsa', 'BE' => 'Benue',
            'BO' => 'Borno', 'CR' => 'Cross River', 'DE' => 'Delta', 'EB' => 'Ebonyi', 'ED' => 'Edo', 'EK' => 'Ekiti', 'EN' => 'Enugu',
            'FC' => 'Federal Capital Territory', 'GO' => 'Gombe', 'IM' => 'Imo', 'JI' => 'Jigawa', 'KD' => 'Kaduna', 'KN' => 'Kano',
            'KT' => 'Katsina', 'KE' => 'Kebbi', 'KO' => 'Kogi', 'KW' => 'Kwara', 'LA' => 'Lagos', 'NA' => 'Nasarawa', 'NI' => 'Niger',
            'OG' => 'Ogun', 'ON' => 'Ondo', 'OS' => 'Osun', 'OY' => 'Oyo', 'PL' => 'Plateau', 'RI' => 'Rivers', 'SO' => 'Sokoto',
            'TA' => 'Taraba', 'YO' => 'Yobe', 'ZA' => 'Zamfara',
        ],
        'tax' => ['name' => 'VAT', 'rate_bp' => 750, 'by_subdivision' => []],
    ],

    'CA' => [
        'country' => 'CA',
        'country_name' => 'Canada',
        'currency' => 'CAD',
        'symbol' => '$',
        'locale' => 'en-CA',
        'calling_code' => '1',
        'trunk_prefix' => '',
        'store_local' => false,
        'phone_example' => '(226) 555-0123',
        'timezone' => 'America/Toronto',
        'map' => ['lat' => 43.4643, 'lng' => -80.5204, 'zoom' => 10],
        'subdivision_label' => 'Province',
        'subdivisions' => [
            'AB' => 'Alberta', 'BC' => 'British Columbia', 'MB' => 'Manitoba', 'NB' => 'New Brunswick', 'NL' => 'Newfoundland and Labrador',
            'NS' => 'Nova Scotia', 'NT' => 'Northwest Territories', 'NU' => 'Nunavut', 'ON' => 'Ontario', 'PE' => 'Prince Edward Island',
            'QC' => 'Quebec', 'SK' => 'Saskatchewan', 'YT' => 'Yukon',
        ],
        // HST provinces charge one combined rate; elsewhere this is the 5% GST only (provincial sales tax, where it
        // applies to delivery services, must be added by setting the city's rate). Check rates with your accountant.
        'tax' => ['name' => 'HST', 'rate_bp' => 1300, 'by_subdivision' => [
            'ON' => ['name' => 'HST', 'rate_bp' => 1300], 'NB' => ['name' => 'HST', 'rate_bp' => 1500], 'NL' => ['name' => 'HST', 'rate_bp' => 1500],
            'NS' => ['name' => 'HST', 'rate_bp' => 1400], 'PE' => ['name' => 'HST', 'rate_bp' => 1500],
            'AB' => ['name' => 'GST', 'rate_bp' => 500], 'BC' => ['name' => 'GST', 'rate_bp' => 500], 'MB' => ['name' => 'GST', 'rate_bp' => 500],
            'QC' => ['name' => 'GST', 'rate_bp' => 500], 'SK' => ['name' => 'GST', 'rate_bp' => 500], 'NT' => ['name' => 'GST', 'rate_bp' => 500],
            'NU' => ['name' => 'GST', 'rate_bp' => 500], 'YT' => ['name' => 'GST', 'rate_bp' => 500],
        ]],
    ],
];
