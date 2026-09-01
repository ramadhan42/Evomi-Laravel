<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class DashboardController extends Controller
{
    /** @var array<string, array{title: string, view: string}> */
    private const PAGES = [
        'cms' => ['title' => 'CMS', 'view' => 'dashboard.cms'],
        'seo' => ['title' => 'SEO', 'view' => 'dashboard.seo'],
        'products' => ['title' => 'Produk', 'view' => 'dashboard.products'],
        'articles' => ['title' => 'Artikel', 'view' => 'dashboard.articles'],
        'promos' => ['title' => 'Promo', 'view' => 'dashboard.promos'],
        'payment' => ['title' => 'Pembayaran', 'view' => 'dashboard.payment'],
        'kurirs' => ['title' => 'Kurir', 'view' => 'dashboard.kurirs'],
        'quiz' => ['title' => 'Kuis', 'view' => 'dashboard.quiz'],
        'orders' => ['title' => 'Pesanan', 'view' => 'dashboard.orders'],
        'trackings' => ['title' => 'Pelacakan', 'view' => 'dashboard.trackings'],
        'messages' => ['title' => 'Pesan', 'view' => 'dashboard.messages'],
        'cart' => ['title' => 'Keranjang', 'view' => 'dashboard.cart'],
        'wishlist' => ['title' => 'Wishlist', 'view' => 'dashboard.wishlist'],
        'users' => ['title' => 'Semua User', 'view' => 'dashboard.users'],
        'traffic' => ['title' => 'Traffic Pengunjung', 'view' => 'dashboard.traffic'],
        'subscribers' => ['title' => 'Subscriber', 'view' => 'dashboard.subscribers'],
        'profile' => ['title' => 'Profil Admin', 'view' => 'dashboard.profile'],
    ];

    public function home(): View
    {
        return view('dashboard.home', [
            'activeMenu' => 'dashboard',
            'pageTitle' => 'Overview',
        ]);
    }

    public function page(string $page): View
    {
        abort_unless(array_key_exists($page, self::PAGES), 404);

        $meta = self::PAGES[$page];

        return view($meta['view'], [
            'activeMenu' => $page,
            'pageTitle' => $meta['title'],
            'pageKey' => $page,
        ]);
    }
}
