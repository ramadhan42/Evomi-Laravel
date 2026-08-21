@extends('layouts.admin')

@section('title', 'Traffic Pengunjung | Evomi Admin')

@section('content')
<div x-data="evomiAdminTraffic" class="space-y-6 pb-12">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900" x-text="t('traffic','title')">Traffic Pengunjung</h1>
            <p class="text-gray-500 mt-1" x-text="t('traffic','subtitle')"></p>
            <p class="text-xs text-gray-400 mt-1.5 flex items-center gap-2">
                <span class="inline-flex h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span x-text="t('traffic','live_hint')"></span>
                <span class="text-gray-300">·</span>
                <span x-text="lastUpdatedLabel()"></span>
            </p>
        </div>
        <div class="flex items-center gap-2">
            <button
                type="button"
                @click="autoRefresh = !autoRefresh"
                class="flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold border transition-colors"
                :class="autoRefresh ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'"
            >
                <span class="h-2 w-2 rounded-full" :class="autoRefresh ? 'bg-emerald-500 animate-pulse' : 'bg-gray-300'"></span>
                <span x-text="autoRefresh ? t('traffic','live_on') : t('traffic','live_off')"></span>
            </button>
            <button type="button" @click="load(true)" class="flex items-center justify-center gap-2 bg-gray-900 hover:bg-gray-800 text-white px-5 py-2.5 rounded-xl text-sm font-semibold shadow-[0_4px_14px_0_rgb(0,0,0,0.1)]">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-3-6.7"/><path d="M21 3v6h-6"/></svg>
                <span x-text="common().refresh"></span>
            </button>
        </div>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <p class="text-xs font-bold uppercase tracking-wider text-gray-400" x-text="t('traffic','stat_online')"></p>
            <p class="mt-2 text-3xl font-bold text-gray-900" x-text="stats.online_now"></p>
            <p class="mt-1 text-[11px] text-gray-500">
                <span class="text-emerald-600 font-semibold" x-text="stats.online_user + ' user'"></span>
                ·
                <span class="text-sky-600 font-semibold" x-text="stats.online_guest + ' guest'"></span>
            </p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <p class="text-xs font-bold uppercase tracking-wider text-gray-400" x-text="t('traffic','stat_today_views')"></p>
            <p class="mt-2 text-3xl font-bold text-gray-900" x-text="stats.today_views"></p>
            <p class="mt-1 text-[11px] text-gray-500" x-text="t('traffic','stat_today_views_hint')"></p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <p class="text-xs font-bold uppercase tracking-wider text-gray-400" x-text="t('traffic','stat_today_users')"></p>
            <p class="mt-2 text-3xl font-bold text-indigo-700" x-text="stats.today_user"></p>
            <p class="mt-1 text-[11px] text-gray-500" x-text="t('traffic','stat_today_users_hint')"></p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <p class="text-xs font-bold uppercase tracking-wider text-gray-400" x-text="t('traffic','stat_today_guests')"></p>
            <p class="mt-2 text-3xl font-bold text-sky-700" x-text="stats.today_guest"></p>
            <p class="mt-1 text-[11px] text-gray-500" x-text="t('traffic','stat_today_guests_hint')"></p>
        </div>
    </div>

    <div x-show="loading && !items.length" class="min-h-[40vh] flex items-center justify-center">
        <div class="w-8 h-8 border-4 border-gray-200 border-t-gray-900 rounded-full animate-spin"></div>
    </div>
    <div x-show="error" class="bg-red-50 text-red-700 rounded-2xl px-5 py-4 text-sm border border-red-100" x-text="error"></div>

    <div x-show="!error" class="admin-table-card">
        <div class="p-5 sm:p-6 border-b border-gray-100 flex flex-col lg:flex-row gap-3 lg:items-center lg:justify-between">
            <div class="relative w-full max-w-md">
                <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400 pointer-events-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                <input type="search" x-model="search" :placeholder="t('traffic','search_ph')" class="admin-search-input">
            </div>
            <div class="flex flex-wrap gap-2">
                <template x-for="opt in typeOptions" :key="opt.id">
                    <button
                        type="button"
                        @click="filterType = opt.id; page = 1"
                        class="px-3.5 py-2 rounded-xl text-xs font-bold border transition-colors"
                        :class="filterType === opt.id ? 'bg-gray-900 text-white border-gray-900' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'"
                        x-text="opt.label"
                    ></button>
                </template>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full border-collapse min-w-[760px]">
                <thead>
                    <tr class="bg-gray-50/80 border-b border-gray-100">
                        <th class="px-5 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-left" x-text="t('traffic','col_when')"></th>
                        <th class="px-5 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-left" x-text="t('traffic','col_visitor')"></th>
                        <th class="px-5 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-left" x-text="t('traffic','col_location')"></th>
                        <th class="px-5 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-left" x-text="t('traffic','col_page')"></th>
                        <th class="px-5 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-center" x-text="t('traffic','col_status')"></th>
                        <th class="px-5 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-center" x-text="common().actions"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <tr x-show="pagedItems().length === 0">
                        <td colspan="6" class="px-6 py-14 text-center">
                            <p class="text-sm font-semibold text-gray-500" x-text="t('traffic','empty_title')"></p>
                            <p class="text-xs text-gray-400 mt-1" x-text="t('traffic','empty_desc')"></p>
                        </td>
                    </tr>
                    <template x-for="v in pagedItems()" :key="v.id">
                        <tr class="hover:bg-gray-50/40 transition-colors">
                            <td class="px-5 py-4 align-top">
                                <p class="text-sm font-semibold text-gray-900" x-text="formatWhen(v.last_seen_at || v.visited_at)"></p>
                                <p class="text-[11px] text-gray-400 mt-0.5" x-text="relativeWhen(v.last_seen_at || v.visited_at)"></p>
                            </td>
                            <td class="px-5 py-4 align-top">
                                <div class="flex items-start gap-2.5">
                                    <span
                                        class="mt-0.5 inline-flex px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider border shrink-0"
                                        :class="v.visitor_type === 'user' ? 'bg-indigo-50 text-indigo-700 border-indigo-100' : 'bg-sky-50 text-sky-700 border-sky-100'"
                                        x-text="v.visitor_type === 'user' ? t('traffic','type_user') : t('traffic','type_guest')"
                                    ></span>
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-gray-900 truncate" x-text="visitorName(v)"></p>
                                        <p class="text-[11px] text-gray-400 truncate" x-text="visitorSub(v)"></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4 align-top">
                                <div class="flex items-start gap-2.5 min-w-0">
                                    <span
                                        x-show="countryFlagUrl(v)"
                                        x-cloak
                                        class="mt-0.5 inline-flex h-5 w-7 shrink-0 overflow-hidden rounded-[3px] border border-gray-200 bg-gray-50 shadow-sm"
                                    >
                                        <img
                                            :src="countryFlagUrl(v)"
                                            :alt="countryCodeLabel(v)"
                                            class="h-full w-full object-cover"
                                            loading="lazy"
                                            decoding="async"
                                            width="28"
                                            height="20"
                                        >
                                    </span>
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-gray-900 truncate" x-text="locationLabel(v)"></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4 align-top max-w-[220px]">
                                <p class="text-sm font-medium text-gray-900 truncate" :title="v.path" x-text="v.path || '/'"></p>
                                <p class="text-[11px] text-gray-400 truncate mt-0.5" x-show="v.referrer" x-text="'via ' + shortUrl(v.referrer)"></p>
                            </td>
                            <td class="px-5 py-4 align-top text-center">
                                <span
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold"
                                    :class="v.is_online ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-500'"
                                >
                                    <span class="h-1.5 w-1.5 rounded-full" :class="v.is_online ? 'bg-emerald-500' : 'bg-gray-400'"></span>
                                    <span x-text="v.is_online ? t('traffic','online') : t('traffic','offline')"></span>
                                </span>
                            </td>
                            <td class="px-5 py-4 align-top text-center">
                                <button
                                    type="button"
                                    class="admin-btn-icon"
                                    :title="t('traffic','view_detail')"
                                    @click="openView(v)"
                                >
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        @include('partials.admin-pagination', ['countExpr' => "filteredItems().length + ' ' + t('traffic','rows_word')"])
    </div>

    <template x-teleport="body">
        <div
            x-show="viewOpen"
            x-cloak
            class="admin-modal-root"
            role="dialog"
            aria-modal="true"
            @keydown.escape.window="closeView()"
            @click.self="closeView()"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
        >
            <div class="admin-modal-panel max-w-lg" role="document" @click.stop>
                <div class="admin-modal-panel__header">
                    <h2 class="text-lg font-bold text-gray-900" x-text="t('traffic','detail_title')"></h2>
                    <button type="button" class="p-1.5 rounded-full hover:bg-gray-100 text-gray-400" @click="closeView()" aria-label="Close">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                    </button>
                </div>
                <div class="admin-modal-panel__body space-y-5">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span
                                    class="inline-flex px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider border"
                                    :class="viewVisit?.visitor_type === 'user' ? 'bg-indigo-50 text-indigo-700 border-indigo-100' : 'bg-sky-50 text-sky-700 border-sky-100'"
                                    x-text="viewVisit?.visitor_type === 'user' ? t('traffic','type_user') : t('traffic','type_guest')"
                                ></span>
                                <span
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold"
                                    :class="viewVisit?.is_online ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-500'"
                                >
                                    <span class="h-1.5 w-1.5 rounded-full" :class="viewVisit?.is_online ? 'bg-emerald-500' : 'bg-gray-400'"></span>
                                    <span x-text="viewVisit?.is_online ? t('traffic','online') : t('traffic','offline')"></span>
                                </span>
                            </div>
                            <p class="mt-2 text-base font-bold text-gray-900 truncate" x-text="visitorName(viewVisit)"></p>
                            <p class="text-sm text-gray-500 truncate" x-text="visitorSub(viewVisit)"></p>
                        </div>
                        <span
                            x-show="countryFlagUrl(viewVisit)"
                            x-cloak
                            class="inline-flex h-7 w-10 shrink-0 overflow-hidden rounded-md border border-gray-200 bg-gray-50 shadow-sm"
                        >
                            <img
                                :src="countryFlagUrl(viewVisit)"
                                :alt="countryCodeLabel(viewVisit)"
                                class="h-full w-full object-cover"
                                width="40"
                                height="28"
                            >
                        </span>
                    </div>

                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                        <div class="sm:col-span-2 rounded-xl border border-gray-100 bg-gray-50/70 px-3.5 py-3">
                            <dt class="text-[11px] font-bold uppercase tracking-wider text-gray-400" x-text="t('traffic','col_ip')"></dt>
                            <dd class="mt-1 font-mono text-[13px] text-gray-900 break-all" x-text="viewVisit?.ip_address || '-'"></dd>
                        </div>
                        <div class="sm:col-span-2 rounded-xl border border-gray-100 bg-gray-50/70 px-3.5 py-3">
                            <dt class="text-[11px] font-bold uppercase tracking-wider text-gray-400" x-text="t('traffic','col_device')"></dt>
                            <dd class="mt-1 text-[13px] text-gray-900" x-text="deviceLabel(viewVisit)"></dd>
                            <div class="mt-2 flex flex-wrap gap-1.5">
                                <span class="inline-flex px-2 py-0.5 rounded-md text-[10px] font-semibold bg-white border border-gray-200 text-gray-600" x-text="viewVisit?.device || '-'"></span>
                                <span class="inline-flex px-2 py-0.5 rounded-md text-[10px] font-semibold bg-white border border-gray-200 text-gray-600" x-text="viewVisit?.browser || '-'"></span>
                                <span class="inline-flex px-2 py-0.5 rounded-md text-[10px] font-semibold bg-white border border-gray-200 text-gray-600" x-text="viewVisit?.platform || '-'"></span>
                            </div>
                        </div>
                        <div>
                            <dt class="text-[11px] font-bold uppercase tracking-wider text-gray-400" x-text="t('traffic','col_location')"></dt>
                            <dd class="mt-0.5 text-gray-900" x-text="locationLabel(viewVisit)"></dd>
                            <dd class="text-[11px] text-gray-400 mt-0.5" x-text="countryCodeLabel(viewVisit) || '-'"></dd>
                        </div>
                        <div>
                            <dt class="text-[11px] font-bold uppercase tracking-wider text-gray-400" x-text="t('traffic','col_when')"></dt>
                            <dd class="mt-0.5 text-gray-900" x-text="formatWhen(viewVisit?.last_seen_at || viewVisit?.visited_at)"></dd>
                            <dd class="text-[11px] text-gray-400 mt-0.5" x-text="relativeWhen(viewVisit?.last_seen_at || viewVisit?.visited_at)"></dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-[11px] font-bold uppercase tracking-wider text-gray-400" x-text="t('traffic','col_page')"></dt>
                            <dd class="mt-0.5 text-gray-900 break-all" x-text="viewVisit?.path || '/'"></dd>
                            <dd class="text-[11px] text-gray-400 mt-0.5 break-all" x-show="viewVisit?.full_url" x-text="viewVisit?.full_url"></dd>
                        </div>
                        <div class="sm:col-span-2" x-show="viewVisit?.referrer">
                            <dt class="text-[11px] font-bold uppercase tracking-wider text-gray-400" x-text="t('traffic','col_referrer')"></dt>
                            <dd class="mt-0.5 text-gray-900 break-all" x-text="viewVisit?.referrer"></dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-[11px] font-bold uppercase tracking-wider text-gray-400" x-text="t('traffic','col_visitor_key')"></dt>
                            <dd class="mt-0.5 font-mono text-[12px] text-gray-600 break-all" x-text="viewVisit?.visitor_key || '-'"></dd>
                        </div>
                    </dl>
                </div>
                <div class="admin-modal-panel__footer">
                    <button type="button" class="px-4 py-2.5 rounded-xl border border-gray-200 text-sm font-semibold hover:bg-gray-50" @click="closeView()" x-text="common().close"></button>
                </div>
            </div>
        </div>
    </template>
</div>
@endsection
