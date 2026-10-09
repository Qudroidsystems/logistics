<?php

// Seeded Super Admin (read via config() so it still works after `php artisan optimize` caches config).
return [
    'name'     => env('ADMIN_NAME', 'Platform Admin'),
    'email'    => env('ADMIN_EMAIL'),
    'password' => env('ADMIN_PASSWORD'),
];
