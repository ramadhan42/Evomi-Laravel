<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Development Admin Account (dari .env)
    |--------------------------------------------------------------------------
    |
    | Akun admin dibuat/di-update saat db:seed (non-production).
    | Pola sama seperti Arcanisia.
    |
    */
    'development_admin' => [
        'name' => env('EVOMI_ADMIN_NAME', 'Evomi Admin'),
        'email' => env('EVOMI_ADMIN_EMAIL'),
        'password' => env('EVOMI_ADMIN_PASSWORD'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Storefront custom cursor (same CDN as evomi.shop)
    |--------------------------------------------------------------------------
    |
    | Production URL: https://cdn.cursors-4u.net/previews/normal-9e607e2c-48.webp
    | Original size ~96px, hotspot 33 30 — match localhost to production.
    |
    */
    'cursor' => [
        'enabled' => env('EVOMI_CURSOR_ENABLED', true),
        'cdn' => env(
            'EVOMI_CURSOR_CDN',
            'https://cdn.cursors-4u.net/previews/normal-9e607e2c-48.webp'
        ),
        'hotspot_x' => (int) env('EVOMI_CURSOR_HOTSPOT_X', 33),
        'hotspot_y' => (int) env('EVOMI_CURSOR_HOTSPOT_Y', 30),
    ],
];
