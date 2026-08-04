<section class="relative z-0 bg-[#1172BA] flex flex-col items-center justify-center pt-10 pb-24 md:pt-8 md:pb-28 overflow-hidden select-none w-full">
    {{-- Header --}}
    <div class="relative z-30 flex items-center justify-center gap-2 md:gap-3 text-center px-3 sm:px-4 py-2 mb-3 md:top-12 md:mb-19 parallax-self" data-reveal data-parallax="0.05">
        <h2 class="text-[24px] sm:text-[28px] md:text-[42px] leading-tight font-bold">
            <span class="text-white">Packaging</span>
            <span class="text-[#A5E194]"> Reveal</span>
        </h2>
        <img
            src="{{ asset('src/images/section 6/star-medium.png') }}"
            alt=""
            class="w-[14px] h-[14px] md:w-[24px] md:h-[24px] object-contain brightness-0 invert shrink-0"
        >
    </div>

    @php
        $labelBase = 'flex items-center gap-1 whitespace-pre-line text-left text-white pointer-events-none';
        $labels = [
            'purpose' => ['text' => "Purpose\nPrestige", 'icon' => 'purpose.png'],
            'rebel' => ['text' => "Rebel\nBrave", 'icon' => 'rabel.png'],
            'peaceful' => ['text' => "Peaceful\nCalm", 'icon' => 'peaceful.png'],
            'sweet' => ['text' => "Sweet\nShy", 'icon' => 'sweetshy.png'],
        ];
        $marqueeIcons = [
            'section 1/purpose.png',
            'section 1/peaceful.png',
            'section 1/rab.png',
            'section 1/sweetshy.png',
        ];
    @endphp

    <div class="relative w-full max-w-[100vw] flex flex-col items-center justify-center px-2 sm:px-3 md:px-2 py-1 md:py-2 translate-y-[3%] md:translate-y-0">
        {{-- Side frames --}}
        <div class="absolute left-0 top-1/2 -translate-y-1/2 w-[12%] max-w-[48px] sm:max-w-[72px] md:w-auto md:max-w-[200px] lg:max-w-none z-0 pointer-events-none opacity-70 md:opacity-100">
            <img
                src="{{ asset('src/images/section 6/frame-kiri.png') }}"
                alt=""
                class="parallax-self w-full h-auto object-contain"
                data-reveal="left"
                data-parallax="0.2"
            >
        </div>
        <div class="absolute right-0 top-1/2 -translate-y-1/2 w-[12%] max-w-[48px] sm:max-w-[72px] md:w-auto md:max-w-[200px] lg:max-w-none z-0 pointer-events-none opacity-70 md:opacity-100">
            <img
                src="{{ asset('src/images/section 6/frame-kanan.png') }}"
                alt=""
                class="parallax-self w-full h-auto object-contain"
                data-reveal="right"
                data-parallax="0.2"
            >
        </div>

        {{-- Labels atas — desktop --}}
        <div class="hidden md:flex absolute top-17 left-8 lg:left-35 w-full px-12 lg:px-60 z-30 justify-between items-center text-white text-lg pointer-events-none">
            <div class="{{ $labelBase }} gap-1.5 translate-x-[calc(-40px-2%)] lg:translate-x-[calc(-70px-2%)] translate-y-[calc(-40px-3%)] lg:translate-y-[calc(-72px-3%)]">
                <span class="text-[16px] leading-tight whitespace-pre-line font-medium">{{ $labels['purpose']['text'] }}</span>
                <img src="{{ asset('src/images/section 6/' . $labels['purpose']['icon']) }}" alt="" class="w-[24px] h-[24px] object-contain">
            </div>
            <div class="{{ $labelBase }} gap-1.5 mr-8 lg:mr-80 translate-x-[calc(-80px+13%)] lg:translate-x-[calc(-245px+13%)] translate-y-[calc(-30px-2%)] lg:translate-y-[calc(-58px-2%)]">
                <span class="text-[16px] leading-tight whitespace-pre-line font-medium">{{ $labels['rebel']['text'] }}</span>
                <img src="{{ asset('src/images/section 6/' . $labels['rebel']['icon']) }}" alt="" class="w-[24px] h-[24px] object-contain">
            </div>
        </div>

        {{-- Packaging image + mobile labels --}}
        <div class="relative z-20 w-full max-w-[min(92vw,340px)] sm:max-w-[400px] md:max-w-[800px] lg:max-w-[1206px] mx-auto mt-[1%] mb-[3%] py-4 md:py-[25px]">
            <div class="relative w-full">
                <img
                    src="{{ asset('src/images/section 6/packaging.png') }}"
                    alt="Evomi Packaging"
                    class="parallax-self w-full h-auto block object-contain drop-shadow-xl transition-[filter,drop-shadow] duration-500 ease-out md:hover:brightness-[1.03] md:hover:drop-shadow-2xl bg-transparent"
                    data-reveal="scale"
                    data-parallax="0.12"
                >

                {{-- Labels mobile --}}
                <div class="md:hidden absolute inset-0 z-30 pointer-events-none">
                    <div class="{{ $labelBase }} absolute left-[0%] top-[-28%] bg-[#0A5A96]/55 backdrop-blur-[2px] px-1.5 py-1 rounded-full">
                        <span class="text-[9px] leading-tight whitespace-pre-line font-medium">{{ $labels['purpose']['text'] }}</span>
                        <img src="{{ asset('src/images/section 6/' . $labels['purpose']['icon']) }}" alt="" class="w-[11px] h-[11px] object-contain">
                    </div>
                    <div class="{{ $labelBase }} absolute right-[31%] top-[-27%] bg-[#0A5A96]/55 backdrop-blur-[2px] px-1.5 py-1 rounded-full">
                        <span class="text-[9px] leading-tight whitespace-pre-line font-medium">{{ $labels['rebel']['text'] }}</span>
                        <img src="{{ asset('src/images/section 6/' . $labels['rebel']['icon']) }}" alt="" class="w-[11px] h-[11px] object-contain">
                    </div>
                    <div class="{{ $labelBase }} absolute left-[17%] bottom-[-27%] bg-[#0A5A96]/55 backdrop-blur-[2px] px-1.5 py-1 rounded-full">
                        <span class="text-[9px] leading-tight whitespace-pre-line font-medium">{{ $labels['peaceful']['text'] }}</span>
                        <img src="{{ asset('src/images/section 6/' . $labels['peaceful']['icon']) }}" alt="" class="w-[11px] h-[11px] object-contain">
                    </div>
                    <div class="{{ $labelBase }} absolute right-[8%] bottom-[-27%] bg-[#0A5A96]/55 backdrop-blur-[2px] px-1.5 py-1 rounded-full">
                        <span class="text-[9px] leading-tight whitespace-pre-line font-medium">{{ $labels['sweet']['text'] }}</span>
                        <img src="{{ asset('src/images/section 6/' . $labels['sweet']['icon']) }}" alt="" class="w-[11px] h-[11px] object-contain">
                    </div>
                </div>
            </div>
        </div>

        {{-- Labels bawah — desktop --}}
        <div class="hidden md:flex absolute bottom-18 left-8 lg:left-20 w-full px-12 lg:px-100 z-30 justify-between items-center text-white text-lg translate-x-[15px] pointer-events-none">
            <div class="{{ $labelBase }} gap-1.5 translate-x-[calc(2rem-12vw)] lg:translate-x-[calc(8.75rem-12vw)] translate-y-[calc(30px-4vh)] lg:translate-y-[calc(52px-4vh)]">
                <span class="text-[16px] leading-tight whitespace-pre-line font-medium">{{ $labels['peaceful']['text'] }}</span>
                <img src="{{ asset('src/images/section 6/' . $labels['peaceful']['icon']) }}" alt="" class="w-[24px] h-[24px] object-contain">
            </div>
            <div class="{{ $labelBase }} gap-1.5 mr-4 translate-x-[calc(-20px-2%)] lg:translate-x-[calc(-60px-2%)] translate-y-[calc(28px+9%-4vh)] lg:translate-y-[calc(48px+9%-4vh)]">
                <span class="text-[16px] leading-tight whitespace-pre-line font-medium">{{ $labels['sweet']['text'] }}</span>
                <img src="{{ asset('src/images/section 6/' . $labels['sweet']['icon']) }}" alt="" class="w-[24px] h-[24px] object-contain">
            </div>
        </div>
    </div>

    {{-- Marquee with character icons --}}
    <div class="absolute bottom-[7%] md:bottom-10 left-0 w-full overflow-hidden py-2.5 md:py-4 border-y border-white/10 z-40 bg-[#0071BC]">
        <div class="animate-marquee flex items-center gap-4 md:gap-8">
            @for ($i = 0; $i < 4; $i++)
                <div class="flex items-center gap-4 md:gap-8 shrink-0">
                    @foreach ($marqueeIcons as $icon)
                        <span class="text-[11px] md:text-[14px] whitespace-nowrap text-white font-medium">Every Version of Me</span>
                        <div class="relative w-[14px] h-[14px] md:w-[25px] md:h-[25px] shrink-0">
                            <img
                                src="{{ asset('src/images/' . $icon) }}"
                                alt=""
                                class="w-full h-full object-contain"
                            >
                        </div>
                    @endforeach
                </div>
            @endfor
        </div>
    </div>
</section>
