@extends('layouts.admin')

@section('title', 'Keranjang | Evomi Admin')

@section('content')
<div x-data="evomiAdminCart" class="space-y-6 pb-12">
    <div>
        <h1 class="text-3xl font-bold text-gray-900" x-text="t('cart','title')">Keranjang</h1>
        <p class="text-gray-500 mt-1" x-text="t('cart','subtitle')"></p>
    </div>

    <div x-show="loading" class="min-h-[60vh] flex items-center justify-center"><div class="w-8 h-8 border-4 border-gray-200 border-t-gray-900 rounded-full animate-spin"></div></div>
    <div x-show="!loading && error" class="bg-red-50 text-red-700 rounded-2xl px-5 py-4 text-sm border border-red-100" x-text="error"></div>

    <div x-show="!loading && !error" class="admin-table-card">
        <div class="p-5 sm:p-6 border-b border-gray-100">
            <div class="relative w-full max-w-md">
                <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400 pointer-events-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                <input type="search" x-model="search" :placeholder="t('cart','search_ph')" class="admin-search-input">
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full border-collapse min-w-[820px]">
                <thead>
                    <tr class="bg-gray-50/80 border-b border-gray-100">
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-left" x-text="common().product"></th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-left" x-text="t('cart','col_customer')"></th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-center" x-text="common().quantity"></th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-center" x-text="common().actions"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <template x-if="pagedItems().length === 0">
                        <tr><td colspan="4" class="px-6 py-12 text-center text-sm text-gray-400" x-text="t('cart','empty')"></td></tr>
                    </template>
                    <template x-for="c in pagedItems()" :key="c.id">
                        <tr class="hover:bg-gray-50/40 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-start gap-3 min-w-0">
                                    @include('partials.admin-thumb', [
                                        'src' => 'productThumb(c.product)',
                                        'alt' => "c.product?.title || ''",
                                        'size' => 'h-12 w-12',
                                        'fit' => 'contain',
                                    ])
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-gray-900 truncate max-w-[220px]" x-text="c.product?.title || c.product_title || t('orders','no_name','Tanpa Nama','No Name')"></p>
                                        <p class="mt-1 text-[11px] font-bold text-gray-500" x-text="formatRupiah(c.product?.price ?? c.price ?? 0)"></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-sm font-semibold text-gray-900" x-text="c.user?.name || c.name || t('cart','no_name')"></p>
                                <p class="text-xs text-gray-400 mt-0.5" x-text="c.user?.email || c.email || ''"></p>
                            </td>
                            <td class="px-6 py-4 text-center text-sm font-semibold" x-text="c.qty ?? c.quantity ?? 0"></td>
                            <td class="px-6 py-4 text-center">
                                <button type="button" class="admin-btn-icon admin-btn-icon--danger" :title="common().delete" @click="remove(c.id)">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/></svg>
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        @include('partials.admin-pagination', ['countExpr' => "filteredItems().length + ' ' + t('cart','items')"])
    </div>
</div>
@endsection
