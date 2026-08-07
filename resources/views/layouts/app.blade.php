<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Temukan keharuman eksklusif Evomi yang mencerminkan kepribadian Anda.">
    <title>@yield('title', 'Evomi | Premium Fragrance & Perfume')</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('favicon.png') }}">
    <script>
        (function () {
            try {
                var key = 'evomi_locale';
                var v = localStorage.getItem(key);
                if (v !== 'en' && v !== 'id') {
                    var m = document.cookie.match(/(?:^|;\s*)evomi_locale=(en|id)/);
                    v = m ? m[1] : 'id';
                    localStorage.setItem(key, v);
                }
                document.documentElement.lang = v;
                var secure = location.protocol === 'https:' ? '; Secure' : '';
                document.cookie = key + '=' + v + '; Path=/; Max-Age=31536000; SameSite=Lax' + secure;
            } catch (e) {}
        })();
    </script>
    {{-- Paint loader before Vite CSS so first paint never flashes the page --}}
    <style>
        html.evomi-loading, html.evomi-loading body { overflow: hidden !important; }
        #evomi-loader {
            position: fixed; inset: 0; z-index: 9999;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            padding: 0 1.5rem;
            background:
                radial-gradient(ellipse 80% 60% at 50% 35%, #1a8fd4 0%, transparent 55%),
                linear-gradient(165deg, #0a5f9e 0%, #1172BA 42%, #0d6aad 100%);
            transition: opacity 0.5s ease-out;
        }
        #evomi-loader.is-fading { opacity: 0; pointer-events: none; }
        #evomi-loader.is-hidden { display: none; }
    </style>
    @php
        $route = request()->route()?->getName();
        $path = trim(request()->path(), '/');
        $skipFullLoader = in_array($route, ['profile.history.show', 'artikel.show', 'checkout'], true)
            || (bool) preg_match('#^artikel/[^/]+$#', $path)
            || (bool) preg_match('#^profile/history/[^/]+$#', $path)
            || $path === 'checkout';
        $surfaceBlue = in_array($route, ['beranda', 'belanja', 'artikel', 'artikel.show', 'login', 'register'], true)
            || $path === 'artikel'
            || str_starts_with($path, 'artikel/');
        $authMode = in_array($route, ['login', 'register'], true);
        $themeAccent = $themeAccent ?? '#1172BA';
        $footerSeamless = $route === 'belanja.show';
    @endphp
    @unless ($skipFullLoader)
        <script>document.documentElement.classList.add('evomi-loading');</script>
    @else
        <script>document.documentElement.classList.remove('evomi-loading');</script>
    @endunless
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body
    class="font-nohemi antialiased bg-white text-[#1172BA] overflow-x-hidden{{ $surfaceBlue ? ' evomi-surface-blue' : '' }}{{ $authMode ? ' evomi-auth-mode' : '' }}{{ $footerSeamless ? ' evomi-detail-seamless' : '' }}"
    style="--evomi-theme: {{ $themeAccent }}"
>
    @unless ($skipFullLoader)
        @include('partials.evomi-loader')
    @endunless
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
