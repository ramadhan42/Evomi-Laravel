@extends('layouts.admin')

@section('title', 'SEO | Evomi Admin')

@section('content')
<div x-data="evomiAdminSeo" class="space-y-6 pb-12">
    <div>
        <h1 class="text-3xl font-bold text-gray-900" x-text="t('seo','title')"></h1>
        <p class="text-gray-500 mt-1" x-text="t('seo','subtitle')"></p>
    </div>

    <div x-show="loading" class="min-h-[60vh] flex items-center justify-center">
        <div class="w-8 h-8 border-4 border-gray-200 border-t-gray-900 rounded-full animate-spin"></div>
    </div>
    <div x-show="!loading && error" class="bg-red-50 text-red-700 rounded-2xl px-5 py-4 text-sm border border-red-100" x-text="error"></div>

    <template x-if="!loading && !error">
        <div class="space-y-4">
            {{-- Why the default row matters, stated once at the top. --}}
            <div class="rounded-2xl border border-blue-100 bg-blue-50/60 px-5 py-4">
                <p class="text-sm font-semibold text-blue-900" x-text="t('seo','how_title')"></p>
                <p class="mt-1 text-[13px] leading-relaxed text-blue-800/80" x-text="t('seo','how_body')"></p>
            </div>

            <template x-for="row in rows" :key="row.page">
                <section class="admin-table-card overflow-hidden">
                    <button
                        type="button"
                        @click="toggle(row.page)"
                        class="w-full flex items-center gap-4 px-5 sm:px-6 py-4 text-left hover:bg-gray-50/70 transition-colors"
                    >
                        <span class="h-14 w-24 shrink-0 rounded-xl border border-gray-200 bg-gray-50 overflow-hidden flex items-center justify-center">
                            <template x-if="row.resolved.image">
                                <img :src="row.resolved.image" alt="" class="h-full w-full object-cover" x-on:error="$el.style.visibility='hidden'">
                            </template>
                            <template x-if="!row.resolved.image">
                                <svg class="h-5 w-5 text-gray-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>
                            </template>
                        </span>

                        <span class="min-w-0 flex-1">
                            <span class="flex items-center gap-2">
                                <span class="font-bold text-gray-900" x-text="pageLabel(row)"></span>
                                <span
                                    x-show="row.page === 'default'"
                                    class="rounded-full bg-gray-900 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white"
                                    x-text="t('seo','badge_default')"
                                ></span>
                                <span
                                    x-show="row.noindex"
                                    class="rounded-full bg-rose-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-rose-600"
                                    x-text="t('seo','badge_noindex')"
                                ></span>
                            </span>
                            <span class="mt-0.5 block truncate text-[13px] text-gray-500" x-text="row.resolved.description"></span>
                        </span>

                        <svg class="h-5 w-5 shrink-0 text-gray-400 transition-transform" :class="openPage === row.page ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                    </button>

                    <div x-show="openPage === row.page" x-cloak class="border-t border-gray-100 px-5 sm:px-6 py-5">
                        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
                            {{-- Fields --}}
                            <div class="space-y-4">
                                <label class="block">
                                    <span class="flex items-center justify-between gap-2 text-sm font-semibold text-gray-700">
                                        <span x-text="t('seo','meta_title')"></span>
                                        <span class="text-[11px] font-semibold tabular-nums" :class="stateClass(row, 'meta_title')">
                                            <span x-text="length(row, 'meta_title') + '/' + titleMax"></span>
                                            <span class="font-normal" x-text="'· ' + stateLabel(row, 'meta_title')"></span>
                                        </span>
                                    </span>
                                    <input
                                        x-model="row.meta_title"
                                        :placeholder="t('seo','meta_title_ph')"
                                        maxlength="255"
                                        class="mt-1.5 block w-full h-11 px-3.5 border border-gray-200 rounded-xl text-sm text-gray-900 placeholder:text-gray-400 bg-white focus:outline-none focus:ring-2 focus:ring-gray-900/10 focus:border-gray-400"
                                    >
                                </label>

                                <label class="block">
                                    <span class="flex items-center justify-between gap-2 text-sm font-semibold text-gray-700">
                                        <span x-text="t('seo','meta_description')"></span>
                                        <span class="text-[11px] font-semibold tabular-nums" :class="stateClass(row, 'meta_description')">
                                            <span x-text="length(row, 'meta_description') + '/' + descriptionMax"></span>
                                            <span class="font-normal" x-text="'· ' + stateLabel(row, 'meta_description')"></span>
                                        </span>
                                    </span>
                                    <textarea
                                        x-model="row.meta_description"
                                        :placeholder="t('seo','meta_description_ph')"
                                        maxlength="500"
                                        rows="3"
                                        class="mt-1.5 block w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm leading-relaxed text-gray-900 placeholder:text-gray-400 bg-white focus:outline-none focus:ring-2 focus:ring-gray-900/10 focus:border-gray-400 resize-y"
                                    ></textarea>
                                </label>

                                <label class="block">
                                    <span class="text-sm font-semibold text-gray-700" x-text="t('seo','meta_keywords')"></span>
                                    <input
                                        x-model="row.meta_keywords"
                                        :placeholder="t('seo','meta_keywords_ph')"
                                        maxlength="255"
                                        class="mt-1.5 block w-full h-11 px-3.5 border border-gray-200 rounded-xl text-sm text-gray-900 placeholder:text-gray-400 bg-white focus:outline-none focus:ring-2 focus:ring-gray-900/10 focus:border-gray-400"
                                    >
                                    <span class="mt-1 block text-[11px] text-gray-400" x-text="t('seo','meta_keywords_hint')"></span>
                                </label>

                                {{-- English --}}
                                <div class="rounded-xl border border-gray-200 bg-gray-50/60 p-4">
                                    <button
                                        type="button"
                                        @click="toggleTranslation(row.page)"
                                        class="flex w-full items-center justify-between text-xs font-bold uppercase tracking-wide text-gray-500 hover:text-gray-800"
                                    >
                                        <span x-text="t('seo','translations')"></span>
                                        <svg class="h-4 w-4 transition-transform" :class="openTranslation === row.page ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                                    </button>
                                    <div x-show="openTranslation === row.page" x-cloak class="mt-3 space-y-3">
                                        <label class="block">
                                            <span class="text-xs font-semibold text-gray-600" x-text="t('seo','meta_title_en')"></span>
                                            <input x-model="row.meta_title_en" maxlength="255" class="mt-1 block w-full h-10 px-3 border border-gray-200 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-gray-900/10">
                                        </label>
                                        <label class="block">
                                            <span class="text-xs font-semibold text-gray-600" x-text="t('seo','meta_description_en')"></span>
                                            <textarea x-model="row.meta_description_en" maxlength="500" rows="2" class="mt-1 block w-full px-3 py-2 border border-gray-200 rounded-lg text-sm leading-relaxed bg-white focus:outline-none focus:ring-2 focus:ring-gray-900/10 resize-y"></textarea>
                                        </label>
                                    </div>
                                </div>

                                <label class="flex items-center gap-3 cursor-pointer" x-show="row.page !== 'default'">
                                    <input type="checkbox" x-model="row.noindex" class="sr-only peer">
                                    <span class="doc-switch-track"><span class="doc-switch-thumb"></span></span>
                                    <span class="text-sm text-gray-700" x-text="t('seo','noindex')"></span>
                                </label>
                            </div>

                            {{-- Share image + Google preview --}}
                            <div class="space-y-4">
                                <div>
                                    <p class="text-sm font-semibold text-gray-700" x-text="t('seo','og_image')"></p>
                                    <div class="mt-1.5 rounded-xl border border-gray-200 bg-gray-50/60 p-3 space-y-2">
                                        <div class="aspect-[1200/630] w-full rounded-lg bg-white border border-gray-200 overflow-hidden flex items-center justify-center">
                                            <template x-if="previewImage(row)">
                                                <img :src="previewImage(row)" alt="" class="h-full w-full object-cover" x-on:error="$el.style.visibility='hidden'">
                                            </template>
                                            <template x-if="!previewImage(row)">
                                                <div class="flex flex-col items-center gap-1 text-gray-400">
                                                    <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>
                                                    <span class="text-[11px]" x-text="t('seo','og_image_empty')"></span>
                                                </div>
                                            </template>
                                        </div>
                                        <input
                                            type="file"
                                            accept="image/jpeg,image/png,image/jpg,image/webp"
                                            @change="onImage(row, $event)"
                                            class="w-full text-xs text-gray-500 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-gray-900 file:text-white hover:file:bg-gray-800"
                                        >
                                        <p class="text-[11px] leading-relaxed text-gray-400" x-text="t('seo','og_image_hint')"></p>
                                        <button
                                            type="button"
                                            x-show="row.og_image"
                                            @click="clearImage(row)"
                                            class="text-[11px] font-semibold text-gray-500 underline hover:text-rose-600"
                                            x-text="t('seo','og_image_remove')"
                                        ></button>
                                    </div>
                                </div>

                                <div>
                                    <p class="text-sm font-semibold text-gray-700" x-text="t('seo','preview')"></p>
                                    <div class="mt-1.5 rounded-xl border border-gray-200 bg-white p-3">
                                        <p class="truncate text-[11px] text-emerald-700" x-text="row.url"></p>
                                        <p class="mt-1 truncate text-[15px] leading-snug text-[#1a0dab]" x-text="effective(row, 'meta_title')"></p>
                                        <p class="mt-1 text-[12px] leading-relaxed text-gray-600 line-clamp-2" x-text="effective(row, 'meta_description')"></p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-5 flex items-center justify-end gap-2 border-t border-gray-100 pt-4">
                            <button
                                type="button"
                                :disabled="savingPage === row.page"
                                @click="reset(row)"
                                class="px-4 py-2.5 rounded-xl text-sm font-semibold text-gray-600 hover:bg-gray-100 transition disabled:opacity-50"
                                x-text="t('seo','reset')"
                            ></button>
                            <button
                                type="button"
                                :disabled="savingPage === row.page"
                                @click="save(row)"
                                class="inline-flex min-w-[8rem] items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-gray-900 text-white text-sm font-semibold disabled:opacity-60 disabled:cursor-wait"
                            >
                                <svg x-show="savingPage === row.page" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                                </svg>
                                <span x-text="savingPage === row.page ? common().saving : common().save"></span>
                            </button>
                        </div>
                    </div>
                </section>
            </template>
        </div>
    </template>
</div>
@endsection
