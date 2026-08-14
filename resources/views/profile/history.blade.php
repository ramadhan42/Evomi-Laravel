@extends('layouts.app')

@section('title', evomi_l('Riwayat Belanja | Evomi', 'Order History | Evomi'))

@section('content')
<x-profile-shell>
    <div x-data="evomiProfileHistory" class="profile-page-card">
        <div
            x-show="loading"
            x-cloak
            class="profile-page-card__loader absolute inset-0 z-10"
        >
            <div class="w-8 h-8 border-4 border-gray-200 border-t-[#1172BA] rounded-full animate-spin"></div>
            <p class="text-gray-500 font-medium text-sm">{{ evomi_l('Memuat riwayat belanja...', 'Loading order history...') }}</p>
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
                            <svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                        </span>
                        <div class="min-w-0 pt-0.5">
                            <h1 class="text-lg sm:text-xl font-bold tracking-tight">{{ evomi_l('Riwayat Belanja', 'Order History') }}</h1>
                            <p class="text-[12px] sm:text-sm text-white/85 font-medium mt-0.5">{{ evomi_l('Pantau status pesanan dan konfirmasi penerimaan paket.', 'Track order status and confirm package delivery.') }}</p>
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

            <div class="profile-page-card__scroll p-5 sm:p-7 bg-slate-50/80">
                <div x-show="error" x-cloak class="rounded-2xl border border-rose-100 bg-rose-50/70 p-8 text-center mb-4">
                    <p class="text-rose-600 mb-4 font-medium text-sm" x-text="error"></p>
                    <button type="button" @click="load()" class="px-6 py-2.5 text-white rounded-xl font-semibold text-sm bg-[#5EA14A] hover:bg-[#3f7d33] transition">{{ evomi_l('Coba Lagi', 'Try Again') }}</button>
                </div>

                <div x-show="!error && groups.length === 0" x-cloak class="rounded-2xl border border-dashed border-emerald-200/80 bg-white px-6 py-14 text-center">
                    <div class="w-16 h-16 rounded-2xl bg-[#5EA14A]/12 flex items-center justify-center mx-auto mb-4">
                        <svg class="w-7 h-7 text-[#5EA14A]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/></svg>
                    </div>
                    <h2 class="text-lg font-bold text-slate-900 mb-2">{{ evomi_l('Riwayat belanja kosong', 'No order history yet') }}</h2>
                    <p class="text-slate-500 text-sm mb-6 max-w-sm mx-auto">{{ evomi_l('Belum ada pembelian. Mulai jelajahi koleksi aroma Evomi.', 'No purchases yet. Start exploring the Evomi scent collection.') }}</p>
                    <a href="{{ route('belanja') }}" data-soft-nav class="inline-flex items-center gap-2 px-6 py-2.5 bg-[#5EA14A] text-white rounded-xl font-semibold text-sm hover:bg-[#3f7d33] transition">{{ evomi_l('Mulai Belanja', 'Start Shopping') }}</a>
                </div>

                <div x-show="!error && groups.length > 0" x-cloak class="space-y-3">
                    <template x-for="(group, index) in pagedGroups" :key="group.groupId">
                        <article
                            class="evomi-profile-list__card group/card"
                            :style="{ '--wl-accent': group.accent || '#5EA14A', '--wl-delay': (index * 40) + 'ms' }"
                        >
                            <button
                                type="button"
                                class="evomi-profile-list__thumb"
                                :style="{ backgroundColor: (group.accent || '#5EA14A') + '18' }"
                                @click="goDetail(group)"
                                :aria-label="$L('Lihat detail', 'View details')"
                            >
                                <img
                                    :src="group.imageUrl"
                                    :alt="group.productTitle"
                                    class="transition-transform duration-500 ease-out group-hover/card:scale-110"
                                    x-on:error="$el.style.display='none'"
                                >
                                <span
                                    x-show="group.extraCount > 0"
                                    x-cloak
                                    class="evomi-profile-list__badge"
                                    x-text="'+' + group.extraCount"
                                ></span>
                            </button>

                            <div class="evomi-profile-list__body min-w-0">
                                <div class="min-w-0">
                                    <p class="text-[11px] font-bold tracking-wide mb-1" :style="{ color: group.accent || '#5EA14A' }" x-text="group.invoice"></p>
                                    <button
                                        type="button"
                                        class="text-left font-semibold text-slate-900 text-[15px] leading-snug line-clamp-2 hover:underline decoration-slate-300 underline-offset-2"
                                        @click="goDetail(group)"
                                    >
                                        <span x-text="group.productTitle"></span>
                                        <span x-show="group.extraCount > 0" class="text-slate-500 font-normal text-sm" x-text="' (+' + group.extraCount + ' ' + $L('Produk Lain', 'More Products') + ')'"></span>
                                    </button>
                                    <div class="mt-2.5 flex flex-wrap items-center gap-1.5">
                                        <span class="evomi-profile-list__chip" x-text="group.dateLabel"></span>
                                        <span class="evomi-profile-list__chip"><span x-text="group.quantity"></span>&nbsp;{{ evomi_l('barang', 'items') }}</span>
                                        <span
                                            x-show="group.paymentKey === 'success'"
                                            class="evomi-profile-list__chip !bg-emerald-50 !text-emerald-700 !border-emerald-100"
                                        >{{ evomi_l('Sudah dibayar', 'Paid') }}</span>
                                        <span
                                            x-show="group.paymentKey === 'awaiting'"
                                            class="evomi-profile-list__chip !bg-amber-50 !text-amber-700 !border-amber-100"
                                        >{{ evomi_l('Menunggu bayar', 'Awaiting payment') }}</span>
                                        <span
                                            x-show="group.paymentKey === 'cancelled' && group.showPaymentBadge"
                                            x-cloak
                                            class="evomi-profile-list__chip !bg-rose-50 !text-rose-700 !border-rose-100"
                                        >{{ evomi_l('Dibatalkan', 'Cancelled') }}</span>
                                        <span
                                            x-show="!group.paymentKey || group.paymentKey === 'pending'"
                                            class="evomi-profile-list__chip !bg-amber-50 !text-amber-700 !border-amber-100"
                                            x-text="group.paymentLabel || $L('Belum dibayar', 'Unpaid')"
                                        ></span>
                                        <span class="evomi-profile-list__chip" :class="group.statusClass" x-text="group.statusLabel"></span>
                                        <span class="evomi-profile-list__price" :style="{ color: group.accent || '#5EA14A' }" x-text="group.totalLabel"></span>
                                    </div>
                                </div>

                                <div class="evomi-profile-list__actions" @click.stop>
                                    <a
                                        x-show="group.isAwaitingPayment && group.paymentUrl"
                                        x-cloak
                                        :href="group.paymentUrl"
                                        data-soft-nav
                                        class="evomi-profile-list__cta"
                                        style="background:#F59E0B"
                                    >
                                        {{ evomi_l('Bayar Sekarang', 'Pay Now') }}
                                    </a>
                                    <a
                                        x-show="group.isAwaitingCod && group.paymentUrl"
                                        x-cloak
                                        :href="group.paymentUrl"
                                        data-soft-nav
                                        class="evomi-profile-list__cta"
                                        style="background:#F59E0B"
                                    >
                                        {{ evomi_l('Lihat tagihan', 'View bill') }}
                                    </a>

                                    <button
                                        type="button"
                                        x-show="group.canConfirm"
                                        x-cloak
                                        class="evomi-profile-list__cta"
                                        style="background:#10B981"
                                        @click.prevent="requestConfirm(group)"
                                    >
                                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                                        {{ evomi_l('Konfirmasi Diterima', 'Confirm Received') }}
                                    </button>

                                    <button
                                        type="button"
                                        class="evomi-profile-list__detail"
                                        @click="goDetail(group)"
                                    >
                                        {{ evomi_l('Lihat detail', 'View details') }}
                                        <svg class="w-3.5 h-3.5 transition-transform group-hover/card:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                                    </button>

                                    <button
                                        type="button"
                                        x-show="group.canDelete"
                                        x-cloak
                                        class="evomi-profile-list__remove"
                                        @click.prevent="requestRemove(group)"
                                        :aria-label="$L('Hapus Riwayat Pesanan', 'Delete Order History')"
                                        :title="$L('Hapus Riwayat Pesanan', 'Delete Order History')"
                                    >
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                                    </button>
                                </div>
                            </div>
                        </article>
                    </template>

                    <div class="flex items-center justify-center gap-4 pt-4" x-show="pageCount > 1">
                        <button type="button" @click="page = Math.max(1, page - 1)" :disabled="page <= 1" class="p-2 rounded-xl bg-white border border-slate-200 hover:bg-slate-50 disabled:opacity-30 transition-all">
                            <svg class="w-5 h-5 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
                        </button>
                        <span class="text-sm font-medium text-slate-600">
                            {{ evomi_l('Halaman', 'Page') }} <span class="font-bold text-[#5EA14A]" x-text="page"></span> {{ evomi_l('dari', 'of') }} <span x-text="pageCount"></span>
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
                <div class="relative bg-white rounded-3xl p-6 max-w-sm w-full text-center space-y-4 shadow-2xl border border-slate-100">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full" :class="modal.variant === 'delete' ? 'bg-rose-50' : 'bg-emerald-50'">
                        <template x-if="modal.type === 'confirm' && modal.variant === 'delete'">
                            <svg class="w-7 h-7 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                        </template>
                        <template x-if="modal.type === 'confirm' && modal.variant !== 'delete'">
                            <svg class="w-7 h-7 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                        </template>
                        <template x-if="modal.type === 'loading'">
                            <div class="w-7 h-7 border-2 border-slate-200 border-t-[#5EA14A] rounded-full animate-spin"></div>
                        </template>
                        <template x-if="modal.type === 'error'">
                            <svg class="w-7 h-7 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                        </template>
                    </div>
                    <div class="space-y-2">
                        <h3 class="text-lg font-bold text-slate-900 tracking-tight" x-text="modal.title"></h3>
                        <p class="text-sm text-slate-600 leading-relaxed" x-text="modal.message"></p>
                    </div>
                    <div class="flex gap-3 pt-1" x-show="modal.type === 'confirm'">
                        <button type="button" @click="closeModal()" class="w-full font-semibold py-3 rounded-xl text-sm border border-slate-200 text-slate-700 hover:bg-slate-50 transition">{{ evomi_l('Batal', 'Cancel') }}</button>
                        <button
                            type="button"
                            @click="runModalAction()"
                            class="w-full font-semibold py-3 rounded-xl text-sm text-white transition"
                            :class="modal.variant === 'delete' ? 'bg-rose-500 hover:bg-rose-600' : 'bg-emerald-500 hover:bg-emerald-600'"
                            x-text="modal.confirmText"
                        ></button>
                    </div>
                    <button type="button" x-show="modal.type === 'error'" @click="closeModal()" class="w-full mt-1 bg-[#5EA14A] font-bold py-3 rounded-xl text-sm text-white">{{ evomi_l('Tutup', 'Close') }}</button>
                </div>
            </div>
        </template>
    </div>
</x-profile-shell>
@endsection
