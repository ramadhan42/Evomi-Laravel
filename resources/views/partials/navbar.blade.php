@php
    $current = request()->route()?->getName();
    $navCms = \App\Support\CmsStorefront::forPage('navbar');
    $navLinks = [
        [
            'href' => route('beranda'),
            'label' => $navCms->get('menu', 'beranda', evomi_l('Beranda', 'Home')),
            'route' => 'beranda',
            'match' => '/',
        ],
        [
            'href' => route('beranda') . '#about',
            'label' => $navCms->get('menu', 'tentang', evomi_l('Tentang', 'About')),
            'route' => null,
            'match' => '#about',
        ],
        [
            'href' => route('belanja'),
            'label' => $navCms->get('menu', 'belanja', evomi_l('Belanja', 'Shop')),
            'route' => 'belanja',
            'match' => '/belanja',
        ],
        [
            'href' => route('artikel'),
            'label' => $navCms->get('menu', 'artikel', evomi_l('Artikel', 'Articles')),
            'route' => 'artikel',
            'match' => '/artikel',
        ],
        [
            'href' => route('kuis'),
            'label' => $navCms->get('menu', 'kuis', evomi_l('Kuis', 'Quiz')),
            'route' => 'kuis',
            'match' => '/kuis',
        ],
    ];
    $navLogin = $navCms->get('menu', 'login', 'Login');
    $navRegister = $navCms->get('menu', 'register', evomi_l('Daftar', 'Sign Up'));
    $navLogout = $navCms->get('menu', 'logout', 'Logout');

    $isNavLinkActive = static function (?string $routeName, ?string $currentRoute): bool {
        if (! $routeName || ! $currentRoute) {
            return false;
        }
        if ($routeName === 'belanja') {
            return str_starts_with($currentRoute, 'belanja') || $currentRoute === 'checkout';
        }
        if ($routeName === 'artikel') {
            return str_starts_with($currentRoute, 'artikel');
        }

        return $currentRoute === $routeName;
    };

    $activeIndex = -1;
    foreach ($navLinks as $i => $link) {
        if ($isNavLinkActive($link['route'] ?? null, $current)) {
            $activeIndex = $i;
            break;
        }
    }
@endphp

@php
    $navAccent = $themeAccent ?? '#1172BA';
@endphp
<header
    id="evomi-header"
    class="fixed inset-x-0 top-0 z-[100] w-full px-2 py-2 md:px-4 md:pt-7 md:pb-4"
    style="background-color: {{ $navAccent }}; --nav-color: {{ $navAccent }}"
    x-data="evomiNavbar({{ $activeIndex }})"
    :class="{ 'is-nav-hidden': isNavHidden }"
>
    <nav class="nav-chrome text-white rounded-[18px] md:rounded-[25px] px-3 py-2 md:px-8 md:py-3 relative w-full max-w-[1280px] mx-auto">
        <div class="flex items-center justify-between gap-3">
            <a
                href="{{ route('beranda') }}"
                class="shrink-0 flex items-center nav-soft"
                data-soft-nav
                data-nav-index="0"
            >
                <img
                    src="{{ asset('src/images/navbar/evomi-logo.png') }}"
                    alt="Evomi"
                    class="object-contain brightness-0 invert w-auto h-5 md:h-10 -translate-y-1 transition-transform duration-500 ease-[cubic-bezier(0.22,1,0.36,1)]"
                >
            </a>

            {{-- Desktop: 5 kolom seperti Next (Beranda · Tentang · Belanja · Artikel · Kuis) --}}
            <div
                class="hidden md:grid grid-cols-5 gap-1 items-center relative"
                x-ref="track"
            >
                <span
                    class="nav-pill-indicator nav-pill-indicator--slide"
                    :style="indicatorStyle"
                    aria-hidden="true"
                ></span>

                @foreach ($navLinks as $i => $link)
                    @php
                        $active = $isNavLinkActive($link['route'] ?? null, $current);
                    @endphp
                    <a
                        href="{{ $link['href'] }}"
                        class="nav-pill relative z-[1] flex justify-center items-center w-full md:min-w-[7.25rem] md:px-5 text-[12px] md:text-[18px] py-2.5 font-normal rounded-full text-center whitespace-nowrap nav-soft {{ $active ? 'is-active text-[var(--nav-color)]' : 'text-white' }}"
                        data-soft-nav
                        data-nav-index="{{ $i }}"
                        data-nav-match="{{ $link['match'] }}"
                    >
                        <span class="relative z-[1]">{{ $link['label'] }}</span>
                    </a>
                @endforeach
            </div>

            <div class="hidden md:flex items-center space-x-2 md:mr-2 shrink-0">
                {{-- Logged in --}}
                <template x-if="isLoggedIn">
                    <div class="flex items-center gap-2">
                        <div
                            class="nav-avatar-wrap relative z-[70]"
                            :class="{ 'is-open': accountMenuOpen }"
                            @click.outside="closeAccountMenu()"
                        >
                            <button
                                type="button"
                                class="nav-avatar relative flex items-center justify-center w-[44px] h-[44px] rounded-full bg-white text-[var(--nav-color)] font-bold text-[17px] border-2 border-white/90 shadow-sm overflow-hidden"
                                aria-label="{{ evomi_l('Menu akun', 'Account menu') }}"
                                :aria-expanded="accountMenuOpen.toString()"
                                aria-haspopup="menu"
                                @click="toggleAccountMenu()"
                            >
                                <span class="nav-avatar-ring" aria-hidden="true"></span>
                                <span class="relative z-[1] h-full w-full rounded-full overflow-hidden flex items-center justify-center bg-white">
                                    <img
                                        x-show="userAvatar"
                                        x-cloak
                                        :key="userAvatar || 'none'"
                                        :src="userAvatar"
                                        alt=""
                                        class="absolute inset-0 h-full w-full object-cover"
                                        x-on:error="userAvatar = null"
                                    >
                                    <span
                                        x-show="!userAvatar"
                                        class="relative z-[1] select-none tracking-tight"
                                        x-text="userInitial"
                                    ></span>
                                </span>
                            </button>

                            <div
                                class="nav-avatar-tooltip"
                                role="menu"
                                aria-label="{{ evomi_l('Menu akun', 'Account menu') }}"
                            >
                                <div class="relative z-[1] space-y-2.5 text-left">
                                    <div class="flex items-center gap-2.5 px-1">
                                        <span class="relative flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[var(--nav-color)] text-white text-sm font-bold overflow-hidden">
                                            <img
                                                x-show="userAvatar"
                                                x-cloak
                                                :key="'tip-' + (userAvatar || 'none')"
                                                :src="userAvatar"
                                                alt=""
                                                class="absolute inset-0 h-full w-full object-cover"
                                                x-on:error="userAvatar = null"
                                            >
                                            <span x-show="!userAvatar" x-text="userInitial"></span>
                                        </span>
                                        <div class="min-w-0">
                                            <p class="text-[12px] font-bold text-gray-900 leading-tight">{{ evomi_l('Akun Saya', 'My Account') }}</p>
                                            <p class="text-[11px] text-gray-500 truncate mt-0.5" x-text="userEmail"></p>
                                        </div>
                                    </div>

                                    <div class="h-px bg-gray-100"></div>

                                    <template x-if="isAdmin">
                                        <a
                                            href="{{ route('dashboard') }}"
                                            role="menuitem"
                                            class="nav-account-item"
                                            @click="closeAccountMenu()"
                                        >
                                            <div class="min-w-0">
                                                <p class="text-[11px] font-semibold text-gray-800">{{ evomi_l('Dashboard Admin', 'Admin Dashboard') }}</p>
                                                <p class="text-[10px] text-gray-500 mt-0.5">{{ evomi_l('Kelola toko & CMS Evomi', 'Manage Evomi store & CMS') }}</p>
                                            </div>
                                        </a>
                                    </template>

                                    <a href="{{ route('profile.index') }}" role="menuitem" class="nav-account-item" data-soft-nav @click="closeAccountMenu()">
                                        <div class="min-w-0">
                                            <p class="text-[11px] font-semibold text-gray-800">{{ evomi_l('Pengaturan Profil', 'Profile Settings') }}</p>
                                            <p class="text-[10px] text-gray-500 mt-0.5">{{ evomi_l('Data akun & pengaturan', 'Account data & settings') }}</p>
                                        </div>
                                    </a>

                                    <a href="{{ route('profile.chat') }}" role="menuitem" class="nav-account-item" data-soft-nav @click="closeAccountMenu()">
                                        <div class="min-w-0">
                                            <p class="text-[11px] font-semibold text-gray-800">{{ evomi_l('Pesan belum dibaca', 'Unread messages') }}</p>
                                            <p class="text-[10px] text-gray-500 mt-0.5" x-text="badgeDesc('unread', $L('Ada balasan baru dari admin', 'New reply from admin'), $L('Tidak ada pesan baru', 'No new messages'))"></p>
                                        </div>
                                        <span
                                            class="shrink-0 flex h-6 min-w-6 px-1.5 items-center justify-center rounded-full text-[11px] font-bold"
                                            :class="badges.unread > 0 ? 'bg-green-500 text-white' : 'bg-gray-100 text-gray-400'"
                                            x-text="badgeLabel('unread') || '0'"
                                        ></span>
                                    </a>

                                    <a href="{{ route('profile.cart') }}" role="menuitem" class="nav-account-item" data-soft-nav @click="closeAccountMenu()">
                                        <div class="min-w-0">
                                            <p class="text-[11px] font-semibold text-gray-800">{{ evomi_l('Keranjang belanja', 'Shopping cart') }}</p>
                                            <p class="text-[10px] text-gray-500 mt-0.5" x-text="badgeDesc('cart', $L('Produk siap checkout', 'Ready to checkout'), $L('Keranjang masih kosong', 'Cart is empty'))"></p>
                                        </div>
                                        <span
                                            class="shrink-0 flex h-6 min-w-6 px-1.5 items-center justify-center rounded-full text-[11px] font-bold"
                                            :class="badges.cart > 0 ? 'bg-green-500 text-white' : 'bg-gray-100 text-gray-400'"
                                            x-text="badgeLabel('cart') || '0'"
                                        ></span>
                                    </a>

                                    <a href="{{ route('profile.history') }}" role="menuitem" class="nav-account-item" data-soft-nav @click="closeAccountMenu()">
                                        <div class="min-w-0">
                                            <p class="text-[11px] font-semibold text-gray-800">{{ evomi_l('Riwayat belanja', 'Order history') }}</p>
                                            <p class="text-[10px] text-gray-500 mt-0.5" x-text="badgeDesc('history', $L('Pesanan yang pernah dibuat', 'Orders you have placed'), $L('Belum ada riwayat', 'No order history yet'))"></p>
                                        </div>
                                        <span
                                            class="shrink-0 flex h-6 min-w-6 px-1.5 items-center justify-center rounded-full text-[11px] font-bold"
                                            :class="badges.history > 0 ? 'bg-green-500 text-white' : 'bg-gray-100 text-gray-400'"
                                            x-text="badgeLabel('history') || '0'"
                                        ></span>
                                    </a>

                                    <a href="{{ route('profile.wishlist') }}" role="menuitem" class="nav-account-item" data-soft-nav @click="closeAccountMenu()">
                                        <div class="min-w-0">
                                            <p class="text-[11px] font-semibold text-gray-800">Wishlist</p>
                                            <p class="text-[10px] text-gray-500 mt-0.5" x-text="badgeDesc('wishlist', $L('Produk yang disimpan', 'Saved products'), $L('Wishlist masih kosong', 'Wishlist is empty'))"></p>
                                        </div>
                                        <span
                                            class="shrink-0 flex h-6 min-w-6 px-1.5 items-center justify-center rounded-full text-[11px] font-bold"
                                            :class="badges.wishlist > 0 ? 'bg-green-500 text-white' : 'bg-gray-100 text-gray-400'"
                                            x-text="badgeLabel('wishlist') || '0'"
                                        ></span>
                                    </a>

                                    <div class="flex items-center justify-between gap-3 px-1 pt-1.5 pb-0.5" data-no-locale-fx>
                                        <p class="text-[11px] font-semibold text-gray-800" x-text="locale === 'en' ? 'Language' : 'Bahasa'">Bahasa</p>
                                        @include('partials.language-switcher', ['variant' => 'dark'])
                                    </div>
                                </div>
                            </div>
                        </div>

                        <button
                            type="button"
                            class="nav-logout flex items-center justify-center gap-2 md:px-6 text-[12px] md:text-[18px] py-2.5 font-normal rounded-full text-center"
                            :disabled="logoutLoading"
                            @click="askLogout()"
                        >
                            <span x-text="logoutLoading ? $L('Keluar...', 'Logging out...') : @js($navLogout)"></span>
                            <svg class="nav-logout-icon w-3.5 h-3.5 md:w-4 md:h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                        </button>
                    </div>
                </template>

                {{-- Guest --}}
                <template x-if="!isLoggedIn">
                    <div class="flex items-center space-x-2">
                        <a
                            href="{{ route('login') }}"
                            class="nav-pill relative z-[1] flex justify-center items-center md:px-6 text-[12px] md:text-[18px] py-2.5 font-normal rounded-full text-center text-white nav-soft"
                            data-soft-nav
                        >
                            <span class="relative z-[1]">{{ $navLogin }}</span>
                        </a>
                        <a
                            href="{{ route('register') }}"
                            class="nav-pill relative z-[1] flex justify-center items-center md:px-6 text-[12px] md:text-[18px] py-2.5 font-normal rounded-full text-center text-white nav-soft"
                            data-soft-nav
                        >
                            <span class="relative z-[1]">{{ $navRegister }}</span>
                        </a>
                    </div>
                </template>
            </div>

            <button
                type="button"
                class="md:hidden inline-flex items-center justify-center w-10 h-10 rounded-full hover:bg-white/15 transition-colors"
                @click="open = !open"
                :aria-expanded="open.toString()"
                :aria-label="open ? $L('Tutup menu', 'Close menu') : $L('Buka menu', 'Open menu')"
            >
                <svg x-show="!open" xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
                <svg x-cloak x-show="open" xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div
            x-cloak
            x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-2"
            class="md:hidden mt-3 pt-3 border-t border-white/20 flex flex-col gap-1 pb-1"
            x-ref="mobileMenu"
        >
            @foreach ($navLinks as $i => $link)
                @php
                    $active = $isNavLinkActive($link['route'] ?? null, $current);
                @endphp
                <a
                    href="{{ $link['href'] }}"
                    class="nav-pill relative z-[1] flex items-center w-full px-3 py-2.5 text-[12px] font-bold rounded-full nav-soft {{ $active ? 'is-active text-[var(--nav-color)]' : 'text-white' }}"
                    data-soft-nav
                    data-nav-index="{{ $i }}"
                    data-nav-match="{{ $link['match'] }}"
                    @click="open = false"
                >
                    <span
                        class="nav-pill-indicator"
                        x-show="activeIndex === {{ $i }}"
                        x-cloak
                        aria-hidden="true"
                    ></span>
                    <span class="relative z-[1]">{{ $link['label'] }}</span>
                </a>
            @endforeach

            <template x-if="isLoggedIn">
                <div class="flex flex-col gap-1 mt-1 pt-2 border-t border-white/20">
                    <div class="px-3 py-2 flex items-center gap-3">
                        <span class="relative flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-white text-[var(--nav-color)] text-sm font-bold overflow-hidden border border-white/40">
                            <img
                                x-show="userAvatar"
                                x-cloak
                                :key="'m-' + (userAvatar || 'none')"
                                :src="userAvatar"
                                alt=""
                                class="absolute inset-0 h-full w-full object-cover"
                                x-on:error="userAvatar = null"
                            >
                            <span x-show="!userAvatar" x-text="userInitial"></span>
                        </span>
                        <div class="min-w-0">
                            <p class="text-[11px] font-bold text-white/90">{{ evomi_l('Akun Saya', 'My Account') }}</p>
                            <p class="text-[11px] text-white/60 truncate" x-text="userEmail"></p>
                        </div>
                    </div>
                    <template x-if="isAdmin">
                        <a
                            href="{{ route('dashboard') }}"
                            class="nav-pill relative z-[1] flex items-center w-full px-3 py-2.5 text-[12px] font-bold rounded-full text-white"
                            @click="open = false"
                        >
                            <span class="relative z-[1]">{{ evomi_l('Dashboard Admin', 'Admin Dashboard') }}</span>
                        </a>
                    </template>
                    <a href="{{ route('profile.index') }}" data-soft-nav class="nav-pill relative z-[1] flex items-center justify-between w-full px-3 py-2.5 text-[12px] font-bold rounded-full text-white" @click="open = false">
                        <span>{{ evomi_l('Pengaturan Profil', 'Profile Settings') }}</span>
                    </a>
                    <a href="{{ route('profile.chat') }}" data-soft-nav class="nav-pill relative z-[1] flex items-center justify-between w-full px-3 py-2.5 text-[12px] font-bold rounded-full text-white" @click="open = false">
                        <span>{{ evomi_l('Pesan', 'Messages') }}</span>
                        <span class="text-[10px] bg-white/20 rounded-full px-2 py-0.5" x-text="badgeLabel('unread') || '0'"></span>
                    </a>
                    <a href="{{ route('profile.cart') }}" data-soft-nav class="nav-pill relative z-[1] flex items-center justify-between w-full px-3 py-2.5 text-[12px] font-bold rounded-full text-white" @click="open = false">
                        <span>{{ evomi_l('Keranjang', 'Cart') }}</span>
                        <span class="text-[10px] bg-white/20 rounded-full px-2 py-0.5" x-text="badgeLabel('cart') || '0'"></span>
                    </a>
                    <a href="{{ route('profile.history') }}" data-soft-nav class="nav-pill relative z-[1] flex items-center justify-between w-full px-3 py-2.5 text-[12px] font-bold rounded-full text-white" @click="open = false">
                        <span>{{ evomi_l('Riwayat', 'History') }}</span>
                        <span class="text-[10px] bg-white/20 rounded-full px-2 py-0.5" x-text="badgeLabel('history') || '0'"></span>
                    </a>
                    <a href="{{ route('profile.wishlist') }}" data-soft-nav class="nav-pill relative z-[1] flex items-center justify-between w-full px-3 py-2.5 text-[12px] font-bold rounded-full text-white" @click="open = false">
                        <span>Wishlist</span>
                        <span class="text-[10px] bg-white/20 rounded-full px-2 py-0.5" x-text="badgeLabel('wishlist') || '0'"></span>
                    </a>
                    <div class="flex items-center justify-between gap-3 px-3 py-2" data-no-locale-fx>
                        <span class="text-[12px] font-bold text-white" x-text="locale === 'en' ? 'Language' : 'Bahasa'">Bahasa</span>
                        @include('partials.language-switcher', ['variant' => 'light'])
                    </div>
                    <button
                        type="button"
                        class="nav-logout flex items-center justify-center gap-2 w-full px-3 py-2.5 text-[12px] font-bold rounded-full"
                        :disabled="logoutLoading"
                        @click="askLogout()"
                    >
                        <span x-text="logoutLoading ? $L('Keluar...', 'Logging out...') : @js($navLogout)"></span>
                        <svg class="nav-logout-icon w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                    </button>
                </div>
            </template>

            <template x-if="!isLoggedIn">
                <div class="flex flex-col gap-1 mt-1">
                    <a
                        href="{{ route('login') }}"
                        class="nav-pill relative z-[1] flex items-center w-full px-3 py-2.5 text-[12px] font-bold rounded-full text-white nav-soft"
                        data-soft-nav
                        @click="open = false"
                    >
                        <span class="relative z-[1]">{{ $navLogin }}</span>
                    </a>
                    <a
                        href="{{ route('register') }}"
                        class="nav-pill relative z-[1] flex items-center w-full px-3 py-2.5 text-[12px] font-bold rounded-full text-white nav-soft"
                        data-soft-nav
                        @click="open = false"
                    >
                        <span class="relative z-[1]">{{ $navRegister }}</span>
                    </a>
                </div>
            </template>
        </div>
    </nav>

    {{-- Logout modal teleported to body (header transform would break fixed positioning) --}}
    <template x-teleport="body">
        <div
            x-show="logoutModal.open"
            x-cloak
            class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @keydown.escape.window="cancelLogout()"
            @click.self="logoutModal.type === 'confirm' && cancelLogout()"
            role="dialog"
            aria-modal="true"
            :aria-labelledby="logoutModal.open ? 'evomi-logout-title' : null"
        >
            <div
                class="nav-logout-modal relative bg-white rounded-[20px] md:rounded-[24px] p-5 md:p-8 max-w-[280px] md:max-w-[340px] w-full text-center shadow-2xl overflow-hidden"
                x-show="logoutModal.open"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-95 translate-y-3"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                @click.stop
            >
                <div
                    class="mx-auto flex items-center justify-center h-14 w-14 md:h-20 md:w-20 rounded-full mb-3 md:mb-5 transition-colors duration-300"
                    :class="{
                        'bg-amber-50 text-amber-500': logoutModal.type === 'confirm',
                        'bg-blue-50 text-[#1172BA]': logoutModal.type === 'loading',
                        'bg-green-50 text-green-500': logoutModal.type === 'success',
                    }"
                >
                    <template x-if="logoutModal.type === 'confirm'">
                        <svg class="h-7 w-7 md:h-10 md:w-10 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </template>
                    <template x-if="logoutModal.type === 'loading'">
                        <svg class="h-7 w-7 md:h-10 md:w-10 animate-spin text-[#1172BA]" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </template>
                    <template x-if="logoutModal.type === 'success'">
                        <svg class="h-7 w-7 md:h-10 md:w-10 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                    </template>
                </div>

                <div class="space-y-1.5 md:space-y-3">
                    <h3 id="evomi-logout-title" class="text-[16px] md:text-[20px] font-bold text-gray-800 tracking-wide" x-text="
                        logoutModal.type === 'loading'
                            ? $L('Memproses...', 'Processing...')
                            : logoutModal.type === 'success'
                                ? $L('Berhasil Keluar', 'Logged Out Successfully')
                                : $L('Konfirmasi Keluar', 'Confirm Logout')
                    "></h3>
                    <p class="text-[11px] md:text-[12px] text-gray-500 leading-relaxed px-1" x-text="
                        logoutModal.type === 'loading'
                            ? $L('Sedang mengeluarkan akun Anda...', 'Logging you out...')
                            : logoutModal.type === 'success'
                                ? $L('Sampai jumpa kembali di Evomi!', 'See you again at Evomi!')
                                : $L('Apakah Anda yakin ingin keluar dari akun Evomi?', 'Are you sure you want to log out of your Evomi account?')
                    "></p>
                </div>

                <div class="flex space-x-2 md:space-x-3 mt-4 md:mt-6" x-show="logoutModal.type === 'confirm'" x-cloak>
                    <button
                        type="button"
                        class="w-full font-bold py-2 md:py-3 rounded-xl text-[11px] md:text-[12px] bg-gray-100 text-gray-700 hover:bg-gray-200 transition-colors"
                        @click="cancelLogout()"
                    >{{ evomi_l('Batal', 'Cancel') }}</button>
                    <button
                        type="button"
                        class="w-full font-bold py-2 md:py-3 rounded-xl text-[11px] md:text-[12px] bg-red-500 text-white hover:bg-red-600 transition-colors"
                        @click="confirmLogout()"
                    >{{ evomi_l('Ya, Keluar', 'Yes, Log Out') }}</button>
                </div>

                <div
                    x-show="logoutModal.type === 'success'"
                    x-cloak
                    class="absolute bottom-0 left-0 h-[4px] bg-green-500 nav-logout-success-bar"
                ></div>
            </div>
        </div>
    </template>
</header>
{{-- Spacer so fixed header doesn't cover content --}}
<div id="evomi-header-spacer" class="w-full" style="background-color: {{ $navAccent }}" aria-hidden="true"></div>

<style>[x-cloak]{display:none!important}</style>
