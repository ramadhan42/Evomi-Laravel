@php
    /** @var \App\Support\CmsStorefront $cms */
    $cms = $cms ?? \App\Support\CmsStorefront::forPage('beranda');
    $img = $cms->image('fourth', 'image', '/src/images/section 4/thanks-card.png');
@endphp
<section class="relative z-0 bg-white w-full overflow-hidden px-[5%] pb-[5%] pt-8 sm:pt-10 md:pt-12" style="{{ $cms->sectionGapStyleAttr('fourth') }}">
    <img
        src="{{ $img }}"
        alt="Evomi Thanks Card"
        class="block w-full h-auto max-w-6xl mx-auto"
    >
</section>
