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

    {{-- Konfirmasi logout --}}
    <div
        x-show="logoutConfirmOpen"
        x-cloak
        class="fixed inset-0 z-[140] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
        @keydown.escape.window="cancelLogout()"
    >
        <div class="bg-white rounded-2xl shadow-xl max-w-sm w-full p-6 text-center space-y-4" @click.stop>
            <h3 class="text-lg font-bold text-gray-900">{{ evomi_l('Konfirmasi Keluar', 'Confirm Logout') }}</h3>
            <p class="text-sm text-gray-500">{{ evomi_l('Yakin ingin keluar dari akun Evomi?', 'Are you sure you want to log out of Evomi?') }}</p>
            <div class="flex gap-3">
                <button
                    type="button"
                    class="flex-1 py-2.5 rounded-xl border border-gray-200 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                    @click="cancelLogout()"
                >{{ evomi_l('Batal', 'Cancel') }}</button>
                <button
                    type="button"
                    class="flex-1 py-2.5 rounded-xl bg-gray-900 text-white text-sm font-semibold hover:bg-gray-800"
                    @click="confirmLogout()"
                >{{ evomi_l('Keluar', 'Log Out') }}</button>
            </div>
        </div>
    </div>
</header>
{{-- Spacer so fixed header doesn't cover content --}}
<div id="evomi-header-spacer" class="w-full" style="background-color: {{ $navAccent }}" aria-hidden="true"></div>

<style>[x-cloak]{display:none!important}</style>
