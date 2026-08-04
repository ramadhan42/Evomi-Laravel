@php
    $activeMenu = $activeMenu ?? 'dashboard';
    $menuItems = [
        ['key' => 'dashboard', 'path' => route('dashboard'), 'label' => 'Dashboard', 'icon' => 'dashboard'],
        ['key' => 'cms', 'path' => route('dashboard.stub', 'cms'), 'label' => 'CMS', 'icon' => 'cms'],
        ['key' => 'products', 'path' => route('dashboard.stub', 'products'), 'label' => 'Produk', 'icon' => 'products'],
        ['key' => 'articles', 'path' => route('dashboard.stub', 'articles'), 'label' => 'Artikel', 'icon' => 'articles'],
        ['key' => 'promos', 'path' => route('dashboard.stub', 'promos'), 'label' => 'Promo', 'icon' => 'promos'],
        ['key' => 'payment', 'path' => route('dashboard.stub', 'payment'), 'label' => 'Pembayaran', 'icon' => 'payment'],
        ['key' => 'kurirs', 'path' => route('dashboard.stub', 'kurirs'), 'label' => 'Kurir', 'icon' => 'kurirs'],
        ['key' => 'quiz', 'path' => route('dashboard.stub', 'quiz'), 'label' => 'Kuis', 'icon' => 'quiz'],
        ['key' => 'orders', 'path' => route('dashboard.stub', 'orders'), 'label' => 'Pesanan', 'icon' => 'orders'],
        ['key' => 'trackings', 'path' => route('dashboard.stub', 'trackings'), 'label' => 'Pelacakan', 'icon' => 'trackings'],
        ['key' => 'messages', 'path' => route('dashboard.stub', 'messages'), 'label' => 'Pesan', 'icon' => 'messages'],
        ['key' => 'cart', 'path' => route('dashboard.stub', 'cart'), 'label' => 'Keranjang', 'icon' => 'cart'],
        ['key' => 'wishlist', 'path' => route('dashboard.stub', 'wishlist'), 'label' => 'Wishlist', 'icon' => 'wishlist'],
        ['key' => 'users', 'path' => route('dashboard.stub', 'users'), 'label' => 'Semua User', 'icon' => 'users'],
        ['key' => 'subscribers', 'path' => route('dashboard.stub', 'subscribers'), 'label' => 'Subscriber', 'icon' => 'subscribers'],
        ['key' => 'profile', 'path' => route('dashboard.stub', 'profile'), 'label' => 'Profil Admin', 'icon' => 'profile'],
    ];
@endphp

<aside class="admin-sidebar hidden md:flex w-64 h-screen bg-white/80 backdrop-blur-xl border-r border-gray-100 flex-col fixed left-0 top-0 overflow-hidden z-40">
    <div class="h-20 flex items-center justify-between px-5 border-b border-gray-100">
        <a href="{{ route('dashboard') }}" class="text-xl font-bold tracking-wider text-gray-900">EVOMI</a>
        <a href="{{ route('beranda') }}" class="text-[10px] uppercase tracking-wider text-gray-400 hover:text-gray-700 transition-colors" title="Ke beranda">Site</a>
    </div>

    <nav class="flex-1 overflow-y-auto py-6 px-4 space-y-1 admin-sidebar-scroll">
        @foreach ($menuItems as $item)
            @php $isActive = $activeMenu === $item['key']; @endphp
            <a
                href="{{ $item['path'] }}"
                class="admin-nav-item flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 {{ $isActive ? 'admin-nav-active bg-gray-900 text-white shadow-md shadow-gray-900/10' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-700' }}"
            >
                @include('partials.admin-icon', ['name' => $item['icon'], 'active' => $isActive])
                <span class="text-sm font-medium">{{ $item['label'] }}</span>
            </a>
        @endforeach
    </nav>

    <div class="p-4 border-t border-gray-100">
        <button
            type="button"
            class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-red-500 hover:bg-red-50 transition-colors text-sm font-medium"
            @click="logout()"
        >
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            Keluar
        </button>
    </div>
</aside>

{{-- Mobile top bar --}}
<div class="md:hidden sticky top-0 z-30 bg-white/90 backdrop-blur border-b border-gray-100 px-4 py-3 flex items-center justify-between">
    <a href="{{ route('dashboard') }}" class="font-bold tracking-wider text-gray-900">EVOMI</a>
    <button type="button" class="text-sm text-red-500 font-medium" @click="logout()">Keluar</button>
</div>

<div class="md:hidden px-4 py-3 overflow-x-auto flex gap-2 border-b border-gray-100 bg-white">
    @foreach ($menuItems as $item)
        @php $isActive = $activeMenu === $item['key']; @endphp
        <a
            href="{{ $item['path'] }}"
            class="shrink-0 px-3 py-1.5 rounded-full text-xs font-medium {{ $isActive ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-600' }}"
        >{{ $item['label'] }}</a>
    @endforeach
</div>
