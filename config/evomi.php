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
    /*
    |--------------------------------------------------------------------------
    | Video layar loading (opsional)
    |--------------------------------------------------------------------------
    |
    | Kosongkan untuk memakai file pertama yang ditemukan di:
    |   public/storage/loading-screen/loading-screen.(webm|mp4)
    |   public/videos/loading-screen.(webm|mp4)
    |
    | Isi dengan path relatif terhadap public/ atau URL penuh untuk memakai
    | berkas lain. Tanpa berkas apa pun, loader memakai animasi orb seperti
    | sebelumnya.
    |
    */
    'loader_video' => env('EVOMI_LOADER_VIDEO'),
    'loader_video_poster' => env('EVOMI_LOADER_VIDEO_POSTER'),

    'development_admin' => [
        'name' => env('EVOMI_ADMIN_NAME', 'Evomi Admin'),
        'email' => env('EVOMI_ADMIN_EMAIL'),
        'password' => env('EVOMI_ADMIN_PASSWORD'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Masa berlaku sesi login
    |--------------------------------------------------------------------------
    |
    | Dua umur token, dipilih oleh centang "Biarkan saya tetap masuk" di
    | halaman login. Yang dicentang bertahan lintas penutupan browser; yang
    | tidak hanya berumur satu sesi peramban dan tokennya kedaluwarsa cepat,
    | supaya login di perangkat pinjaman tidak tertinggal terbuka.
    |
    */
    'auth' => [
        'remember_days' => (int) env('EVOMI_AUTH_REMEMBER_DAYS', 30),
        'session_hours' => (int) env('EVOMI_AUTH_SESSION_HOURS', 12),
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
    | Tautan marketplace per varian
    |--------------------------------------------------------------------------
    |
    | Dipakai tombol di halaman detail belanja. Kuncinya adalah personality_type
    | produk: prestige, peaceful_calm, rebel_brave, sweet_shy.
    |
    | Urutan pada 'channels' menentukan urutan tombol tampil. Tautan yang
    | dikosongkan membuat tombolnya tidak dirender sama sekali, jadi varian baru
    | aman ditambahkan bertahap.
    |
    */
    'marketplaces' => [
        'channels' => [
            'shopee' => ['label' => 'Shopee', 'color' => '#EE4D2D'],
            'tokopedia' => ['label' => 'Tokopedia', 'color' => '#42B549'],
            'tiktok' => ['label' => 'TikTok Shop', 'color' => '#111111'],
        ],

        'links' => [
            'prestige' => [
                'shopee' => 'https://shopee.co.id/EVOMI-Purpose-Prestige-Eau-De-Parfum-50ml-i.1790723799.58065852279',
                'tokopedia' => 'https://tk.tokopedia.com/ZSVseguCc/',
                'tiktok' => 'https://vt.tokopedia.com/t/ZS9BUrjBYtPBW-aBoCQ/',
            ],
            'peaceful_calm' => [
                'shopee' => 'https://shopee.co.id/EVOMI-Peaceful-Calm-Eau-De-Parfum-50ml-i.1790723799.45215872382',
                'tokopedia' => 'https://tk.tokopedia.com/ZSVsewhpV/',
                'tiktok' => 'https://vt.tokopedia.com/t/ZS9BUMoWvTsry-HdLE0/',
            ],
            'rebel_brave' => [
                'shopee' => 'https://shopee.co.id/EVOMI-Rebel-Brave-Eau-De-Parfum-50ml-i.1790723799.56215852499',
                'tokopedia' => 'https://tk.tokopedia.com/ZSVsec9Vh/',
                'tiktok' => 'https://vt.tokopedia.com/t/ZS9BUryS3eUM3-vWB96/',
            ],
            'sweet_shy' => [
                'shopee' => 'https://shopee.co.id/EVOMI-Sweet-Shy-Eau-De-Parfum-50ml-i.1790723799.51465860719',
                'tokopedia' => 'https://tk.tokopedia.com/ZSVsengge/',
                'tiktok' => 'https://vt.tokopedia.com/t/ZS9BUrhHoU9Fd-wbAPY/',
            ],
        ],
    ],
    /*
    |--------------------------------------------------------------------------
    | Harga tampilan
    |--------------------------------------------------------------------------
    |
    | Harga coret dan harga jual yang tampil di beranda serta halaman detail.
    | Ditaruh di sini - bukan di tabel produk - supaya perubahan harga promo ikut
    | ter-deploy bersama kode dan tidak perlu disunting ulang di tiap lingkungan.
    |
    | compare_at : harga sebelum diskon, ditampilkan tercoret. null -> tanpa coretan.
    | display    : harga jual yang ditampilkan. null -> pakai harga dari tabel produk.
    |
    */
    'pricing' => [
        'compare_at' => 250000,
        'display' => 190000,
    ],

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
