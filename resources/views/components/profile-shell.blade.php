@php
    $activeMenu = $activeMenu ?? 'settings';
    $menuItems = [
        ['key' => 'settings', 'href' => route('profile.index'), 'label' => 'Pengaturan Profil', 'badge' => null, 'color' => '#1172BA'],
        ['key' => 'chat', 'href' => route('profile.chat'), 'label' => 'Pesan Anda', 'badge' => 'unread', 'color' => '#0EA5E9'],
        ['key' => 'cart', 'href' => route('profile.cart'), 'label' => 'Keranjang Belanja', 'badge' => 'cart', 'color' => '#1172BA'],
        ['key' => 'history', 'href' => route('profile.history'), 'label' => 'Riwayat Belanja', 'badge' => 'history', 'color' => '#5EA14A'],
        ['key' => 'wishlist', 'href' => route('profile.wishlist'), 'label' => 'Wishlist', 'badge' => 'wishlist', 'color' => '#DD74A5'],
    ];
@endphp

<div
    class="evomi-profile-shell max-w-7xl mx-auto px-4 py-8 sm:py-12 sm:px-6 lg:px-8 bg-white min-h-[70vh]"
    x-data="evomiProfileShell"
    data-profile-page="1"
>
    <div
        x-show="!ready"
        x-cloak
        class="py-24 flex justify-center"
    >
        <div class="w-8 h-8 border-4 border-gray-200 border-t-[#1172BA] rounded-full animate-spin"></div>
    </div>

    <div x-show="ready" x-cloak class="flex flex-col lg:flex-row gap-6 lg:gap-8">
        <aside class="w-full lg:w-72 shrink-0">
            <div class="bg-white rounded-2xl sm:rounded-3xl border border-gray-100 p-4 sm:p-5 sticky top-6">
                <div class="px-3 sm:px-4 py-2 sm:py-3 mb-3 sm:mb-4">
                    <h2 class="text-xs font-bold text-gray-400 uppercase tracking-widest">Menu Akun</h2>
                    <p class="text-sm text-gray-500 font-light mt-0.5">Kelola aktivitas &amp; akun Anda</p>
                </div>

                <nav class="space-y-1.5">
                    @foreach ($menuItems as $item)
                        @php $isActive = $activeMenu === $item['key']; @endphp
                        <a
                            href="{{ $item['href'] }}"
                            data-soft-nav
                            class="profile-nav-item flex items-center gap-3.5 px-4 py-3.5 text-sm font-medium rounded-2xl transition-all duration-200 {{ $isActive ? 'text-white font-semibold' : 'text-gray-600' }}"
                            style="--profile-item-color: {{ $item['color'] }};{{ $isActive ? ' background-color:'.$item['color'] : '' }}"
                        >
                            @include('partials.profile-icon', ['name' => $item['key'], 'active' => $isActive])
                            <span>{{ $item['label'] }}</span>
                            @if ($item['badge'])
                                <span
                                    class="ml-auto flex h-5 min-w-5 px-1 items-center justify-center rounded-full text-[10px] font-bold {{ $isActive ? 'bg-white text-black' : 'bg-green-500 text-white' }}"
                                    x-show="badgeLabel('{{ $item['badge'] }}')"
                                    x-text="badgeLabel('{{ $item['badge'] }}')"
                                    x-cloak
                                ></span>
                            @endif
                        </a>
                    @endforeach
                </nav>
            </div>
        </aside>

        <div class="flex-1 min-w-0">
            {{ $slot }}
        </div>
    </div>
</div>
