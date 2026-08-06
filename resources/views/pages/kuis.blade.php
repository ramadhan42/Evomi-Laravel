@extends('layouts.app')

@section('title', evomi_l('Kuis | Evomi', 'Quiz | Evomi'))

@section('content')
@php
    $questions = $questions ?? [];
    $results = $results ?? [];
    $resultProducts = $resultProducts ?? [];
@endphp

<div
    class="w-full bg-white flex flex-col items-center font-nohemi transition-colors duration-500"
    x-data="evomiKuis(@js($questions), @js($results))"
    :class="finished ? 'min-h-screen justify-start pt-0 pb-0 px-0' : 'min-h-[60vh] justify-center py-4 md:py-12 md:mb-7 px-4 md:px-6'"
>
    <div
        x-show="!finished"
        x-cloak
        class="w-full max-w-[900px] min-h-[420px] rounded-[24px] flex flex-col overflow-hidden bg-white border-2 border-[#1172BA]/55"
    >
        <template x-if="questions.length === 0">
            <div class="flex-grow px-6 md:px-10 py-16 flex flex-col items-center justify-center text-center bg-white">
                <h2 class="text-xl font-semibold text-gray-900 mb-2">{{ evomi_l('Soal kuis belum tersedia', 'Quiz questions are not available yet') }}</h2>
                <p class="text-sm text-gray-500 max-w-md">{{ evomi_l('Data soal/jawaban diambil dari database. Silakan seed quiz atau tambah soal di admin.', 'Question and answer data is loaded from the database. Please seed the quiz or add questions in admin.') }}</p>
            </div>
        </template>

        <template x-if="questions.length > 0">
            <div class="flex flex-col min-h-[420px]">
                <div
                    class="px-8 md:px-10 py-6 shrink-0 flex flex-col justify-center h-[160px] transition-colors duration-500"
                    :style="{ backgroundColor: accent }"
                >
                    <div class="flex items-center gap-2 mb-1.5">
                        <img src="{{ asset('src/images/kuis/scent-finder-Icon.png') }}" alt="" class="w-4 h-4 md:w-5 md:h-5 object-contain brightness-0 invert opacity-90">
                        <p class="text-[12px] md:text-[13px] text-white font-normal uppercase tracking-wide">{{ evomi_l('Kuis Scent Finder', 'Scent Finder Quiz') }}</p>
                    </div>
                    <h1 class="text-[28px] md:text-[32px] font-semibold text-white tracking-tight">{{ evomi_l('Temukan aromamu', 'Discover your scent') }}</h1>
                    <div class="mt-4 w-full h-1.5 bg-white/30 rounded-full overflow-hidden">
                        <div class="h-full bg-[#A5E194] rounded-full transition-all duration-500 ease-out" :style="{ width: progress + '%' }"></div>
                    </div>
                </div>

                <div class="flex-grow px-6 md:px-10 py-6 md:py-8 flex flex-col justify-center bg-white">
                    <div class="flex flex-col h-full justify-between">
                        <h2 class="text-[18px] md:text-[22px] font-semibold leading-snug transition-colors duration-500" :style="{ color: accent }" x-text="currentQuestion?.text"></h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-6">
                            <template x-for="option in (currentQuestion?.options || [])" :key="option.id">
                                <button
                                    type="button"
                                    class="bg-white hover:bg-sky-50 text-[13px] md:text-[15px] font-medium p-3.5 md:p-4 rounded-[16px] flex justify-between items-center gap-3 border-2 border-[#1172BA]/45 hover:border-[#1172BA]/75 active:scale-[0.98] text-left transition-all"
                                    @click="answer(option)"
                                >
                                    <span x-text="option.text"></span>
                                    <svg class="w-4 h-4 text-[#1172BA] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <div x-show="finished && result" x-cloak class="w-full flex flex-col items-center">
        <div class="mb-0 w-full max-w-7xl flex justify-center px-4 md:px-[5px] md:mt-[15px]">
            <div
                class="relative w-full min-h-[280px] h-auto md:h-[320px] rounded-[20px] overflow-hidden flex flex-col md:flex-row shadow-lg transition-colors duration-500 py-6 md:py-0"
                :style="{ backgroundColor: result?.color || '#1172BA' }"
            >
                <div class="relative z-20 w-full md:w-1/2 flex flex-col justify-center pl-5 sm:pl-6 md:pl-10 pr-4 pt-2 md:pt-[10px] pb-4 md:pb-0">
                    <h1 class="font-nohemi text-[22px] sm:text-[26px] md:text-[34px] font-semibold text-white leading-tight mb-3 max-w-[70%] md:max-w-none" x-text="result?.title"></h1>
                    <p class="font-sans text-[13px] md:text-[16px] font-normal text-white leading-relaxed mb-5 md:mb-6 opacity-90 max-w-[65%] md:max-w-sm" x-text="result?.description"></p>

                    <div class="flex flex-wrap gap-2.5 relative z-30">
                        <button
                            type="button"
                            @click="scrollToDetail()"
                            class="font-nohemi flex items-center gap-2 bg-white text-[12px] md:text-[14px] font-semibold py-2.5 px-4 md:py-[10px] md:px-[20px] rounded-full transition-transform active:scale-95 shadow-sm"
                            :style="{ color: result?.color || '#1172BA' }"
                        >
                            {{ evomi_l('Lihat Produk', 'View Product') }}
                            <svg width="10" height="6" viewBox="0 0 12 8" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path d="M1 1L6 6L11 1" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                        </button>

                        <button
                            type="button"
                            @click="restart()"
                            class="font-nohemi flex items-center gap-2 border border-white text-white text-[12px] md:text-[14px] font-semibold py-2.5 px-4 md:py-[10px] md:px-[20px] rounded-full transition-all hover:bg-white/10 active:scale-95"
                            :style="{ backgroundColor: result?.color || '#1172BA' }"
                        >
                            {{ evomi_l('Ulangi Kuis', 'Retake Quiz') }}
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M23 4v6h-6M1 20v-6h6M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="absolute top-0 right-0 h-full w-[45%] md:w-[400px] pointer-events-none overflow-hidden md:overflow-visible">
                    <div class="absolute inset-0 z-0 flex items-end justify-end">
                        <img
                            x-show="result?.bg_image"
                            :src="result?.bg_image"
                            alt=""
                            class="object-contain opacity-100 brightness-100 scale-90 md:scale-100 h-auto max-w-none"
                            :class="hasCustomBgWidth ? 'w-[var(--qr-bg-w-m)] md:w-[var(--qr-bg-w-d)]' : 'w-[220px] md:w-[320px]'"
                            :style="resultImageStyle('bg')"
                        >
                    </div>
                    <div class="absolute inset-0 z-10 flex items-end justify-end">
                        <img
                            x-show="result?.product_image"
                            :src="result?.product_image"
                            alt=""
                            class="object-contain origin-bottom-right transition-transform duration-500 opacity-100 brightness-100 h-auto max-w-none"
                            :class="hasCustomProductWidth ? 'w-[var(--qr-product-w-m)] md:w-[var(--qr-product-w-d)]' : 'w-[200px] sm:w-[240px] md:w-[340px] scale-[1.15] md:scale-[1.45]'"
                            :style="resultImageStyle('product')"
                        >
                    </div>
                </div>
            </div>
        </div>

        <div x-ref="productDetail" class="w-full">
            @foreach ($resultProducts as $pid => $bundle)
                <div
                    x-show="String(result?.forced_product_id || result?.product_id || '') === '{{ $pid }}'"
                    x-cloak
                    class="bg-white flex flex-col justify-center items-center w-full overflow-visible relative z-0"
                >
                    @include('belanja.detail', array_merge($bundle, [
                        'showDivider' => false,
                        'applyTheme' => false,
                    ]))
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
