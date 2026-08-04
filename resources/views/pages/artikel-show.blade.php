@extends('layouts.app')

@section('title', ($article['title'] ?? 'Artikel') . ' | Evomi')

@section('content')
@php
    $paragraphs = preg_split("/\n\s*\n/", trim($article['content'] ?? '')) ?: [];
    $words = str_word_count(strip_tags($article['content'] ?? ''));
    $readMinutes = max(1, (int) ceil($words / 180));
    $related = $related ?? [];
@endphp

<div class="min-h-0 flex flex-col font-nohemi w-full" x-data="{ copied: false }">
    <section class="relative overflow-hidden text-white py-10 md:py-14 px-5 md:px-8 bg-[linear-gradient(160deg,#1172BA_0%,#2E86C8_50%,#5BA3DC_100%)]">
        <div class="relative max-w-3xl mx-auto">
            <a href="{{ route('artikel') }}" data-soft-nav class="group inline-flex items-center gap-2 text-sm text-white/85 hover:text-white mb-7">
                <span class="h-8 w-8 rounded-full bg-white/15 ring-1 ring-white/20 inline-flex items-center justify-center">←</span>
                Kembali ke artikel
            </a>
            <span class="inline-flex rounded-full bg-white/15 px-3.5 py-1 text-[11px] uppercase tracking-[0.16em] text-[#E8F4FC] font-semibold ring-1 ring-white/20">
                {{ $article['category'] }}
            </span>
            <h1 class="mt-4 text-[28px] md:text-[40px] font-semibold leading-tight tracking-tight">{{ $article['title'] }}</h1>
            <p class="mt-4 text-sm text-white/80">
                {{ \Illuminate\Support\Carbon::parse($article['published_at'])->translatedFormat('d M Y') }}
                · {{ $article['author'] }}
                · {{ $readMinutes }} menit baca
            </p>
        </div>
    </section>

    <section class="flex-1 bg-white px-5 md:px-8 py-10 md:py-14">
        <article class="max-w-3xl mx-auto">
            <p class="text-gray-700 leading-relaxed text-[15px] md:text-base">{{ $article['excerpt'] }}</p>

            <div class="relative mt-8 aspect-[16/9] overflow-hidden rounded-[28px] border border-[#1172BA]/10 bg-[#E8F4FC] shadow-[0_18px_40px_-28px_rgba(17,114,186,0.45)]">
                <div class="absolute inset-0 bg-[linear-gradient(135deg,#E8F4FC,#9CD6FF66)]"></div>
            </div>

            <div class="mt-8 flex flex-wrap items-center gap-3">
                <button
                    type="button"
                    class="rounded-full border border-[#1172BA]/20 bg-[#E8F4FC] px-4 py-2 text-sm font-medium text-[#1172BA]"
                    @click="navigator.clipboard.writeText(window.location.href); copied = true; setTimeout(() => copied = false, 2000)"
                    x-text="copied ? 'Tersalin' : 'Salin tautan'"
                ></button>
            </div>

            <div class="my-9 h-px bg-gradient-to-r from-transparent via-[#1172BA]/25 to-transparent"></div>

            <div class="space-y-5 text-gray-700 leading-[1.8] text-[15px] md:text-base">
                @foreach ($paragraphs as $paragraph)
                    <p>{{ $paragraph }}</p>
                @endforeach
            </div>

            @if (count($related) > 0)
                <div class="mt-14 pt-10 border-t border-gray-100">
                    <p class="text-xs uppercase tracking-[0.18em] text-[#1172BA]/70 font-semibold">Jurnal Evomi</p>
                    <h2 class="mt-2 text-2xl md:text-3xl text-gray-900 font-semibold">Artikel terkait</h2>
                    <div class="mt-6 grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach ($related as $item)
                            <a href="{{ route('artikel.show', $item['slug']) }}" data-soft-nav class="rounded-[20px] border border-gray-100 p-4 hover:border-[#1172BA]/25 transition-colors">
                                <p class="text-[10px] uppercase tracking-wider text-[#1172BA] font-semibold">{{ $item['category'] }}</p>
                                <h3 class="mt-2 font-semibold text-gray-900 text-sm leading-snug">{{ $item['title'] }}</h3>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </article>
    </section>
</div>
@endsection
