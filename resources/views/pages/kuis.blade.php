@extends('layouts.app')

@section('title', 'Kuis | Evomi')

@section('content')
@php
    $questions = $questions ?? [];
    $results = $results ?? [];
@endphp

<div
    class="w-full min-h-[60vh] bg-white flex flex-col items-center justify-center py-6 md:py-12 px-4 md:px-6 font-nohemi"
    x-data="evomiKuis(@js($questions), @js($results))"
>
    <template x-if="!finished">
        <div class="w-full max-w-[900px] min-h-[420px] rounded-[24px] flex flex-col overflow-hidden bg-white border-2 border-[#1172BA]/55 shadow-sm">
            <div class="px-6 md:px-10 py-6 shrink-0" :style="{ backgroundColor: accent }">
                <div class="flex items-center gap-2 mb-1.5">
                    <span class="w-4 h-4 md:w-5 md:h-5 rounded-full bg-white/30"></span>
                    <p class="text-[12px] md:text-[13px] text-white uppercase tracking-wide">Kuis Scent Finder</p>
                </div>
                <h1 class="text-[24px] md:text-[32px] font-semibold text-white tracking-tight">Temukan aromamu</h1>
                <div class="mt-4 w-full h-1.5 bg-white/30 rounded-full overflow-hidden">
                    <div class="h-full bg-[#A5E194] rounded-full transition-all duration-500 ease-out" :style="{ width: progress + '%' }"></div>
                </div>
            </div>

            <div class="flex-grow px-5 md:px-10 py-6 md:py-8 bg-white">
                <h2 class="text-[17px] md:text-[22px] font-semibold leading-snug" :style="{ color: accent }" x-text="currentQuestion?.text"></h2>
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
    </template>

    <template x-if="finished && result">
        <div class="w-full max-w-7xl">
            <div class="relative w-full min-h-[280px] md:min-h-[320px] rounded-[20px] overflow-hidden flex flex-col md:flex-row shadow-lg py-6 md:py-8" :style="{ backgroundColor: result.color }">
                <div class="relative z-20 w-full md:w-1/2 flex flex-col justify-center px-5 md:pl-10 md:pr-4">
                    <h1 class="font-nohemi text-[22px] sm:text-[26px] md:text-[34px] font-semibold text-white leading-tight mb-3" x-text="result.title"></h1>
                    <p class="text-[13px] md:text-[16px] text-white/90 mb-5 md:mb-6 max-w-sm" x-text="result.description"></p>
                    <div class="flex flex-wrap gap-2.5">
                        <a :href="'/belanja/' + result.product_id" data-soft-nav class="bg-white rounded-full py-2.5 px-4 font-semibold text-[12px] md:text-[14px]" :style="{ color: result.color }">Lihat Produk →</a>
                        <button type="button" @click="restart()" class="border border-white text-white rounded-full py-2.5 px-4 font-semibold text-[12px] md:text-[14px]">Ulangi Kuis ↻</button>
                    </div>
                </div>
                <div class="relative md:absolute md:top-0 md:right-0 h-40 md:h-full w-full md:w-[45%] mt-4 md:mt-0 flex items-center justify-center">
                    <div class="w-40 h-40 md:w-56 md:h-56 rounded-full bg-white/15 backdrop-blur-sm"></div>
                </div>
            </div>
        </div>
    </template>
</div>
@endsection
