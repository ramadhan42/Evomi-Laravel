@php
    /** @var \App\Support\CmsStorefront $cms */
    $cms = $cms ?? \App\Support\CmsStorefront::forPage('beranda');
    $isEn = \App\Support\StorefrontText::isEn();
    if ($isEn) {
        $en1 = $cms->richText('seventh', 'en_l1', 'Find your', 2);
        $en2 = $cms->richText('seventh', 'en_l2', 'scent by', 2);
        $en3 = $cms->richText('seventh', 'en_l3', 'playing the ', 2);
        $en4 = $cms->richText('seventh', 'en_l4', 'quiz', 2);
    } else {
        $hl1 = $cms->richText('seventh', 'headline_1', 'Temukan', 2);
        $hl2 = $cms->richText('seventh', 'headline_2', 'aromamu', 2);
        $hl3 = $cms->richText('seventh', 'headline_3', 'dengan', 2);
        $hl4 = $cms->richText('seventh', 'headline_4', 'bermain', 2);
        $hl5 = $cms->richText('seventh', 'headline_5', 'kuis', 2);
    }
    $cta = $cms->richText('seventh', 'cta_label', evomi_l('Temukan Aromamu', 'Find Your Scent'), 1);
    $productImg = $cms->image('seventh', 'product_image', '/src/images/section 7/produk.webp');
    $badges = [];
    for ($i = 1; $i <= 4; $i++) {
        $defaults = [
            1 => ['text' => 'Prestige', 'title' => 'Purpose Prestige', 'color' => '#5CB2ED', 'left' => '61%', 'top' => '33%'],
            2 => ['text' => 'Calm', 'title' => 'Peaceful Calm', 'color' => '#5EA14A', 'left' => '82%', 'top' => '15%'],
            3 => ['text' => 'Rebel', 'title' => 'Rebel Brave', 'color' => '#E33D35', 'left' => '26%', 'top' => '15%'],
            4 => ['text' => 'Sweet', 'title' => 'Sweet Shy', 'color' => '#DD74A5', 'left' => '47.5%', 'top' => '-3%'],
        ][$i];
        $badges[] = [
            'text' => $cms->richText('seventh', "label{$i}_text", $defaults['text'], 2),
            'title' => $cms->textLines('seventh', "label{$i}_title", $defaults['title'], 2),
            'color' => $cms->get('seventh', "label{$i}_color", $defaults['color']),
            'left' => $cms->get('seventh', "label{$i}_left_mobile", $defaults['left']) ?: $defaults['left'],
            'top' => $cms->get('seventh', "label{$i}_top_mobile", $defaults['top']) ?: $defaults['top'],
            'fsMobile' => $cms->get('seventh', "label{$i}_fs_mobile", '9px'),
            'fsDesktop' => $cms->get('seventh', "label{$i}_fs_desktop", '16px'),
            'style' => $cms->fontInline('seventh', "label{$i}_text", '700'),
        ];
    }
@endphp
<section class="relative bg-white flex flex-col md:block items-center justify-between px-5 sm:px-8 md:px-0 md:pl-16 lg:pl-24 md:pr-0 pt-12 pb-10 md:pt-20 md:pb-0 md:min-h-[677px] lg:min-h-[742px] overflow-hidden select-none" style="{{ $cms->sectionGapStyleAttr('seventh', ['hx_m' => '16px', 'hx_d' => '24px', 'vy_m' => '24px', 'vy_d' => '32px']) }}">
    <div class="absolute top-0 left-0 w-full overflow-hidden h-[15px] md:h-[23px] pointer-events-none z-10">
        <div class="flex w-max gap-[10px] md:gap-[15px] animate-slide-right">
            @for ($i = 0; $i < 80; $i++)
                <div class="w-[30px] h-[30px] md:w-[46px] md:h-[46px] bg-[#1172BA] rounded-full shrink-0 -mt-[15px] md:-mt-[23px]"></div>
            @endfor
        </div>
    </div>

    <div class="relative z-20 w-full md:w-auto md:absolute md:left-[calc(2.5rem+7%)] lg:left-[calc(4rem+7%)] md:top-1/2 md:-translate-y-1/2 md:max-w-[420px] lg:max-w-xl px-1 md:px-0 md:pl-0">
        <div class="parallax-self flex flex-col justify-center items-center text-center md:items-start md:text-left gap-6 md:gap-8" data-reveal data-parallax="0.06">
            <h2 class="text-[30px] sm:text-[36px] md:text-[48px] lg:text-[55px] leading-[1.12] font-semibold text-center md:text-left">
                @if ($isEn)
                    <span class="cms-fs cms-lines text-[#1172BA]" style="{{ $cms->fontInline('seventh', 'en_l1', '600') }}">{!! $en1 !!}</span><br>
                    <span class="cms-fs cms-lines text-[#DD74A5]" style="{{ $cms->fontInline('seventh', 'en_l2', '600') }}">{!! $en2 !!}</span><br>
                    <span class="cms-fs cms-lines text-[#1172BA]" style="{{ $cms->fontInline('seventh', 'en_l3', '600') }}">{!! $en3 !!}</span><span class="cms-fs cms-lines text-[#5EA14A]" style="{{ $cms->fontInline('seventh', 'en_l4', '600') }}">{!! $en4 !!}</span>
                @else
                    <span class="cms-fs cms-lines text-[#1172BA]" style="{{ $cms->fontInline('seventh', 'headline_1', '600') }}">{!! $hl1 !!}</span><br>
                    <span class="cms-fs cms-lines text-[#DD74A5]" style="{{ $cms->fontInline('seventh', 'headline_2', '600') }}">{!! $hl2 !!}</span><br>
                    <span class="cms-fs cms-lines text-[#1172BA]" style="{{ $cms->fontInline('seventh', 'headline_3', '600') }}">{!! $hl3 !!}</span><br>
                    <span class="cms-fs cms-lines text-[#1172BA]" style="{{ $cms->fontInline('seventh', 'headline_4', '600') }}">{!! $hl4 !!} </span><span class="cms-fs cms-lines text-[#5EA14A]" style="{{ $cms->fontInline('seventh', 'headline_5', '600') }}">{!! $hl5 !!}</span>
                @endif
            </h2>

            <a
                href="{{ route('kuis') }}"
                class="cms-fs group relative z-20 overflow-hidden self-center md:self-start text-[14px] md:text-[19px] text-white bg-[#1172BA] px-6 md:px-9 py-3 md:py-3.5 rounded-full shadow-md transition-[background-color,box-shadow] duration-200 hover:bg-[#0e5d99] hover:shadow-lg active:brightness-95 inline-flex items-center gap-2 md:gap-2.5 font-semibold"
                style="{{ $cms->fontInline('seventh', 'cta_label', '600') }}"
            >
                <span
                    class="pointer-events-none absolute inset-0 rounded-full bg-[linear-gradient(120deg,transparent_0%,rgba(255,255,255,0.22)_45%,transparent_70%)] -translate-x-[120%] transition-transform duration-500 ease-[cubic-bezier(0.22,1,0.36,1)] group-hover:translate-x-[120%]"
                    aria-hidden="true"
                ></span>
                <span class="relative cms-lines">{!! $cta !!}</span>
                <svg class="relative w-4 h-4 md:w-[18px] md:h-[18px] pointer-events-none transition-transform duration-300 ease-out group-hover:translate-x-1" viewBox="0 0 19 19" fill="none" aria-hidden="true">
                    <path d="M3.80933 9.14282H14.476" stroke="currentColor" stroke-width="1.52381" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M9.14282 3.80957L14.4762 9.1429L9.14282 14.4762" stroke="currentColor" stroke-width="1.52381" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </a>
        </div>
    </div>

    {{-- Panel biru dan gambar produk sengaja tidak beranimasi: hanya teks yang
         bergerak. Parallax pada gambar juga dilepas supaya ia tetap menempel di
         dasar section, bukan ikut bergeser naik-turun saat halaman digulir. --}}
    <div class="relative z-20 w-full max-w-[380px] mx-auto mt-8 md:hidden">
        <div class="relative w-full h-[294px] bg-[#1172BA] rounded-[24px] shadow-lg overflow-visible">
            <div class="absolute bottom-0 right-0 w-[88%] overflow-visible">
                <div class="relative w-full drop-shadow-2xl overflow-visible">
                    <img src="{{ $productImg }}" alt="Evomi" class="w-full h-auto object-contain">
                    @foreach ($badges as $badge)
                        <a
                            href="{{ route('belanja') }}"
                            aria-label="{{ evomi_l('Lihat detail', 'View details') }} {{ $badge['title'] }}"
                            class="s7-badge-hit absolute -translate-x-1/2 -translate-y-[calc(100%+6px)] px-2.5 py-0.5 bg-white rounded-full shadow-md whitespace-nowrap z-30 font-bold"
                            style="color: {{ $badge['color'] }}; left: {{ $badge['left'] }}; top: {{ $badge['top'] }}; font-size: {{ $badge['fsMobile'] }}; {{ $badge['style'] }}"
                        >
                            {!! $badge['text'] !!}
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Tinggi panel ikut lebar viewport agar selalu proporsional dengan gambar produk --}}
    <div class="hidden md:block absolute right-0 bottom-[7.5%] z-10 w-[52%] max-w-[780px] h-[min(45vw,624px)] overflow-visible pointer-events-none">
        <div class="absolute inset-0 bg-[#1172BA] rounded-l-[40px] shadow-lg"></div>
    </div>

    <div class="hidden md:block absolute z-20 right-0 bottom-0 w-[50%] lg:w-[52%] max-w-[720px] overflow-visible">
        <div class="relative w-full drop-shadow-2xl overflow-visible">
            <img src="{{ $productImg }}" alt="Evomi" class="w-full h-auto object-contain">
            @foreach ($badges as $badge)
                <a
                    href="{{ route('belanja') }}"
                    aria-label="{{ evomi_l('Lihat detail', 'View details') }} {{ $badge['title'] }}"
                    class="s7-badge-hit absolute -translate-x-1/2 -translate-y-[calc(100%+10px)] px-3.5 lg:px-4 py-1 bg-white rounded-full shadow-md whitespace-nowrap z-30 font-bold"
                    style="color: {{ $badge['color'] }}; left: {{ $badge['left'] }}; top: {{ $badge['top'] }}; font-size: {{ $badge['fsDesktop'] }}; {{ $badge['style'] }}"
                >
                    {!! $badge['text'] !!}
                </a>
            @endforeach
        </div>
    </div>

    {{-- z-[15] menaruh wave di atas panel biru tapi tetap di bawah gambar produk (z-20) --}}
    <div class="absolute bottom-2 md:bottom-16 left-0 w-full z-[15] leading-[0] pointer-events-none overflow-hidden">
        <div class="relative w-[140%] md:w-[120%] -ml-[15%] md:-ml-[14%] h-[70px] sm:h-[100px] md:h-[180px] lg:h-[200px]">
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
