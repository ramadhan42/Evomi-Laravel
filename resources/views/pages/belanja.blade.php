@extends('layouts.app')

@section('title', 'Belanja | Evomi')

@section('content')
    @php
        $cms = \App\Support\CmsStorefront::forPage('belanja');
        $charPurpose = $cms->image('images', 'deco_purpose', asset('src/images/belanja/deco/char-purpose.png'));
        $charPeaceful = $cms->image('images', 'deco_peaceful', asset('src/images/belanja/deco/char-peaceful.png'));
        $charRebel = $cms->image('images', 'deco_rebel', asset('src/images/belanja/deco/char-rebel.png'));
        $charSweet = $cms->image('images', 'deco_sweet', asset('src/images/belanja/deco/char-sweet.png'));
    @endphp

    {{-- Figma BELANJA content (header/footer tetap dari layout) --}}
    <div class="belanja-page relative w-full min-h-0 flex flex-col items-center bg-[#f6f6f6] overflow-visible">
        {{-- Mascots: Figma 1433:748 — locked to 1322 artboard so edges match design --}}
        <div class="belanja-page__deco pointer-events-none absolute inset-y-0 left-1/2 z-[5] hidden w-full max-w-[1322px] -translate-x-1/2 lg:block" aria-hidden="true">
            <div class="belanja-deco belanja-deco--purpose">
                <img src="{{ $charPurpose }}" alt="" width="134" height="191">
            </div>
            <div class="belanja-deco belanja-deco--peaceful">
                <img src="{{ $charPeaceful }}" alt="" width="166" height="225">
            </div>
            <div class="belanja-deco belanja-deco--rebel">
                <img src="{{ $charRebel }}" alt="" width="157" height="225">
            </div>
            <div class="belanja-deco belanja-deco--sweet">
                <img src="{{ $charSweet }}" alt="" width="143" height="215">
            </div>
        </div>

        @include('belanja.hero')
        @include('belanja.products')
    </div>
@endsection
