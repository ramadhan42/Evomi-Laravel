@extends('layouts.app')

@section('title', 'FAQ | Evomi')

@section('content')
@php $faqGroups = $faqGroups ?? []; @endphp

<div
    class="min-h-0 bg-white py-10 md:py-16 px-4 sm:px-6 md:px-12 lg:px-24 font-nohemi w-full"
    x-data="evomiFaq(@js($faqGroups))"
>
    <div class="max-w-4xl mx-auto text-center">
        <h1 class="text-[28px] sm:text-[32px] md:text-[48px] font-bold text-gray-900 mb-4 md:mb-6">Pusat Bantuan Evomi</h1>
        <div class="relative mt-8 md:mt-10 max-w-lg mx-auto">
            <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M11 18a7 7 0 1 1 0-14 7 7 0 0 1 0 14Z"/></svg>
            <input
                type="search"
                x-model="query"
                placeholder="Cari topik bantuan..."
                class="w-full h-[52px] md:h-[56px] pl-12 pr-4 rounded-full border border-gray-200 outline-none focus:border-[#1172BA] shadow-sm text-sm md:text-base"
            >
        </div>
    </div>

    <div class="max-w-4xl mx-auto grid grid-cols-1 gap-10 md:gap-12 mt-12 md:mt-16">
        <template x-for="group in visibleGroups" :key="group.category">
            <div>
                <div class="flex items-center gap-3 mb-4 md:mb-6">
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-[#E8F4FC] text-[#1172BA]">?</span>
                    <h2 class="text-[18px] md:text-[24px] font-bold text-gray-900" x-text="group.category"></h2>
                </div>
                <div class="bg-white">
                    <template x-for="(item, idx) in group.items" :key="group.category + '-' + idx">
                        <div class="border-b border-gray-200" x-data="{ open: false }">
                            <button type="button" class="flex justify-between items-center w-full py-5 md:py-6 text-left group gap-4" @click="open = !open">
                                <span class="text-[15px] md:text-[18px] font-medium text-gray-800 group-hover:text-[#1172BA]" x-text="item.q"></span>
                                <svg class="w-5 h-5 text-gray-400 shrink-0 transition-transform" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                            </button>
                            <div x-show="open" x-cloak x-transition.opacity.duration.200ms>
                                <p class="pb-6 text-[14px] md:text-[16px] text-gray-600 leading-relaxed" x-text="item.a"></p>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </template>

        <p class="text-center text-gray-500" x-show="visibleGroups.length === 0" x-cloak>Tidak ada hasil untuk pencarian Anda.</p>
    </div>

    <div class="max-w-4xl mx-auto mt-14 md:mt-20 p-6 md:p-8 bg-blue-50 rounded-[28px] md:rounded-[32px] flex flex-col md:flex-row items-center justify-between gap-6">
        <div class="text-center md:text-left">
            <h3 class="text-lg md:text-xl font-bold text-gray-900 mb-2">Masih butuh bantuan?</h3>
            <p class="text-gray-600 text-sm md:text-base">Tim support Evomi siap membantu Anda melalui email.</p>
        </div>
        <a href="{{ route('kontak') }}" data-soft-nav data-open-kontak class="inline-flex items-center gap-2 bg-[#1172BA] text-white px-7 md:px-8 py-3.5 md:py-4 rounded-full font-bold hover:bg-[#0e609d] transition-colors">
            Hubungi Kami
        </a>
    </div>
</div>
@endsection
