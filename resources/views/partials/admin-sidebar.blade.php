@php
    $activeMenu = $activeMenu ?? 'dashboard';
    $menuItems = [
        ['key' => 'dashboard', 'path' => route('dashboard'), 'label' => 'Dashboard', 'icon' => 'dashboard'],
        ['key' => 'cms', 'path' => route('dashboard.page', 'cms'), 'label' => 'CMS', 'icon' => 'cms'],
        ['key' => 'seo', 'path' => route('dashboard.page', 'seo'), 'label' => 'SEO', 'icon' => 'seo'],
        ['key' => 'products', 'path' => route('dashboard.page', 'products'), 'label' => 'Produk', 'icon' => 'products'],
        ['key' => 'articles', 'path' => route('dashboard.page', 'articles'), 'label' => 'Artikel', 'icon' => 'articles'],
        ['key' => 'promos', 'path' => route('dashboard.page', 'promos'), 'label' => 'Promo', 'icon' => 'promos'],
        ['key' => 'payment', 'path' => route('dashboard.page', 'payment'), 'label' => 'Pembayaran', 'icon' => 'payment'],
        ['key' => 'kurirs', 'path' => route('dashboard.page', 'kurirs'), 'label' => 'Kurir', 'icon' => 'kurirs'],
        ['key' => 'quiz', 'path' => route('dashboard.page', 'quiz'), 'label' => 'Kuis', 'icon' => 'quiz'],
        ['key' => 'orders', 'path' => route('dashboard.page', 'orders'), 'label' => 'Pesanan', 'icon' => 'orders'],
        ['key' => 'trackings', 'path' => route('dashboard.page', 'trackings'), 'label' => 'Pelacakan', 'icon' => 'trackings'],
        ['key' => 'messages', 'path' => route('dashboard.page', 'messages'), 'label' => 'Pesan', 'icon' => 'messages'],
        ['key' => 'cart', 'path' => route('dashboard.page', 'cart'), 'label' => 'Keranjang', 'icon' => 'cart'],
        ['key' => 'wishlist', 'path' => route('dashboard.page', 'wishlist'), 'label' => 'Wishlist', 'icon' => 'wishlist'],
        ['key' => 'users', 'path' => route('dashboard.page', 'users'), 'label' => 'Semua User', 'icon' => 'users'],
        ['key' => 'traffic', 'path' => route('dashboard.page', 'traffic'), 'label' => 'Traffic', 'icon' => 'traffic'],
        ['key' => 'subscribers', 'path' => route('dashboard.page', 'subscribers'), 'label' => 'Subscriber', 'icon' => 'subscribers'],
        ['key' => 'profile', 'path' => route('dashboard.page', 'profile'), 'label' => 'Profil Admin', 'icon' => 'profile'],
    ];
@endphp

<aside class="admin-sidebar hidden md:flex w-64 h-screen bg-white/80 backdrop-blur-xl border-r border-gray-100 flex-col fixed left-0 top-0 overflow-hidden z-40">
    <div class="admin-brand-bar shrink-0 px-4 border-b border-gray-100">
        <a href="{{ route('dashboard') }}" data-admin-nav-link class="admin-brand group">
            <span class="admin-brand-mark" aria-hidden="true">
                <span class="admin-brand-mark-letter">E</span>
            </span>
            <span class="admin-brand-copy">
                <span class="admin-brand-name">EVOMI</span>
                <span class="admin-brand-sub" x-text="t('sidebar', 'admin_label', 'Admin Panel', 'Admin Panel')">Admin Panel</span>
            </span>
        </a>
        @include('partials.language-switcher', ['variant' => 'dark', 'size' => 'admin'])
    </div>

    <nav class="flex-1 overflow-y-auto py-6 px-4 space-y-1 admin-sidebar-scroll" data-admin-nav-desktop>
        @foreach ($menuItems as $item)
            @php $isActive = $activeMenu === $item['key']; @endphp
            <a
                href="{{ $item['path'] }}"
                data-admin-nav="{{ $item['key'] }}"
                @if ($isActive) aria-current="page" @endif
                class="admin-nav-item flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 {{ $isActive ? 'admin-nav-active bg-gray-900 text-white shadow-md shadow-gray-900/10' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-700' }}"
            >
                @include('partials.admin-icon', ['name' => $item['icon'], 'active' => $isActive])
                <span class="text-sm font-medium truncate" x-text="t('sidebar', '{{ $item['key'] }}', '{{ $item['label'] }}')">{{ $item['label'] }}</span>
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
            <span x-text="t('sidebar','logout','Keluar')">Keluar</span>
        </button>
    </div>
</aside>

<div class="md:hidden sticky top-0 z-30 bg-white/90 backdrop-blur border-b border-gray-100 px-4 py-3 flex items-center justify-between gap-3">
    <a href="{{ route('dashboard') }}" data-admin-nav-link class="admin-brand admin-brand--compact group">
        <span class="admin-brand-mark" aria-hidden="true">
            <span class="admin-brand-mark-letter">E</span>
        </span>
        <span class="admin-brand-copy">
            <span class="admin-brand-name">EVOMI</span>
            <span class="admin-brand-sub" x-text="t('sidebar', 'admin_label', 'Admin Panel', 'Admin Panel')">Admin Panel</span>
        </span>
    </a>
    <div class="flex items-center gap-2.5 shrink-0">
        @include('partials.language-switcher', ['variant' => 'dark', 'size' => 'admin'])
        <button type="button" class="text-sm text-red-500 font-medium" @click="logout()" x-text="t('sidebar','logout','Keluar')">Keluar</button>
    </div>
</div>

<div class="md:hidden px-4 py-3 overflow-x-auto flex gap-2 border-b border-gray-100 bg-white" data-admin-nav-mobile>
    @foreach ($menuItems as $item)
        @php $isActive = $activeMenu === $item['key']; @endphp
        <a
            href="{{ $item['path'] }}"
            data-admin-nav="{{ $item['key'] }}"
            @if ($isActive) aria-current="page" @endif
            class="shrink-0 px-3 py-1.5 rounded-full text-xs font-medium transition-colors duration-200 {{ $isActive ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-600' }}"
            x-text="t('sidebar', '{{ $item['key'] }}', '{{ $item['label'] }}')"
        >{{ $item['label'] }}</a>
    @endforeach
</div>
