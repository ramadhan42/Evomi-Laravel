<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Temukan keharuman eksklusif Evomi yang mencerminkan kepribadian Anda.">
    <title>@yield('title', 'Evomi | Premium Fragrance & Perfume')</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
@php
    $route = request()->route()?->getName();
    $surfaceBlue = in_array($route, ['beranda', 'belanja', 'artikel', 'artikel.show', 'login', 'register'], true);
    $authMode = in_array($route, ['login', 'register'], true);
    $themeAccent = $themeAccent ?? '#1172BA';
    $footerSeamless = $route === 'belanja.show';
@endphp
<body
    class="font-nohemi antialiased bg-white text-[#1172BA] overflow-x-hidden{{ $surfaceBlue ? ' evomi-surface-blue' : '' }}{{ $authMode ? ' evomi-auth-mode' : '' }}{{ $footerSeamless ? ' evomi-detail-seamless' : '' }}"
    style="--evomi-theme: {{ $themeAccent }}"
>
    <div class="evomi-site min-h-screen w-full flex flex-col">
        @include('partials.navbar')
        <main id="evomi-main" class="page-shell w-full flex-1{{ $footerSeamless ? ' overflow-visible' : ' overflow-x-hidden' }}">
            @yield('content')
        </main>
        <div
            id="evomi-footer-wrap"
            class="relative z-10{{ $footerSeamless ? ' belanja-detail-footer-seam' : '' }}"
            style="background-color: {{ $themeAccent }}"
        >
            @include('partials.footer')
        </div>
    </div>
</body>
</html>
