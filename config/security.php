<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Master switch — aktif di localhost & production
    |--------------------------------------------------------------------------
    */
    'enabled' => env('SECURITY_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | HTTP security headers (web + API responses)
    |--------------------------------------------------------------------------
    */
    'headers' => [
        'enabled' => env('SECURITY_HEADERS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Blokir path probe umum (.env, wp-admin, dll.)
    |--------------------------------------------------------------------------
    */
    'block_probes' => [
        'enabled' => env('SECURITY_BLOCK_PROBES', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Honeypot anti-spam bot pada form POST
    |--------------------------------------------------------------------------
    */
    'honeypot' => [
        'enabled' => env('SECURITY_HONEYPOT', true),
        'fields' => ['website', 'company', '_hp', 'url'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate limit global API (termasuk localhost bila enabled)
    |--------------------------------------------------------------------------
    */
    'api_throttle' => [
        'enabled' => env('SECURITY_API_THROTTLE', true),
        'limit' => env('SECURITY_API_THROTTLE_LIMIT', '120,1'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cloudflare Turnstile (CAPTCHA)
    |--------------------------------------------------------------------------
    */
    'turnstile' => [
        'enabled' => env('TURNSTILE_ENABLED', true),
        'site_key' => env('TURNSTILE_SITE_KEY', env('APP_ENV') === 'local'
            ? '1x00000000000000000000AA'
            : ''),
        'secret_key' => env('TURNSTILE_SECRET_KEY', env('APP_ENV') === 'local'
            ? '1x0000000000000000000000000000000AA'
            : ''),

        // Berapa lama satu verifikasi berlaku untuk mode "session" (dipakai chat).
        // User cukup centang sekali, lalu bebas mengobrol selama jendela ini.
        'session_minutes' => (int) env('TURNSTILE_SESSION_MINUTES', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Blokir klik kanan (view source via menu konteks)
    |--------------------------------------------------------------------------
    |
    | Aktif di storefront & dashboard. Email pada exempt_email (default dari
    | EVOMI_ADMIN_EMAIL) tetap bisa klik kanan saat login.
    |
    */
    'source_guard' => [
        'enabled' => env('SECURITY_SOURCE_GUARD', true),
        'exempt_email' => strtolower(trim((string) (
            env('EVOMI_ADMIN_EMAIL')
            ?: config('evomi.development_admin.email')
            ?: 'admin@evomi.com'
        ))),
    ],

];
