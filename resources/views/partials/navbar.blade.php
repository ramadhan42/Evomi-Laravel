@php
    $current = request()->route()?->getName();
    // Load ID + EN so navbar can switch live on toggle (soft-nav does not remount header).
    $navCmsId = \App\Support\CmsStorefront::forPage('navbar', 'id');
    // EN rows only — forPage('en') merges ID underneath and would hide missing EN translations.
    $navEnMenu = \App\Models\SiteContent::query()
        ->where('page', 'navbar')
        ->where('section', 'menu')
        ->where('locale', 'en')
        ->pluck('value', 'key');
    $navLabel = static function (string $key, string $idDefault, string $enDefault) use ($navCmsId, $navEnMenu): array {
        $en = trim((string) ($navEnMenu[$key] ?? ''));

        return [
            'label_id' => $navCmsId->get('menu', $key, $idDefault),
            'label_en' => $en !== '' ? $en : $enDefault,
        ];
    };
    $navLinks = [
        array_merge([
            'href' => route('beranda'),
            'route' => 'beranda',
            'match' => '/',
        ], $navLabel('beranda', 'Beranda', 'Home')),
        array_merge([
            'href' => route('beranda') . '#about',
            'route' => null,
            'match' => '#about',
        ], $navLabel('tentang', 'Tentang', 'About')),
        array_merge([
            'href' => route('belanja'),
            'route' => 'belanja',
            'match' => '/belanja',
        ], $navLabel('belanja', 'Belanja', 'Shop')),
        array_merge([
            'href' => route('kuis'),
            'route' => 'kuis',
            'match' => '/kuis',
        ], $navLabel('kuis', 'Temukan Aromamu', 'Find Your Scent')),
    ];
    $navLoginPair = $navLabel('login', 'Login', 'Login');
    $navLogoutPair = $navLabel('logout', 'Logout', 'Logout');
    $navLoginId = $navLoginPair['label_id'];
    $navLoginEn = $navLoginPair['label_en'];
    $navLogoutId = $navLogoutPair['label_id'];
    $navLogoutEn = $navLogoutPair['label_en'];

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
    class="fixed inset-x-0 top-0 z-[100] w-full"
    style="background-color: {{ $navAccent }}; --nav-color: {{ $navAccent }}"
    x-data="evomiNavbar({{ $activeIndex }})"
    :class="{ 'is-nav-hidden': isNavHidden }"
    @keydown.escape.window="$store.evomiTrackModal.open && closeTrackModal()"
>
    {{-- Figma Navbar 1532:2319 — h 72px, px 24 / py 16, menu centered --}}
    <nav class="nav-chrome text-white relative w-full max-w-[1280px] mx-auto px-4 py-3 md:px-6 md:py-4">
        <div class="flex items-center justify-between gap-3 md:grid md:grid-cols-[1fr_auto_1fr] md:items-center md:gap-4">
            <a
                href="{{ route('beranda') }}"
                class="shrink-0 flex items-center nav-soft justify-self-start"
                data-soft-nav
                data-nav-index="0"
            >
                <img
                    src="{{ asset('src/images/navbar/evomi-logo.png') }}"
                    alt="Evomi"
                    class="nav-logo object-contain brightness-0 invert w-auto h-6 md:h-8 transition-transform duration-500 ease-[cubic-bezier(0.22,1,0.36,1)]"
                >
            </a>

            {{-- Desktop menu — sejajar vertikal dengan logo (optical center) --}}
            <div
                class="nav-desktop-menu hidden md:flex items-center justify-center gap-1 relative shrink-0 h-10 self-center"
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
                        class="nav-pill relative z-[1] inline-flex justify-center items-center h-10 px-4 text-[14px] font-normal leading-none rounded-full text-center whitespace-nowrap nav-soft {{ $active ? 'is-active text-[var(--nav-color)]' : 'text-white' }}"
                        data-soft-nav
                        data-nav-index="{{ $i }}"
                        data-nav-match="{{ $link['match'] }}"
                    >
                        <span
                            class="relative z-[1] flex items-center leading-none"
                            x-text="locale === 'en' ? @js($link['label_en']) : @js($link['label_id'])"
                        >{{ $link['label_id'] }}</span>
                    </a>
                @endforeach
            </div>

            <div class="nav-desktop-actions hidden md:flex items-center justify-end gap-2 shrink-0 justify-self-end self-center h-10">
                {{-- Logged in --}}
                <template x-if="isLoggedIn">
                    <div class="flex items-center gap-2 h-10">
                        <div
                            class="nav-avatar-wrap relative z-[70]"
                            :class="{ 'is-open': accountMenuOpen }"
                            @click.outside="closeAccountMenu()"
                        >
                            <button
                                type="button"
                                class="nav-avatar relative flex items-center justify-center w-10 h-10 rounded-full bg-white text-[var(--nav-color)] font-bold text-[15px] border-2 border-white/90 shadow-sm overflow-hidden"
                                :aria-label="locale === 'en' ? 'Account menu' : 'Menu akun'"
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
                                :aria-label="locale === 'en' ? 'Account menu' : 'Menu akun'"
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
                                            <p class="text-[12px] font-bold text-gray-900 leading-tight" x-text="locale === 'en' ? 'My Account' : 'Akun Saya'">Akun Saya</p>
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
                                            <div class="nav-account-item__main">
                                                <span class="nav-account-item__icon is-admin">
                                                    @include('partials.icons.dashboard', ['class' => 'w-[15px] h-[15px]'])
                                                </span>
                                                <div class="min-w-0">
                                                    <p class="text-[11px] font-semibold text-gray-800" x-text="locale === 'en' ? 'Admin Dashboard' : 'Dashboard Admin'">Dashboard Admin</p>
                                                    <p class="text-[10px] text-gray-500 mt-0.5" x-text="locale === 'en' ? 'Manage Evomi store & CMS' : 'Kelola toko & CMS Evomi'">Kelola toko & CMS Evomi</p>
                                                </div>
                                            </div>
                                        </a>
                                    </template>

                                    <a href="{{ route('profile.index') }}" role="menuitem" class="nav-account-item" data-soft-nav @click="closeAccountMenu()">
                                        <div class="nav-account-item__main">
                                            <span class="nav-account-item__icon">
                                                @include('partials.icons.user', ['class' => 'w-[15px] h-[15px]'])
                                            </span>
                                            <div class="min-w-0">
                                                <p class="text-[11px] font-semibold text-gray-800" x-text="locale === 'en' ? 'Profile Settings' : 'Pengaturan Profil'">Pengaturan Profil</p>
                                                <p class="text-[10px] text-gray-500 mt-0.5" x-text="locale === 'en' ? 'Account data & settings' : 'Data akun & pengaturan'">Data akun & pengaturan</p>
                                            </div>
                                        </div>
                                    </a>

                                    <button
                                        type="button"
                                        role="menuitem"
                                        class="nav-account-item w-full"
                                        @click="closeAccountMenu(); openAccountDrawer('cart')"
                                    >
                                        <div class="nav-account-item__main">
                                            <span class="nav-account-item__icon is-cart">
                                                @include('partials.icons.cart', ['class' => 'w-[15px] h-[15px]'])
                                            </span>
                                            <div class="min-w-0 text-left">
                                                <p class="text-[11px] font-semibold text-gray-800" x-text="locale === 'en' ? 'My Cart' : 'Keranjang Saya'">Keranjang Saya</p>
                                                <p class="text-[10px] text-gray-500 mt-0.5" x-text="badgeDesc('cart', locale === 'en' ? 'View items in cart' : 'Lihat item di keranjang', locale === 'en' ? 'Cart is empty' : 'Keranjang kosong')"></p>
                                            </div>
                                        </div>
                                        <span
                                            class="shrink-0 flex h-6 min-w-6 px-1.5 items-center justify-center rounded-full text-[11px] font-bold"
                                            :class="badges.cart > 0 ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-400'"
                                            x-text="badgeLabel('cart') || '0'"
                                        ></span>
                                    </button>

                                    <button
                                        type="button"
                                        role="menuitem"
                                        class="nav-account-item w-full"
                                        @click="closeAccountMenu(); openAccountDrawer('track')"
                                    >
                                        <div class="nav-account-item__main">
                                            <span class="nav-account-item__icon is-track">
                                                @include('partials.icons.truck', ['class' => 'w-[15px] h-[15px]'])
                                            </span>
                                            <div class="min-w-0 text-left">
                                                <p class="text-[11px] font-semibold text-gray-800" x-text="locale === 'en' ? 'Track Order' : 'Lacak Pesanan'">Lacak Pesanan</p>
                                                <p class="text-[10px] text-gray-500 mt-0.5" x-text="badgeDesc('history', locale === 'en' ? 'Monitor shipping status' : 'Pantau status pengiriman', locale === 'en' ? 'No orders yet' : 'Belum ada pesanan')"></p>
                                            </div>
                                        </div>
                                        <span
                                            class="shrink-0 flex h-6 min-w-6 px-1.5 items-center justify-center rounded-full text-[11px] font-bold"
                                            :class="badges.history > 0 ? 'bg-green-500 text-white' : 'bg-gray-100 text-gray-400'"
                                            x-text="badgeLabel('history') || '0'"
                                        ></span>
                                    </button>

                                    <div class="nav-account-lang" data-no-locale-fx>
                                        <div class="nav-account-lang__label">
                                            <span class="nav-account-item__icon">
                                                @include('partials.icons.globe', ['class' => 'w-[15px] h-[15px]'])
                                            </span>
                                            <p class="text-[11px] font-semibold text-gray-800" x-text="locale === 'en' ? 'Language' : 'Bahasa'">Bahasa</p>
                                        </div>
                                        @include('partials.language-switcher', ['variant' => 'dark'])
                                    </div>

                                    <div class="h-px bg-gray-100"></div>

                                    <button
                                        type="button"
                                        role="menuitem"
                                        class="nav-account-item nav-account-item--logout w-full"
                                        :disabled="logoutLoading"
                                        @click="closeAccountMenu(); askLogout()"
                                    >
                                        <div class="nav-account-item__main">
                                            <span class="nav-account-item__icon is-logout">
                                                @include('partials.icons.logout', ['class' => 'w-[15px] h-[15px]'])
                                            </span>
                                            <div class="min-w-0 text-left">
                                                <p class="text-[11px] font-semibold text-rose-600" x-text="logoutLoading ? (locale === 'en' ? 'Logging out...' : 'Keluar...') : (locale === 'en' ? @js($navLogoutEn) : @js($navLogoutId))"></p>
                                                <p class="text-[10px] text-rose-400/90 mt-0.5" x-text="locale === 'en' ? 'Sign out of Evomi' : 'Keluar dari akun Evomi'">Keluar dari akun Evomi</p>
                                            </div>
                                        </div>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <button
                            type="button"
                            class="nav-cart-btn relative z-[1] inline-flex items-center justify-center size-10 rounded-full text-white"
                            :aria-label="locale === 'en' ? 'Cart' : 'Keranjang'"
                            @click="closeAccountMenu(); openAccountDrawer()"
                        >
                            @include('partials.icons.cart', ['class' => 'w-[18px] h-[18px]'])
                            <span
                                class="nav-cart-badge"
                                x-show="badges.cart > 0"
                                x-cloak
                                x-text="badgeLabel('cart')"
                            ></span>
                        </button>
                    </div>
                </template>

                {{-- Guest --}}
                <template x-if="!isLoggedIn">
                    <div class="flex items-center gap-2 h-10">
                        <a
                            href="{{ route('login') }}"
                            class="nav-pill nav-login-link relative z-[1] inline-flex justify-center items-center h-10 px-4 text-[14px] font-normal leading-none rounded-full text-white nav-soft"
                            data-soft-nav
                        >
                            <span
                                class="relative z-[1] flex items-center leading-none"
                                x-text="locale === 'en' ? @js($navLoginEn) : @js($navLoginId)"
                            >{{ $navLoginId }}</span>
                        </a>
                        <button
                            type="button"
                            class="nav-cart-btn relative z-[1] inline-flex items-center justify-center size-10 rounded-full text-white"
                            :aria-label="locale === 'en' ? 'Cart' : 'Keranjang'"
                            @click="openAccountDrawer()"
                        >
                            @include('partials.icons.cart', ['class' => 'w-[18px] h-[18px]'])
                            <span
                                class="nav-cart-badge"
                                x-show="badges.cart > 0"
                                x-cloak
                                x-text="badgeLabel('cart')"
                            ></span>
                        </button>
                    </div>
                </template>
            </div>

            <div class="md:hidden flex items-center gap-1.5 shrink-0">
                <button
                    type="button"
                    class="nav-cart-btn relative z-[1] inline-flex items-center justify-center w-10 h-10 rounded-full text-white"
                    :aria-label="locale === 'en' ? 'Cart' : 'Keranjang'"
                    @click="openAccountDrawer()"
                >
                    @include('partials.icons.cart')
                    <span
                        class="nav-cart-badge"
                        x-show="badges.cart > 0"
                        x-cloak
                        x-text="badgeLabel('cart')"
                    ></span>
                </button>
                <button
                    type="button"
                    class="inline-flex items-center justify-center w-10 h-10 rounded-full hover:bg-white/15 transition-colors"
                    @click="open = !open"
                    :aria-expanded="open.toString()"
                    :aria-label="open
                        ? (locale === 'en' ? 'Close menu' : 'Tutup menu')
                        : (locale === 'en' ? 'Open menu' : 'Buka menu')"
                >
                    <svg x-show="!open" xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                    <svg x-cloak x-show="open" xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
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
                    <span
                        class="relative z-[1]"
                        x-text="locale === 'en' ? @js($link['label_en']) : @js($link['label_id'])"
                    >{{ $link['label_id'] }}</span>
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
                            <p class="text-[11px] font-bold text-white/90" x-text="locale === 'en' ? 'My Account' : 'Akun Saya'">Akun Saya</p>
                            <p class="text-[11px] text-white/60 truncate" x-text="userEmail"></p>
                        </div>
                    </div>
                    <template x-if="isAdmin">
                        <a
                            href="{{ route('dashboard') }}"
                            class="nav-pill relative z-[1] flex items-center gap-2.5 w-full px-3 py-2.5 text-[12px] font-bold rounded-full text-white"
                            @click="open = false"
                        >
                            <span class="relative z-[1] inline-flex h-7 w-7 items-center justify-center rounded-full bg-white/15">@include('partials.icons.dashboard', ['class' => 'w-3.5 h-3.5'])</span>
                            <span class="relative z-[1]" x-text="locale === 'en' ? 'Admin Dashboard' : 'Dashboard Admin'">Dashboard Admin</span>
                        </a>
                    </template>
                    <a href="{{ route('profile.index') }}" data-soft-nav class="nav-pill relative z-[1] flex items-center gap-2.5 w-full px-3 py-2.5 text-[12px] font-bold rounded-full text-white" @click="open = false">
                        <span class="relative z-[1] inline-flex h-7 w-7 items-center justify-center rounded-full bg-white/15">@include('partials.icons.user', ['class' => 'w-3.5 h-3.5'])</span>
                        <span class="relative z-[1]" x-text="locale === 'en' ? 'Profile Settings' : 'Pengaturan Profil'">Pengaturan Profil</span>
                    </a>
                    <button type="button" class="nav-pill relative z-[1] flex items-center justify-between w-full px-3 py-2.5 text-[12px] font-bold rounded-full text-white" @click="open = false; openAccountDrawer('cart')">
                        <span class="relative z-[1] inline-flex items-center gap-2.5">
                            <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-white/15">@include('partials.icons.cart', ['class' => 'w-3.5 h-3.5'])</span>
                            <span x-text="locale === 'en' ? 'My Cart' : 'Keranjang Saya'">Keranjang Saya</span>
                        </span>
                        <span class="text-[10px] bg-white/20 rounded-full px-2 py-0.5" x-text="badgeLabel('cart') || '0'"></span>
                    </button>
                    <button type="button" class="nav-pill relative z-[1] flex items-center justify-between w-full px-3 py-2.5 text-[12px] font-bold rounded-full text-white" @click="open = false; openAccountDrawer('track')">
                        <span class="relative z-[1] inline-flex items-center gap-2.5">
                            <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-white/15">@include('partials.icons.truck', ['class' => 'w-3.5 h-3.5'])</span>
                            <span x-text="locale === 'en' ? 'Track Order' : 'Lacak Pesanan'">Lacak Pesanan</span>
                        </span>
                        <span class="text-[10px] bg-white/20 rounded-full px-2 py-0.5" x-text="badgeLabel('history') || '0'"></span>
                    </button>
                    <div class="flex items-center justify-between gap-3 px-3 py-2" data-no-locale-fx>
                        <span class="relative z-[1] inline-flex items-center gap-2.5 text-[12px] font-bold text-white">
                            <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-white/15">@include('partials.icons.globe', ['class' => 'w-3.5 h-3.5'])</span>
                            <span x-text="locale === 'en' ? 'Language' : 'Bahasa'">Bahasa</span>
                        </span>
                        @include('partials.language-switcher', ['variant' => 'light'])
                    </div>
                    <button
                        type="button"
                        class="nav-pill relative z-[1] flex items-center gap-2.5 w-full px-3 py-2.5 text-[12px] font-bold rounded-full text-rose-100"
                        :disabled="logoutLoading"
                        @click="open = false; askLogout()"
                    >
                        <span class="relative z-[1] inline-flex h-7 w-7 items-center justify-center rounded-full bg-rose-500/25 text-rose-100">
                            @include('partials.icons.logout', ['class' => 'w-3.5 h-3.5'])
                        </span>
                        <span class="relative z-[1]" x-text="logoutLoading ? (locale === 'en' ? 'Logging out...' : 'Keluar...') : (locale === 'en' ? @js($navLogoutEn) : @js($navLogoutId))"></span>
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
                        <span class="relative z-[1]" x-text="locale === 'en' ? @js($navLoginEn) : @js($navLoginId)">{{ $navLoginId }}</span>
                    </a>
                    <button
                        type="button"
                        class="nav-pill relative z-[1] flex items-center justify-between w-full px-3 py-2.5 text-[12px] font-bold rounded-full text-white"
                        @click="open = false; openAccountDrawer()"
                    >
                        <span class="relative z-[1]" x-text="locale === 'en' ? 'Cart' : 'Keranjang'">Keranjang</span>
                        <span class="text-[10px] bg-white/20 rounded-full px-2 py-0.5" x-text="badgeLabel('cart') || '0'"></span>
                    </button>
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
                            ? (locale === 'en' ? 'Processing...' : 'Memproses...')
                            : logoutModal.type === 'success'
                                ? (locale === 'en' ? 'Logged Out Successfully' : 'Berhasil Keluar')
                                : (locale === 'en' ? 'Confirm Logout' : 'Konfirmasi Keluar')
                    "></h3>
                    <p class="text-[11px] md:text-[12px] text-gray-500 leading-relaxed px-1" x-text="
                        logoutModal.type === 'loading'
                            ? (locale === 'en' ? 'Logging you out...' : 'Sedang mengeluarkan akun Anda...')
                            : logoutModal.type === 'success'
                                ? (locale === 'en' ? 'See you again at Evomi!' : 'Sampai jumpa kembali di Evomi!')
                                : (locale === 'en' ? 'Are you sure you want to log out of your Evomi account?' : 'Apakah Anda yakin ingin keluar dari akun Evomi?')
                    "></p>
                </div>

                <div class="flex space-x-2 md:space-x-3 mt-4 md:mt-6" x-show="logoutModal.type === 'confirm'" x-cloak>
                    <button
                        type="button"
                        class="w-full font-bold py-2 md:py-3 rounded-xl text-[11px] md:text-[12px] bg-gray-100 text-gray-700 hover:bg-gray-200 transition-colors"
                        @click="cancelLogout()"
                    ><span x-text="locale === 'en' ? 'Cancel' : 'Batal'">Batal</span></button>
                    <button
                        type="button"
                        class="w-full font-bold py-2 md:py-3 rounded-xl text-[11px] md:text-[12px] bg-red-500 text-white hover:bg-red-600 transition-colors"
                        @click="confirmLogout()"
                    ><span x-text="locale === 'en' ? 'Yes, Log Out' : 'Ya, Keluar'">Ya, Keluar</span></button>
                </div>

                <div
                    x-show="logoutModal.type === 'success'"
                    x-cloak
                    class="absolute bottom-0 left-0 h-[4px] bg-green-500 nav-logout-success-bar"
                ></div>
            </div>
        </div>
    </template>

    @include('partials.account-drawer')
    @include('partials.track-modal')
    @include('partials.faq-modal')
    @include('partials.kontak-modal')
    @include('partials.settings-modal')
    @include('partials.chat-modal')
    @include('partials.wishlist-modal')
    @include('partials.history-modal')
    @include('partials.history-detail-modal')
</header>
{{-- Spacer so fixed header doesn't cover content --}}
<div id="evomi-header-spacer" class="w-full" style="background-color: {{ $navAccent }}" aria-hidden="true"></div>

<style>[x-cloak]{display:none!important}</style>
