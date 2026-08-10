@extends('layouts.app')

@section('title', evomi_l('Menunggu Pembayaran | Evomi', 'Pending Payments | Evomi'))

@section('content')
<x-profile-shell>
    <div x-data="evomiProfilePayments">
        <div
            x-show="loading"
            x-cloak
            class="rounded-[28px] overflow-hidden border border-gray-100 min-h-[400px] flex flex-col items-center justify-center bg-white"
        >
            <div class="w-8 h-8 border-4 border-gray-200 border-t-[#F59E0B] rounded-full animate-spin mb-4"></div>
            <p class="text-gray-500 font-medium text-sm">{{ evomi_l('Memuat pembayaran tertunda...', 'Loading pending payments...') }}</p>
        </div>

        <div x-show="!loading" x-cloak class="relative rounded-[28px] overflow-hidden border border-gray-100 bg-white">
            <div class="relative px-5 sm:px-7 py-5 text-white" style="background: linear-gradient(135deg, #F59E0B 0%, #D97706 55%, #B45309 100%)">
                <div class="pointer flex items-start justify-between gap-3">
                    <div class="min-w-0 flex items-start gap-3">
                        <span class="w-10 h-10 rounded-2xl bg-white/15 border border-white/25 flex items-center justify-center shrink-0">
                            <svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z"/></svg>
                        </span>
                        <div class="min-w-0 pt-0.5">
                            <h1 class="text-lg sm:text-xl font-bold tracking-tight">{{ evomi_l('Menunggu Pembayaran', 'Pending Payments') }}</h1>
                            <p class="text-[12px] sm:text-sm text-white/80 font-medium mt-0.5">{{ evomi_l('Selesaikan pembayaran dalam 24 jam sebelum pesanan dibatalkan.', 'Complete payment within 24 hours before the order is cancelled.') }}</p>
                        </div>
                    </div>
                    <span x-show="items.length > 0" x-cloak class="shrink-0 text-[11px] font-semibold px-3 py-1.5 rounded-full bg-white/15 border border-white/25">
                        <span x-text="items.length"></span> {{ evomi_l('tagihan', 'bills') }}
                    </span>
                </div>
            </div>

            <div class="p-5 sm:p-7 bg-white">
                <div x-show="error" x-cloak class="rounded-2xl border border-rose-100 bg-rose-50/50 p-10 text-center mb-4">
                    <p class="text-rose-600 mb-4 font-medium text-sm" x-text="error"></p>
                    <button type="button" @click="load()" class="px-6 py-2.5 text-white rounded-xl font-semibold text-sm bg-[#F59E0B]">{{ evomi_l('Coba Lagi', 'Try Again') }}</button>
                </div>

                <div x-show="!error && items.length === 0" x-cloak class="rounded-2xl border border-dashed border-gray-200 px-6 py-14 text-center">
                    <div class="w-16 h-16 rounded-2xl bg-amber-50 flex items-center justify-center mx-auto mb-4">
                        <svg class="w-7 h-7 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                    </div>
                    <h2 class="text-lg font-bold text-slate-900 mb-2">{{ evomi_l('Tidak ada tagihan menunggu', 'No pending bills') }}</h2>
                    <p class="text-slate-500 text-sm mb-6 max-w-sm mx-auto">{{ evomi_l('Semua pembayaran sudah selesai, atau belum ada checkout QRIS / transfer bank.', 'All payments are settled, or you have no QRIS / bank transfer checkouts yet.') }}</p>
                    <a href="{{ route('belanja') }}" data-soft-nav class="inline-flex items-center gap-2 px-6 py-2.5 bg-[#1172BA] text-white rounded-xl font-semibold text-sm">{{ evomi_l('Mulai Belanja', 'Start Shopping') }}</a>
                </div>

                <div x-show="!error && items.length > 0" x-cloak class="space-y-3">
                    <template x-for="row in items" :key="row.invoice_id">
                        <a
                            :href="row.payment_url || ('/pembayaran/' + encodeURIComponent(row.invoice_id))"
                            data-soft-nav
                            class="flex flex-col sm:flex-row sm:items-center justify-between p-4 md:p-5 rounded-2xl border border-amber-100 bg-amber-50/40 hover:border-amber-200 transition-all gap-4"
                        >
                            <div class="flex items-start gap-4 min-w-0">
                                <div class="w-16 h-16 rounded-xl flex-shrink-0 flex items-center justify-center overflow-hidden p-1.5" :style="{ backgroundColor: (row.brand_color || '#F59E0B') + '22' }">
                                    <img x-show="row.image" :src="imageUrl(row.image)" class="max-h-full max-w-full object-contain" alt="">
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-bold text-slate-900 truncate" x-text="row.title + (row.extra_count > 0 ? ' +' + row.extra_count : '')"></p>
                                    <p class="text-[11px] text-gray-500 mt-0.5 break-all" x-text="row.invoice_id"></p>
                                    <p class="text-[11px] text-amber-700 font-semibold mt-1" x-text="row.payment_method"></p>
                                    <p class="text-[11px] text-gray-500 mt-1" x-text="'Sisa waktu: ' + formatCountdown(row.seconds_remaining)"></p>
                                </div>
                            </div>
                            <div class="sm:text-right shrink-0">
                                <p class="text-base font-bold" :style="{ color: row.brand_color || '#D97706' }" x-text="formatRupiah(row.amount)"></p>
                                <span class="inline-flex mt-2 text-[11px] font-bold px-2.5 py-1 rounded-full bg-amber-100 text-amber-800">{{ evomi_l('Bayar sekarang', 'Pay now') }}</span>
                            </div>
                        </a>
                    </template>
                </div>
            </div>
        </div>
    </div>
</x-profile-shell>
@endsection
