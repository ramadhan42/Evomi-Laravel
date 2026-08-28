@php
    /** @var \App\Support\CmsStorefront $cms */
    $cms = $cms ?? \App\Support\CmsStorefront::forPage('beranda');
    $title1 = $cms->textLines('sixth', 'title_1', 'Packaging', 2);
    $title2 = $cms->textLines('sixth', 'title_2', 'Reveal', 2);
    $packagingImg = $cms->image('sixth', 'image', '/src/images/section 6/packaging.webp');
    $marqueeText = $cms->textLines('sixth', 'marquee_text', 'Every Version of Me', 1);
    $labelBase = 'flex items-center gap-1 whitespace-pre-line text-left text-white pointer-events-none';
    $labels = [
        'purpose' => [
            'text' => $cms->textLines('sixth', 'label1', "Purpose\nPrestige", 2),
            'icon' => asset('src/images/section 6/purpose.webp'),
            'style' => $cms->fontInline('sixth', 'label1', '500'),
        ],
        'rebel' => [
            'text' => $cms->textLines('sixth', 'label2', "Rebel\nBrave", 2),
            'icon' => asset('src/images/section 6/rabel.webp'),
            'style' => $cms->fontInline('sixth', 'label2', '500'),
        ],
        'peaceful' => [
            'text' => $cms->textLines('sixth', 'label3', "Peaceful\nCalm", 2),
            'icon' => asset('src/images/section 6/peaceful.webp'),
            'style' => $cms->fontInline('sixth', 'label3', '500'),
        ],
        'sweet' => [
            'text' => $cms->textLines('sixth', 'label4', "Sweet\nShy", 2),
            'icon' => asset('src/images/section 6/sweetshy.webp'),
            'style' => $cms->fontInline('sixth', 'label4', '500'),
        ],
    ];
    $marqueeIcons = [
        asset('src/images/section 1/purpose.webp'),
        asset('src/images/section 1/peaceful.webp'),
        asset('src/images/section 1/rab.webp'),
        asset('src/images/section 1/sweetshy.webp'),
    ];
@endphp
<section class="relative z-0 bg-[#1172BA] flex flex-col items-center justify-center pt-10 pb-24 md:pt-8 md:pb-28 overflow-hidden select-none w-full" style="{{ $cms->sectionGapStyleAttr('sixth', ['hx_m' => '16px', 'hx_d' => '24px', 'vy_m' => '12px', 'vy_d' => '20px']) }}">
    <div class="relative z-30 flex items-center justify-center gap-2 md:gap-3 text-center px-3 sm:px-4 py-2 mb-3 md:top-12 md:mb-19 parallax-self" data-reveal data-parallax="0.05">
        <h2 class="text-[24px] sm:text-[28px] md:text-[42px] leading-tight font-bold">
            <span class="cms-fs cms-lines text-white" style="{{ $cms->fontInline('sixth', 'title_1', '700') }}">{{ $title1 }}</span>
            <span class="cms-fs cms-lines text-[#A5E194]" style="{{ $cms->fontInline('sixth', 'title_2', '700') }}"> {{ $title2 }}</span>
        </h2>
        <img
            src="{{ asset('src/images/section 6/star-medium.webp') }}"
            alt=""
            class="w-[14px] h-[14px] md:w-[24px] md:h-[24px] object-contain brightness-0 invert shrink-0"
        >
    </div>

    <div class="relative w-full max-w-[100vw] flex flex-col items-center justify-center px-2 sm:px-3 md:px-2 py-1 md:py-2 translate-y-[3%] md:translate-y-0">
        <div class="absolute left-0 top-1/2 -translate-y-1/2 w-[12%] max-w-[48px] sm:max-w-[72px] md:w-auto md:max-w-[200px] lg:max-w-none z-0 pointer-events-none opacity-70 md:opacity-100">
            <img
                src="{{ asset('src/images/section 6/frame-kiri.webp') }}"
                alt=""
                class="parallax-self w-full h-auto object-contain"
                data-reveal="left"
                data-parallax="0.2"
            >
        </div>
        <div class="absolute right-0 top-1/2 -translate-y-1/2 w-[12%] max-w-[48px] sm:max-w-[72px] md:w-auto md:max-w-[200px] lg:max-w-none z-0 pointer-events-none opacity-70 md:opacity-100">
            <img
                src="{{ asset('src/images/section 6/frame-kanan.webp') }}"
                alt=""
                class="parallax-self w-full h-auto object-contain"
                data-reveal="right"
                data-parallax="0.2"
            >
        </div>

        <div class="hidden md:flex absolute top-17 left-8 lg:left-35 w-full px-12 lg:px-60 z-30 justify-between items-center text-white text-lg pointer-events-none">
            <div
                class="{{ $labelBase }} gap-1.5 translate-x-[calc(-40px-2%)] lg:translate-x-[calc(-70px-2%)] translate-y-[calc(-40px-3%)] lg:translate-y-[calc(-72px-3%)]"
                data-reveal
                data-reveal-delay="0.3"
            >
                <span class="cms-fs text-[16px] leading-tight whitespace-pre-line" style="{{ $labels['purpose']['style'] }}">{{ $labels['purpose']['text'] }}</span>
                <img src="{{ $labels['purpose']['icon'] }}" alt="Purpose" class="w-[24px] h-[24px] object-contain">
            </div>

            <div
                class="{{ $labelBase }} gap-1.5 mr-8 lg:mr-80 translate-x-[calc(-80px+13%)] lg:translate-x-[calc(-245px+13%)] translate-y-[calc(-30px-2%)] lg:translate-y-[calc(-58px-2%)]"
                data-reveal
                data-reveal-delay="0.4"
            >
                <span class="cms-fs text-[16px] leading-tight whitespace-pre-line" style="{{ $labels['rebel']['style'] }}">{{ $labels['rebel']['text'] }}</span>
                <img src="{{ $labels['rebel']['icon'] }}" alt="Rabel" class="w-[24px] h-[24px] object-contain">
            </div>
        </div>

        <div class="relative z-20 w-full max-w-[min(92vw,340px)] sm:max-w-[400px] md:max-w-[800px] lg:max-w-[1206px] mx-auto mt-[1%] mb-[3%] py-4 md:py-[25px]">
            <div class="relative w-full">
                <img
                    src="{{ $packagingImg }}"
                    alt="Evomi Packaging"
                    class="parallax-self w-full h-auto block object-contain drop-shadow-xl transition-[filter,drop-shadow] duration-500 ease-out md:hover:brightness-[1.03] md:hover:drop-shadow-2xl bg-transparent"
                    data-reveal="scale"
                    data-parallax="0.12"
                >

                <div class="md:hidden absolute inset-0 z-30 pointer-events-none">
                    <div class="{{ $labelBase }} absolute left-[0%] top-[-28%] bg-[#0A5A96]/55 backdrop-blur-[2px] px-1.5 py-1 rounded-full">
                        <span class="cms-fs text-[9px] leading-tight whitespace-pre-line" style="{{ $labels['purpose']['style'] }}">{{ $labels['purpose']['text'] }}</span>
                        <img src="{{ $labels['purpose']['icon'] }}" alt="" class="w-[11px] h-[11px] object-contain">
                    </div>
                    <div class="{{ $labelBase }} absolute right-[31%] top-[-27%] bg-[#0A5A96]/55 backdrop-blur-[2px] px-1.5 py-1 rounded-full">
                        <span class="cms-fs text-[9px] leading-tight whitespace-pre-line" style="{{ $labels['rebel']['style'] }}">{{ $labels['rebel']['text'] }}</span>
                        <img src="{{ $labels['rebel']['icon'] }}" alt="" class="w-[11px] h-[11px] object-contain">
                    </div>
                    <div class="{{ $labelBase }} absolute left-[17%] bottom-[-27%] bg-[#0A5A96]/55 backdrop-blur-[2px] px-1.5 py-1 rounded-full">
                        <span class="cms-fs text-[9px] leading-tight whitespace-pre-line" style="{{ $labels['peaceful']['style'] }}">{{ $labels['peaceful']['text'] }}</span>
                        <img src="{{ $labels['peaceful']['icon'] }}" alt="" class="w-[11px] h-[11px] object-contain">
                    </div>
                    <div class="{{ $labelBase }} absolute right-[8%] bottom-[-27%] bg-[#0A5A96]/55 backdrop-blur-[2px] px-1.5 py-1 rounded-full">
                        <span class="cms-fs text-[9px] leading-tight whitespace-pre-line" style="{{ $labels['sweet']['style'] }}">{{ $labels['sweet']['text'] }}</span>
                        <img src="{{ $labels['sweet']['icon'] }}" alt="" class="w-[11px] h-[11px] object-contain">
                    </div>
                </div>
            </div>
        </div>

        <div class="hidden md:flex absolute bottom-18 left-8 lg:left-20 w-full px-12 lg:px-100 z-30 justify-between items-center text-white text-lg translate-x-[15px] pointer-events-none">
            <div
                class="{{ $labelBase }} gap-1.5 translate-x-[calc(2rem-12vw)] lg:translate-x-[calc(8.75rem-12vw)] translate-y-[calc(30px-4vh)] lg:translate-y-[calc(52px-4vh)]"
                data-reveal
                data-reveal-delay="0.5"
            >
                <span class="cms-fs text-[16px] leading-tight whitespace-pre-line" style="{{ $labels['peaceful']['style'] }}">{{ $labels['peaceful']['text'] }}</span>
                <img src="{{ $labels['peaceful']['icon'] }}" alt="Peaceful" class="w-[24px] h-[24px] object-contain">
            </div>

            <div
                class="{{ $labelBase }} gap-1.5 mr-4 translate-x-[calc(-20px-2%)] lg:translate-x-[calc(-60px-2%)] translate-y-[calc(28px+9%-4vh)] lg:translate-y-[calc(48px+9%-4vh)]"
                data-reveal
                data-reveal-delay="0.6"
            >
                <span class="cms-fs text-[16px] leading-tight whitespace-pre-line" style="{{ $labels['sweet']['style'] }}">{{ $labels['sweet']['text'] }}</span>
                <img src="{{ $labels['sweet']['icon'] }}" alt="Sweet" class="w-[24px] h-[24px] object-contain">
            </div>
        </div>
    </div>

    <div class="absolute bottom-[7%] md:bottom-10 left-0 w-full overflow-hidden py-2.5 md:py-4 border-y border-white/10 z-40 bg-[#0071BC]">
        {{-- Exactly 2 equal halves + trailing pad = gap → seamless -50% loop (40s). --}}
        <div class="animate-marquee flex items-center">
            @for ($dup = 0; $dup < 2; $dup++)
                <div
                    class="marquee-group gap-4 md:gap-8 pr-4 md:pr-8"
                    @if ($dup === 1) aria-hidden="true" @endif
                >
                    @for ($i = 0; $i < 2; $i++)
                        @foreach ($marqueeIcons as $icon)
                            <span class="cms-fs text-[11px] md:text-[14px] whitespace-nowrap text-white" style="{{ $cms->fontInline('sixth', 'marquee_text', '500') }}">{{ $marqueeText }}</span>
                            <div class="relative w-[14px] h-[14px] md:w-[25px] md:h-[25px] shrink-0">
                                <img src="{{ $icon }}" alt="" class="w-full h-full object-contain">
                            </div>
                        @endforeach
                    @endfor
                </div>
            @endfor
        </div>
    </div>
</section>
