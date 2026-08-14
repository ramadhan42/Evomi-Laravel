@extends('layouts.app')

@section('title', evomi_l('Wishlist | Evomi', 'Wishlist | Evomi'))

@section('content')
<x-profile-shell>
    <div x-data="evomiProfileWishlist" class="profile-page-card">
        <div
            x-show="loading"
            x-cloak
            class="profile-page-card__loader absolute inset-0 z-10"
        >
            <div class="w-8 h-8 border-4 border-gray-200 border-t-[#1172BA] rounded-full animate-spin"></div>
            <p class="text-gray-500 font-medium text-sm">{{ evomi_l('Memuat wishlist...', 'Loading wishlist...') }}</p>
        </div>

        <div
            x-show="!loading"
            x-cloak
            class="profile-page-card__body"
        >
            <div class="relative shrink-0 px-5 sm:px-7 py-5 text-white" style="background: linear-gradient(135deg, #1172BA 0%, #1a7fc4 52%, #0e6aad 100%)">
                <div class="pointer-events-none absolute inset-0 opacity-30" style="background-image: radial-gradient(circle at 12% 20%, rgba(255,255,255,0.35), transparent 40%), radial-gradient(circle at 90% 0%, rgba(255,255,255,0.18), transparent 35%)"></div>
                <div class="relative flex items-start justify-between gap-3">
                    <div class="min-w-0 flex items-start gap-3">
                        <span class="w-10 h-10 rounded-2xl bg-white/15 border border-white/25 flex items-center justify-center shrink-0">
                            <svg class="w-[18px] h-[18px]" fill="currentColor" viewBox="0 0 24 24"><path d="M11.645 20.91a.75.75 0 0 0 .71 0C18.75 17.09 22.5 12.82 22.5 8.625A5.25 5.25 0 0 0 12 5.197 5.25 5.25 0 0 0 1.5 8.625c0 4.195 3.75 8.465 10.145 12.285Z"/></svg>
                        </span>
                        <div class="min-w-0 pt-0.5">
                            <h1 class="text-lg sm:text-xl font-bold tracking-tight">Wishlist</h1>
                            <p class="text-[12px] sm:text-sm text-white/85 font-medium mt-0.5">{{ evomi_l('Koleksi aroma favoritmu — siap dipindah ke keranjang kapan saja.', 'Your favorite scents — ready to move to cart anytime.') }}</p>
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
                        <a
                            href="{{ route('profile.cart') }}"
                            data-soft-nav
                            class="inline-flex items-center gap-1.5 text-[11px] font-semibold px-3 py-1.5 rounded-full bg-white text-[#1172BA] hover:bg-white/90 transition shadow-sm"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z"/></svg>
                            {{ evomi_l('Keranjang', 'Cart') }}
                        </a>
                    </div>
                </div>
            </div>

            <div class="profile-page-card__scroll p-5 sm:p-7 bg-slate-50/80">
                <div x-show="error" x-cloak class="rounded-2xl border border-rose-100 bg-rose-50/70 p-8 text-center mb-4">
                    <p class="text-rose-600 mb-4 font-medium text-sm" x-text="error"></p>
                    <button type="button" @click="load()" class="px-6 py-2.5 text-white rounded-xl font-semibold text-sm bg-[#DD74A5] hover:bg-[#c44d86] transition">{{ evomi_l('Coba Lagi', 'Try Again') }}</button>
                </div>

                <div x-show="!error && items.length === 0" x-cloak class="rounded-2xl border border-dashed border-pink-200/80 bg-white px-6 py-14 text-center">
                    <div class="w-16 h-16 rounded-2xl bg-[#DD74A5]/12 flex items-center justify-center mx-auto mb-4">
                        <svg class="w-7 h-7 text-[#DD74A5]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z"/></svg>
                    </div>
                    <h2 class="text-lg font-bold text-slate-900 mb-2">{{ evomi_l('Wishlist masih kosong', 'Your wishlist is empty') }}</h2>
                    <p class="text-slate-500 text-sm mb-6 max-w-sm mx-auto">{{ evomi_l('Simpan produk favoritmu di sini biar gampang ditemukan lagi.', 'Save your favorite products here for easy access later.') }}</p>
                    <a href="{{ route('belanja') }}" data-soft-nav class="inline-flex items-center gap-2 px-6 py-2.5 bg-[#DD74A5] text-white rounded-xl font-semibold text-sm hover:bg-[#c44d86] transition">{{ evomi_l('Jelajahi Produk', 'Browse Products') }}</a>
                </div>

                <div x-show="items.length > 0" x-cloak class="evomi-wishlist-page__list space-y-3">
                    <template x-for="(item, index) in items" :key="item.id">
                        <article
                            class="evomi-wishlist-page__card group/card"
                            :class="{ 'is-removing': removingId === item.id, 'is-adding': addingId === item.id }"
                            :style="{ '--wl-accent': item.accent || '#DD74A5', '--wl-delay': (index * 40) + 'ms' }"
                        >
                            <button
                                type="button"
                                class="evomi-wishlist-page__thumb"
                                :style="{ backgroundColor: (item.accent || '#DD74A5') + '18' }"
                                @click="goProduct(item)"
                                :aria-label="$L('Lihat produk', 'View product')"
                            >
                                <img
                                    :src="item.imageUrl"
                                    :alt="item.title"
                                    class="transition-transform duration-500 ease-out group-hover/card:scale-110"
                                    x-on:error="$el.style.display='none'"
                                >
                                <span class="evomi-wishlist-page__heart" aria-hidden="true">
                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M11.645 20.91a.75.75 0 0 0 .71 0C18.75 17.09 22.5 12.82 22.5 8.625A5.25 5.25 0 0 0 12 5.197 5.25 5.25 0 0 0 1.5 8.625c0 4.195 3.75 8.465 10.145 12.285Z"/></svg>
                                </span>
                            </button>

                            <div class="evomi-wishlist-page__body min-w-0">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <button
                                            type="button"
                                            class="text-left font-semibold text-slate-900 text-[15px] leading-snug line-clamp-2 hover:underline decoration-slate-300 underline-offset-2"
                                            @click="goProduct(item)"
                                            x-text="item.title"
                                        ></button>
                                        <p class="mt-1 text-[12px] text-slate-500 line-clamp-2 leading-relaxed" x-text="item.description"></p>
                                        <div class="mt-2.5 flex flex-wrap items-center gap-1.5">
                                            <span class="evomi-wishlist-page__chip" x-text="item.sizeLabel"></span>
                                            <span class="evomi-wishlist-page__chip" x-text="item.genderLabel"></span>
                                            <span class="evomi-wishlist-page__price" :style="{ color: item.accent || '#DD74A5' }" x-text="item.priceLabel"></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="evomi-wishlist-page__actions">
                                    <button
                                        type="button"
                                        class="evomi-wishlist-page__cart"
                                        :style="{ backgroundColor: item.accent || '#1172BA' }"
                                        :disabled="addingId === item.id || removingId === item.id"
                                        @click="moveToCart(item, $event)"
                                    >
                                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z"/></svg>
                                        <span x-text="addingId === item.id ? $L('Menambahkan...', 'Adding...') : $L('Masukkan Keranjang', 'Add to Cart')"></span>
                                    </button>

                                    <a
                                        :href="'/belanja/' + item.product_id"
                                        :data-accent="item.accent"
                                        data-soft-nav
                                        class="evomi-wishlist-page__detail"
                                    >
                                        {{ evomi_l('Lihat detail', 'View details') }}
                                        <svg class="w-3.5 h-3.5 transition-transform group-hover/card:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                                    </a>

                                    <button
                                        type="button"
                                        class="evomi-wishlist-page__remove"
                                        :disabled="removingId === item.id || addingId === item.id"
                                        @click="requestRemove(item)"
                                        :aria-label="$L('Hapus dari wishlist', 'Remove from wishlist')"
                                        :title="$L('Hapus dari wishlist', 'Remove from wishlist')"
                                    >
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                                    </button>
                                </div>
                            </div>
                        </article>
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
                <div class="relative bg-white rounded-3xl p-6 max-w-sm w-full shadow-2xl border border-slate-100 text-center space-y-4">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full" :class="modal.type === 'error' ? 'bg-rose-50' : 'bg-pink-50'">
                        <svg x-show="modal.type === 'confirm'" class="w-7 h-7 text-[#DD74A5]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z"/></svg>
                        <svg x-show="modal.type === 'error'" class="w-7 h-7 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                    </div>
                    <div class="space-y-2">
                        <h3 class="text-lg font-bold text-slate-900 tracking-tight" x-text="modal.type === 'confirm' ? $L('Hapus dari wishlist?', 'Remove from wishlist?') : $L('Gagal', 'Failed')"></h3>
                        <p class="text-sm text-slate-600 leading-relaxed" x-text="modal.message"></p>
                    </div>
                    <div class="flex gap-3 pt-1" x-show="modal.type === 'confirm'">
                        <button type="button" @click="closeModal()" class="flex-1 py-2.5 rounded-xl border border-slate-200 hover:bg-slate-50 text-sm font-semibold text-slate-700 transition">{{ evomi_l('Batal', 'Cancel') }}</button>
                        <button type="button" @click="confirmRemove()" class="flex-1 py-2.5 bg-rose-500 hover:bg-rose-600 text-white rounded-xl text-sm font-semibold transition">{{ evomi_l('Ya, Hapus', 'Yes, Remove') }}</button>
                    </div>
                    <button type="button" x-show="modal.type === 'error'" @click="closeModal()" class="w-full py-2.5 rounded-xl text-sm font-semibold text-white bg-[#DD74A5] hover:bg-[#c44d86] transition">{{ evomi_l('Mengerti', 'Got it') }}</button>
                </div>
            </div>
        </template>
    </div>
</x-profile-shell>
@endsection
