<section class="relative bg-white flex flex-col md:block items-center justify-between px-5 sm:px-8 md:px-0 md:pl-16 lg:pl-24 md:pr-0 pt-12 pb-10 md:pt-20 md:pb-0 md:min-h-[677px] lg:min-h-[742px] overflow-hidden select-none">
    {{-- Circle divider top --}}
    <div class="absolute top-0 left-0 w-full overflow-hidden h-[15px] md:h-[23px] pointer-events-none z-10">
        <div class="flex w-max gap-[10px] md:gap-[15px] animate-slide-right">
            @for ($i = 0; $i < 80; $i++)
                <div class="w-[30px] h-[30px] md:w-[46px] md:h-[46px] bg-[#1172BA] rounded-full shrink-0 -mt-[15px] md:-mt-[23px]"></div>
            @endfor
        </div>
    </div>

    @php
        $badges = [
            ['text' => 'Prestige', 'title' => 'Purpose Prestige', 'color' => '#5CB2ED', 'left' => '61%', 'top' => '33%'],
            ['text' => 'Calm', 'title' => 'Peaceful Calm', 'color' => '#5EA14A', 'left' => '82%', 'top' => '15%'],
            ['text' => 'Rebel', 'title' => 'Rebel Brave', 'color' => '#E33D35', 'left' => '26%', 'top' => '15%'],
            ['text' => 'Sweet', 'title' => 'Sweet Shy', 'color' => '#DD74A5', 'left' => '47.5%', 'top' => '-3%'],
        ];
    @endphp

    {{-- Left: text & CTA --}}
    <div class="relative z-20 w-full md:w-auto md:absolute md:left-16 lg:left-24 md:top-1/2 md:-translate-y-1/2 md:max-w-[420px] lg:max-w-xl">
        <div class="parallax-self flex flex-col justify-center items-center md:items-start gap-6 md:gap-8 text-center md:text-left" data-reveal data-parallax="0.06">
            <h2 class="text-[30px] sm:text-[36px] md:text-[48px] lg:text-[55px] leading-[1.12] font-semibold">
                <span class="text-[#1172BA]">Temukan</span><br>
                <span class="text-[#DD74A5]">aromamu</span><br>
                <span class="text-[#1172BA]">dengan</span><br>
                <span class="text-[#1172BA]">bermain </span><span class="text-[#5EA14A]">kuis</span>
            </h2>

            <a
                href="{{ route('kuis') }}"
                class="group relative z-20 overflow-hidden text-[11px] md:text-[15px] text-white bg-[#1172BA] px-5 md:px-7 py-2.5 md:py-3 rounded-full shadow-md transition-[background-color,box-shadow] duration-200 hover:bg-[#0e5d99] hover:shadow-lg active:brightness-95 inline-flex items-center gap-1.5 md:gap-2 font-semibold"
            >
                <span
                    class="pointer-events-none absolute inset-0 rounded-full bg-[linear-gradient(120deg,transparent_0%,rgba(255,255,255,0.22)_45%,transparent_70%)] -translate-x-[120%] transition-transform duration-500 ease-[cubic-bezier(0.22,1,0.36,1)] group-hover:translate-x-[120%]"
                    aria-hidden="true"
                ></span>
                <span class="relative">Mulai Kuis</span>
                <svg class="relative w-3.5 h-3.5 md:w-4 md:h-4 pointer-events-none transition-transform duration-300 ease-out group-hover:translate-x-1" viewBox="0 0 19 19" fill="none" aria-hidden="true">
                    <path d="M3.80933 9.14282H14.476" stroke="currentColor" stroke-width="1.52381" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M9.14282 3.80957L14.4762 9.1429L9.14282 14.4762" stroke="currentColor" stroke-width="1.52381" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </a>
        </div>
    </div>

    {{-- Mobile: blue panel + product + badges --}}
    <div class="relative z-20 w-full max-w-[380px] mx-auto mt-8 md:hidden">
        <div class="parallax-self relative w-full h-[294px] bg-[#1172BA] rounded-[24px] shadow-lg overflow-visible" data-reveal="right" data-parallax="0.14">
            <div class="absolute bottom-0 right-0 w-[88%] overflow-visible">
                <div class="relative w-full drop-shadow-2xl overflow-visible">
                    <img
                        src="{{ asset('src/images/section 7/produk.png') }}"
                        alt="Produk Evomi"
                        class="w-full h-auto object-contain"
                    >
                    @foreach ($badges as $badge)
                        <a
                            href="{{ route('belanja') }}"
                            aria-label="Lihat detail {{ $badge['title'] }}"
                            class="s7-badge-hit absolute -translate-x-1/2 -translate-y-[calc(100%+6px)] px-2.5 py-0.5 bg-white rounded-full shadow-md whitespace-nowrap z-30 font-bold text-[9px]"
                            style="color: {{ $badge['color'] }}; left: {{ $badge['left'] }}; top: {{ $badge['top'] }};"
                        >
                            {{ $badge['text'] }}
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Desktop: blue panel --}}
    <div class="hidden md:block absolute right-0 bottom-[10%] z-10 w-[55%] max-w-[780px] h-[504px] lg:h-[578px] overflow-visible pointer-events-none">
        <div class="absolute inset-0 bg-[#1172BA] rounded-l-[40px] shadow-lg"></div>
    </div>

    {{-- Desktop: product + badges --}}
    <div class="hidden md:block absolute z-20 right-0 bottom-0 w-[50%] lg:w-[52%] max-w-[720px] overflow-visible">
        <div class="parallax-self relative w-full drop-shadow-2xl overflow-visible" data-reveal="right" data-parallax="0.14">
            <img
                src="{{ asset('src/images/section 7/produk.png') }}"
                alt="Produk Evomi"
                class="w-full h-auto object-contain"
            >
            @foreach ($badges as $badge)
                <a
                    href="{{ route('belanja') }}"
                    aria-label="Lihat detail {{ $badge['title'] }}"
                    class="s7-badge-hit absolute -translate-x-1/2 -translate-y-[calc(100%+10px)] px-3.5 lg:px-4 py-1 bg-white rounded-full shadow-md whitespace-nowrap z-30 font-bold text-[16px]"
                    style="color: {{ $badge['color'] }}; left: {{ $badge['left'] }}; top: {{ $badge['top'] }};"
                >
                    {{ $badge['text'] }}
                </a>
            @endforeach
        </div>
    </div>

    {{-- Bottom waves --}}
    <div class="absolute bottom-0 md:bottom-8 left-0 w-full z-[5] leading-[0] pointer-events-none overflow-hidden">
        <div class="relative w-[140%] md:w-[120%] -ml-[15%] md:-ml-[8%] h-[70px] sm:h-[100px] md:h-[180px] lg:h-[200px]">
            <img
                src="{{ asset('src/images/section 7/vector-diseksi7-1.svg') }}"
                alt=""
                class="s7-wave-1 absolute bottom-0 left-0 w-full h-full object-fill origin-bottom"
            >
            <img
                src="{{ asset('src/images/section 7/vector-diseksi7-2.svg') }}"
                alt=""
                class="s7-wave-2 absolute bottom-0 left-0 w-full h-full object-fill origin-bottom"
            >
        </div>
    </div>

    <div class="hidden md:block h-[568px] lg:h-[633px]" aria-hidden="true"></div>
</section>
