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
            class="flex items-center justify-center gap-2 bg-gray-900 hover:bg-gray-800 text-white px-5 py-2.5 rounded-xl text-sm font-semibold transition-all shadow-[0_4px_14px_0_rgb(0,0,0,0.1)] active:scale-[0.98]"
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
        <div class="p-5 sm:p-6 border-b border-gray-100 flex flex-col lg:flex-row lg:items-center gap-3 lg:justify-between">
            <div class="relative w-full max-w-md">
                <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400 pointer-events-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                <input type="search" x-model="search" :placeholder="t('articles','search_placeholder')" class="block w-full h-11 pl-10 pr-3 border border-gray-200 rounded-xl text-sm text-gray-900 placeholder:text-gray-400 bg-white focus:outline-none focus:ring-2 focus:ring-gray-900/10 focus:border-gray-400">
            </div>

            {{-- Status filter --}}
            <div class="inline-flex rounded-xl bg-gray-100 p-1 self-start">
                <template x-for="tab in ['all','published','draft']" :key="tab">
                    <button
                        type="button"
                        class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-all"
                        :class="statusFilter === tab ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-800'"
                        @click="setStatusFilter(tab)"
                    >
                        <span x-text="t('articles', tab === 'all' ? 'filter_all' : (tab === 'published' ? 'published' : 'draft'))"></span>
                        <span class="ml-1 tabular-nums opacity-60" x-text="statusCount(tab)"></span>
                    </button>
                </template>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full border-collapse min-w-[900px]">
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
                        <tr
                            class="group cursor-pointer transition-colors hover:bg-blue-50/40"
                            :title="t('articles','row_hint')"
                            @click="openEdit(a)"
                        >
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3 min-w-[260px]">
                                    @include('partials.admin-thumb', [
                                        'src' => 'articleThumb(a)',
                                        'alt' => 'a.title',
                                        'size' => 'h-16 w-24',
                                        'fit' => 'cover',
                                    ])
                                    <div class="min-w-0">
                                        <p class="font-semibold text-gray-900 truncate group-hover:text-[#0b57d0] transition-colors" x-text="a.title"></p>
                                        <p class="text-xs text-gray-500 truncate" x-text="'/' + (a.slug || '')"></p>
                                        <p class="mt-0.5 text-xs text-gray-400 truncate max-w-[22rem]" x-text="excerptPreview(a)"></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center text-sm text-gray-600 capitalize" x-text="a.category || '-'"></td>
                            <td class="px-6 py-4 text-center">
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium transition-all hover:shadow-sm active:scale-[0.97]"
                                    :class="a.is_published ? 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                                    :title="t('articles','toggle_publish')"
                                    @click.stop="togglePublish(a)"
                                >
                                    <span class="h-1.5 w-1.5 rounded-full" :class="a.is_published ? 'bg-emerald-500' : 'bg-gray-400'"></span>
                                    <span x-text="a.is_published ? t('articles','published') : t('articles','draft')"></span>
                                </button>
                            </td>
                            <td class="px-6 py-4 text-center text-sm text-gray-600 whitespace-nowrap" x-text="formatDate(a.published_at)"></td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-2 opacity-70 group-hover:opacity-100 transition-opacity">
                                    <a
                                        :href="previewUrl(a)"
                                        target="_blank"
                                        rel="noopener"
                                        class="admin-btn-icon"
                                        :title="t('articles','preview')"
                                        @click.stop
                                    >
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1.5 12S5 5 12 5s10.5 7 10.5 7-3.5 7-10.5 7S1.5 12 1.5 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                                    </a>
                                    <button type="button" class="admin-btn-icon" :title="common().edit" @click.stop="openEdit(a)">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                                    </button>
                                    <button type="button" class="admin-btn-icon admin-btn-icon--danger" :title="common().delete" @click.stop="remove(a.id)">
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
