@extends('layouts.app')

@section('title', evomi_l('Pembayaran | Evomi', 'Payments | Evomi'))

@section('content')
<x-profile-shell>
    <div x-data="evomiProfilePayments" class="profile-page-card">
        <div
            x-show="loading"
            x-cloak
            class="profile-page-card__loader absolute inset-0 z-10"
        >
            <div class="w-8 h-8 border-4 border-gray-200 border-t-[#1172BA] rounded-full animate-spin"></div>
            <p class="text-gray-500 font-medium text-sm">{{ evomi_l('Memuat pembayaran tertunda...', 'Loading pending payments...') }}</p>
        </div>

        <div x-show="!loading" x-cloak class="profile-page-card__body">
            <div class="relative shrink-0 px-5 sm:px-7 py-5 text-white" style="background: linear-gradient(135deg, #1172BA 0%, #1a7fc4 52%, #0e6aad 100%)">
                <div class="pointer-events-none absolute inset-0 opacity-30" style="background-image: radial-gradient(circle at 12% 20%, rgba(255,255,255,0.35), transparent 40%), radial-gradient(circle at 90% 0%, rgba(255,255,255,0.18), transparent 35%)"></div>
                <div class="relative flex items-start justify-between gap-3">
                    <div class="min-w-0 flex items-start gap-3">
                        <span class="w-10 h-10 rounded-2xl bg-white/15 border border-white/25 flex items-center justify-center shrink-0">
                            <svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z"/></svg>
                        </span>
                        <div class="min-w-0 pt-0.5">
                            <h1 class="text-lg sm:text-xl font-bold tracking-tight">{{ evomi_l('Pembayaran', 'Payments') }}</h1>
                            <p class="text-[12px] sm:text-sm text-white/85 font-medium mt-0.5">
                                <span x-show="hasCod && !hasOnline">{{ evomi_l('Batalkan sebelum dikirim. Otomatis batal dalam 24 jam jika belum dalam perjalanan.', 'Cancel before we ship. Auto-cancels in 24 hours if not yet in transit.') }}</span>
                                <span x-show="hasOnline && !hasCod">{{ evomi_l('Bayar dalam 24 jam, atau batalkan jika belum membayar.', 'Pay within 24 hours, or cancel if you have not paid yet.') }}</span>
                                <span x-show="hasCod && hasOnline">{{ evomi_l('QRIS/VA bisa dibatalkan sebelum bayar. COD bisa dibatalkan sebelum dikirim.', 'QRIS/VA can be cancelled before payment. COD can be cancelled before shipping.') }}</span>
                                <span x-show="!hasCod && !hasOnline">{{ evomi_l('Tagihan QRIS, transfer, dan COD yang belum dikonfirmasi.', 'QRIS, transfer, and unconfirmed COD bills.') }}</span>
                            </p>
                        </div>
                    </div>
                    <span x-show="items.length > 0" x-cloak class="shrink-0 text-[11px] font-semibold px-3 py-1.5 rounded-full bg-white/15 border border-white/25">
                        <span x-text="items.length"></span> {{ evomi_l('tagihan', 'bills') }}
                    </span>
                </div>
            </div>

            <div class="profile-page-card__scroll p-5 sm:p-7 bg-slate-50/80">
                <div x-show="error" x-cloak class="rounded-2xl border border-rose-100 bg-rose-50/70 p-8 text-center mb-4">
                    <p class="text-rose-600 mb-4 font-medium text-sm" x-text="error"></p>
                    <button type="button" @click="load()" class="px-6 py-2.5 text-white rounded-xl font-semibold text-sm bg-[#F59E0B] hover:bg-[#D97706] transition">{{ evomi_l('Coba Lagi', 'Try Again') }}</button>
                </div>

                <div x-show="!error && items.length === 0" x-cloak class="rounded-2xl border border-dashed border-amber-200/80 bg-white px-6 py-14 text-center">
                    <div class="w-16 h-16 rounded-2xl bg-amber-50 flex items-center justify-center mx-auto mb-4">
                        <svg class="w-7 h-7 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                    </div>
                    <h2 class="text-lg font-bold text-slate-900 mb-2">{{ evomi_l('Tidak ada tagihan menunggu', 'No pending bills') }}</h2>
                    <p class="text-slate-500 text-sm mb-6 max-w-sm mx-auto">{{ evomi_l('Semua pembayaran sudah selesai. COD hanya hilang dari sini setelah barang tiba dan admin mengonfirmasi pembayaran.', 'All payments are settled. COD leaves this list after delivery and admin payment confirmation.') }}</p>
                    <a href="{{ route('belanja') }}" data-soft-nav class="inline-flex items-center gap-2 px-6 py-2.5 bg-[#F59E0B] text-white rounded-xl font-semibold text-sm hover:bg-[#D97706] transition">{{ evomi_l('Mulai Belanja', 'Start Shopping') }}</a>
                </div>

                <div x-show="!error && items.length > 0" x-cloak class="space-y-3">
                    <template x-for="(row, index) in items" :key="row.invoice_id">
                        <article
                            class="evomi-profile-list__card group/card"
                            :style="{ '--wl-accent': row.brand_color || '#F59E0B', '--wl-delay': (index * 40) + 'ms' }"
                        >
                            <a
                                :href="row.payment_url || ('/pembayaran/' + encodeURIComponent(row.invoice_id))"
                                data-soft-nav
                                class="evomi-profile-list__thumb"
                                :style="{ backgroundColor: (row.brand_color || '#F59E0B') + '22' }"
                            >
                                <img
                                    x-show="row.image"
                                    :src="imageUrl(row.image)"
                                    :alt="row.title"
                                    class="transition-transform duration-500 ease-out group-hover/card:scale-110"
                                    x-on:error="$el.style.display='none'"
                                >
                                <span class="evomi-profile-list__badge" aria-hidden="true">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5"/></svg>
                                </span>
                            </a>

                            <div class="evomi-profile-list__body min-w-0">
                                <div class="min-w-0">
                                    <a
                                        :href="row.payment_url || ('/pembayaran/' + encodeURIComponent(row.invoice_id))"
                                        data-soft-nav
                                        class="text-left font-semibold text-slate-900 text-[15px] leading-snug line-clamp-2 hover:underline decoration-slate-300 underline-offset-2 block"
                                        x-text="row.title + (row.extra_count > 0 ? ' +' + row.extra_count : '')"
                                    ></a>
                                    <p class="mt-1 text-[12px] text-slate-500 break-all leading-relaxed" x-text="row.invoice_id"></p>
                                    <div class="mt-2.5 flex flex-wrap items-center gap-1.5">
                                        <span class="evomi-profile-list__chip !bg-amber-50 !text-amber-700 !border-amber-100" x-text="row.payment_method"></span>
                                        <span
                                            x-show="!row.is_cod"
                                            class="evomi-profile-list__chip"
                                        >{{ evomi_l('Sisa', 'Left') }}&nbsp;<span x-text="formatCountdown(row.seconds_remaining)"></span></span>
                                        <span
                                            x-show="row.is_cod && row.can_cancel"
                                            class="evomi-profile-list__chip"
                                        >{{ evomi_l('Batal otomatis', 'Auto-cancel') }}&nbsp;<span x-text="formatCountdown(row.seconds_remaining)"></span></span>
                                        <span
                                            x-show="row.is_cod && !row.can_cancel"
                                            class="evomi-profile-list__chip !bg-sky-50 !text-sky-700 !border-sky-100"
                                        >{{ evomi_l('Bayar saat barang tiba', 'Pay on delivery') }}</span>
                                        <span class="evomi-profile-list__price" :style="{ color: row.brand_color || '#D97706' }" x-text="formatRupiah(row.amount)"></span>
                                    </div>
                                </div>

                                <div class="evomi-profile-list__actions">
                                    <a
                                        x-show="!row.is_cod"
                                        :href="row.payment_url || ('/pembayaran/' + encodeURIComponent(row.invoice_id))"
                                        data-soft-nav
                                        class="evomi-profile-list__cta"
                                        :style="{ backgroundColor: row.brand_color || '#F59E0B' }"
                                    >
                                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z"/></svg>
                                        {{ evomi_l('Bayar Sekarang', 'Pay Now') }}
                                    </a>
                                    <a
                                        x-show="row.is_cod"
                                        :href="row.payment_url || ('/pembayaran/' + encodeURIComponent(row.invoice_id))"
                                        data-soft-nav
                                        class="evomi-profile-list__cta"
                                        :style="{ backgroundColor: row.brand_color || '#1172BA' }"
                                    >
                                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z"/></svg>
                                        {{ evomi_l('Lihat tagihan', 'View bill') }}
                                    </a>
                                    <button
                                        type="button"
                                        x-show="row.can_cancel"
                                        x-cloak
                                        @click="requestCancel(row)"
                                        :disabled="cancelModal.busy"
                                        class="evomi-profile-list__detail !text-rose-600 hover:!bg-rose-50"
                                    >
                                        {{ evomi_l('Batalkan pesanan', 'Cancel order') }}
                                    </button>
                                    <p
                                        x-show="row.is_cod && row.can_cancel"
                                        class="text-[11px] text-slate-500 leading-relaxed"
                                    >{{ evomi_l('Bisa dibatalkan sebelum dikirim. Otomatis batal dalam 24 jam jika belum dalam perjalanan.', 'Cancel before we ship. Auto-cancels in 24 hours if not yet in transit.') }}</p>
                                    <p
                                        x-show="!row.is_cod && row.can_cancel"
                                        class="text-[11px] text-slate-500 leading-relaxed"
                                    >{{ evomi_l('Bisa dibatalkan selama belum membayar. Otomatis batal dalam 24 jam.', 'You can cancel while unpaid. Auto-cancels in 24 hours.') }}</p>
                                    <p
                                        x-show="row.is_cod && !row.can_cancel"
                                        class="text-[11px] text-slate-500 leading-relaxed"
                                    >{{ evomi_l('Sudah dikirim — bayar saat barang tiba, atau menunggu konfirmasi admin.', 'Already shipped — pay on delivery, or wait for admin confirmation.') }}</p>
                                    <a
                                        :href="row.payment_url || ('/pembayaran/' + encodeURIComponent(row.invoice_id))"
                                        data-soft-nav
                                        class="evomi-profile-list__detail"
                                    >
                                        {{ evomi_l('Lihat tagihan', 'View bill') }}
                                        <svg class="w-3.5 h-3.5 transition-transform group-hover/card:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                                    </a>
                                </div>
                            </div>
                        </article>
                    </template>
                </div>
            </div>
        </div>

    <template x-teleport="body">
        <div
            x-show="cancelModal.open"
            x-cloak
            class="fixed inset-0 z-[220] flex items-center justify-center p-4"
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @keydown.escape.window="cancelModal.open && !cancelModal.busy && closeCancelModal()"
        >
            <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="!cancelModal.busy && closeCancelModal()"></div>
            <div class="relative bg-white w-full max-w-[400px] rounded-[24px] shadow-2xl p-6 text-center" @click.stop>
                <div class="mx-auto mb-4 w-14 h-14 rounded-full flex items-center justify-center bg-rose-50 text-rose-500">
                    <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/></svg>
                </div>
                <h3 class="font-nohemi text-[18px] font-bold text-[#0F172A] mb-2" x-text="cancelModal.row?.is_cod
                    ? $L('Batalkan pesanan COD?', 'Cancel this COD order?')
                    : $L('Batalkan tagihan?', 'Cancel this bill?')"></h3>
                <p class="font-parkinsans text-[13px] text-[#64748B] leading-relaxed mb-1" x-text="cancelModal.row?.title || ''"></p>
                <p class="font-parkinsans text-[13px] text-[#64748B] leading-relaxed mb-5" x-text="cancelModal.row?.is_cod
                    ? $L('Pesanan hanya bisa dibatalkan sebelum barang dikirim / dalam perjalanan.', 'You can only cancel before the goods are shipped / in transit.')
                    : $L('Tagihan QRIS / transfer ini belum dibayar. Pesanan akan dibatalkan.', 'This QRIS / transfer bill is unpaid. The order will be cancelled.')"></p>
                <div class="flex gap-2.5">
                    <button
                        type="button"
                        class="flex-1 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                        :disabled="cancelModal.busy"
                        @click="closeCancelModal()"
                    >{{ evomi_l('Kembali', 'Back') }}</button>
                    <button
                        type="button"
                        class="flex-1 py-2.5 rounded-xl bg-rose-500 hover:bg-rose-600 text-white text-sm font-semibold disabled:opacity-60"
                        :disabled="cancelModal.busy"
                        @click="confirmCancel()"
                    >
                        <span x-show="!cancelModal.busy">{{ evomi_l('Ya, batalkan', 'Yes, cancel') }}</span>
                        <span x-show="cancelModal.busy" x-cloak>{{ evomi_l('Membatalkan…', 'Cancelling…') }}</span>
                    </button>
                </div>
            </div>
        </div>
    </template>
    </div>
</x-profile-shell>
@endsection
