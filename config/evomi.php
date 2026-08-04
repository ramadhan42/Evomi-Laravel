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
];
