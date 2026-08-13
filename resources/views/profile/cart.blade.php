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
        <div class="relative shrink-0 px-5 sm:px-7 py-5 text-white" style="background: linear-gradient(135deg, #1172BA 0%, #1a7fc4 55%, #0e6aad 100%)">
            <div class="pointer-events-none absolute inset-0 opacity-30" style="background-image: radial-gradient(circle at 12% 20%, rgba(255,255,255,0.35), transparent 40%), radial-gradient(circle at 90% 0%, rgba(255,255,255,0.18), transparent 35%)"></div>
            <div class="relative flex items-start justify-between gap-3">
                <div class="min-w-0 flex items-start gap-3">
                    <span class="w-10 h-10 rounded-2xl bg-white/15 border border-white/25 flex items-center justify-center shrink-0">
                        <svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/></svg>
                    </span>
                    <div class="min-w-0 pt-0.5">
                        <div class="flex items-center gap-1.5">
                            <h1 class="text-lg sm:text-xl font-bold tracking-tight truncate">{{ evomi_l('Keranjang Belanja', 'Shopping Cart') }}</h1>
                        </div>
                        <p class="text-[12px] sm:text-sm text-white/80 font-medium mt-0.5">
                            <span x-show="items.length > 0" x-cloak><span x-text="itemCount"></span> {{ evomi_l('item di keranjang', 'items in cart') }}</span>
                            <span x-show="items.length === 0" x-cloak>{{ evomi_l('Siap checkout? Cek ulang item favoritmu dulu.', 'Ready to checkout? Review your favorite items first.') }}</span>
                        </p>
                    </div>
                </div>
                <a href="{{ route('belanja') }}" data-soft-nav class="shrink-0 hidden sm:inline-flex items-center gap-1.5 rounded-full bg-white/15 hover:bg-white/25 border border-white/20 px-3 py-1.5 text-[11px] font-semibold transition">
                    {{ evomi_l('Lanjut Belanja', 'Continue Shopping') }}
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                </a>
            </div>
        </div>

        <div class="profile-page-card__scroll p-5 sm:p-7 bg-white">
            <div x-show="error" x-cloak class="mb-4 rounded-2xl border border-rose-100 bg-rose-50 px-4 py-3 text-sm text-rose-700 font-medium" x-text="error"></div>

            <div x-show="!error && items.length === 0" x-cloak class="rounded-2xl border border-dashed border-gray-200 py-14 text-center px-6">
                <div class="w-16 h-16 rounded-2xl bg-[#1172BA]/10 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-7 h-7 text-[#1172BA]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/></svg>
                </div>
                <h2 class="text-lg font-bold text-slate-900 mb-2">{{ evomi_l('Keranjang masih kosong', 'Your cart is empty') }}</h2>
                <p class="text-slate-500 text-sm mb-6 max-w-sm mx-auto">{{ evomi_l('Jelajahi koleksi aroma Evomi dan temukan yang paling “gue banget”.', 'Explore Evomi scents and find your perfect match.') }}</p>
                <a href="{{ route('belanja') }}" data-soft-nav class="inline-flex items-center gap-2 px-6 py-2.5 bg-[#1172BA] text-white rounded-xl font-semibold text-sm hover:bg-[#0d5a94]">{{ evomi_l('Mulai Belanja', 'Start Shopping') }}</a>
            </div>

            <div x-show="items.length > 0" x-cloak class="grid grid-cols-1 lg:grid-cols-12 gap-5">
                <div class="lg:col-span-8 space-y-3">
                    <template x-for="item in items" :key="item.id">
                        <div class="bg-white rounded-2xl border border-gray-100 p-4 sm:p-5 flex flex-col sm:flex-row gap-4">
                            <a :href="'/belanja/' + item.product_id" data-soft-nav class="w-full sm:w-24 h-28 sm:h-24 rounded-2xl overflow-hidden shrink-0 flex items-center justify-center border border-gray-100" :style="{ backgroundColor: (item.accent || '#1172BA') + '12' }">
                                <img :src="item.imageUrl" :alt="item.title" class="max-h-full max-w-full object-contain p-2" x-on:error="$el.style.display='none'">
                            </a>
                            <div class="flex-1 min-w-0 flex flex-col">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <a :href="'/belanja/' + item.product_id" data-soft-nav class="font-bold text-gray-900 hover:underline line-clamp-2 text-[15px]" x-text="item.title"></a>
                                        <p class="text-sm text-gray-500 mt-1">
                                            <span x-text="item.priceLabel"></span>
                                            <span class="text-gray-400"> · {{ evomi_l('per item', 'per item') }}</span>
                                        </p>
                                        <p class="text-[11px] font-medium text-gray-400 mt-1" x-text="$L('Sisa stok ', 'Stock left ') + item.stock"></p>
                                    </div>
                                    <button type="button" @click="requestRemove(item)" :disabled="updatingId === item.id" class="shrink-0 p-2 rounded-xl text-gray-400 hover:text-rose-600 hover:bg-rose-50 border border-transparent hover:border-rose-100 transition disabled:opacity-40" :aria-label="$L('Hapus', 'Remove')">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                                    </button>
                                </div>

                                <div class="mt-auto pt-4 flex flex-wrap items-center justify-between gap-3">
                                    <div class="inline-flex items-center gap-1 rounded-xl border border-gray-200 bg-gray-50 p-1">
                                        <button type="button" class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-600 hover:bg-white hover:shadow-sm disabled:opacity-40" @click="changeQty(item, -1)" :disabled="updatingId === item.id">−</button>
                                        <span class="min-w-[28px] text-center text-sm font-bold text-gray-900" x-text="updatingId === item.id ? '…' : item.quantity"></span>
                                        <button type="button" class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-600 hover:bg-white hover:shadow-sm disabled:opacity-40" @click="changeQty(item, 1)" :disabled="updatingId === item.id || (item.stock && item.quantity >= item.stock)">+</button>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-[11px] text-gray-400 font-medium">{{ evomi_l('Total', 'Total') }}</p>
                                        <p class="text-sm font-bold text-[#1172BA]" x-text="item.lineTotalLabel"></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <aside class="lg:col-span-4">
                    <div class="rounded-2xl border border-gray-100 bg-white p-5 sticky top-6 space-y-4">
                        <h2 class="font-semibold text-gray-900">{{ evomi_l('Ringkasan', 'Summary') }}</h2>
                        <div class="flex justify-between text-sm text-gray-600">
                            <span>{{ evomi_l('Subtotal', 'Subtotal') }}</span>
                            <span x-text="subtotalLabel"></span>
                        </div>
                        <div class="flex justify-between text-xs text-gray-400">
                            <span x-text="itemCount + ' ' + $L('item', 'items')"></span>
                        </div>
                        <div class="flex justify-between text-base font-bold text-gray-900 border-t border-gray-100 pt-3">
                            <span>{{ evomi_l('Total Belanja', 'Order Total') }}</span>
                            <span class="text-[#1172BA]" x-text="subtotalLabel"></span>
                        </div>
                        <button type="button" class="w-full py-3 rounded-2xl bg-[#1172BA] text-white text-sm font-semibold hover:opacity-90 active:scale-[0.99] transition" @click="goCheckout()">{{ evomi_l('Checkout Sekarang', 'Checkout Now') }}</button>
                    </div>
                </aside>
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
                        <h3 class="text-lg font-bold text-slate-900 mb-2" x-text="modal.type === 'confirm' ? $L('Hapus item?', 'Remove item?') : $L('Gagal', 'Failed')"></h3>
                        <p class="text-sm text-slate-600 mb-6" x-text="modal.message"></p>
                        <div class="flex gap-2" x-show="modal.type === 'confirm'">
                            <button type="button" @click="closeModal()" class="flex-1 py-2.5 rounded-xl border border-slate-200 hover:bg-slate-50 text-sm font-medium">{{ evomi_l('Batal', 'Cancel') }}</button>
                            <button type="button" @click="confirmModal()" class="flex-1 py-2.5 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-sm font-semibold">{{ evomi_l('Ya, Hapus', 'Yes, Remove') }}</button>
                        </div>
                        <button type="button" x-show="modal.type === 'error'" @click="closeModal()" class="w-full py-2.5 rounded-xl text-sm font-semibold text-white bg-[#1172BA]">{{ evomi_l('Mengerti', 'Got it') }}</button>
                    </div>
                </div>
            </div>
        </template>
    </div>
</x-profile-shell>
@endsection
