<?php

/*
| Where this install operates: currency, phone numbers, time zone, provinces/states and default sales tax.
| REGION picks a preset from config/region-presets.php (NG or CA); each REGION_* key below overrides one value.
*/
$presets = require __DIR__.'/region-presets.php';
$p = $presets[strtoupper((string) env('REGION', 'NG'))] ?? $presets['NG'];

return [
    'country' => $p['country'],
    'country_name' => $p['country_name'],
    'currency' => strtoupper((string) env('REGION_CURRENCY', $p['currency'])),
    'symbol' => (string) env('REGION_CURRENCY_SYMBOL', $p['symbol']),
    'locale' => (string) env('REGION_LOCALE', $p['locale']),
    'calling_code' => (string) env('REGION_CALLING_CODE', $p['calling_code']),
    'trunk_prefix' => (string) env('REGION_TRUNK_PREFIX', $p['trunk_prefix']),
    'store_local' => (bool) env('REGION_STORE_LOCAL_PHONES', $p['store_local']),
    'phone_example' => $p['phone_example'],
    'map' => [
        'lat' => (float) env('REGION_MAP_LAT', $p['map']['lat']),
        'lng' => (float) env('REGION_MAP_LNG', $p['map']['lng']),
        'zoom' => (int) env('REGION_MAP_ZOOM', $p['map']['zoom']),
    ],
    'subdivision_label' => $p['subdivision_label'],
    'subdivisions' => $p['subdivisions'],
    'tax' => $p['tax'],
];
