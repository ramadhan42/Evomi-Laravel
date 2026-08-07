@extends('layouts.admin')

@section('title', 'Subscriber | Evomi Admin')

@section('content')
<div x-data="evomiAdminSubscribers" class="space-y-6 pb-12">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900" x-text="t('subscribers','title')">Newsletter Subscribers</h1>
            <p class="text-gray-500 mt-1" x-text="t('subscribers','subtitle')"></p>
        </div>
        <button type="button" @click="load()" class="flex items-center justify-center gap-2 bg-gray-900 hover:bg-gray-800 text-white px-5 py-2.5 rounded-xl text-sm font-semibold shadow-[0_4px_14px_0_rgb(0,0,0,0.1)]">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-3-6.7"/><path d="M21 3v6h-6"/></svg>
            <span x-text="common().refresh"></span>
        </button>
    </div>

    <div x-show="loading" class="min-h-[60vh] flex items-center justify-center"><div class="w-8 h-8 border-4 border-gray-200 border-t-gray-900 rounded-full animate-spin"></div></div>
    <div x-show="!loading && error" class="bg-red-50 text-red-700 rounded-2xl px-5 py-4 text-sm border border-red-100" x-text="error"></div>

    <div x-show="!loading && !error" class="admin-table-card">
        <div class="p-5 sm:p-6 border-b border-gray-100">
            <div class="relative w-full max-w-md">
                <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400 pointer-events-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                <input type="search" x-model="search" :placeholder="common().email" class="admin-search-input">
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full border-collapse min-w-[520px]">
                <thead>
                    <tr class="bg-gray-50/80 border-b border-gray-100">
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-left" x-text="common().email"></th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-center" x-text="t('subscribers','col_subscribed_at')"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <tr x-show="pagedItems().length === 0">
                        <td colspan="2" class="px-6 py-14 text-center">
                            <p class="text-sm font-semibold text-gray-500" x-text="t('subscribers','empty_title')"></p>
                            <p class="text-xs text-gray-400 mt-1" x-text="t('subscribers','empty_desc')"></p>
                        </td>
                    </tr>
                    <template x-for="s in pagedItems()" :key="s.id || s.email">
                        <tr class="hover:bg-gray-50/40 transition-colors">
                            <td class="px-6 py-4 text-sm font-semibold text-gray-900" x-text="s.email"></td>
                            <td class="px-6 py-4 text-center text-sm text-gray-600" x-text="formatDate(s.created_at)"></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        @include('partials.admin-pagination', ['countExpr' => "filteredItems().length + ' ' + t('subscribers','total_found')"])
    </div>
</div>
@endsection
