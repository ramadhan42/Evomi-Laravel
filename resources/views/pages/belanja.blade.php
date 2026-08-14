@extends('layouts.app')

@section('title', 'Belanja | Evomi')

@section('content')
    @php
        $cms = \App\Support\CmsStorefront::forPage('belanja');
        // Prefer local HiDPI deco PNGs; only use CMS when it is an uploaded storage file.
        $decoSrc = static function (string $cmsKey, string $file) use ($cms): string {
            $fallback = asset('src/images/belanja/deco/'.$file);
            $fromCms = trim($cms->image('images', $cmsKey, ''));
            $isUpload = $fromCms !== ''
                && ! str_contains($fromCms, '/src/images/belanja/deco/')
                && ! str_contains($fromCms, 'belanja/deco/char-');
            if ($isUpload) {
                return $fromCms;
            }
            $path = public_path('src/images/belanja/deco/'.$file);
            $v = @filemtime($path) ?: time();

            return $fallback.(str_contains($fallback, '?') ? '&' : '?').'v='.$v;
        };
        $charPurpose = $decoSrc('deco_purpose', 'char-purpose.png');
        $charPeaceful = $decoSrc('deco_peaceful', 'char-peaceful.png');
        $charRebel = $decoSrc('deco_rebel', 'char-rebel.png');
        $charSweet = $decoSrc('deco_sweet', 'char-sweet.png');
        $decoStyle = \App\Support\BelanjaCmsDefaults::decoStyleAttr($cms);
    @endphp

    {{-- Figma BELANJA content (header/footer tetap dari layout) --}}
    <div
        class="belanja-page relative w-full flex-1 flex flex-col items-center justify-center bg-[#f6f6f6] overflow-visible pt-4 pb-8 md:pt-6 md:pb-10"
        style="{{ $decoStyle }}"
    >
        {{-- Mascots: left/right columns; jarak dari CMS (gap vertikal & horizontal) --}}
        <div class="belanja-page__deco pointer-events-none absolute inset-0 z-[5] hidden lg:flex" aria-hidden="true">
            <div class="belanja-page__deco-col belanja-page__deco-col--left" data-belanja-enter="fade">
                <div class="belanja-deco belanja-deco--purpose auth-char-float">
                    <img src="{{ $charPurpose }}" alt="" width="160" height="228" decoding="async" fetchpriority="low">
                </div>
                <div class="belanja-deco belanja-deco--rebel auth-char-float auth-char-float-delay-2">
                    <img src="{{ $charRebel }}" alt="" width="170" height="244" decoding="async" fetchpriority="low">
                </div>
            </div>
            <div class="belanja-page__deco-col belanja-page__deco-col--right" data-belanja-enter="fade">
                <div class="belanja-deco belanja-deco--peaceful auth-char-float auth-char-float-delay-1">
                    <img src="{{ $charPeaceful }}" alt="" width="178" height="242" decoding="async" fetchpriority="low">
                </div>
                <div class="belanja-deco belanja-deco--sweet auth-char-float auth-char-float-delay-3">
                    <img src="{{ $charSweet }}" alt="" width="156" height="234" decoding="async" fetchpriority="low">
                </div>
            </div>
        </div>

        <div class="belanja-page__content relative z-0 w-full flex flex-col items-center">
            @include('belanja.hero')
            @include('belanja.products')
        </div>
    </div>
@endsection
