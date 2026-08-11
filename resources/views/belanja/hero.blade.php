@php
    $cms = \App\Support\CmsStorefront::forPage('belanja');
    $headline1 = $cms->get('hero', 'headline_1', evomi_l('Koleksi', 'Evomi'));
    $headline2 = $cms->get('hero', 'headline_2', evomi_l('Aroma', 'Scent'));
    $headline3 = $cms->get('hero', 'headline_3', evomi_l('Evomi', 'Collection'));
    $color1 = $cms->get('hero', 'headline_1_color', '#5EA14A');
    $color2 = $cms->get('hero', 'headline_2_color', '#DD74A5');
    $color3 = $cms->get('hero', 'headline_3_color', '#1172BA');
    $starUrl = $cms->image('hero', 'star_icon', asset('src/images/belanja/deco/title-star.svg'));
@endphp

<section class="belanja-hero w-full flex flex-col justify-center items-center text-center px-4 pt-10 md:pt-[48px] pb-1 md:pb-2 relative bg-[#f6f6f6]">
    <div class="max-w-5xl mx-auto flex items-center justify-center gap-2 md:gap-3.5 relative z-10">
        <img
            src="{{ $starUrl }}"
            alt=""
            class="belanja-hero__star w-[22px] h-[22px] md:w-7 md:h-7 shrink-0 select-none"
            width="28"
            height="28"
            aria-hidden="true"
        >
        <h1 class="font-nohemi text-[26px] sm:text-[30px] md:text-[32px] font-semibold leading-[44px] tracking-tight whitespace-nowrap">
            <span style="color: {{ $color1 }}">{{ $headline1 }}</span><span class="inline-block w-[0.28em]" aria-hidden="true"></span><span style="color: {{ $color2 }}">{{ $headline2 }}</span><span class="inline-block w-[0.28em]" aria-hidden="true"></span><span style="color: {{ $color3 }}">{{ $headline3 }}</span>
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
