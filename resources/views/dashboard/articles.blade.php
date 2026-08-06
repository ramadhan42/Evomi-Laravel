@extends('layouts.admin')

@section('title', 'Artikel | Evomi Admin')

@section('content')
<div x-data="evomiAdminArticles" class="space-y-6 pb-12">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900" x-text="t('articles','title')"></h1>
            <p class="text-gray-500 mt-1" x-text="t('articles','subtitle')"></p>
        </div>
        <button
            type="button"
            @click="openAdd()"
            class="flex items-center justify-center gap-2 bg-gray-900 hover:bg-gray-800 text-white px-5 py-2.5 rounded-xl text-sm font-semibold transition-all shadow-[0_4px_14px_0_rgb(0,0,0,0.1)]"
        >
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
            <span x-text="t('articles','add')"></span>
        </button>
    </div>

    <div x-show="loading" class="min-h-[60vh] flex items-center justify-center">
        <div class="w-8 h-8 border-4 border-gray-200 border-t-gray-900 rounded-full animate-spin"></div>
    </div>
    <div x-show="!loading && error" class="bg-red-50 text-red-700 rounded-2xl px-5 py-4 text-sm border border-red-100" x-text="error"></div>

    <div x-show="!loading && !error" class="admin-table-card">
        <div class="p-5 sm:p-6 border-b border-gray-100">
            <div class="relative w-full max-w-md">
                <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400 pointer-events-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                <input type="search" x-model="search" :placeholder="t('articles','search_placeholder')" class="block w-full h-11 pl-10 pr-3 border border-gray-200 rounded-xl text-sm text-gray-900 placeholder:text-gray-400 bg-white focus:outline-none focus:ring-2 focus:ring-gray-900/10 focus:border-gray-400">
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full border-collapse min-w-[860px]">
                <thead>
                    <tr class="bg-gray-50/80 border-b border-gray-100">
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-left" x-text="t('articles','col_article')"></th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-center" x-text="t('articles','col_category')"></th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-center" x-text="common().status"></th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-center" x-text="t('articles','col_date')"></th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-center" x-text="common().actions"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <template x-if="pagedItems().length === 0">
                        <tr><td colspan="5" class="px-6 py-12 text-center text-sm text-gray-400 font-medium" x-text="t('articles','empty')"></td></tr>
                    </template>
                    <template x-for="a in pagedItems()" :key="a.id">
                        <tr class="hover:bg-gray-50/40 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3 min-w-[240px]">
                                    @include('partials.admin-thumb', [
                                        'src' => 'articleThumb(a)',
                                        'alt' => 'a.title',
                                        'size' => 'h-16 w-24',
                                        'fit' => 'cover',
                                    ])
                                    <div class="min-w-0">
                                        <p class="font-semibold text-gray-900 truncate" x-text="a.title"></p>
                                        <p class="text-xs text-gray-500 truncate" x-text="'/' + (a.slug || '')"></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center text-sm text-gray-600 capitalize" x-text="a.category || '-'"></td>
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium" :class="a.is_published ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-600'" x-text="a.is_published ? t('articles','published') : t('articles','draft')"></span>
                            </td>
                            <td class="px-6 py-4 text-center text-sm text-gray-600 whitespace-nowrap" x-text="formatDate(a.published_at)"></td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <button type="button" class="admin-btn-icon" :title="common().edit" @click="openEdit(a)">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                                    </button>
                                    <button type="button" class="admin-btn-icon admin-btn-icon--danger" :title="common().delete" @click="remove(a.id)">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        @include('partials.admin-pagination', ['countExpr' => "filteredItems().length + ' ' + t('articles','items')"])
    </div>

    @include('partials.admin-article-modal')
</div>
@endsection
