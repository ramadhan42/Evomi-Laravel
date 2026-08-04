<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        // Unified Laravel Blade app
        'http://localhost:8000',
        'http://127.0.0.1:8000',
        // Next.js frontend (legacy / parallel)
        'http://localhost:3000',
        'http://127.0.0.1:3000',
        'https://evomi.shop',
        'https://www.evomi.shop',
        'https://evomi-rama.vercel.app',
        'https://belajar-frontend-website-v2.vercel.app',
    ],

    'allowed_origins_patterns' => [
        '#^https://evomi-rama(-[a-z0-9-]+)?\.vercel\.app$#',
        '#^https://evomi-rama-[a-z0-9-]+-[a-z0-9-]+\.vercel\.app$#',
        '#^https://belajar-frontend-website-v2(-[a-z0-9-]+)?\.vercel\.app$#',
        '#^https://belajar-frontend-website-v2-[a-z0-9-]+-[a-z0-9-]+\.vercel\.app$#',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
