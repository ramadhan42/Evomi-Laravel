<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Wishlist;
use App\Support\LocaleResolver;
use App\Support\ProductLocalizer;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    // Mengambil data wishlist milik user yang sedang login
    public function index(Request $request)
    {
        $locale = LocaleResolver::normalize($request->query('locale', 'id'));
        $wishlists = Wishlist::where('user_id', $request->user()->id)
            ->with('product')
            ->get();

        return response()->json(ProductLocalizer::mapWithProduct($wishlists, $locale));
    }

    /**
     * READ ALL: Mengambil semua data wishlist dari semua user (Untuk Admin)
     */
    public function getAllWishlists()
    {
        // Memuat data produk dan user pemilik wishlist
        $wishlists = Wishlist::with(['product', 'user'])->latest()->get();

        return response()->json([
            'success' => true,
            'message' => 'Semua data wishlist berhasil diambil.',
            'data' => $wishlists
        ], 200);
    }

    /**
     * GET /api/wishlists/{id} | /api/wishlist/{id}
     */
    public function show($id, Request $request)
    {
        $locale = LocaleResolver::normalize($request->query('locale', 'id'));
        $wishlist = Wishlist::with('product')
            ->where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$wishlist) {
            return response()->json([
                'success' => false,
                'message' => 'Wishlist tidak ditemukan.',
            ], 404);
        }

        $data = $wishlist->toArray();
        $data['product'] = ProductLocalizer::localize($wishlist->product, $locale);

        return response()->json([
            'success' => true,
            'data' => $data,
        ], 200);
    }

    public function store(Request $request)
    {
        // Validasi request
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        // Cek apakah sudah ada di wishlist agar tidak duplikat
        $exists = Wishlist::where('user_id', $request->user()->id)
            ->where('product_id', $request->product_id)
            ->first();

        if ($exists) {
            return response()->json(['message' => 'Produk sudah ada di wishlist'], 400);
        }

        // Simpan ke database
        $wishlist = Wishlist::create([
            'user_id' => $request->user()->id,
            'product_id' => $request->product_id,
        ]);

        return response()->json(['message' => 'Berhasil ditambahkan ke wishlist', 'data' => $wishlist], 201);
    }

    // Menghapus dari wishlist
    public function destroy($id, Request $request)
    {
        $wishlist = Wishlist::find($id);

        if (!$wishlist) {
            return response()->json(['message' => 'Item tidak ditemukan'], 404);
        }

        // Cek otorisasi:
        // User ID 1 adalah admin (bisa hapus semua)
        // User lain hanya bisa hapus jika wishlist milik mereka sendiri
        if (!auth()->user()?->is_admin && $wishlist->user_id !== auth()->id()) {
            return response()->json(['message' => 'Anda tidak diizinkan menghapus item ini.'], 403);
        }

        $wishlist->delete();

        return response()->json(['message' => 'Berhasil dihapus dari wishlist']);
    }
}