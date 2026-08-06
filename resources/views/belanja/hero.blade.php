@php
    $cms = \App\Support\CmsStorefront::forPage('belanja');
    $headline1 = $cms->get('hero', 'headline_1', evomi_l('Koleksi', 'Evomi'));
    $headline2 = $cms->get('hero', 'headline_2', evomi_l('Aroma', 'Scent'));
    $headline3 = $cms->get('hero', 'headline_3', evomi_l('Evomi', 'Collection'));
    $subtitle = $cms->get('hero', 'subtitle', evomi_l('Pilih karakter aromamu, atau coba semuanya!', 'Pick your scent character, or try them all!'));
@endphp

<section class="w-full flex flex-col justify-center items-center text-center px-4 py-6 md:py-10 mb-0 min-h-[12vh] md:min-h-[20vh] relative overflow-hidden bg-[#1172BA]">
    <div class="max-w-5xl mx-auto flex flex-col items-center justify-center relative z-10">
        <h1 class="font-nohemi text-[26px] md:text-[38px] font-semibold leading-tight mb-1 md:mb-0 tracking-tight">
            <span class="text-white">{{ $headline1 }} </span>
            <span class="text-[#A5E194]">{{ $headline2 }} </span>
            <span class="text-white">{{ $headline3 }}</span>
        </h1>
        <p class="font-nohemi text-[12px] md:text-[14px] font-normal text-white max-w-3xl opacity-95 leading-relaxed">
            {{ $subtitle }}
        </p>
    </div>
</section>
