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
    | URL publik (frontend & aset)
    |--------------------------------------------------------------------------
    |
    | WAJIB dibaca lewat config('evomi.frontend_url'), BUKAN env() langsung di
    | controller/mailable. Setelah `php artisan config:cache`, Laravel berhenti
    | memuat .env, sehingga env() di luar folder config/ mengembalikan null dan
    | tautan email diam-diam jatuh ke localhost.
    |
    | frontend_url : basis tautan yang dikirim ke pengguna (email, redirect)
    | asset_url    : basis URL gambar /storage yang disematkan di email
    |
    | Nilai sudah di-rtrim, jadi pemanggil cukup menyambung '/path'.
    |
    */
    'frontend_url' => rtrim(
        (string) (env('FRONTEND_URL') ?: env('APP_FRONTEND_URL') ?: env('APP_URL') ?: 'https://evomi.shop'),
        '/'
    ),

    'asset_url' => rtrim(
        (string) (env('APP_URL') ?: 'https://evomi.shop'),
        '/'
    ),

    /*
    |--------------------------------------------------------------------------
    | Storefront custom cursor
    |--------------------------------------------------------------------------
    |
    | CDN 96px untuk semua halaman (ukuran default evomi.shop).
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
