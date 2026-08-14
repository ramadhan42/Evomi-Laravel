@php
    $cms = \App\Support\CmsStorefront::forPage('belanja');
    $headline1 = $cms->textLines('hero', 'headline_1', evomi_l('Koleksi', 'Evomi'), 1);
    $headline2 = $cms->textLines('hero', 'headline_2', evomi_l('Aroma', 'Scent'), 1);
    $headline3 = $cms->textLines('hero', 'headline_3', evomi_l('Evomi', 'Collection'), 1);
    $color1 = $cms->get('hero', 'headline_1_color', '#5EA14A');
    $color2 = $cms->get('hero', 'headline_2_color', '#DD74A5');
    $color3 = $cms->get('hero', 'headline_3_color', '#1172BA');
    $starUrl = $cms->image('hero', 'star_icon', asset('src/images/belanja/deco/title-star.svg'));
@endphp

<section class="belanja-hero w-full flex flex-col justify-center items-center text-center px-4 pt-0 pb-3 md:pb-4 relative bg-transparent" data-belanja-enter="up">
    <div class="max-w-5xl mx-auto flex items-center justify-center gap-2 md:gap-3.5 relative z-10">
        <img
            src="{{ $starUrl }}"
            alt=""
            class="belanja-hero__star w-[22px] h-[22px] md:w-7 md:h-7 shrink-0 select-none"
            width="28"
            height="28"
            aria-hidden="true"
        >
        <h1 class="belanja-hero__title font-nohemi font-semibold leading-[44px] tracking-tight whitespace-nowrap">
            <span class="cms-fs" style="color: {{ $color1 }}; {{ $cms->fontInline('hero', 'headline_1', '600') }}">{{ $headline1 }}</span><span class="inline-block w-[0.28em]" aria-hidden="true"></span><span class="cms-fs" style="color: {{ $color2 }}; {{ $cms->fontInline('hero', 'headline_2', '600') }}">{{ $headline2 }}</span><span class="inline-block w-[0.28em]" aria-hidden="true"></span><span class="cms-fs" style="color: {{ $color3 }}; {{ $cms->fontInline('hero', 'headline_3', '600') }}">{{ $headline3 }}</span>
        </h1>
        <img
            src="{{ $starUrl }}"
            alt=""
            class="belanja-hero__star w-[22px] h-[22px] md:w-7 md:h-7 shrink-0 select-none"
            width="28"
            height="28"
            aria-hidden="true"
        >
    </div>
</section>
