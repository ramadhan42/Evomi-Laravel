@extends('layouts.app')

@section('title', 'Detail Pesanan | Evomi')

@section('content')
<x-profile-shell>
    <div x-data="evomiProfileHistoryShow(@js($orderId))" class="space-y-6">
        <a href="{{ route('profile.history') }}" data-soft-nav class="inline-flex text-sm font-semibold text-[#1172BA] hover:underline">← Kembali ke Riwayat</a>

        <div x-show="loading" x-cloak class="py-16 flex justify-center">
            <div class="w-8 h-8 border-4 border-gray-200 border-t-[#1172BA] rounded-full animate-spin"></div>
        </div>

        <div x-show="!loading && error" x-cloak class="rounded-2xl bg-red-50 border border-red-100 text-red-700 px-4 py-3 text-sm" x-text="error"></div>

        <div x-show="!loading && !error && group" x-cloak class="space-y-6">
            <div class="profile-brand-header rounded-2xl sm:rounded-3xl p-6 sm:p-8 text-white" style="background: linear-gradient(135deg, #1172BA 0%, #0d5a94 100%)">
                <h1 class="text-2xl sm:text-3xl font-bold">Detail Pesanan</h1>
                <p class="mt-1 text-white/80 text-sm" x-text="group.invoice"></p>
            </div>

            <div class="rounded-2xl border border-gray-100 bg-white p-5 space-y-3">
                <div class="flex flex-wrap gap-2">
                    <span class="text-[10px] font-medium px-2 py-0.5 rounded-md border" :class="group.statusClass" x-text="group.statusLabel"></span>
                    <span class="text-[10px] font-medium px-2 py-0.5 rounded-md border" :class="group.paymentClass" x-text="group.paymentLabel"></span>
                </div>
                <button
                    type="button"
                    x-show="group.canConfirm"
                    class="text-xs font-semibold px-4 py-2 rounded-xl bg-emerald-500 text-white"
                    @click="confirmGroup()"
                >Konfirmasi Pesanan Diterima</button>
            </div>

            <div class="rounded-2xl border border-gray-100 bg-white p-5 space-y-4">
                <h2 class="font-semibold text-gray-900">Informasi Produk</h2>
                <template x-for="item in group.items" :key="item.id">
                    <div class="flex gap-4 border-t border-gray-50 pt-4 first:border-0 first:pt-0">
                        <div class="w-16 h-16 rounded-xl overflow-hidden bg-gray-50 border shrink-0">
                            <img :src="item.imageUrl" :alt="item.title" class="w-full h-full object-cover">
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold text-sm text-gray-900" x-text="item.title"></p>
                            <p class="text-xs text-gray-500 mt-1" x-text="item.priceLabel + ' × ' + item.quantity"></p>
                            <p class="text-sm font-bold text-gray-900 mt-2" x-text="item.lineTotalLabel"></p>
                        </div>
                    </div>
                </template>
            </div>

            <div class="rounded-2xl border border-gray-100 bg-white p-5 space-y-2 text-sm">
                <h2 class="font-semibold text-gray-900 mb-3">Rincian Pembayaran</h2>
                <div class="flex justify-between text-gray-600"><span>Metode</span><span x-text="group.paymentMethod || '—'"></span></div>
                <div class="flex justify-between text-gray-600"><span>Tanggal</span><span x-text="group.dateLabel"></span></div>
                <div class="flex justify-between text-gray-600"><span>Subtotal</span><span x-text="group.subtotalLabel"></span></div>
                <div class="flex justify-between text-gray-600"><span>Ongkir</span><span x-text="group.shippingLabel"></span></div>
                <div class="flex justify-between text-gray-600"><span>Promo</span><span x-text="group.promoLabel"></span></div>
                <div class="flex justify-between font-bold text-gray-900 border-t border-gray-100 pt-3"><span>Total Belanja</span><span x-text="group.totalLabel"></span></div>
            </div>
        </div>
    </div>
</x-profile-shell>
@endsection
