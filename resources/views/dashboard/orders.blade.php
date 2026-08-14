@extends('layouts.admin')

@section('title', 'Pesanan | Evomi Admin')

@section('content')
<div x-data="evomiAdminOrders" class="space-y-6 pb-12">
    <div>
        <h1 class="text-3xl font-bold text-gray-900" x-text="t('orders','title')"></h1>
        <p class="text-gray-500 mt-1" x-text="t('orders','subtitle')"></p>
    </div>

    <div x-show="loading" class="min-h-[60vh] flex items-center justify-center"><div class="w-8 h-8 border-4 border-gray-200 border-t-gray-900 rounded-full animate-spin"></div></div>
    <div x-show="!loading && error" class="bg-red-50 text-red-700 rounded-2xl px-5 py-4 text-sm border border-red-100" x-text="error"></div>

    <div x-show="!loading && !error" class="admin-table-card">
        <div class="p-5 sm:p-6 border-b border-gray-100">
            <div class="relative w-full max-w-md">
                <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400 pointer-events-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                <input type="search" x-model="search" :placeholder="t('orders','search_ph','Cari ID pesanan atau nama user...','Search order ID or user name...')" class="admin-search-input">
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full border-collapse min-w-[900px]">
                <thead><tr class="bg-gray-50/80 border-b border-gray-100">
                    <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-left" x-text="common().product"></th>
                    <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-left" x-text="t('orders','col_customer','Pelanggan','Customer')"></th>
                    <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-center" x-text="t('orders','col_total','Total Harga','Total Price')"></th>
                    <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-center" x-text="common().status"></th>
                    <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-center" x-text="common().actions"></th>
                </tr></thead>
                <tbody class="divide-y divide-gray-50">
                    <template x-if="pagedItems().length === 0"><tr><td colspan="5" class="px-6 py-14 text-center text-sm text-gray-400" x-text="t('orders','empty')"></td></tr></template>
                    <template x-for="o in pagedItems()" :key="o.id">
                        <tr class="hover:bg-gray-50/40 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-start gap-3 min-w-0">
                                    @include('partials.admin-thumb', [
                                        'src' => 'productImage(o)',
                                        'alt' => "o.product?.title || ''",
                                        'size' => 'h-12 w-12',
                                        'fit' => 'contain',
                                    ])
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-gray-900 truncate max-w-[240px]" x-text="o.product?.title || t('orders','no_name','Tanpa Nama','No Name')"></p>
                                        <p class="mt-1 text-[11px] font-bold font-mono text-gray-500 truncate" x-text="'#' + o.id"></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4"><p class="text-sm font-semibold text-gray-900" x-text="customerName(o)"></p><p class="text-xs text-gray-400 mt-0.5" x-text="customerEmail(o)"></p></td>
                            <td class="px-6 py-4 text-center"><p class="text-sm font-bold" :class="payLabel(o.payment_status) === payLabel('success') ? 'text-gray-900' : 'text-gray-400 line-through'" x-text="total(o)"></p><span class="mt-1 inline-flex px-2 py-0.5 rounded-md text-[10px] font-bold border" :class="payClass(o.payment_status)" x-text="payLabel(o.payment_status)"></span><p x-show="o.is_cod_payment || (o.payment_channel || '').toLowerCase() === 'cod'" x-cloak class="mt-1 text-[10px] font-bold uppercase tracking-wider text-sky-700">COD</p></td>
                            <td class="px-6 py-4 text-center"><span class="inline-flex px-2.5 py-1 rounded-lg text-[11px] font-bold uppercase tracking-wider border" :class="statusClass(o.status)" x-text="statusLabel(o.status)"></span></td>
                            <td class="px-6 py-4 text-center"><div class="flex justify-center gap-2">
                                <button type="button" class="admin-btn-icon" :title="common().edit" @click="openEdit(o)"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg></button>
                                <button type="button" class="admin-btn-icon admin-btn-icon--danger" :title="common().delete" @click="remove(o.id)"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/></svg></button>
                            </div></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        <div class="px-6 py-5 border-t border-gray-100 bg-gray-50/50">
            <div class="flex items-center gap-4 bg-white px-5 py-3 rounded-2xl border border-gray-200 shadow-sm w-full sm:w-auto sm:inline-flex">
                <div class="w-12 h-12 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-600 border border-emerald-100"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M16 13h.01"/><path d="M2 10h20"/></svg></div>
                <div><p class="text-xs font-bold text-gray-500 uppercase tracking-wider" x-text="t('orders','revenue')"></p><p class="text-xl font-black text-gray-900 mt-0.5" x-text="formatRupiah(revenue())"></p></div>
            </div>
        </div>
        @include('partials.admin-pagination', [
            'countExpr' => "filteredItems().length + ' ' + t('orders','items','pesanan','orders')",
        ])
    </div>

<template x-teleport="body">
    <div
        x-show="editOpen"
        x-cloak
        class="admin-modal-root"
        role="dialog"
        aria-modal="true"
        @keydown.escape.window="closeEdit()"
        @click.self="closeEdit()"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    >
        <div
            class="admin-modal-panel max-w-md"
            role="document"
            @click.stop
            x-show="editOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
        >
            <div class="admin-modal-panel__header bg-gray-50/60">
                <div class="min-w-0">
                    <h2 class="text-lg font-bold text-gray-900" x-text="t('orders','change_status_title','Ubah Status Pesanan','Change Order Status')"></h2>
                    <p class="text-xs text-gray-500 mt-0.5">
                        ID:
                        <span class="font-mono font-semibold text-gray-700" x-text="edit.id"></span>
                    </p>
                </div>
                <button type="button" class="p-1.5 rounded-xl hover:bg-gray-200/60 text-gray-400 hover:text-gray-600 transition-colors" @click="closeEdit()" aria-label="Close">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                </button>
            </div>

            <form @submit.prevent="saveStatus" class="flex flex-col flex-1 min-h-0">
                <div class="admin-modal-panel__body space-y-4">
                    {{-- Order preview --}}
                    <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl border border-gray-100">
                        <div class="w-11 h-11 rounded-lg border border-gray-200 bg-white flex items-center justify-center overflow-hidden shrink-0">
                            <template x-if="edit.imageUrl">
                                <img :src="edit.imageUrl" :alt="edit.productTitle" class="w-full h-full object-contain p-0.5">
                            </template>
                            <template x-if="!edit.imageUrl">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-gray-400"><rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                            </template>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-medium text-gray-500 truncate">
                                <span x-text="t('orders','customer_label','Pelanggan','Customer')"></span>:
                                <span x-text="edit.customerName"></span>
                            </p>
                            <p class="text-sm font-semibold text-gray-900 truncate" x-text="edit.productTitle"></p>
                            <p class="text-[11px] text-gray-400 mt-0.5 tabular-nums" x-text="edit.totalLabel"></p>
                            <p x-show="edit.isCod" x-cloak class="text-[10px] font-bold uppercase tracking-wider text-sky-700 mt-1">COD</p>
                        </div>
                    </div>

                    <div
                        x-show="edit.isCod && edit.payment_status === 'pending'"
                        x-cloak
                        class="rounded-xl border border-sky-100 bg-sky-50 px-3.5 py-2.5 text-[12px] leading-relaxed text-sky-800"
                        x-text="t('orders','payment_cod_hint')"
                    ></div>

                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-gray-400 mb-2.5" x-text="t('orders','select_new_status','Pilih Status Baru','Select New Status')"></p>
                        <div class="grid grid-cols-1 gap-2.5">
                            <template x-for="opt in statusCards" :key="opt.id">
                                <button
                                    type="button"
                                    class="admin-order-status-card flex items-start gap-3.5 p-3.5 rounded-xl border text-left transition-all duration-150"
                                    :class="edit.status === opt.id ? opt.activeClass + ' shadow-sm' : 'border-gray-200 hover:border-gray-300 hover:bg-gray-50/70'"
                                    @click="selectStatus(opt.id)"
                                >
                                    <div
                                        class="p-2 rounded-xl shrink-0 transition-colors"
                                        :class="edit.status === opt.id ? 'bg-white shadow-sm' : 'bg-gray-50 border border-gray-100'"
                                    >
                                        <span class="block w-5 h-5" :class="opt.iconClass" x-html="opt.icon"></span>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center justify-between gap-2">
                                            <p class="text-sm font-semibold" :class="edit.status === opt.id ? 'text-gray-900' : 'text-gray-700'" x-text="opt.label"></p>
                                            <span
                                                x-show="edit.status === opt.id"
                                                class="w-2 h-2 rounded-full bg-gray-900 animate-pulse shrink-0"
                                            ></span>
                                        </div>
                                        <p class="text-xs text-gray-500 mt-0.5 leading-relaxed" x-text="opt.desc"></p>
                                    </div>
                                </button>
                            </template>
                        </div>
                    </div>

                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-gray-400 mb-2.5 pt-1" x-text="t('orders','select_payment_status','Status Pembayaran','Payment Status')"></p>
                        <div class="grid grid-cols-1 gap-2">
                            <template x-for="opt in paymentCards" :key="opt.id">
                                <button
                                    type="button"
                                    class="admin-order-pay-card flex items-start justify-between gap-3 p-3 rounded-xl border text-left transition-all duration-150"
                                    :class="edit.payment_status === opt.id
                                        ? 'ring-2 ring-gray-900 border-gray-900 bg-gray-50 shadow-sm'
                                        : 'border-gray-200 hover:border-gray-300 hover:bg-gray-50/70'"
                                    @click="edit.payment_status = opt.id"
                                >
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-gray-900" x-text="opt.label"></p>
                                        <p class="text-xs text-gray-500 mt-0.5" x-text="opt.desc"></p>
                                    </div>
                                    <span
                                        class="shrink-0 inline-flex px-2 py-0.5 rounded-md text-[10px] font-bold border uppercase tracking-wide"
                                        :class="payClass(opt.id)"
                                        x-text="opt.badge"
                                    ></span>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>

                <div class="admin-modal-panel__footer bg-gray-50/50">
                    <button type="button" class="px-4 py-2.5 rounded-xl border border-gray-200 bg-white hover:bg-gray-50 text-sm font-semibold text-gray-700 shadow-sm transition-colors" @click="closeEdit()" x-text="common().cancel"></button>
                    <button
                        type="submit"
                        :disabled="saving || !canSaveEdit"
                        class="px-5 py-2.5 rounded-xl bg-gray-900 text-white text-sm font-semibold hover:bg-gray-800 shadow-sm transition-colors disabled:opacity-60 inline-flex items-center gap-2"
                    >
                        <svg x-show="saving" x-cloak class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        <span x-text="saving ? common().saving : common().save_changes"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
</div>
@endsection