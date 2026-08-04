@extends('layouts.app')

@section('title', 'Wishlist | Evomi')

@section('content')
<x-profile-shell>
    <div x-data="evomiProfileWishlist" class="space-y-6">
        <div class="profile-brand-header rounded-2xl sm:rounded-3xl p-6 sm:p-8 text-white flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4" style="background: linear-gradient(135deg, #1172BA 0%, #0d5a94 100%)">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold">Wishlist</h1>
                <p class="mt-1 text-white/80 text-sm"><span x-text="items.length"></span> produk disimpan</p>
            </div>
            <a href="{{ route('profile.cart') }}" data-soft-nav class="inline-flex items-center justify-center px-5 py-2.5 rounded-full bg-white text-[#1172BA] text-sm font-semibold">Ke Keranjang</a>
        </div>

        <div x-show="loading" x-cloak class="py-16 flex justify-center">
            <div class="w-8 h-8 border-4 border-gray-200 border-t-[#1172BA] rounded-full animate-spin"></div>
        </div>

        <div x-show="!loading && error" x-cloak class="rounded-2xl bg-red-50 border border-red-100 text-red-700 px-4 py-3 text-sm" x-text="error"></div>

        <div x-show="!loading && items.length === 0" x-cloak class="rounded-3xl border border-dashed border-gray-200 py-16 text-center">
            <p class="text-gray-500">Wishlist masih kosong.</p>
            <a href="{{ route('belanja') }}" data-soft-nav class="inline-flex mt-4 px-5 py-2.5 rounded-full bg-[#1172BA] text-white text-sm font-semibold">Mulai Belanja</a>
        </div>

        <div x-show="!loading && items.length > 0" x-cloak class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
            <template x-for="item in items" :key="item.id">
                <div class="rounded-2xl border border-gray-100 bg-white overflow-hidden flex flex-col">
                    <a :href="'/belanja/' + item.product_id" data-soft-nav class="aspect-square bg-gray-50">
                        <img :src="item.imageUrl" :alt="item.title" class="w-full h-full object-cover" x-on:error="$el.style.display='none'">
                    </a>
                    <div class="p-4 flex-1 flex flex-col gap-3">
                        <div>
                            <a :href="'/belanja/' + item.product_id" data-soft-nav class="font-semibold text-gray-900 text-sm hover:text-[#1172BA]" x-text="item.title"></a>
                            <p class="text-sm font-bold text-[#1172BA] mt-1" x-text="item.priceLabel"></p>
                        </div>
                        <div class="mt-auto flex flex-col gap-2">
                            <a :href="'/belanja/' + item.product_id" data-soft-nav class="text-center text-xs font-semibold py-2 rounded-xl border border-gray-200 text-gray-700 hover:bg-gray-50">Lihat produk</a>
                            <button type="button" class="text-xs font-semibold py-2 rounded-xl bg-[#1172BA] text-white hover:bg-[#0d5a94]" @click="moveToCart(item)">Masukkan Keranjang</button>
                            <button type="button" class="text-xs font-semibold text-red-500" @click="remove(item)">Hapus</button>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <p x-show="toast" x-cloak class="text-sm text-center text-emerald-600" x-text="toast"></p>
    </div>
</x-profile-shell>
@endsection
