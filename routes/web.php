<?php

use App\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'beranda'])->name('beranda');
Route::get('/belanja', [PageController::class, 'belanja'])->name('belanja');
Route::get('/artikel', [PageController::class, 'artikel'])->name('artikel');
Route::get('/kuis', [PageController::class, 'kuis'])->name('kuis');
Route::get('/faq', [PageController::class, 'faq'])->name('faq');
Route::get('/kontak', [PageController::class, 'kontak'])->name('kontak');
Route::get('/pengiriman', [PageController::class, 'pengiriman'])->name('pengiriman');
Route::get('/login', [PageController::class, 'login'])->name('login');
Route::get('/register', [PageController::class, 'register'])->name('register');
