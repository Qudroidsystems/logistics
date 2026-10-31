<?php

return [
    /*
    | Road routing for quotes, negotiation distance and ETAs.
    |
    |   straight  straight-line distance x 1.35 at 25 km/h. No dependency, always available.
    |   osrm      any OSRM-compatible server (self-hosted OSRM, or a Valhalla/GraphHopper OSRM shim).
    |
    | If the engine is down or slow the app falls back to `straight`, so quoting never stops.
    | The public demo server (router.project-osrm.org) is for testing only and must not be used in production.
    |
    |   google    Google Routes API (GOOGLE_MAPS_KEY or ROUTING_GOOGLE_KEY, with "Routes API" enabled). Paid per request; results are cached.
    |   mapbox    Mapbox Directions (MAPBOX_TOKEN). Paid per request above the free tier; results are cached.
    */
    //   auto      (default) google or mapbox when that key is set for address search (config/maps.php), otherwise straight
    'driver' => env('ROUTING_DRIVER', 'auto'),

    'google' => [
        'key' => env('ROUTING_GOOGLE_KEY'),
        // Live traffic makes durations better and requests dearer.
        'traffic' => (bool) env('ROUTING_TRAFFIC', false),
    ],
    'mapbox' => [
        'traffic' => (bool) env('ROUTING_TRAFFIC', false),
    ],

    'osrm' => [
        'base_url' => rtrim(env('OSRM_BASE_URL', 'http://osrm:5000'), '/'),
        // OSRM serves one profile per server. Map each vehicle profile to the base URL that serves it;
        // profiles without their own server use the default `car` one.
        'profiles' => [
            'car' => env('OSRM_CAR_URL'),
            'bike' => env('OSRM_BIKE_URL'),
        ],
        'timeout' => (float) env('OSRM_TIMEOUT', 2.5),
    ],

    // Cached routes are reused for this long (hours), per ~110 m cell pair and hour of day.
    'cache_hours' => (int) env('ROUTING_CACHE_HOURS', 24 * 7),

    'fallback' => ['road_factor' => 1.35, 'speed_kmh' => 25],
];
