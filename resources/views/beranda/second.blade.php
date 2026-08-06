@php
    /** @var \App\Support\CmsStorefront $cms */
    $cms = $cms ?? \App\Support\CmsStorefront::forPage('beranda');
    $h1 = $cms->textLines('second', 'headline_1', 'Kenalan sama', 1);
    $h2 = $cms->textLines('second', 'headline_2', 'karakter ', 1);
    $h3 = $cms->textLines('second', 'headline_3', 'kita yuk!', 1);
    $cta = $cms->textLines('second', 'cta_label', 'Lihat Semua Karakter', 1);
    $colors = ['text-[#0D71BA]', 'text-[#5EA14A]', 'text-[#E33D35]', 'text-[#DD74A5]'];

    $characters = [];
    for ($i = 1; $i <= 4; $i++) {
        $lines = $cms->lines('second', "card{$i}_name", match ($i) {
            1 => "Purpose\nPrestige",
            2 => "Peaceful\nCalm",
            3 => "Rebel\nBrave",
            default => "Sweet\nShy",
        }, 2);
        if ($lines === []) {
            $lines = [''];
        }
        $characters[] = [
            'lines' => $lines,
            'img' => $cms->image('second', "card{$i}_image", match ($i) {
                1 => '/src/images/section 2/purpose-prestige.png',
                2 => '/src/images/section 2/peaceful-calm.png',
                3 => '/src/images/section 2/rabel-brave.png',
                default => '/src/images/section 2/sweet-shy.png',
            }),
            'title' => $cms->get('second', "card{$i}_title", trim(implode(' ', $lines))),
            'color' => $colors[$i - 1],
            'nameStyle' => $cms->fontInline('second', "card{$i}_name", '700'),
        ];
    }
@endphp
<section class="relative bg-white flex flex-col items-center text-center px-4 w-full overflow-x-hidden pb-10 md:pb-14" style="{{ $cms->sectionGapStyleAttr('second', ['hx_m' => '16px', 'hx_d' => '32px', 'vy_m' => '40px', 'vy_d' => '56px']) }}">
    {{-- Circle divider top --}}
    <div class="absolute top-0 left-0 w-full overflow-hidden h-[15px] md:h-[23px] pointer-events-none">
        <div class="flex w-max gap-[10px] md:gap-[15px] animate-slide-right">
            @for ($i = 0; $i < 80; $i++)
                <div class="w-[30px] h-[30px] md:w-[46px] md:h-[46px] bg-[#1172BA] rounded-full shrink-0 -mt-[15px] md:-mt-[23px]"></div>
            @endfor
        </div>
    </div>

    {{-- Stack: headline → karakter → CTA (jarak vertikal dari CMS) --}}
    <div class="s2-content-stack w-full flex flex-col items-center mt-14 md:mt-24 mb-10 md:mb-12">
        <h2 class="text-[20px] sm:text-[24px] md:text-[42px] leading-tight px-2 font-semibold parallax-self text-center" data-reveal data-parallax="0.06">
            <span class="cms-fs cms-lines text-[#0071BC]" style="{{ $cms->fontInline('second', 'headline_1', '600') }}">{{ $h1 }}</span>{{ ' ' }}
            <span class="cms-fs cms-lines text-[#FF8A84]" style="{{ $cms->fontInline('second', 'headline_2', '600') }}">{{ $h2 }}</span>{{ ' ' }}
            <span class="cms-fs cms-lines text-[#0071BC]" style="{{ $cms->fontInline('second', 'headline_3', '600') }}">{{ $h3 }}</span>
        </h2>

        <div class="s2-char-grid w-full max-w-4xl px-2">
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
                            src="{{ $char['img'] }}"
                            alt="{{ $char['title'] }}"
                            class="w-full h-full object-contain drop-shadow-sm"
                        >
                    </div>
                    <h3 class="s2-char-label cms-fs cms-lines font-heavy tracking-tight {{ $char['color'] }}" style="{{ $char['nameStyle'] }}">
                        @foreach ($char['lines'] as $line)
                            <span class="s2-char-line block">{{ $line }}</span>
                        @endforeach
                    </h3>
                </a>
            @endforeach
        </div>

        <a
            href="{{ route('belanja') }}"
            class="s2-cta-btn cms-fs group inline-flex items-center gap-1.5 md:gap-2 bg-[#0071BC] text-white text-[11px] md:text-[15px] px-5 md:px-7 py-2.5 md:py-3 rounded-full shadow-lg hover:bg-[#0062a3] hover:shadow-xl transition-all font-bold relative overflow-hidden"
            data-reveal
            data-reveal-delay="0.2"
            style="{{ $cms->fontInline('second', 'cta_label', '700') }}"
        >
            <span
                class="pointer-events-none absolute inset-0 rounded-full bg-[linear-gradient(120deg,transparent_0%,rgba(255,255,255,0.22)_45%,transparent_70%)] -translate-x-[120%] transition-transform duration-500 ease-[cubic-bezier(0.22,1,0.36,1)] group-hover:translate-x-[120%]"
                aria-hidden="true"
            ></span>
            <span class="relative cms-lines">{{ $cta }}</span>
            <svg class="s2-cta-icon relative w-3.5 h-3.5 md:w-4 md:h-4 transition-transform duration-300 group-hover:translate-x-1" viewBox="0 0 19 19" fill="none" aria-hidden="true">
                <path d="M3.8 9.1H14.5" stroke="#fff" stroke-width="1.5" stroke-linecap="round"/>
                <path d="M9.1 3.8L14.5 9.1L9.1 14.5" stroke="#fff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </a>
    </div>

    {{-- Circle divider bottom --}}
    <div class="absolute bottom-0 left-0 w-full overflow-hidden h-[15px] md:h-[23px] pointer-events-none">
        <div class="flex w-max gap-[10px] md:gap-[15px] animate-slide-right">
            @for ($i = 0; $i < 80; $i++)
                <div class="w-[30px] h-[30px] md:w-[46px] md:h-[46px] bg-[#1172BA] rounded-full shrink-0"></div>
            @endfor
        </div>
    </div>
</section>
