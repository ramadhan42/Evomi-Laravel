<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Product;
use App\Support\LocaleResolver;
use App\Support\ProductLocalizer;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index(Request $request)
    {
        $locale = LocaleResolver::normalize($request->query('locale', 'id'));
        $carts = $request->user()->carts()->with('product')->get();

        return response()->json(ProductLocalizer::mapWithProduct($carts, $locale));
    }

    /**
     * READ ALL: Mengambil semua data keranjang dari semua user (Untuk Admin)
     */
    public function getAllCarts()
    {
        // Memuat data produk dan user pemilik keranjang
        $carts = Cart::with(['product', 'user'])->latest()->get();

        return response()->json([
            'success' => true,
            'message' => 'Semua data keranjang berhasil diambil.',
            'data' => $carts
        ], 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'nullable|integer|min:1'
        ]);

        $user = $request->user();
        $qty = $request->quantity ?? 1;

        $product = Product::find($request->product_id);
        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Produk tidak ditemukan.',
            ], 404);
        }

        $available = (int) $product->quantity;
        if ($available <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Stok produk habis.',
            ], 422);
        }

        // Cek apakah produk sudah ada di keranjang (karena ada unique constraint di database)
        $cart = Cart::where('user_id', $user->id)->where('product_id', $request->product_id)->first();
        $nextQty = ($cart ? (int) $cart->quantity : 0) + $qty;

        if ($nextQty > $available) {
            return response()->json([
                'success' => false,
                'message' => "Stok tidak cukup. Tersedia: {$available}.",
            ], 422);
        }

        if ($cart) {
            $cart->increment('quantity', $qty);
        } else {
            $cart = Cart::create([
                'user_id' => $user->id,
                'product_id' => $request->product_id,
                'quantity' => $qty
            ]);
        }

        return response()->json(['message' => 'Ditambahkan ke keranjang', 'cart' => $cart], 201);
    }

    // Di dalam CartController.php
    public function update(Request $request, $id)
    {
        // Validasi: Pastikan quantity ada dan minimal 1
        $request->validate([
            'quantity' => 'required|integer|min:1'
        ]);

        // Cari item di keranjang berdasarkan ID item keranjang (bukan ID produk)
        $cartItem = Cart::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$cartItem) {
            return response()->json(['success' => false, 'message' => 'Item tidak ditemukan'], 404);
        }

        $product = Product::find($cartItem->product_id);
        $available = (int) ($product?->quantity ?? 0);
        $requested = (int) $request->quantity;

        if ($requested > $available) {
            return response()->json([
                'success' => false,
                'message' => "Stok tidak cukup. Tersedia: {$available}.",
            ], 422);
        }

        // Update nilai quantity
        $cartItem->update([
            'quantity' => $requested
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Jumlah item berhasil diupdate',
            'data' => $cartItem
        ], 200);
    }

    public function destroy($id)
    {
        // Cari item berdasarkan ID
        $cartItem = Cart::find($id);

        if (!$cartItem) {
            return response()->json(['message' => 'Item tidak ditemukan'], 404);
        }

        // Cek otorisasi:
        // User ID 1 adalah admin (bisa hapus semua)
        // User lain hanya bisa hapus jika cart milik mereka sendiri
        if (!auth()->user()?->is_admin && $cartItem->user_id !== auth()->id()) {
            return response()->json(['message' => 'Anda tidak diizinkan menghapus item ini.'], 403);
        }

        $cartItem->delete();

        return response()->json(['message' => 'Berhasil dihapus']);
    }
}
