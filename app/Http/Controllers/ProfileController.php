<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class ProfileController extends Controller
{
    public function index(): View
    {
        return view('profile.settings', [
            'activeMenu' => 'settings',
            'pageTitle' => 'Pengaturan Profil',
        ]);
    }

    public function chat(): View
    {
        return view('profile.chat', [
            'activeMenu' => 'chat',
            'pageTitle' => 'Pesan Anda',
        ]);
    }

    public function cart(): View
    {
        return view('profile.cart', [
            'activeMenu' => 'cart',
            'pageTitle' => 'Keranjang Belanja',
        ]);
    }

    public function payments(): View
    {
        return view('profile.payments', [
            'activeMenu' => 'payments',
            'pageTitle' => 'Pembayaran',
        ]);
    }

    public function history(): View
    {
        return view('profile.history', [
            'activeMenu' => 'history',
            'pageTitle' => 'Riwayat Belanja',
        ]);
    }

    public function historyShow(string $id): View
    {
        return view('profile.history-show', [
            'activeMenu' => 'history',
            'pageTitle' => 'Detail Pesanan',
            'orderId' => $id,
        ]);
    }

    public function wishlist(): View
    {
        return view('profile.wishlist', [
            'activeMenu' => 'wishlist',
            'pageTitle' => 'Wishlist',
        ]);
    }
}
