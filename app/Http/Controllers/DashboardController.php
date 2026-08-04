<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class DashboardController extends Controller
{
    /** @var array<string, string> */
    private const STUB_PAGES = [
        'cms' => 'CMS',
        'products' => 'Produk',
        'articles' => 'Artikel',
        'promos' => 'Promo',
        'payment' => 'Pembayaran',
        'kurirs' => 'Kurir',
        'quiz' => 'Kuis',
        'orders' => 'Pesanan',
        'trackings' => 'Pelacakan',
        'messages' => 'Pesan',
        'cart' => 'Keranjang',
        'wishlist' => 'Wishlist',
        'users' => 'Semua User',
        'subscribers' => 'Subscriber',
        'profile' => 'Profil Admin',
    ];

    public function home(): View
    {
        return view('dashboard.home', [
            'activeMenu' => 'dashboard',
            'pageTitle' => 'Overview',
        ]);
    }

    public function stub(string $page): View
    {
        abort_unless(array_key_exists($page, self::STUB_PAGES), 404);

        return view('dashboard.stub', [
            'activeMenu' => $page,
            'pageTitle' => self::STUB_PAGES[$page],
            'pageKey' => $page,
        ]);
    }
}
