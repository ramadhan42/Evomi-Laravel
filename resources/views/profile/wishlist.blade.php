@extends('layouts.app')

@section('title', 'Wishlist | Evomi')

@section('content')
<x-profile-shell>
    <div x-data="evomiProfileWishlist">
        <div
            x-show="loading"
            x-cloak
            class="rounded-[28px] overflow-hidden border border-gray-100 min-h-[400px] flex flex-col items-center justify-center bg-white"
        >
            <div class="w-8 h-8 border-4 border-gray-200 border-t-[#1172BA] rounded-full animate-spin mb-4"></div>
            <p class="text-gray-500 font-medium text-sm">{{ evomi_l('Memuat wishlist...', 'Loading wishlist...') }}</p>
        </div>

        <div
            x-show="!loading"
            x-cloak
            class="relative rounded-[28px] overflow-hidden border border-gray-100 bg-white"
        >
            <div class="relative px-5 sm:px-7 py-5 text-white" style="background: linear-gradient(135deg, #1172BA 0%, #1a7fc4 55%, #0e6aad 100%)">
                <div class="pointer-events-none absolute inset-0 opacity-30" style="background-image: radial-gradient(circle at 12% 20%, rgba(255,255,255,0.35), transparent 40%), radial-gradient(circle at 90% 0%, rgba(255,255,255,0.18), transparent 35%)"></div>
                <div class="relative flex items-start justify-between gap-3">
                    <div class="min-w-0 flex items-start gap-3">
                        <span class="w-10 h-10 rounded-2xl bg-white/15 border border-white/25 flex items-center justify-center shrink-0">
                            <svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z"/></svg>
                        </span>
                        <div class="min-w-0 pt-0.5">
                            <h1 class="text-lg sm:text-xl font-bold tracking-tight">Wishlist</h1>
                            <p class="text-[12px] sm:text-sm text-white/80 font-medium mt-0.5">{{ evomi_l('Koleksi aroma favoritmu — siap dipindah ke keranjang kapan saja.', 'Your favorite scents — ready to move to cart anytime.') }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <span
                            x-show="items.length > 0"
                            x-cloak
                            class="text-[11px] sm:text-xs font-semibold px-3 py-1.5 rounded-full bg-white/15 border border-white/25"
                        >
                            <span x-text="items.length"></span> {{ evomi_l('produk', 'products') }}
                        </span>
                        <a href="{{ route('profile.cart') }}" data-soft-nav class="inline-flex items-center gap-1.5 text-[11px] font-semibold px-3 py-1.5 rounded-full bg-white/15 border border-white/25 hover:bg-white/25 transition">
                            {{ evomi_l('Ke Keranjang', 'Go to Cart') }}
                        </a>
                    </div>
                </div>
            </div>

            <div class="p-5 sm:p-7 bg-white">
                <div x-show="error" x-cloak class="rounded-2xl bg-red-50 border border-red-100 text-red-700 px-4 py-3 text-sm mb-4" x-text="error"></div>

                <div x-show="!error && items.length === 0" x-cloak class="rounded-2xl border border-dashed border-gray-200 px-6 py-14 text-center">
                    <div class="w-16 h-16 rounded-2xl bg-[#1172BA]/10 flex items-center justify-center mx-auto mb-4">
                        <svg class="w-7 h-7 text-[#1172BA]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z"/></svg>
                    </div>
                    <h2 class="text-lg font-bold text-slate-900 mb-2">{{ evomi_l('Wishlist masih kosong', 'Your wishlist is empty') }}</h2>
                    <p class="text-slate-500 text-sm mb-6 max-w-sm mx-auto">{{ evomi_l('Simpan produk favoritmu di sini biar gampang ditemukan lagi.', 'Save your favorite products here for easy access later.') }}</p>
                    <a href="{{ route('belanja') }}" data-soft-nav class="inline-flex items-center gap-2 px-6 py-2.5 bg-[#1172BA] text-white rounded-xl font-semibold text-sm hover:bg-[#0d5a94]">{{ evomi_l('Mulai Belanja', 'Start Shopping') }}</a>
                </div>

                <div x-show="items.length > 0" x-cloak class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <template x-for="item in items" :key="item.id">
                        <div class="relative flex flex-col rounded-2xl border border-gray-100 bg-white p-4 transition-all group hover:border-slate-200">
                            <button
                                type="button"
                                @click="requestRemove(item)"
                                class="absolute top-3 right-3 z-10 p-2 bg-white text-rose-500 hover:bg-rose-500 hover:text-white rounded-xl transition-colors border border-rose-100"
                                :aria-label="$L('Hapus dari wishlist', 'Remove from wishlist')"
                            >
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                            </button>

                            <a :href="'/belanja/' + item.product_id" data-soft-nav class="block focus:outline-none">
                                <div
                                    class="w-[78%] mx-auto aspect-square rounded-xl mb-4 flex items-center justify-center overflow-hidden p-2.5"
                                    :style="{ backgroundColor: item.accent || '#1172BA' }"
                                >
                                    <img
                                        :src="item.imageUrl"
                                        :alt="item.title"
                                        class="w-full h-full object-contain transition-transform duration-500 ease-out group-hover:scale-105"
                                        x-on:error="$el.style.display='none'"
                                    >
                                </div>
                                <div class="mb-3 pr-8">
                                    <h3 class="font-semibold text-slate-900 line-clamp-2 group-hover:underline decoration-slate-300 underline-offset-2" x-text="item.title"></h3>
                                    <p class="text-lg font-bold mt-1 text-[#1172BA]" x-text="item.priceLabel"></p>
                                    <span class="inline-flex items-center gap-1 mt-1.5 text-[11px] font-medium text-slate-400 group-hover:text-slate-600 transition-colors">
                                        {{ evomi_l('Lihat produk', 'View product') }}
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/></svg>
                                    </span>
                                </div>
                            </a>

                            <button
                                type="button"
                                class="mt-auto w-full text-white py-2.5 rounded-xl text-sm font-semibold transition hover:opacity-90 disabled:opacity-60 inline-flex items-center justify-center gap-2 bg-[#1172BA]"
                                :disabled="addingId === item.id"
                                @click="moveToCart(item, $event)"
                            >
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z"/></svg>
                                <span x-text="addingId === item.id ? $L('Menambahkan...', 'Adding...') : $L('Masukkan Keranjang', 'Add to Cart')"></span>
                            </button>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <div
            x-show="toast"
            x-cloak
            x-transition
            class="fixed bottom-6 left-1/2 -translate-x-1/2 z-[110] px-4 py-2.5 rounded-full bg-slate-900 text-white text-sm font-medium shadow-lg"
            x-text="toast"
        ></div>

        <template x-teleport="body">
            <div x-show="modal.open" x-cloak class="fixed inset-0 z-[200] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
                <div class="absolute inset-0" @click="closeModal()"></div>
                <div class="relative bg-white rounded-2xl p-6 max-w-sm w-full shadow-2xl border border-slate-100">
                    <div class="text-center mt-1">
                        <div class="flex justify-center mb-4">
                            <svg x-show="modal.type === 'confirm'" class="w-12 h-12 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/></svg>
                            <svg x-show="modal.type === 'error'" class="w-12 h-12 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 mb-2" x-text="modal.type === 'confirm' ? $L('Hapus dari wishlist?', 'Remove from wishlist?') : $L('Gagal', 'Failed')"></h3>
                        <p class="text-sm text-slate-600 mb-6" x-text="modal.message"></p>
                        <div class="flex gap-2" x-show="modal.type === 'confirm'">
                            <button type="button" @click="closeModal()" class="flex-1 py-2.5 rounded-xl border border-slate-200 hover:bg-slate-50 text-sm font-medium">{{ evomi_l('Batal', 'Cancel') }}</button>
                            <button type="button" @click="confirmRemove()" class="flex-1 py-2.5 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-sm font-semibold">{{ evomi_l('Ya, Hapus', 'Yes, Remove') }}</button>
                        </div>
                        <button type="button" x-show="modal.type === 'error'" @click="closeModal()" class="w-full py-2.5 rounded-xl text-sm font-semibold text-white bg-[#1172BA]">{{ evomi_l('Mengerti', 'Got it') }}</button>
                    </div>
                </div>
            </div>
        </template>
    </div>
</x-profile-shell>
@endsection
