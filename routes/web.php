<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'beranda'])->name('beranda');
Route::get('/belanja', [PageController::class, 'belanja'])->name('belanja');
Route::get('/belanja/{id}', [PageController::class, 'belanjaShow'])->name('belanja.show')->whereNumber('id');
Route::get('/checkout', [PageController::class, 'checkout'])->name('checkout');
Route::get('/artikel', [PageController::class, 'artikel'])->name('artikel');
Route::get('/artikel/{slug}', [PageController::class, 'artikelShow'])->name('artikel.show');
Route::get('/kuis', [PageController::class, 'kuis'])->name('kuis');
Route::get('/faq', [PageController::class, 'faq'])->name('faq');
Route::get('/kontak', [PageController::class, 'kontak'])->name('kontak');
Route::get('/pengiriman', [PageController::class, 'pengiriman'])->name('pengiriman');
Route::get('/pengiriman/{resi}', [PageController::class, 'pengirimanShow'])->name('pengiriman.show');
Route::get('/login', [PageController::class, 'login'])->name('login');
Route::get('/register', [PageController::class, 'register'])->name('register');

Route::get('/dashboard', [DashboardController::class, 'home'])->name('dashboard');
Route::get('/dashboard/{page}', [DashboardController::class, 'stub'])
    ->name('dashboard.stub')
    ->where('page', 'cms|products|articles|promos|payment|kurirs|quiz|orders|trackings|messages|cart|wishlist|users|subscribers|profile');

Route::prefix('profile')->name('profile.')->group(function () {
    Route::get('/', [ProfileController::class, 'index'])->name('index');
    Route::get('/chat', [ProfileController::class, 'chat'])->name('chat');
    Route::get('/cart', [ProfileController::class, 'cart'])->name('cart');
    Route::get('/history', [ProfileController::class, 'history'])->name('history');
    Route::get('/history/{id}', [ProfileController::class, 'historyShow'])->name('history.show');
    Route::get('/wishlist', [ProfileController::class, 'wishlist'])->name('wishlist');
});
