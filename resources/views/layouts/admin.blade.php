<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Evomi Admin Dashboard">
    <title>@yield('title', 'Dashboard | Evomi')</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-nohemi antialiased evomi-admin-mode bg-[#F8F9FA] text-gray-900 overflow-x-hidden" x-data="evomiAdminGate">
    <div
        x-show="denied"
        x-cloak
        class="fixed inset-0 z-[200] flex items-center justify-center bg-[#0b0d12]/70 backdrop-blur-sm p-4"
    >
        <div class="bg-white rounded-2xl shadow-xl max-w-sm w-full p-6 text-center space-y-3">
            <div class="mx-auto h-12 w-12 rounded-full bg-amber-50 text-amber-600 flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            </div>
            <h2 class="text-lg font-bold text-gray-900">Akses Dibatasi</h2>
            <p class="text-sm text-gray-500" x-text="deniedMessage"></p>
        </div>
    </div>

    <div
        class="admin-shell min-h-screen flex"
        x-show="ready"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
    >
        @include('partials.admin-sidebar')
        <main class="flex-1 ml-0 md:ml-64 p-4 sm:p-6 md:p-8 min-w-0">
            <div class="max-w-7xl mx-auto">
                @yield('content')
            </div>
        </main>
    </div>

    <div
        x-show="!ready && !denied"
        x-cloak
        class="fixed inset-0 z-[190] flex items-center justify-center bg-[#F8F9FA]"
    >
        <div class="w-8 h-8 border-4 border-gray-200 border-t-gray-900 rounded-full animate-spin"></div>
    </div>
</body>
</html>
