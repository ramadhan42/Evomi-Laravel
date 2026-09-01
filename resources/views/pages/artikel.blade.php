@extends('layouts.app')

@section('content')
@php
    $articles = $articles ?? [];
@endphp

<div
    class="min-h-0 flex flex-col font-nohemi w-full artikel-page"
    x-data="evomiArtikelList(@js($articles))"
>
    <section class="relative overflow-hidden text-white pt-10 md:pt-14 pb-16 md:pb-20 px-5 md:px-8">
        @include('partials.artikel-hero-backdrop')

        <div class="relative z-10 max-w-6xl mx-auto">
            <p class="artikel-fade-up uppercase tracking-[0.22em] text-white/75 text-xs md:text-sm mb-3" style="--artikel-delay: 0ms">
                {{ evomi_l('Jurnal Evomi', 'Evomi Journal') }}
            </p>
            <h1 class="artikel-fade-up font-nohemi text-4xl md:text-6xl leading-none tracking-tight" style="--artikel-delay: 50ms">
                {{ evomi_l('Artikel Parfum', 'Perfume Articles') }}
            </h1>
            <p class="artikel-fade-up mt-4 max-w-2xl text-white/90 text-sm md:text-base leading-relaxed" style="--artikel-delay: 120ms">
                {!! evomi_l(
                    'Baca panduan aroma, tips perawatan parfum,<br class="hidden sm:inline"> dan cerita di balik karakter wewangian Evomi.',
                    'Read scent guides, perfume care tips,<br class="hidden sm:inline"> and stories behind Evomi fragrance characters.'
                ) !!}
            </p>

            <div
                class="artikel-fade-up mt-8 relative max-w-md transition-transform duration-300"
                style="--artikel-delay: 180ms"
                :class="searchFocused ? 'scale-[1.02]' : 'scale-100'"
            >
                <svg
                    class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 transition-colors"
                    :class="searchFocused ? 'text-[#1172BA]' : 'text-[#1172BA]/70'"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="2"
                ><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M11 18a7 7 0 1 1 0-14 7 7 0 0 1 0 14Z"/></svg>
                <input
                    type="search"
                    x-model="query"
                    @focus="searchFocused = true"
                    @blur="searchFocused = false"
                    placeholder="{{ evomi_l('Cari artikel...', 'Search articles...') }}"
                    class="w-full rounded-full bg-white text-gray-900 pl-11 pr-11 py-3.5 text-sm outline-none shadow-[0_12px_40px_-18px_rgba(17,114,186,0.55)] transition-shadow duration-300"
                    :class="searchFocused ? 'ring-2 ring-[#9CD6FF] shadow-[0_16px_44px_-14px_rgba(17,114,186,0.65)]' : 'ring-0'"
                >
                <button
                    type="button"
                    x-show="query"
                    x-cloak
                    @click="query = ''"
                    class="absolute right-3 top-1/2 -translate-y-1/2 inline-flex h-7 w-7 items-center justify-center rounded-full text-gray-400 hover:bg-gray-100 hover:text-gray-700 transition-colors"
                    aria-label="{{ evomi_l('Hapus pencarian', 'Clear search') }}"
                >
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <p class="artikel-fade-up mt-4 text-xs text-white/70" style="--artikel-delay: 280ms" x-text="resultLabel"></p>
        </div>
    </section>

    <section class="flex-1 bg-white px-5 md:px-8 py-10 md:py-14">
        <div class="max-w-6xl mx-auto">
            <div
                x-show="filtered.length === 0"
                x-cloak
                class="flex flex-col items-center justify-center rounded-[32px] border border-dashed border-[#1172BA]/25 bg-[#F7FBFE] px-6 py-16 text-center"
            >
                <span class="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-[#E8F4FC] text-[#1172BA]">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
                </span>
                <h2 class="mt-5 text-xl text-gray-900 font-semibold">{{ evomi_l('Artikel tidak ditemukan', 'No articles found') }}</h2>
                <p class="mt-2 max-w-sm text-sm text-gray-500" x-text="query.trim() ? $L('Coba kata kunci lain, atau hapus pencarian untuk melihat semua artikel.', 'Try another keyword, or clear search to see all articles.') : $L('Belum ada artikel parfum yang dipublikasikan.', 'No perfume articles have been published yet.')"></p>
                <button
                    type="button"
                    x-show="query.trim()"
                    x-cloak
                    @click="query = ''"
                    class="mt-6 inline-flex items-center gap-2 rounded-full bg-[#1172BA] px-5 py-2.5 text-sm font-medium text-white transition-colors hover:bg-[#0d5f9c]"
                >{{ evomi_l('Hapus pencarian', 'Clear search') }}</button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6" x-show="filtered.length > 0" x-cloak>
                <template x-for="(article, index) in paged" :key="article.id">
                    <a
                        :href="'/artikel/' + article.slug"
                        data-soft-nav
                        class="artikel-card group block h-full overflow-hidden rounded-[28px] border border-gray-100 bg-white shadow-[0_1px_0_rgba(17,114,186,0.04)] transition-all duration-300 hover:-translate-y-1 hover:border-[#1172BA]/20 hover:shadow-[0_22px_44px_-28px_rgba(17,114,186,0.45)]"
                        :style="'--artikel-delay:' + Math.min(index * 50, 250) + 'ms'"
                    >
                        <div class="relative aspect-[16/10] overflow-hidden bg-[#E8F4FC]">
                            <template x-if="article.image">
                                <img
                                    :src="article.image"
                                    :alt="article.title"
                                    class="absolute inset-0 h-full w-full object-cover transition-transform duration-700 ease-out group-hover:scale-105"
                                    loading="lazy"
                                >
                            </template>
                            <template x-if="!article.image">
                                <div class="absolute inset-0 flex items-center justify-center text-[#1172BA]/35">
                                    <svg class="w-10 h-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
                                </div>
                            </template>
                            <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-[#1172BA]/25 via-transparent to-transparent opacity-0 transition-opacity duration-300 group-hover:opacity-100"></div>
                            <span
                                x-show="article.category"
                                class="absolute left-3 top-3 rounded-full bg-white/90 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.14em] text-[#1172BA] backdrop-blur-sm"
                                x-text="article.category"
                            ></span>
                        </div>
                        <div class="flex flex-col p-5 text-left">
                            <h3 class="font-semibold text-lg leading-snug text-gray-900 transition-colors group-hover:text-[#1172BA]" x-text="article.title"></h3>
                            <p class="mt-2 text-sm text-gray-600 line-clamp-3" x-text="article.excerpt_text || article.excerpt"></p>
                            <div class="mt-4 flex items-center justify-between gap-3">
                                <p class="text-xs text-gray-400" x-text="formatDate(article.published_at)"></p>
                                <span class="text-xs font-medium text-[#1172BA] opacity-0 translate-x-1 transition-all duration-300 group-hover:opacity-100 group-hover:translate-x-0">{{ evomi_l('Baca →', 'Read →') }}</span>
                            </div>
                        </div>
                    </a>
                </template>
            </div>

            <div
                class="mt-12 flex flex-col sm:flex-row items-center justify-between gap-5 rounded-[24px] border border-gray-100 bg-[#F7FBFE] px-5 py-5 md:px-6"
                x-show="filtered.length > perPage"
                x-cloak
            >
                <p class="text-sm text-gray-500">
                    {{ evomi_l('Halaman', 'Page') }} <span x-text="page"></span> {{ evomi_l('dari', 'of') }} <span x-text="totalPages"></span>
                    <span class="text-gray-300 mx-2">·</span>
                    <span x-text="filtered.length"></span> {{ evomi_l('artikel', 'articles') }}
                </p>

                <div class="flex items-center gap-2.5">
                    <button
                        type="button"
                        @click="goPrev()"
                        :disabled="page <= 1"
                        class="inline-flex items-center gap-1.5 rounded-full border border-[#1172BA]/25 bg-white px-4 py-2.5 text-sm font-medium text-[#1172BA] transition-all hover:bg-[#1172BA] hover:text-white hover:shadow-md disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:bg-white disabled:hover:text-[#1172BA] disabled:hover:shadow-none"
                        aria-label="{{ evomi_l('Sebelumnya', 'Previous') }}"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
                        {{ evomi_l('Sebelumnya', 'Previous') }}
                    </button>

                    <div class="flex items-center gap-1.5">
                        <template x-for="n in pageNumbers" :key="n">
                            <button
                                type="button"
                                @click="goToPage(n)"
                                class="h-9 min-w-9 px-2 rounded-full text-sm font-medium transition-all"
                                :class="n === page
                                    ? 'bg-[#1172BA] text-white shadow-[0_10px_24px_-12px_rgba(17,114,186,0.8)] scale-105'
                                    : 'bg-white text-[#1172BA] border border-[#1172BA]/15 hover:bg-[#1172BA]/10'"
                                :aria-label="$L('Halaman ', 'Page ') + n"
                                :aria-current="n === page ? 'page' : null"
                                x-text="n"
                            ></button>
                        </template>
                    </div>

                    <button
                        type="button"
                        @click="goNext()"
                        :disabled="page >= totalPages"
                        class="inline-flex items-center gap-1.5 rounded-full border border-[#1172BA]/25 bg-white px-4 py-2.5 text-sm font-medium text-[#1172BA] transition-all hover:bg-[#1172BA] hover:text-white hover:shadow-md disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:bg-white disabled:hover:text-[#1172BA] disabled:hover:shadow-none"
                        aria-label="{{ evomi_l('Berikutnya', 'Next') }}"
                    >
                        {{ evomi_l('Berikutnya', 'Next') }}
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                    </button>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
