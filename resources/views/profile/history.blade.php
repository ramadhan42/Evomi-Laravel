@extends('layouts.app')

@section('title', evomi_l('Riwayat Belanja | Evomi', 'Order History | Evomi'))

@section('content')
<x-profile-shell>
    <div x-data="evomiProfileHistory">
        <div
            x-show="loading"
            x-cloak
            class="rounded-[28px] overflow-hidden border border-gray-100 min-h-[400px] flex flex-col items-center justify-center bg-white"
        >
            <div class="w-8 h-8 border-4 border-gray-200 border-t-[#1172BA] rounded-full animate-spin mb-4"></div>
            <p class="text-gray-500 font-medium text-sm">{{ evomi_l('Memuat riwayat belanja...', 'Loading order history...') }}</p>
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
                            <svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                        </span>
                        <div class="min-w-0 pt-0.5">
                            <h1 class="text-lg sm:text-xl font-bold tracking-tight">{{ evomi_l('Riwayat Belanja', 'Order History') }}</h1>
                            <p class="text-[12px] sm:text-sm text-white/80 font-medium mt-0.5">{{ evomi_l('Pantau status pesanan dan konfirmasi penerimaan paket.', 'Track order status and confirm package delivery.') }}</p>
                        </div>
                    </div>
                    <span
                        x-show="groups.length > 0"
                        x-cloak
                        class="shrink-0 self-start text-[11px] sm:text-xs font-semibold px-3 py-1.5 rounded-full bg-white/15 border border-white/25"
                    >
                        <span x-text="groups.length"></span> {{ evomi_l('pesanan', 'orders') }}
                    </span>
                </div>
            </div>

            <div class="p-5 sm:p-7 bg-white">
                <div x-show="error" x-cloak class="rounded-2xl border border-rose-100 bg-rose-50/50 p-10 text-center mb-4">
                    <p class="text-rose-600 mb-4 font-medium text-sm" x-text="error"></p>
                    <button type="button" @click="load()" class="px-6 py-2.5 text-white rounded-xl font-semibold text-sm bg-[#1172BA] hover:bg-[#0d5a94]">{{ evomi_l('Coba Lagi', 'Try Again') }}</button>
                </div>

                <div x-show="!error && groups.length === 0" x-cloak class="rounded-2xl border border-dashed border-gray-200 px-6 py-14 text-center">
                    <div class="w-16 h-16 rounded-2xl bg-[#1172BA]/10 flex items-center justify-center mx-auto mb-4">
                        <svg class="w-7 h-7 text-[#1172BA]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/></svg>
                    </div>
                    <h2 class="text-lg font-bold text-slate-900 mb-2">{{ evomi_l('Riwayat belanja kosong', 'No order history yet') }}</h2>
                    <p class="text-slate-500 text-sm mb-6 max-w-sm mx-auto">{{ evomi_l('Belum ada pembelian. Mulai jelajahi koleksi aroma Evomi.', 'No purchases yet. Start exploring the Evomi scent collection.') }}</p>
                    <a href="{{ route('belanja') }}" data-soft-nav class="inline-flex items-center gap-2 px-6 py-2.5 bg-[#1172BA] text-white rounded-xl font-semibold text-sm hover:bg-[#0d5a94]">{{ evomi_l('Mulai Belanja', 'Start Shopping') }}</a>
                </div>

                <div x-show="!error && groups.length > 0" x-cloak class="space-y-3">
                    <template x-for="group in pagedGroups" :key="group.groupId">
                        <a
                            :href="'/profile/history/' + group.groupId"
                            data-soft-nav
                            class="flex flex-col sm:flex-row sm:items-center justify-between p-4 md:p-5 rounded-2xl border border-gray-100 bg-white hover:border-slate-200 transition-all gap-4 cursor-pointer group"
                        >
                            <div class="flex items-start gap-4 w-full sm:w-auto overflow-hidden min-w-0">
                                <div
                                    class="w-16 h-16 sm:w-20 sm:h-20 rounded-xl flex-shrink-0 flex items-center justify-center overflow-hidden relative p-1.5 border border-white/40"
                                    :style="{ backgroundColor: (group.accent || '#1172BA') + '14' }"
                                >
                                    <img
                                        :src="group.imageUrl"
                                        :alt="group.productTitle"
                                        class="max-h-full max-w-full w-auto h-auto object-contain group-hover:scale-105 transition-transform duration-300"
                                        x-on:error="$el.style.display='none'"
                                    >
                                    <span
                                        x-show="group.extraCount > 0"
                                        x-cloak
                                        class="absolute bottom-1 right-1 bg-slate-900/75 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-md"
                                        x-text="'+' + group.extraCount"
                                    ></span>
                                </div>

                                <div class="flex-1 min-w-0 py-0.5">
                                    <p class="font-bold text-xs mb-1 tracking-wide text-[#1172BA]" x-text="group.invoice"></p>
                                    <p class="font-semibold text-slate-900 text-[15px] truncate mb-1.5">
                                        <span x-text="group.productTitle"></span>
                                        <span x-show="group.extraCount > 0" class="text-slate-500 font-normal text-sm" x-text="' (+' + group.extraCount + ' ' + $L('Produk Lain', 'More Products') + ')'"></span>
                                    </p>
                                    <div class="flex flex-wrap items-center gap-2 md:gap-3 text-sm text-slate-500">
                                        <span x-text="group.dateLabel"></span>
                                        <span class="w-1 h-1 bg-slate-300 rounded-full hidden sm:block"></span>
                                        <span><span x-text="group.quantity"></span> {{ evomi_l('Barang', 'Items') }}</span>
                                        <span class="w-1 h-1 bg-slate-300 rounded-full hidden sm:block"></span>
                                        <span class="font-bold text-slate-900" x-text="group.totalLabel"></span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center justify-between sm:justify-end gap-2.5 w-full sm:w-auto pt-3 sm:pt-0 border-t sm:border-0 border-slate-100" @click.stop>
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold border" :class="group.paymentClass" x-text="group.paymentLabel"></span>
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold border" :class="group.statusClass">
                                        <span class="w-1.5 h-1.5 rounded-full" :class="group.statusDot"></span>
                                        <span x-text="group.statusLabel"></span>
                                    </span>
                                </div>

                                <a
                                    x-show="group.isAwaitingPayment && group.paymentUrl"
                                    x-cloak
                                    :href="group.paymentUrl"
                                    data-soft-nav
                                    class="flex items-center gap-1.5 px-3 py-1.5 bg-amber-500 text-white hover:bg-amber-600 rounded-xl text-xs font-bold transition-colors"
                                >
                                    {{ evomi_l('Bayar', 'Pay') }}
                                </a>

                                <button
                                    type="button"
                                    x-show="group.canConfirm"
                                    x-cloak
                                    @click.prevent.stop="requestConfirm(group)"
                                    class="flex items-center gap-1.5 px-3 py-1.5 bg-emerald-500 text-white hover:bg-emerald-600 rounded-xl text-xs font-bold transition-colors"
                                    :title="$L('Konfirmasi Pesanan Diterima', 'Confirm Order Received')"
                                >
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                                    <span class="hidden sm:inline">{{ evomi_l('Diterima', 'Received') }}</span>
                                </button>

                                <button
                                    type="button"
                                    x-show="group.canDelete"
                                    x-cloak
                                    @click.prevent.stop="requestRemove(group)"
                                    class="p-2 text-rose-500 hover:text-white hover:bg-rose-500 rounded-xl transition-colors bg-rose-50 border border-rose-100"
                                    :title="$L('Hapus Riwayat Pesanan', 'Delete Order History')"
                                >
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                                </button>

                                <span class="hidden sm:inline-flex items-center gap-1 text-xs font-semibold text-slate-400 group-hover:text-slate-700 transition-colors">
                                    {{ evomi_l('Lihat detail', 'View details') }}
                                    <svg class="w-4 h-4 group-hover:translate-x-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                                </span>
                            </div>
                        </a>
                    </template>

                    <div class="flex items-center justify-center gap-4 pt-5" x-show="pageCount > 1">
                        <button type="button" @click="page = Math.max(1, page - 1)" :disabled="page <= 1" class="p-2 rounded-xl bg-white border border-slate-200 hover:bg-slate-50 disabled:opacity-30 transition-all">
                            <svg class="w-5 h-5 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
                        </button>
                        <span class="text-sm font-medium text-slate-600">
                            {{ evomi_l('Halaman', 'Page') }} <span class="font-bold text-[#1172BA]" x-text="page"></span> {{ evomi_l('dari', 'of') }} <span x-text="pageCount"></span>
                        </span>
                        <button type="button" @click="page = Math.min(pageCount, page + 1)" :disabled="page >= pageCount" class="p-2 rounded-xl bg-white border border-slate-200 hover:bg-slate-50 disabled:opacity-30 transition-all">
                            <svg class="w-5 h-5 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
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
                <div class="absolute inset-0" @click="modal.type !== 'loading' && closeModal()"></div>
                <div class="relative rounded-3xl p-6 max-w-sm w-full text-center space-y-4 shadow-2xl border border-white/20" style="background: linear-gradient(135deg, #1172BA 0%, #0e6aad 100%)">
                    <div class="mx-auto flex items-center justify-center h-14 w-14 rounded-2xl bg-white/15">
                        <template x-if="modal.type === 'confirm' && modal.variant === 'delete'">
                            <svg class="w-7 h-7 text-amber-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                        </template>
                        <template x-if="modal.type === 'confirm' && modal.variant !== 'delete'">
                            <svg class="w-7 h-7 text-emerald-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                        </template>
                        <template x-if="modal.type === 'loading'">
                            <div class="w-7 h-7 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                        </template>
                        <template x-if="modal.type === 'error'">
                            <svg class="w-7 h-7 text-rose-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                        </template>
                    </div>
                    <div class="space-y-2">
                        <h3 class="text-lg font-bold text-white tracking-tight" x-text="modal.title"></h3>
                        <p class="text-sm text-white/80 leading-relaxed" x-text="modal.message"></p>
                    </div>
                    <div class="flex gap-3 pt-1" x-show="modal.type === 'confirm'">
                        <button type="button" @click="closeModal()" class="w-full font-semibold py-3 rounded-xl text-sm bg-white/15 text-white hover:bg-white/25 transition">{{ evomi_l('Batal', 'Cancel') }}</button>
                        <button
                            type="button"
                            @click="runModalAction()"
                            class="w-full font-semibold py-3 rounded-xl text-sm text-white transition"
                            :class="modal.variant === 'delete' ? 'bg-rose-500 hover:bg-rose-600' : 'bg-emerald-500 hover:bg-emerald-600'"
                            x-text="modal.confirmText"
                        ></button>
                    </div>
                    <button type="button" x-show="modal.type === 'error'" @click="closeModal()" class="w-full mt-1 bg-white font-bold py-3 rounded-xl text-sm transition hover:bg-blue-50 text-[#1172BA]">{{ evomi_l('Tutup', 'Close') }}</button>
                </div>
            </div>
        </template>
    </div>
</x-profile-shell>
@endsection
