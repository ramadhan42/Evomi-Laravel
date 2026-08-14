@extends('layouts.app')

@section('title', evomi_l('Pembayaran | Evomi', 'Payment | Evomi'))

@section('content')
<section
    class="evomi-payment-page evomi-soft-enter bg-[#F0F3F7] w-full flex flex-col"
    x-data="evomiPaymentPage(@js($invoiceId))"
    x-init="boot()"
>
    <div
        x-show="loading"
        x-cloak
        class="flex flex-col items-center justify-center min-h-[50vh] py-16"
    >
        <div class="w-9 h-9 border-4 border-gray-200 rounded-full animate-spin mb-3" :style="{ borderTopColor: brand }"></div>
        <p class="text-sm text-gray-500 font-parkinsans">{{ evomi_l('Memuat pembayaran…', 'Loading payment…') }}</p>
    </div>

    <div
        x-show="!loading && error"
        x-cloak
        class="flex items-center justify-center px-4 py-16"
    >
        <div class="rounded-2xl bg-white border border-rose-100 p-8 shadow-sm max-w-md w-full text-center" data-soft-enter="up">
            <p class="text-rose-600 font-medium mb-5" x-text="error"></p>
            <a href="{{ route('belanja') }}" data-soft-nav class="inline-flex px-5 py-2.5 rounded-xl text-white text-sm font-semibold" :style="{ backgroundColor: brand }">{{ evomi_l('Kembali belanja', 'Back to shop') }}</a>
        </div>
    </div>

    <div
        x-show="!loading && !error && data"
        x-cloak
        class="max-w-5xl w-full mx-auto px-3 sm:px-5 py-3 sm:py-4 flex flex-col gap-3"
    >
        <div class="shrink-0 rounded-2xl overflow-hidden shadow-sm border border-white/70" data-soft-enter="up">
            <div
                class="px-4 sm:px-5 py-3 sm:py-3.5 text-white flex flex-row items-center justify-between gap-3"
                :style="{ background: 'linear-gradient(135deg, ' + brand + ' 0%, ' + brandDark + ' 100%)' }"
            >
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2 mb-0.5">
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-white/20 border border-white/25" x-text="badgeLabel"></span>
                        <span class="text-[10px] text-white/75 truncate" x-text="data.payment_method"></span>
                    </div>
                    <h1 class="text-base sm:text-lg font-bold font-nohemi tracking-tight truncate" x-text="statusTitle"></h1>
                </div>
                <div
                    x-show="isAwaiting && (!isCod || data?.can_cancel)"
                    x-cloak
                    class="shrink-0 rounded-xl bg-white/15 border border-white/25 px-3 sm:px-4 py-2 text-right"
                >
                    <p class="text-[9px] uppercase tracking-wider font-bold text-white/70" x-text="isCod ? $L('Batal otomatis', 'Auto-cancel') : $L('Sisa waktu', 'Time left')"></p>
                    <p class="font-nohemi text-lg sm:text-xl font-bold tracking-wide leading-none mt-0.5" x-text="countdownLabel"></p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-3 items-stretch">
            <div class="lg:col-span-7 flex flex-col" data-soft-enter="bottle">
                <div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden flex flex-col h-full min-h-[420px] lg:min-h-[460px]">
                    <div class="shrink-0 px-4 sm:px-5 py-2.5 border-b border-gray-100 flex items-center justify-between gap-2">
                        <h2 class="text-sm font-bold text-slate-900">{{ evomi_l('Cara pembayaran', 'How to pay') }}</h2>
                        <span class="text-[10px] font-semibold text-gray-500" x-text="channelLabel"></span>
                    </div>

                    <div class="flex-1 p-4 sm:p-5 flex flex-col gap-3">
                        <div
                            class="shrink-0 rounded-xl px-3.5 py-3 flex items-center justify-between gap-3"
                            :style="{ backgroundColor: brand + '12', border: '1px solid ' + brand + '22' }"
                        >
                            <div>
                                <p class="text-[10px] font-semibold text-gray-500 uppercase tracking-wide">{{ evomi_l('Total dibayar', 'Amount due') }}</p>
                                <p class="text-xl sm:text-2xl font-bold font-nohemi leading-tight" :style="{ color: brand }" x-text="formatPrice(data.amount)"></p>
                            </div>
                            <p class="hidden sm:block text-[11px] text-gray-500 font-parkinsans text-right max-w-[11rem] leading-snug" x-show="!isCod">
                                {{ evomi_l('Bayar tepat sesuai nominal.', 'Pay the exact amount.') }}
                            </p>
                            <p class="hidden sm:block text-[11px] text-gray-500 font-parkinsans text-right max-w-[11rem] leading-snug" x-show="isCod" x-cloak>
                                {{ evomi_l('Bayar saat barang tiba.', 'Pay when the goods arrive.') }}
                            </p>
                        </div>

                        <template x-if="data.payment_channel === 'qris' && data.meta?.qr_string && isAwaiting">
                            <div class="flex-1 grid grid-cols-1 sm:grid-cols-[auto_1fr] gap-4 items-center content-center">
                                <div class="flex justify-center sm:justify-start">
                                    <div class="p-2 rounded-xl border border-gray-100 bg-white shadow-sm">
                                        <img :src="qrisImageUrl" alt="QRIS" class="w-36 h-36 sm:w-40 sm:h-40" width="160" height="160">
                                    </div>
                                </div>
                                <ol class="space-y-2 text-[12px] sm:text-sm text-gray-600 font-parkinsans">
                                    <li class="flex gap-2"><span class="w-5 h-5 rounded-full text-[10px] font-bold text-white flex items-center justify-center shrink-0" :style="{ backgroundColor: brand }">1</span><span>{{ evomi_l('Buka e-wallet / m-banking', 'Open e-wallet / mobile banking') }}</span></li>
                                    <li class="flex gap-2"><span class="w-5 h-5 rounded-full text-[10px] font-bold text-white flex items-center justify-center shrink-0" :style="{ backgroundColor: brand }">2</span><span>{{ evomi_l('Scan QRIS ini', 'Scan this QRIS') }}</span></li>
                                    <li class="flex gap-2"><span class="w-5 h-5 rounded-full text-[10px] font-bold text-white flex items-center justify-center shrink-0" :style="{ backgroundColor: brand }">3</span><span>{{ evomi_l('Konfirmasi nominal, lalu bayar', 'Confirm amount, then pay') }}</span></li>
                                </ol>
                            </div>
                        </template>

                        <template x-if="data.payment_channel === 'va' && data.meta?.va_number && isAwaiting">
                            <div class="flex-1 flex flex-col gap-3 justify-center">
                                <div class="rounded-xl border border-gray-100 bg-[#F8FAFC] p-3.5 space-y-3">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="text-[10px] font-semibold text-gray-500 uppercase">{{ evomi_l('Bank', 'Bank') }}</span>
                                        <span class="text-sm font-bold uppercase" x-text="(data.meta.bank || '').toUpperCase()"></span>
                                    </div>
                                    <div>
                                        <span class="block text-[10px] font-semibold text-gray-500 uppercase mb-1">{{ evomi_l('Nomor VA', 'VA Number') }}</span>
                                        <div class="flex flex-col sm:flex-row sm:items-center gap-2">
                                            <span class="flex-1 text-lg sm:text-xl font-bold font-nohemi tracking-wide break-all" :style="{ color: brand }" x-text="data.meta.va_number"></span>
                                            <button type="button" @click="copyVa()" class="shrink-0 px-3.5 py-2 rounded-xl text-xs font-bold text-white" :style="{ backgroundColor: brand }" x-text="copied ? $L('Tersalin', 'Copied') : $L('Salin', 'Copy')"></button>
                                        </div>
                                        <p x-show="data.meta.biller_code && data.meta.bill_key" x-cloak class="mt-1.5 text-[11px] text-gray-500"
                                           x-text="'Kode: ' + data.meta.biller_code + ' · Bill Key: ' + data.meta.bill_key"></p>
                                    </div>
                                </div>
                                <ol class="space-y-1.5 text-[12px] text-gray-600 font-parkinsans">
                                    <li class="flex gap-2"><span class="w-5 h-5 rounded-full text-[10px] font-bold text-white flex items-center justify-center shrink-0" :style="{ backgroundColor: brand }">1</span><span>{{ evomi_l('Buka m-banking / ATM', 'Open mobile banking / ATM') }}</span></li>
                                    <li class="flex gap-2"><span class="w-5 h-5 rounded-full text-[10px] font-bold text-white flex items-center justify-center shrink-0" :style="{ backgroundColor: brand }">2</span><span>{{ evomi_l('Pilih Transfer VA', 'Choose VA Transfer') }}</span></li>
                                    <li class="flex gap-2"><span class="w-5 h-5 rounded-full text-[10px] font-bold text-white flex items-center justify-center shrink-0" :style="{ backgroundColor: brand }">3</span><span>{{ evomi_l('Transfer tepat sesuai total', 'Transfer the exact total') }}</span></li>
                                </ol>
                                <p class="text-[11px] text-amber-800 bg-amber-50 border border-amber-100 rounded-lg px-3 py-2">{{ evomi_l('Jangan ubah nominal. Status update otomatis setelah bayar.', 'Do not change the amount. Status updates automatically after payment.') }}</p>
                            </div>
                        </template>

                        <template x-if="isCod && isAwaiting">
                            <div class="flex-1 flex flex-col gap-3 justify-center">
                                <div class="rounded-xl border border-gray-100 bg-[#F8FAFC] p-3.5 space-y-3">
                                    <p class="text-sm font-bold text-slate-900">Cash on Delivery</p>
                                    <ol class="space-y-2 text-[12px] sm:text-sm text-gray-600 font-parkinsans">
                                        <li class="flex gap-2"><span class="w-5 h-5 rounded-full text-[10px] font-bold text-white flex items-center justify-center shrink-0" :style="{ backgroundColor: brand }">1</span><span>{{ evomi_l('Bayar tunai saat barang tiba di tujuan.', 'Pay in cash when the goods arrive.') }}</span></li>
                                        <li class="flex gap-2"><span class="w-5 h-5 rounded-full text-[10px] font-bold text-white flex items-center justify-center shrink-0" :style="{ backgroundColor: brand }">2</span><span>{{ evomi_l('Pesanan bisa dibatalkan sebelum dikirim.', 'You can cancel before we ship.') }}</span></li>
                                        <li class="flex gap-2"><span class="w-5 h-5 rounded-full text-[10px] font-bold text-white flex items-center justify-center shrink-0" :style="{ backgroundColor: brand }">3</span><span>{{ evomi_l('Lunas setelah admin mengonfirmasi pembayaran.', 'Marked paid after admin confirms payment.') }}</span></li>
                                    </ol>
                                </div>
                                <p class="text-[11px] text-sky-800 bg-sky-50 border border-sky-100 rounded-lg px-3 py-2" x-show="data?.can_cancel">{{ evomi_l('Otomatis batal dalam 24 jam jika belum dalam perjalanan.', 'Auto-cancels in 24 hours if not yet in transit.') }}</p>
                                <p class="text-[11px] text-sky-800 bg-sky-50 border border-sky-100 rounded-lg px-3 py-2" x-show="!data?.can_cancel">{{ evomi_l('Sudah dikirim — bayar saat barang tiba.', 'Already shipped — pay on delivery.') }}</p>
                            </div>
                        </template>

                        <template x-if="isAwaiting && !hasPaymentDetails && !isCod">
                            <div class="flex-1 flex items-center justify-center rounded-xl border border-dashed border-gray-200 px-4 py-6 text-center text-sm text-gray-500">
                                {{ evomi_l('Detail pembayaran sedang disiapkan…', 'Preparing payment details…') }}
                            </div>
                        </template>

                        <div x-show="isAwaiting && !isCod" x-cloak class="shrink-0 flex items-center gap-2 text-[11px] font-parkinsans" :style="{ color: brand }">
                            <svg class="w-3 h-3 animate-spin shrink-0" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                            {{ evomi_l('Menunggu pembayaran… status otomatis', 'Waiting for payment… auto status') }}
                        </div>

                        <div x-show="isPaid" x-cloak class="shrink-0 space-y-2.5">
                            <div class="rounded-xl bg-emerald-50 border border-emerald-100 px-3 py-2.5 text-sm text-emerald-800">{{ evomi_l('Pembayaran berhasil. Pesanan diproses.', 'Payment successful. Order is processing.') }}</div>
                            <div class="flex gap-2">
                                <button
                                    type="button"
                                    class="flex-1 inline-flex items-center justify-center py-2.5 rounded-xl text-white font-bold text-sm"
                                    :style="{ backgroundColor: brand }"
                                    @click="window.evomiOpenTrack(data.invoice_id)"
                                >{{ evomi_l('Lacak', 'Track') }}</button>
                                <a href="{{ route('profile.history') }}" data-soft-nav class="flex-1 inline-flex items-center justify-center py-2.5 rounded-xl border border-gray-200 text-sm font-semibold text-gray-700">{{ evomi_l('Riwayat', 'History') }}</a>
                            </div>
                        </div>

                        <div x-show="isExpired" x-cloak class="shrink-0 space-y-2.5">
                            <div class="rounded-xl bg-rose-50 border border-rose-100 px-3 py-2.5 text-sm text-rose-700">{{ evomi_l('Batas 24 jam habis. Pesanan dibatalkan.', '24h window ended. Order cancelled.') }}</div>
                            <a href="{{ route('belanja') }}" data-soft-nav class="w-full inline-flex items-center justify-center py-2.5 rounded-xl text-white font-bold text-sm" :style="{ backgroundColor: brand }">{{ evomi_l('Belanja Lagi', 'Shop Again') }}</a>
                        </div>
                    </div>
                </div>
            </div>

            <aside class="lg:col-span-5 flex flex-col" data-soft-enter="fade">
                <div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden flex flex-col h-full min-h-[420px] lg:min-h-[460px]">
                    <div class="shrink-0 px-4 sm:px-5 py-2.5 border-b border-gray-100">
                        <h2 class="text-sm font-bold text-slate-900">{{ evomi_l('Ringkasan belanja', 'Order summary') }}</h2>
                    </div>
                    <div class="flex-1 p-4 sm:p-5 flex flex-col gap-3">
                        <div class="shrink-0 flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <p class="text-[10px] text-gray-500 font-semibold uppercase">Invoice</p>
                                <p class="text-xs sm:text-sm font-bold text-slate-900 break-all mt-0.5" x-text="data.invoice_id"></p>
                            </div>
                            <span class="shrink-0 text-[10px] font-bold px-2 py-1 rounded-full" :class="badgeClass" x-text="badgeLabel"></span>
                        </div>

                        <div class="h-px bg-gray-100 shrink-0"></div>

                        <div class="flex-1 space-y-2.5">
                            <template x-for="item in (data.items || [])" :key="item.id">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-11 h-11 rounded-lg overflow-hidden flex items-center justify-center shrink-0" :style="{ backgroundColor: (item.color || brand) + '18' }">
                                        <img x-show="item.image" :src="itemImage(item)" class="max-h-full max-w-full object-contain p-0.5" alt="">
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-semibold text-slate-900 truncate" x-text="item.title"></p>
                                        <p class="text-[10px] text-gray-500" x-text="'Qty ' + item.quantity"></p>
                                    </div>
                                    <p class="text-sm font-bold shrink-0" :style="{ color: item.color || brand }" x-text="formatPrice(item.price)"></p>
                                </div>
                            </template>
                        </div>

                        <div class="h-px bg-gray-100 shrink-0"></div>

                        <div class="shrink-0 space-y-1.5 text-sm">
                            <div class="flex justify-between text-gray-600 text-xs">
                                <span>{{ evomi_l('Metode', 'Method') }}</span>
                                <span class="font-medium text-slate-800 text-right" x-text="data.payment_method"></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="font-bold text-slate-900">{{ evomi_l('Total', 'Total') }}</span>
                                <span class="text-lg font-bold font-nohemi" :style="{ color: brand }" x-text="formatPrice(data.amount)"></span>
                            </div>
                        </div>

                        <a
                            x-show="isAwaiting"
                            x-cloak
                            href="{{ route('profile.payments') }}"
                            data-soft-nav
                            class="shrink-0 w-full inline-flex items-center justify-center py-2.5 rounded-xl border border-gray-200 text-xs font-semibold text-gray-700 hover:bg-gray-50"
                        >{{ evomi_l('Daftar menunggu bayar', 'Pending payments list') }}</a>
                        <button
                            type="button"
                            x-show="isAwaiting && data?.can_cancel"
                            x-cloak
                            @click="requestCancel()"
                            :disabled="cancelModal.busy"
                            class="shrink-0 w-full inline-flex items-center justify-center py-2.5 rounded-xl border border-rose-200 text-xs font-semibold text-rose-600 hover:bg-rose-50 disabled:opacity-60"
                        >{{ evomi_l('Batalkan pesanan', 'Cancel order') }}</button>
                    </div>
                </div>
            </aside>
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
                <h3 class="font-nohemi text-[18px] font-bold text-[#0F172A] mb-2" x-text="isCod
                    ? $L('Batalkan pesanan COD?', 'Cancel this COD order?')
                    : $L('Batalkan tagihan?', 'Cancel this bill?')"></h3>
                <p class="font-parkinsans text-[13px] text-[#64748B] leading-relaxed mb-5" x-text="isCod
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
</section>
@endsection
