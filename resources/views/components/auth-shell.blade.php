{{-- Auth shell: blue canvas + orbs + floating characters + card (match Next.js auth layout) --}}
@php
    $authChars = [
        [
            'src' => asset('src/images/section 2/purpose-prestige.png'),
            'alt' => 'Evomi Purpose Prestige',
            'wrap' => 'absolute top-[8%] left-[5%] md:top-[12%] md:left-[15%] w-14 h-14 sm:w-24 sm:h-24 md:w-36 md:h-36 opacity-70 sm:opacity-90 z-0 auth-char-float',
            'inner' => '-rotate-12 drop-shadow-2xl hover:rotate-0 transition-transform duration-300 w-full h-full',
        ],
        [
            'src' => asset('src/images/section 2/peaceful-calm.png'),
            'alt' => 'Evomi Peaceful Calm',
            'wrap' => 'absolute top-[12%] right-[5%] md:top-[15%] md:right-[15%] w-14 h-14 sm:w-24 sm:h-24 md:w-36 md:h-36 opacity-70 sm:opacity-90 z-0 auth-char-float auth-char-float-delay-1',
            'inner' => 'rotate-12 drop-shadow-2xl hover:rotate-0 transition-transform duration-300 w-full h-full',
        ],
        [
            'src' => asset('src/images/section 2/sweet-shy.png'),
            'alt' => 'Evomi Sweet Shy',
            'wrap' => 'absolute bottom-[12%] left-[8%] md:bottom-[15%] md:left-[15%] w-14 h-14 sm:w-24 sm:h-24 md:w-36 md:h-36 opacity-70 sm:opacity-90 z-0 auth-char-float auth-char-float-delay-2',
            'inner' => 'rotate-6 drop-shadow-2xl hover:-rotate-12 transition-transform duration-300 w-full h-full',
        ],
        [
            'src' => asset('src/images/section 2/rabel-brave.png'),
            'alt' => 'Evomi Rebel Brave',
            'wrap' => 'absolute bottom-[12%] right-[8%] md:bottom-[12%] md:right-[15%] w-14 h-14 sm:w-24 sm:h-24 md:w-36 md:h-36 opacity-70 sm:opacity-90 z-0 auth-char-float auth-char-float-delay-3',
            'inner' => '-rotate-[15deg] drop-shadow-2xl hover:rotate-6 transition-transform duration-300 w-full h-full',
        ],
    ];
@endphp

<div
    {{ $attributes->merge(['class' => 'evomi-auth-shell min-h-[100dvh] w-full flex items-center justify-center p-4 sm:p-6 relative overflow-hidden']) }}
    style="background-color:#2B92DE"
    data-auth-page="1"
>
    <div class="pointer-events-none absolute top-[-10%] left-[-10%] w-[40%] h-[40%] bg-blue-50 rounded-full blur-[120px]"></div>
    <div class="pointerpointer-events-none absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] bg-slate-50 rounded-full blur-[120px]"></div>

    <a
        href="{{ route('beranda') }}"
        data-soft-nav
        class="auth-back-home absolute top-4 left-4 sm:top-6 sm:left-6 z-20 inline-flex items-center gap-2 rounded-full px-4 py-2.5 text-sm font-semibold text-white transition-colors"
        style="background-color: rgba(0, 0, 0, 0.55);"
        onmouseover="this.style.backgroundColor='rgba(0,0,0,0.72)'"
        onmouseout="this.style.backgroundColor='rgba(0,0,0,0.55)'"
    >
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M15 18l-6-6 6-6"/>
        </svg>
        <span>{{ evomi_l('Beranda', 'Home') }}</span>
    </a>

    @foreach ($authChars as $char)
        <div class="{{ $char['wrap'] }} pointer-events-none sm:pointer-events-auto">
            <div class="{{ $char['inner'] }}">
                <img src="{{ $char['src'] }}" alt="{{ $char['alt'] }}" class="w-full h-full object-contain" draggable="false">
            </div>
        </div>
    @endforeach

    <div
        class="w-full max-w-[480px] rounded-[40px] p-8 md:p-12 shadow-2xl shadow-blue-950/20 border border-blue-600/20 relative z-10 text-white"
        style="background-color:#1172ba"
    >
        {{ $slot }}
    </div>
</div>
