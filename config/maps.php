<?php

/*
| Address search and map tiles.
|
| Address search runs through the backend (/geo/search, /api/v1/public/geo/search), so keys stay private and results
| are cached. Pick one:
|   google     Google Places (New): best Canadian address coverage. GOOGLE_MAPS_KEY = a server key with "Places API (New)".
|   mapbox     Mapbox Geocoding v6. MAPBOX_TOKEN = a secret or public token. Set MAPBOX_PERMANENT=true if your plan
|              includes permanent geocoding (Mapbox's terms allow storing results only with it).
|   nominatim  OpenStreetMap's free server: for testing only. Its policy allows 1 request a second for the whole app
|              and no search-as-you-type, so forms then search when the person presses Enter or "Find".
|
| Map tiles: MAP_TILE_URL is a Leaflet URL template. With a MAPBOX_PUBLIC_TOKEN and no MAP_TILE_URL, Mapbox Streets
| tiles are used; otherwise OpenStreetMap's own tiles (light use only, no service guarantee).
*/
return [
    'geocoder' => env('GEOCODER', 'nominatim'),

    'google' => [
        'key' => env('GOOGLE_MAPS_KEY'),
    ],

    'mapbox' => [
        'token' => env('MAPBOX_TOKEN'),
        'permanent' => (bool) env('MAPBOX_PERMANENT', false),
        'public_token' => env('MAPBOX_PUBLIC_TOKEN'),
    ],

    'nominatim' => [
        'url' => rtrim(env('NOMINATIM_URL', 'https://nominatim.openstreetmap.org'), '/'),
    ],

    'tiles' => [
        'url' => env('MAP_TILE_URL'),
        'attribution' => env('MAP_TILE_ATTRIBUTION'),
    ],

    // Search results are cached this long (minutes); the same addresses are typed again and again.
    'cache_minutes' => (int) env('GEOCODER_CACHE_MINUTES', 60 * 24),
];
