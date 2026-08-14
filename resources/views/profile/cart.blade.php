@extends('layouts.app')

@section('title', evomi_l('Keranjang Belanja | Evomi', 'Shopping Cart | Evomi'))

@section('content')
<x-profile-shell>
    <div x-data="evomiProfileCart" class="profile-page-card">
        <div
            x-show="loading"
            x-cloak
            class="profile-page-card__loader absolute inset-0 z-10"
        >
            <div class="w-8 h-8 border-4 border-gray-200 border-t-[#1172BA] rounded-full animate-spin"></div>
            <p class="text-gray-500 font-medium text-sm">{{ evomi_l('Memuat keranjang...', 'Loading cart...') }}</p>
        </div>

        <div x-show="!loading" x-cloak class="profile-page-card__body">
            <div class="relative shrink-0 px-5 sm:px-7 py-5 text-white" style="background: linear-gradient(135deg, #1172BA 0%, #1a7fc4 52%, #0e6aad 100%)">
                <div class="pointer-events-none absolute inset-0 opacity-30" style="background-image: radial-gradient(circle at 12% 20%, rgba(255,255,255,0.35), transparent 40%), radial-gradient(circle at 90% 0%, rgba(255,255,255,0.18), transparent 35%)"></div>
                <div class="relative flex items-start justify-between gap-3">
                    <div class="min-w-0 flex items-start gap-3">
                        <span class="w-10 h-10 rounded-2xl bg-white/15 border border-white/25 flex items-center justify-center shrink-0">
                            <svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/></svg>
                        </span>
                        <div class="min-w-0 pt-0.5">
                            <h1 class="text-lg sm:text-xl font-bold tracking-tight">{{ evomi_l('Keranjang Belanja', 'Shopping Cart') }}</h1>
                            <p class="text-[12px] sm:text-sm text-white/85 font-medium mt-0.5">
                                <span x-show="items.length > 0" x-cloak><span x-text="itemCount"></span> {{ evomi_l('item di keranjang', 'items in cart') }}</span>
                                <span x-show="items.length === 0" x-cloak>{{ evomi_l('Siap checkout? Cek ulang item favoritmu dulu.', 'Ready to checkout? Review your favorite items first.') }}</span>
                            </p>
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
                            href="{{ route('belanja') }}"
                            data-soft-nav
                            class="inline-flex items-center gap-1.5 text-[11px] font-semibold px-3 py-1.5 rounded-full bg-white text-[#0e6aad] hover:bg-white/90 transition shadow-sm"
                        >
                            {{ evomi_l('Belanja', 'Shop') }}
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                        </a>
                    </div>
                </div>
            </div>

            <div class="profile-page-card__scroll p-5 sm:p-7 bg-slate-50/80">
                <div x-show="error" x-cloak class="rounded-2xl border border-rose-100 bg-rose-50/70 p-8 text-center mb-4">
                    <p class="text-rose-600 mb-4 font-medium text-sm" x-text="error"></p>
                    <button type="button" @click="load()" class="px-6 py-2.5 text-white rounded-xl font-semibold text-sm bg-[#1172BA] hover:bg-[#0d5a94] transition">{{ evomi_l('Coba Lagi', 'Try Again') }}</button>
                </div>

                <div x-show="!error && items.length === 0" x-cloak class="rounded-2xl border border-dashed border-sky-200/80 bg-white px-6 py-14 text-center">
                    <div class="w-16 h-16 rounded-2xl bg-[#1172BA]/12 flex items-center justify-center mx-auto mb-4">
                        <svg class="w-7 h-7 text-[#1172BA]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/></svg>
                    </div>
                    <h2 class="text-lg font-bold text-slate-900 mb-2">{{ evomi_l('Keranjang masih kosong', 'Your cart is empty') }}</h2>
                    <p class="text-slate-500 text-sm mb-6 max-w-sm mx-auto">{{ evomi_l('Jelajahi koleksi aroma Evomi dan temukan yang paling “gue banget”.', 'Explore Evomi scents and find your perfect match.') }}</p>
                    <a href="{{ route('belanja') }}" data-soft-nav class="inline-flex items-center gap-2 px-6 py-2.5 bg-[#1172BA] text-white rounded-xl font-semibold text-sm hover:bg-[#0d5a94] transition">{{ evomi_l('Mulai Belanja', 'Start Shopping') }}</a>
                </div>

                <div x-show="items.length > 0" x-cloak class="space-y-3">
                    <template x-for="(item, index) in items" :key="item.id">
                        <article
                            class="evomi-profile-list__card group/card"
                            :class="{ 'is-removing': removingId === item.id, 'is-adding': updatingId === item.id }"
                            :style="{ '--wl-accent': item.accent || '#1172BA', '--wl-delay': (index * 40) + 'ms' }"
                        >
                            <button
                                type="button"
                                class="evomi-profile-list__thumb"
                                :style="{ backgroundColor: (item.accent || '#1172BA') + '18' }"
                                @click="goProduct(item)"
                                :aria-label="$L('Lihat produk', 'View product')"
                            >
                                <img
                                    :src="item.imageUrl"
                                    :alt="item.title"
                                    class="transition-transform duration-500 ease-out group-hover/card:scale-110"
                                    x-on:error="$el.style.display='none'"
                                >
                            </button>

                            <div class="evomi-profile-list__body min-w-0">
                                <div class="min-w-0">
                                    <button
                                        type="button"
                                        class="text-left font-semibold text-slate-900 text-[15px] leading-snug line-clamp-2 hover:underline decoration-slate-300 underline-offset-2"
                                        @click="goProduct(item)"
                                        x-text="item.title"
                                    ></button>
                                    <p class="mt-1 text-[12px] text-slate-500 leading-relaxed">
                                        <span x-text="item.priceLabel"></span>
                                        <span class="text-slate-400"> · {{ evomi_l('per item', 'per item') }}</span>
                                    </p>
                                    <div class="mt-2.5 flex flex-wrap items-center gap-1.5">
                                        <span class="evomi-profile-list__chip" x-show="item.sizeLabel" x-text="item.sizeLabel"></span>
                                        <span class="evomi-profile-list__chip" x-show="item.genderLabel" x-text="item.genderLabel"></span>
                                        <span class="evomi-profile-list__chip">{{ evomi_l('Stok', 'Stock') }}&nbsp;<span x-text="item.stock"></span></span>
                                        <span class="evomi-profile-list__price" :style="{ color: item.accent || '#1172BA' }" x-text="item.lineTotalLabel"></span>
                                    </div>
                                </div>

                                <div class="evomi-profile-list__actions">
                                    <div class="evomi-profile-list__qty">
                                        <button type="button" @click="changeQty(item, -1)" :disabled="updatingId === item.id || removingId === item.id">−</button>
                                        <span x-text="updatingId === item.id ? '…' : item.quantity"></span>
                                        <button type="button" @click="changeQty(item, 1)" :disabled="updatingId === item.id || removingId === item.id || (item.stock && item.quantity >= item.stock)">+</button>
                                    </div>

                                    <a
                                        :href="'/belanja/' + item.product_id"
                                        data-soft-nav
                                        class="evomi-profile-list__detail"
                                    >
                                        {{ evomi_l('Lihat detail', 'View details') }}
                                        <svg class="w-3.5 h-3.5 transition-transform group-hover/card:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                                    </a>

                                    <button
                                        type="button"
                                        class="evomi-profile-list__remove"
                                        :disabled="updatingId === item.id || removingId === item.id"
                                        @click="requestRemove(item)"
                                        :aria-label="$L('Hapus', 'Remove')"
                                        :title="$L('Hapus dari keranjang', 'Remove from cart')"
                                    >
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                                    </button>
                                </div>
                            </div>
                        </article>
                    </template>

                    <div class="evomi-profile-list__summary space-y-3.5">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-slate-900">{{ evomi_l('Ringkasan Belanja', 'Order Summary') }}</p>
                                <p class="text-[12px] text-slate-500 mt-0.5" x-text="itemCount + ' ' + $L('item', 'items')"></p>
                            </div>
                            <div class="text-right">
                                <p class="text-[11px] text-slate-400 font-medium">{{ evomi_l('Subtotal', 'Subtotal') }}</p>
                                <p class="text-base font-extrabold text-[#1172BA]" x-text="subtotalLabel"></p>
                            </div>
                        </div>
                        <button
                            type="button"
                            class="evomi-cart-checkout"
                            :class="{ 'is-busy': checkingOut }"
                            :disabled="checkingOut || !items.length"
                            @click="goCheckout()"
                        >
                            <span class="evomi-cart-checkout__shine" aria-hidden="true"></span>
                            <span class="evomi-cart-checkout__icon" aria-hidden="true">
                                <svg x-show="!checkingOut" class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007Z"/></svg>
                                <span x-show="checkingOut" x-cloak class="evomi-cart-checkout__spinner"></span>
                            </span>
                            <span class="evomi-cart-checkout__copy min-w-0">
                                <span class="evomi-cart-checkout__label" x-text="checkingOut ? $L('Menuju checkout...', 'Going to checkout...') : $L('Checkout Sekarang', 'Checkout Now')"></span>
                                <span class="evomi-cart-checkout__hint" x-show="!checkingOut" x-cloak x-text="itemCount + ' ' + $L('item · lanjut ke pembayaran', 'items · continue to payment')"></span>
                            </span>
                            <span class="evomi-cart-checkout__amount" x-show="!checkingOut" x-cloak>
                                <span x-text="subtotalLabel"></span>
                                <svg class="evomi-cart-checkout__arrow w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                            </span>
                        </button>
                    </div>
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
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full" :class="modal.type === 'error' ? 'bg-rose-50' : 'bg-sky-50'">
                        <svg x-show="modal.type === 'confirm'" class="w-7 h-7 text-[#1172BA]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/></svg>
                        <svg x-show="modal.type === 'error'" class="w-7 h-7 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                    </div>
                    <div class="space-y-2">
                        <h3 class="text-lg font-bold text-slate-900 tracking-tight" x-text="modal.type === 'confirm' ? $L('Hapus item?', 'Remove item?') : $L('Gagal', 'Failed')"></h3>
                        <p class="text-sm text-slate-600 leading-relaxed" x-text="modal.message"></p>
                    </div>
                    <div class="flex gap-3 pt-1" x-show="modal.type === 'confirm'">
                        <button type="button" @click="closeModal()" class="flex-1 py-2.5 rounded-xl border border-slate-200 hover:bg-slate-50 text-sm font-semibold text-slate-700 transition">{{ evomi_l('Batal', 'Cancel') }}</button>
                        <button type="button" @click="confirmModal()" class="flex-1 py-2.5 bg-rose-500 hover:bg-rose-600 text-white rounded-xl text-sm font-semibold transition">{{ evomi_l('Ya, Hapus', 'Yes, Remove') }}</button>
                    </div>
                    <button type="button" x-show="modal.type === 'error'" @click="closeModal()" class="w-full py-2.5 rounded-xl text-sm font-semibold text-white bg-[#1172BA] hover:bg-[#0d5a94] transition">{{ evomi_l('Mengerti', 'Got it') }}</button>
                </div>
            </div>
        </template>
    </div>
</x-profile-shell>
@endsection
