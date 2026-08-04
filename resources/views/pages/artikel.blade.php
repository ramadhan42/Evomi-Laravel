@extends('layouts.app')

@section('title', 'Artikel | Evomi')

@section('content')
@php
    $articles = $articles ?? [];
@endphp

<div
    class="min-h-0 flex flex-col font-nohemi w-full"
    x-data="evomiArtikelList(@js($articles))"
>
    <section class="relative overflow-hidden text-white py-10 md:py-14 px-5 md:px-8 bg-[linear-gradient(160deg,#1172BA_0%,#2E86C8_45%,#5BA3DC_100%)]">
        <div class="pointer-events-none absolute inset-0 opacity-30" style="background-image:radial-gradient(circle at 20% 20%,rgba(255,255,255,.35),transparent 40%),radial-gradient(circle at 80% 0%,rgba(165,225,148,.25),transparent 35%)"></div>
        <div class="relative max-w-6xl mx-auto">
            <p class="text-[11px] md:text-xs uppercase tracking-[0.18em] font-semibold text-white/80 mb-3">Jurnal Evomi</p>
            <h1 class="text-[28px] md:text-[42px] font-semibold tracking-tight">Artikel Parfum</h1>
            <p class="mt-3 text-sm md:text-base text-white/90 max-w-2xl leading-relaxed">
                Baca panduan aroma, tips perawatan parfum, dan cerita di balik karakter wewangian Evomi.
            </p>

            <div class="mt-8 relative max-w-md" :class="searchFocused && 'scale-[1.02]'" class="transition-transform duration-300">
                <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M11 18a7 7 0 1 1 0-14 7 7 0 0 1 0 14Z"/></svg>
                <input
                    type="search"
                    x-model="query"
                    @focus="searchFocused = true"
                    @blur="searchFocused = false"
                    placeholder="Cari artikel..."
                    class="w-full rounded-full bg-white text-gray-900 pl-11 pr-11 py-3.5 text-sm outline-none shadow-[0_12px_40px_-18px_rgba(17,114,186,0.55)] focus:ring-2 focus:ring-[#9CD6FF]"
                >
                <button
                    type="button"
                    x-show="query"
                    x-cloak
                    @click="query = ''"
                    class="absolute right-3 top-1/2 -translate-y-1/2 h-7 w-7 rounded-full bg-gray-100 text-gray-500 text-sm"
                >×</button>
            </div>
            <p class="mt-4 text-xs text-white/80" x-text="resultLabel"></p>
        </div>
    </section>

    <section class="flex-1 bg-white px-5 md:px-8 py-10 md:py-14">
        <div class="max-w-6xl mx-auto">
            <template x-if="filtered.length === 0">
                <div class="rounded-[32px] border border-dashed border-[#1172BA]/25 bg-[#F7FBFE] px-6 py-16 text-center">
                    <h2 class="text-lg font-semibold text-gray-900">Artikel tidak ditemukan</h2>
                    <p class="mt-2 text-sm text-gray-600" x-text="query ? 'Coba kata kunci lain, atau hapus pencarian untuk melihat semua artikel.' : 'Belum ada artikel parfum yang dipublikasikan.'"></p>
                    <button type="button" x-show="query" @click="query = ''" class="mt-6 rounded-full bg-[#1172BA] px-5 py-2.5 text-sm text-white" x-cloak>Hapus pencarian</button>
                </div>
            </template>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6" x-show="filtered.length > 0">
                <template x-for="article in paged" :key="article.id">
                    <a
                        :href="'/artikel/' + article.slug"
                        data-soft-nav
                        class="group block h-full overflow-hidden rounded-[28px] border border-gray-100 bg-white shadow-[0_1px_0_rgba(17,114,186,0.04)] transition-all duration-300 hover:-translate-y-1 hover:border-[#1172BA]/20 hover:shadow-[0_22px_44px_-28px_rgba(17,114,186,0.45)]"
                    >
                        <div class="relative aspect-[16/10] overflow-hidden bg-[#E8F4FC]">
                            <div class="absolute inset-0 bg-[linear-gradient(135deg,#E8F4FC,#9CD6FF55)]"></div>
                            <span class="absolute left-3 top-3 rounded-full bg-white/90 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.14em] text-[#1172BA]" x-text="article.category"></span>
                        </div>
                        <div class="flex flex-col p-5 text-left">
                            <h3 class="font-semibold text-lg leading-snug text-gray-900 group-hover:text-[#1172BA]" x-text="article.title"></h3>
                            <p class="mt-2 text-sm text-gray-600 line-clamp-3" x-text="article.excerpt"></p>
                            <div class="mt-4 flex items-center justify-between gap-3">
                                <p class="text-xs text-gray-400" x-text="formatDate(article.published_at)"></p>
                                <span class="text-xs font-medium text-[#1172BA] opacity-0 group-hover:opacity-100 transition-opacity">Baca →</span>
                            </div>
                        </div>
                    </a>
                </template>
            </div>

            <div
                class="mt-12 flex flex-col sm:flex-row items-center justify-between gap-5 rounded-[24px] border border-gray-100 bg-[#F7FBFE] px-5 py-5 md:px-6"
                x-show="totalPages > 1"
                x-cloak
            >
                <p class="text-sm text-gray-600" x-text="'Halaman ' + page + ' dari ' + totalPages + ' · ' + filtered.length + ' artikel'"></p>
                <div class="flex items-center gap-2">
                    <button type="button" @click="page = Math.max(1, page - 1)" :disabled="page <= 1" class="rounded-full border border-[#1172BA]/25 bg-white px-4 py-2.5 text-sm font-medium text-[#1172BA] disabled:opacity-40">Sebelumnya</button>
                    <button type="button" @click="page = Math.min(totalPages, page + 1)" :disabled="page >= totalPages" class="rounded-full border border-[#1172BA]/25 bg-white px-4 py-2.5 text-sm font-medium text-[#1172BA] disabled:opacity-40">Berikutnya</button>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
