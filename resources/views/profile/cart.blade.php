@extends('layouts.app')

@section('title', 'Keranjang Belanja | Evomi')

@section('content')
<x-profile-shell>
    <div x-data="evomiProfileCart" class="space-y-6">
        <div class="profile-brand-header rounded-2xl sm:rounded-3xl p-6 sm:p-8 text-white flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4" style="background: linear-gradient(135deg, #1172BA 0%, #0d5a94 100%)">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold">Keranjang Belanja</h1>
                <p class="mt-1 text-white/80 text-sm"><span x-text="items.length"></span> item di keranjang</p>
            </div>
            <a href="{{ route('belanja') }}" data-soft-nav class="inline-flex items-center justify-center px-5 py-2.5 rounded-full bg-white text-[#1172BA] text-sm font-semibold">Lanjut Belanja</a>
        </div>

        <div x-show="loading" x-cloak class="py-16 flex justify-center">
            <div class="w-8 h-8 border-4 border-gray-200 border-t-[#1172BA] rounded-full animate-spin"></div>
        </div>

        <div x-show="!loading && error" x-cloak class="rounded-2xl bg-red-50 border border-red-100 text-red-700 px-4 py-3 text-sm" x-text="error"></div>

        <div x-show="!loading && !error && items.length === 0" x-cloak class="rounded-3xl border border-dashed border-gray-200 py-16 text-center">
            <p class="text-gray-500">Keranjang masih kosong.</p>
            <a href="{{ route('belanja') }}" data-soft-nav class="inline-flex mt-4 px-5 py-2.5 rounded-full bg-[#1172BA] text-white text-sm font-semibold">Mulai Belanja</a>
        </div>

        <div x-show="!loading && items.length > 0" x-cloak class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-4">
                <template x-for="item in items" :key="item.id">
                    <div class="flex gap-4 rounded-2xl border border-gray-100 bg-white p-4">
                        <a :href="'/belanja/' + item.product_id" data-soft-nav class="w-20 h-20 rounded-xl overflow-hidden bg-gray-50 border border-gray-100 shrink-0">
                            <img :src="item.imageUrl" :alt="item.title" class="w-full h-full object-cover" x-on:error="$el.src=''">
                        </a>
                        <div class="min-w-0 flex-1">
                            <a :href="'/belanja/' + item.product_id" data-soft-nav class="font-semibold text-gray-900 text-sm hover:text-[#1172BA]" x-text="item.title"></a>
                            <p class="text-xs text-gray-500 mt-1" x-text="item.priceLabel + ' · Stok ' + item.stock"></p>
                            <div class="mt-3 flex items-center justify-between gap-3">
                                <div class="inline-flex items-center rounded-full border border-gray-200">
                                    <button type="button" class="w-8 h-8 text-gray-600" @click="changeQty(item, -1)">−</button>
                                    <span class="w-8 text-center text-sm font-semibold" x-text="item.quantity"></span>
                                    <button type="button" class="w-8 h-8 text-gray-600" @click="changeQty(item, 1)">+</button>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="text-sm font-bold text-gray-900" x-text="item.lineTotalLabel"></span>
                                    <button type="button" class="text-red-500 text-xs font-semibold" @click="remove(item)">Hapus</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <aside class="rounded-2xl border border-gray-100 bg-white p-5 h-fit sticky top-6 space-y-4">
                <h2 class="font-semibold text-gray-900">Ringkasan</h2>
                <div class="flex justify-between text-sm text-gray-600">
                    <span>Subtotal</span>
                    <span x-text="subtotalLabel"></span>
                </div>
                <div class="flex justify-between text-base font-bold text-gray-900 border-t border-gray-100 pt-3">
                    <span>Total Belanja</span>
                    <span x-text="subtotalLabel"></span>
                </div>
                <button type="button" class="w-full py-3 rounded-2xl bg-[#1172BA] text-white text-sm font-semibold hover:bg-[#0d5a94]" @click="goCheckout()">Checkout Sekarang</button>
                <p x-show="toast" x-cloak class="text-xs text-center text-amber-600" x-text="toast"></p>
            </aside>
        </div>
    </div>
</x-profile-shell>
@endsection
