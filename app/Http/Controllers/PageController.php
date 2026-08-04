<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class PageController extends Controller
{
    public function beranda(): View
    {
        return view('pages.beranda');
    }

    public function belanja(): View
    {
        return view('pages.stub', [
            'title' => 'Belanja',
            'description' => 'Katalog produk Evomi akan segera tersedia di Laravel frontend.',
        ]);
    }

    public function artikel(): View
    {
        return view('pages.stub', [
            'title' => 'Artikel',
            'description' => 'Daftar artikel parfum Evomi akan segera tersedia di Laravel frontend.',
        ]);
    }

    public function kuis(): View
    {
        return view('pages.stub', [
            'title' => 'Kuis',
            'description' => 'Kuis persona aroma Evomi akan segera tersedia di Laravel frontend.',
        ]);
    }

    public function faq(): View
    {
        return view('pages.stub', [
            'title' => 'FAQ',
            'description' => 'Pusat bantuan Evomi akan segera tersedia di Laravel frontend.',
        ]);
    }

    public function kontak(): View
    {
        return view('pages.stub', [
            'title' => 'Kontak',
            'description' => 'Halaman kontak Evomi akan segera tersedia di Laravel frontend.',
        ]);
    }

    public function pengiriman(): View
    {
        return view('pages.stub', [
            'title' => 'Pengiriman',
            'description' => 'Info pengiriman Evomi akan segera tersedia di Laravel frontend.',
        ]);
    }

    public function login(): View
    {
        return view('pages.stub', [
            'title' => 'Login',
            'description' => 'Halaman login Evomi akan segera tersedia di Laravel frontend.',
        ]);
    }

    public function register(): View
    {
        return view('pages.stub', [
            'title' => 'Daftar',
            'description' => 'Halaman registrasi Evomi akan segera tersedia di Laravel frontend.',
        ]);
    }
}
