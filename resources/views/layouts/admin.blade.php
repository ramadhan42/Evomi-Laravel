<!DOCTYPE html>
<html lang="id" x-data="evomiAdminGate" :lang="locale" data-admin-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Evomi Admin Dashboard">
    <title>@yield('title', 'Dashboard | Evomi')</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('favicon.png') }}">
    <script>
        (function () {
            try {
                localStorage.setItem('evomi-admin-theme', 'light');
                document.documentElement.setAttribute('data-admin-theme', 'light');
                var key = 'evomi_locale';
                var v = localStorage.getItem(key);
                if (v !== 'en' && v !== 'id') v = 'id';
                document.documentElement.lang = v;
                var secure = location.protocol === 'https:' ? '; Secure' : '';
                document.cookie = key + '=' + v + '; Path=/; Max-Age=31536000; SameSite=Lax' + secure;
            } catch (e) {}
        })();
    </script>
    @include('partials.evomi-cursor-styles')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-nohemi antialiased evomi-admin-mode overflow-x-hidden bg-[#F8F9FA] text-gray-900">
    <div
        x-show="denied"
        x-cloak
        class="admin-modal-root admin-modal-root--shell flex items-center justify-center"
        role="dialog"
        aria-modal="true"
    >
        <div class="bg-white rounded-2xl p-6 max-w-sm w-full shadow-2xl border border-gray-100 text-center" role="document">
            <div class="flex justify-center mb-4 text-red-500">
                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            </div>
            <h2 class="text-lg font-bold text-gray-900 mb-2" x-text="t('auth','denied_title')"></h2>
            <p class="text-sm text-gray-600 mb-6" x-text="deniedMessage"></p>
            <a href="{{ route('login') }}" class="block w-full py-2.5 rounded-xl text-sm font-semibold text-white bg-gray-900 hover:bg-gray-800 transition-colors shadow-sm" x-text="t('auth','back_login')"></a>
        </div>
    </div>

    <div
        class="admin-shell min-h-screen flex"
        data-admin-theme="light"
        x-show="ready"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
    >
        @include('partials.admin-sidebar')
        <main class="flex-1 ml-0 md:ml-64 p-4 sm:p-6 md:p-8 min-w-0">
            <div class="max-w-7xl mx-auto">
                <div id="admin-page-loading" class="admin-page-loading" aria-hidden="true">
                    <span class="admin-page-spinner"></span>
                </div>
                <div
                    id="admin-page"
                    class="admin-page-panel"
                    data-admin-page="{{ $activeMenu ?? 'dashboard' }}"
                >
                    @yield('content')
                </div>
            </div>
        </main>
        @include('partials.admin-toast')
    </div>

    <div
        x-show="!ready && !denied"
        x-cloak
        class="fixed inset-0 z-[190] flex flex-col items-center justify-center bg-[#F8F9FA]"
    >
        <div class="w-10 h-10 border-4 border-gray-200 border-t-gray-900 rounded-full animate-spin mb-4"></div>
        <p class="text-sm font-medium text-gray-500" x-text="t('auth','verifying')"></p>
    </div>
</body>
</html>
