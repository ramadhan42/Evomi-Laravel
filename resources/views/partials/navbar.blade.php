@php
    $current = request()->route()?->getName();
    $navLinks = [
        [
            'href' => route('beranda'),
            'label' => 'Beranda',
            'route' => 'beranda',
            'match' => '/',
        ],
        [
            'href' => route('beranda') . '#about',
            'label' => 'Tentang',
            'route' => null,
            'match' => '#about',
        ],
        [
            'href' => route('belanja'),
            'label' => 'Belanja',
            'route' => 'belanja',
            'match' => '/belanja',
        ],
        [
            'href' => route('artikel'),
            'label' => 'Artikel',
            'route' => 'artikel',
            'match' => '/artikel',
        ],
        [
            'href' => route('kuis'),
            'label' => 'Kuis',
            'route' => 'kuis',
            'match' => '/kuis',
        ],
    ];

    $activeIndex = 0;
    foreach ($navLinks as $i => $link) {
        if ($link['route'] === 'belanja' && (str_starts_with((string) $current, 'belanja') || $current === 'checkout')) {
            $activeIndex = $i;
            break;
        }
        if ($link['route'] === 'artikel' && str_starts_with((string) $current, 'artikel')) {
            $activeIndex = $i;
            break;
        }
        if ($link['route'] && $current === $link['route']) {
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
                        $active = ($link['route'] && $current === $link['route'])
                            || ($link['route'] === 'belanja' && (str_starts_with((string) $current, 'belanja') || $current === 'checkout'));
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
                                class="nav-avatar relative flex items-center justify-center w-[44px] h-[44px] rounded-full bg-white text-[var(--nav-color)] font-bold text-[17px] border-2 border-white/90 shadow-sm"
                                aria-label="Menu akun"
                                :aria-expanded="accountMenuOpen.toString()"
                                aria-haspopup="menu"
                                @click="toggleAccountMenu()"
                            >
                                <span class="nav-avatar-ring" aria-hidden="true"></span>
                                <span class="relative z-[1] h-full w-full rounded-full overflow-hidden flex items-center justify-center">
                                    <template x-if="userAvatar">
                                        <img :src="userAvatar" alt="" class="h-full w-full object-cover" x-on:error="userAvatar = null">
                                    </template>
                                    <template x-if="!userAvatar">
                                        <span class="select-none tracking-tight" x-text="userInitial"></span>
                                    </template>
                                </span>
                            </button>

                            <div
                                class="nav-avatar-tooltip"
                                role="menu"
                                aria-label="Menu akun"
                            >
                                <div class="relative z-[1] space-y-2.5 text-left">
                                    <div class="flex items-center gap-2.5 px-1">
                                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[var(--nav-color)] text-white text-sm font-bold overflow-hidden">
                                            <template x-if="userAvatar">
                                                <img :src="userAvatar" alt="" class="h-full w-full object-cover" x-on:error="userAvatar = null">
                                            </template>
                                            <template x-if="!userAvatar">
                                                <span x-text="userInitial"></span>
                                            </template>
                                        </span>
                                        <div class="min-w-0">
                                            <p class="text-[12px] font-bold text-gray-900 leading-tight">Akun Saya</p>
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
                                                <p class="text-[11px] font-semibold text-gray-800">Dashboard Admin</p>
                                                <p class="text-[10px] text-gray-500 mt-0.5">Kelola toko &amp; CMS Evomi</p>
                                            </div>
                                        </a>
                                    </template>

                                    <a href="{{ route('profile.index') }}" role="menuitem" class="nav-account-item" data-soft-nav @click="closeAccountMenu()">
                                        <div class="min-w-0">
                                            <p class="text-[11px] font-semibold text-gray-800">Pengaturan Profil</p>
                                            <p class="text-[10px] text-gray-500 mt-0.5">Data akun &amp; pengaturan</p>
                                        </div>
                                    </a>

                                    <a href="{{ route('profile.chat') }}" role="menuitem" class="nav-account-item" data-soft-nav @click="closeAccountMenu()">
                                        <div class="min-w-0">
                                            <p class="text-[11px] font-semibold text-gray-800">Pesan belum dibaca</p>
                                            <p class="text-[10px] text-gray-500 mt-0.5" x-text="badgeDesc('unread', 'Ada balasan baru dari admin', 'Tidak ada pesan baru')"></p>
                                        </div>
                                        <span
                                            class="shrink-0 flex h-6 min-w-6 px-1.5 items-center justify-center rounded-full text-[11px] font-bold"
                                            :class="badges.unread > 0 ? 'bg-green-500 text-white' : 'bg-gray-100 text-gray-400'"
                                            x-text="badgeLabel('unread') || '0'"
                                        ></span>
                                    </a>

                                    <a href="{{ route('profile.cart') }}" role="menuitem" class="nav-account-item" data-soft-nav @click="closeAccountMenu()">
                                        <div class="min-w-0">
                                            <p class="text-[11px] font-semibold text-gray-800">Keranjang belanja</p>
                                            <p class="text-[10px] text-gray-500 mt-0.5" x-text="badgeDesc('cart', 'Produk siap checkout', 'Keranjang masih kosong')"></p>
                                        </div>
                                        <span
                                            class="shrink-0 flex h-6 min-w-6 px-1.5 items-center justify-center rounded-full text-[11px] font-bold"
                                            :class="badges.cart > 0 ? 'bg-green-500 text-white' : 'bg-gray-100 text-gray-400'"
                                            x-text="badgeLabel('cart') || '0'"
                                        ></span>
                                    </a>

                                    <a href="{{ route('profile.history') }}" role="menuitem" class="nav-account-item" data-soft-nav @click="closeAccountMenu()">
                                        <div class="min-w-0">
                                            <p class="text-[11px] font-semibold text-gray-800">Riwayat belanja</p>
                                            <p class="text-[10px] text-gray-500 mt-0.5" x-text="badgeDesc('history', 'Pesanan yang pernah dibuat', 'Belum ada riwayat')"></p>
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
                                            <p class="text-[10px] text-gray-500 mt-0.5" x-text="badgeDesc('wishlist', 'Produk yang disimpan', 'Wishlist masih kosong')"></p>
                                        </div>
                                        <span
                                            class="shrink-0 flex h-6 min-w-6 px-1.5 items-center justify-center rounded-full text-[11px] font-bold"
                                            :class="badges.wishlist > 0 ? 'bg-green-500 text-white' : 'bg-gray-100 text-gray-400'"
                                            x-text="badgeLabel('wishlist') || '0'"
                                        ></span>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <button
                            type="button"
                            class="nav-logout flex items-center justify-center gap-2 md:px-6 text-[12px] md:text-[18px] py-2.5 font-normal rounded-full text-center"
                            :disabled="logoutLoading"
                            @click="askLogout()"
                        >
                            <span x-text="logoutLoading ? 'Keluar...' : 'Logout'"></span>
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
                            <span class="relative z-[1]">Login</span>
                        </a>
                        <a
                            href="{{ route('register') }}"
                            class="nav-pill relative z-[1] flex justify-center items-center md:px-6 text-[12px] md:text-[18px] py-2.5 font-normal rounded-full text-center text-white nav-soft"
                            data-soft-nav
                        >
                            <span class="relative z-[1]">Daftar</span>
                        </a>
                    </div>
                </template>
            </div>

            <button
                type="button"
                class="md:hidden inline-flex items-center justify-center w-10 h-10 rounded-full hover:bg-white/15 transition-colors"
                @click="open = !open"
                :aria-expanded="open.toString()"
                aria-label="Menu"
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
                    $active = ($link['route'] && $current === $link['route'])
                        || ($link['route'] === 'belanja' && (str_starts_with((string) $current, 'belanja') || $current === 'checkout'));
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
                    <div class="px-3 py-2">
                        <p class="text-[11px] font-bold text-white/90">Akun Saya</p>
                        <p class="text-[11px] text-white/60 truncate" x-text="userEmail"></p>
                    </div>
                    <template x-if="isAdmin">
                        <a
                            href="{{ route('dashboard') }}"
                            class="nav-pill relative z-[1] flex items-center w-full px-3 py-2.5 text-[12px] font-bold rounded-full text-white"
                            @click="open = false"
                        >
                            <span class="relative z-[1]">Dashboard Admin</span>
                        </a>
                    </template>
                    <a href="{{ route('profile.index') }}" data-soft-nav class="nav-pill relative z-[1] flex items-center justify-between w-full px-3 py-2.5 text-[12px] font-bold rounded-full text-white" @click="open = false">
                        <span>Pengaturan Profil</span>
                    </a>
                    <a href="{{ route('profile.chat') }}" data-soft-nav class="nav-pill relative z-[1] flex items-center justify-between w-full px-3 py-2.5 text-[12px] font-bold rounded-full text-white" @click="open = false">
                        <span>Pesan</span>
                        <span class="text-[10px] bg-white/20 rounded-full px-2 py-0.5" x-text="badgeLabel('unread') || '0'"></span>
                    </a>
                    <a href="{{ route('profile.cart') }}" data-soft-nav class="nav-pill relative z-[1] flex items-center justify-between w-full px-3 py-2.5 text-[12px] font-bold rounded-full text-white" @click="open = false">
                        <span>Keranjang</span>
                        <span class="text-[10px] bg-white/20 rounded-full px-2 py-0.5" x-text="badgeLabel('cart') || '0'"></span>
                    </a>
                    <a href="{{ route('profile.history') }}" data-soft-nav class="nav-pill relative z-[1] flex items-center justify-between w-full px-3 py-2.5 text-[12px] font-bold rounded-full text-white" @click="open = false">
                        <span>Riwayat</span>
                        <span class="text-[10px] bg-white/20 rounded-full px-2 py-0.5" x-text="badgeLabel('history') || '0'"></span>
                    </a>
                    <a href="{{ route('profile.wishlist') }}" data-soft-nav class="nav-pill relative z-[1] flex items-center justify-between w-full px-3 py-2.5 text-[12px] font-bold rounded-full text-white" @click="open = false">
                        <span>Wishlist</span>
                        <span class="text-[10px] bg-white/20 rounded-full px-2 py-0.5" x-text="badgeLabel('wishlist') || '0'"></span>
                    </a>
                    <button
                        type="button"
                        class="nav-logout flex items-center justify-center gap-2 w-full px-3 py-2.5 text-[12px] font-bold rounded-full"
                        :disabled="logoutLoading"
                        @click="askLogout()"
                    >
                        <span x-text="logoutLoading ? 'Keluar...' : 'Logout'"></span>
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
                        <span class="relative z-[1]">Login</span>
                    </a>
                    <a
                        href="{{ route('register') }}"
                        class="nav-pill relative z-[1] flex items-center w-full px-3 py-2.5 text-[12px] font-bold rounded-full text-white nav-soft"
                        data-soft-nav
                        @click="open = false"
                    >
                        <span class="relative z-[1]">Daftar</span>
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
            <h3 class="text-lg font-bold text-gray-900">Konfirmasi Keluar</h3>
            <p class="text-sm text-gray-500">Yakin ingin keluar dari akun Evomi?</p>
            <div class="flex gap-3">
                <button
                    type="button"
                    class="flex-1 py-2.5 rounded-xl border border-gray-200 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                    @click="cancelLogout()"
                >Batal</button>
                <button
                    type="button"
                    class="flex-1 py-2.5 rounded-xl bg-gray-900 text-white text-sm font-semibold hover:bg-gray-800"
                    @click="confirmLogout()"
                >Keluar</button>
            </div>
        </div>
    </div>
</header>
{{-- Spacer so fixed header doesn't cover content --}}
<div id="evomi-header-spacer" class="w-full" style="background-color: {{ $navAccent }}" aria-hidden="true"></div>

<style>[x-cloak]{display:none!important}</style>
