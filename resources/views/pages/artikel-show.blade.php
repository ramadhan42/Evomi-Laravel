@extends('layouts.app')

@section('title', ($article['title'] ?? evomi_l('Artikel', 'Article')) . ' | Evomi')

@section('content')
@php
    $contentRaw = trim((string) ($article['content'] ?? ''));
    $paragraphs = preg_split("/\n\s*\n/", $contentRaw) ?: [];
    $paragraphs = array_values(array_filter(array_map('trim', $paragraphs)));
    $wordCount = count(preg_split('/\s+/u', $contentRaw, -1, PREG_SPLIT_NO_EMPTY) ?: []);
    $readMinutes = max(1, (int) ceil($wordCount / 180));
    $related = $related ?? [];
    $publishedLabel = ! empty($article['published_at'])
        ? \Illuminate\Support\Carbon::parse($article['published_at'])->locale(evomi_locale())->translatedFormat('j F Y')
        : '';
@endphp

<div
    class="min-h-screen flex flex-col font-nohemi w-full artikel-page artikel-detail-page"
    x-data="evomiArtikelShow(@js([
        'title' => $article['title'] ?? '',
        'excerpt' => $article['excerpt'] ?? '',
    ]))"
>
    <section class="relative overflow-hidden text-white pt-10 md:pt-14 pb-14 md:pb-16 px-5 md:px-8">
        @include('partials.artikel-hero-backdrop')

        <div class="relative z-10 max-w-3xl mx-auto">
            <a
                href="{{ route('artikel') }}"
                data-soft-nav
                class="artikel-back-link group inline-flex items-center gap-2 text-sm text-white/85 hover:text-white mb-7 transition-colors"
            >
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-white/15 ring-1 ring-white/20 transition-all group-hover:-translate-x-0.5 group-hover:bg-white/25 group-hover:ring-white/35">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
                </span>
                {{ evomi_l('Kembali ke artikel', 'Back to articles') }}
            </a>

            <div class="artikel-fade-up" style="--artikel-delay: 40ms">
                <span class="inline-flex rounded-full bg-white/15 px-3.5 py-1 text-[11px] uppercase tracking-[0.16em] text-[#E8F4FC] font-semibold ring-1 ring-white/20 backdrop-blur-sm">
                    {{ $article['category'] ?? 'parfum' }}
                </span>
                <h1
                    class="artikel-detail-title mt-5 leading-[1.12] tracking-tight drop-shadow-[0_1px_0_rgba(0,0,0,0.05)] text-white"
                    style="{{ $article['font_title'] ?? '' }}"
                >{{ $article['title'] }}</h1>
                <div class="mt-5 flex flex-wrap items-center gap-x-3 gap-y-2 text-sm text-white/80">
                    @if ($publishedLabel !== '')
                        <span>{{ $publishedLabel }}</span>
                    @endif
                    @if (! empty($article['author']))
                        <span class="text-white/40" aria-hidden="true">·</span>
                        <span>{{ $article['author'] }}</span>
                    @endif
                    <span class="text-white/40" aria-hidden="true">·</span>
                    <span>{{ $readMinutes }} {{ evomi_l('menit baca', 'min read') }}</span>
                </div>
            </div>
        </div>
    </section>

    <section class="flex-1 bg-white px-5 md:px-8 py-10 md:py-14">
        <div class="max-w-3xl mx-auto">
            <article class="artikel-fade-up" style="--artikel-delay: 80ms">
                @if (! empty($article['excerpt']))
                    <p class="text-gray-700 leading-relaxed" style="{{ $article['font_excerpt'] ?? '' }}">
                        {{ $article['excerpt'] }}
                    </p>
                @endif

                @if (! empty($article['image']))
                    <div class="relative mt-8 aspect-[16/9] overflow-hidden rounded-[28px] border border-[#1172BA]/10 bg-[#E8F4FC] shadow-[0_18px_40px_-28px_rgba(17,114,186,0.45)]">
                        <img
                            src="{{ $article['image'] }}"
                            alt="{{ $article['title'] }}"
                            class="absolute inset-0 h-full w-full object-cover"
                            loading="eager"
                            fetchpriority="high"
                        >
                    </div>
                @endif

                <div class="mt-8 flex flex-wrap items-center gap-3">
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-full border border-[#1172BA]/20 bg-[#E8F4FC] px-4 py-2 text-sm font-medium text-[#1172BA] transition-all hover:bg-[#1172BA] hover:text-white hover:shadow-md active:scale-[0.98]"
                        @click="copyLink()"
                    >
                        <svg x-show="!copied" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0 0 13.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 0 1-.75.75H9a.75.75 0 0 1-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 0 1-2.25 2.25H6.75A2.25 2.25 0 0 1 4.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 0 1 1.927-.184"/></svg>
                        <svg x-show="copied" x-cloak class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                        <span x-text="copied ? $L('Tersalin', 'Copied') : $L('Salin tautan', 'Copy link')"></span>
                    </button>
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-full border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition-all hover:border-[#1172BA]/30 hover:text-[#1172BA] hover:shadow-sm active:scale-[0.98]"
                        @click="share()"
                    >
                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M7.217 10.907a2.25 2.25 0 1 0 0 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186 9.566-5.314m-9.566 7.5 9.566 5.314m0 0a2.25 2.25 0 1 0 3.935 2.186 2.25 2.25 0 0 0-3.935-2.186Zm0-12.814a2.25 2.25 0 1 0 3.933-2.185 2.25 2.25 0 0 0-3.933 2.185Z"/></svg>
                        {{ evomi_l('Bagikan', 'Share') }}
                    </button>
                </div>

                <div class="my-9 h-px bg-gradient-to-r from-transparent via-[#1172BA]/25 to-transparent"></div>

                <div class="artikel-detail-body space-y-5 text-gray-700 leading-[1.8]" style="{{ $article['font_content'] ?? '' }}">
                    @forelse ($paragraphs as $paragraph)
                        <p>{{ $paragraph }}</p>
                    @empty
                        <p class="text-gray-500">{{ evomi_l('Konten artikel belum tersedia.', 'Article content is not available yet.') }}</p>
                    @endforelse
                </div>

                @if (count($related) > 0)
                    <div class="mt-14 pt-10 border-t border-gray-100">
                        <div class="flex items-end justify-between gap-4 mb-6">
                            <div>
                                <p class="text-xs uppercase tracking-[0.18em] text-[#1172BA]/70 font-semibold">{{ evomi_l('Jurnal Evomi', 'Evomi Journal') }}</p>
                                <h2 class="mt-2 text-2xl md:text-3xl text-gray-900">{{ evomi_l('Artikel terkait', 'Related articles') }}</h2>
                            </div>
                            <a href="{{ route('artikel') }}" data-soft-nav class="hidden sm:inline text-sm font-medium text-[#1172BA] hover:underline">{{ evomi_l('Lihat semua', 'View all') }}</a>
                        </div>

                        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach ($related as $index => $item)
                                <a
                                    href="{{ route('artikel.show', $item['slug']) }}"
                                    data-soft-nav
                                    class="artikel-related-card group block overflow-hidden rounded-3xl border border-gray-100 bg-white transition-all duration-300 hover:-translate-y-0.5 hover:border-[#1172BA]/20 hover:shadow-[0_18px_36px_-24px_rgba(17,114,186,0.4)]"
                                    style="--artikel-delay: {{ min($index * 60, 180) }}ms"
                                >
                                    <div class="relative aspect-[16/10] bg-[#E8F4FC] overflow-hidden">
                                        @if (! empty($item['image']))
                                            <img
                                                src="{{ $item['image'] }}"
                                                alt="{{ $item['title'] }}"
                                                class="absolute inset-0 h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                                                loading="lazy"
                                            >
                                        @else
                                            <div class="absolute inset-0 flex items-center justify-center text-[#1172BA]/30">
                                                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="p-4">
                                        <h3 class="font-semibold text-base leading-snug text-gray-900 group-hover:text-[#1172BA] transition-colors line-clamp-2">{{ $item['title'] }}</h3>
                                        <p class="mt-2 text-xs text-gray-400">
                                            {{ ! empty($item['published_at']) ? \Illuminate\Support\Carbon::parse($item['published_at'])->locale(evomi_locale())->translatedFormat('j F Y') : '' }}
                                        </p>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </article>
        </div>
    </section>
</div>
@endsection
