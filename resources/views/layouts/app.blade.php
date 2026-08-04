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
<body class="font-nohemi antialiased bg-white text-[#1172BA] overflow-x-hidden">
    <div class="evomi-site min-h-screen w-full flex flex-col">
        @include('partials.navbar')
        <main id="evomi-main" class="page-shell w-full flex-1 overflow-x-hidden">
            @yield('content')
        </main>
        <div id="evomi-footer-wrap">
            @include('partials.footer')
        </div>
    </div>
</body>
</html>
