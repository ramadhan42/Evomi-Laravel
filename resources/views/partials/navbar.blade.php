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
        if ($link['route'] && $current === $link['route']) {
            $activeIndex = $i;
            break;
        }
    }
@endphp

<header
    id="evomi-header"
    class="sticky top-0 z-[100] w-full bg-[#1172BA] px-2 py-2 md:px-4 md:pt-7 md:pb-2"
    style="--nav-color: #1172BA"
    x-data="evomiNavbar({{ $activeIndex }})"
    x-init="init()"
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
                    @php $active = ($link['route'] && $current === $link['route']); @endphp
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
                @php $active = ($link['route'] && $current === $link['route']); @endphp
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
    </nav>
</header>

<style>[x-cloak]{display:none!important}</style>
