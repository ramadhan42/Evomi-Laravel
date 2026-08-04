<section class="relative bg-white flex flex-col items-center text-center px-4 w-full overflow-x-hidden pb-10 md:pb-14">
    {{-- Circle divider top --}}
    <div class="absolute top-0 left-0 w-full overflow-hidden h-[15px] md:h-[23px] pointer-events-none">
        <div class="flex w-max gap-[10px] md:gap-[15px] animate-slide-right">
            @for ($i = 0; $i < 80; $i++)
                <div class="w-[30px] h-[30px] md:w-[46px] md:h-[46px] bg-[#1172BA] rounded-full shrink-0 -mt-[15px] md:-mt-[23px]"></div>
            @endfor
        </div>
    </div>

    <h2 class="mt-14 md:mt-24 mb-8 md:mb-10 text-[24px] md:text-[42px] leading-tight px-2 font-semibold parallax-self" data-reveal data-parallax="0.06">
        <span class="text-[#0071BC]">Kenalan sama</span><br>
        <span class="text-[#FF8A84]">karakter </span>
        <span class="text-[#0071BC]">kita yuk!</span>
    </h2>

    @php
        $characters = [
            ['lines' => ['Purpose', 'Prestige'], 'img' => 'purpose-prestige.png', 'color' => 'text-[#0D71BA]'],
            ['lines' => ['Peaceful', 'Calm'], 'img' => 'peaceful-calm.png', 'color' => 'text-[#5EA14A]'],
            ['lines' => ['Rebel', 'Brave'], 'img' => 'rabel-brave.png', 'color' => 'text-[#E33D35]'],
            ['lines' => ['Sweet', 'Shy'], 'img' => 'sweet-shy.png', 'color' => 'text-[#DD74A5]'],
        ];
    @endphp

    <div class="s2-char-grid w-full max-w-4xl mt-2 mb-8 px-2">
        @foreach ($characters as $char)
            <a
                href="{{ route('belanja') }}"
                class="s2-char-card group parallax-self"
                data-reveal
                data-reveal-delay="{{ number_format($loop->index * 0.08, 2, '.', '') }}"
                data-parallax="0.12"
            >
                <div class="s2-char-visual s2-char-icon">
                    <img
                        src="{{ asset('src/images/section 2/' . $char['img']) }}"
                        alt="{{ implode(' ', $char['lines']) }}"
                        class="w-full h-full object-contain drop-shadow-sm"
                    >
                </div>
                <h3 class="s2-char-label font-heavy tracking-tight {{ $char['color'] }}">
                    @foreach ($char['lines'] as $line)
                        <span class="s2-char-line">{{ $line }}</span>
                    @endforeach
                </h3>
            </a>
        @endforeach
    </div>

    <a
        href="{{ route('belanja') }}"
        class="s2-cta-btn group inline-flex items-center gap-1.5 md:gap-2 bg-[#0071BC] text-white text-[11px] md:text-[15px] px-5 md:px-7 py-2.5 md:py-3 rounded-full shadow-lg hover:bg-[#0062a3] hover:shadow-xl transition-all font-bold mb-10 md:mb-12 relative overflow-hidden"
        data-reveal
        data-reveal-delay="0.2"
    >
        <span
            class="pointer-events-none absolute inset-0 rounded-full bg-[linear-gradient(120deg,transparent_0%,rgba(255,255,255,0.22)_45%,transparent_70%)] -translate-x-[120%] transition-transform duration-500 ease-[cubic-bezier(0.22,1,0.36,1)] group-hover:translate-x-[120%]"
            aria-hidden="true"
        ></span>
        <span class="relative">Lihat Semua Karakter</span>
        <svg class="s2-cta-icon relative w-3.5 h-3.5 md:w-4 md:h-4 transition-transform duration-300 group-hover:translate-x-1" viewBox="0 0 19 19" fill="none" aria-hidden="true">
            <path d="M3.8 9.1H14.5" stroke="#fff" stroke-width="1.5" stroke-linecap="round"/>
            <path d="M9.1 3.8L14.5 9.1L9.1 14.5" stroke="#fff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
    </a>

    {{-- Circle divider bottom --}}
    <div class="absolute bottom-0 left-0 w-full overflow-hidden h-[15px] md:h-[23px] pointer-events-none">
        <div class="flex w-max gap-[10px] md:gap-[15px] animate-slide-right">
            @for ($i = 0; $i < 80; $i++)
                <div class="w-[30px] h-[30px] md:w-[46px] md:h-[46px] bg-[#1172BA] rounded-full shrink-0"></div>
            @endfor
        </div>
    </div>
</section>
