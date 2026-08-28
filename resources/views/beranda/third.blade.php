@php
    /** @var \App\Support\CmsStorefront $cms */
    $cms = $cms ?? \App\Support\CmsStorefront::forPage('beranda');
    $title1 = $cms->textLines('third', 'title_1', 'Brand', 2);
    $title2 = $cms->textLines('third', 'title_2', 'Value', 2);
    $tagline = $cms->textLines('third', 'tagline', 'Every Version of Me', 2);
    $defaults = [
        1 => [
            'title' => "Self\nAwareness",
            'desc' => 'Setiap aroma dirancang untuk merepresentasikan versi diri, emosi, dan karakter manusia yang berbeda, sehingga parfum menjadi medium ekspresi personal, bukan sekadar wewangian.',
            'icon' => '/src/images/section 3/star-medium.webp',
        ],
        2 => [
            'title' => "Environment\nFriendly",
            'desc' => 'Mengusung kepedulian terhadap lingkungan melalui pemanfaatan daur ulang tutup botol plastik menjadi bagian dari identitas produk, sebagai bentuk kontribusi kecil dalam mengurangi limbah plastik sekaligus menghadirkan nilai sustainability.',
            'icon' => '/src/images/section 3/peaceful-calm.webp',
        ],
        3 => [
            'title' => "Playful Design\nConcept",
            'desc' => 'Dikemas dengan pendekatan visual yang playful, ekspresif, dan dekat dengan generasi muda agar pengalaman menggunakan parfum terasa lebih personal dan menyenangkan.',
            'icon' => '/src/images/section 3/triangle.webp',
        ],
    ];
    $values = [];
    for ($i = 1; $i <= 3; $i++) {
        $values[] = [
            'title' => $cms->lines('third', "card{$i}_title", $defaults[$i]['title'], 2),
            'desc' => $cms->textLines('third', "card{$i}_desc", $defaults[$i]['desc'], 3),
            'icon' => $cms->image('third', "card{$i}_icon", $defaults[$i]['icon']),
            'titleStyle' => $cms->fontInline('third', "card{$i}_title", '600'),
            'descStyle' => $cms->fontInline('third', "card{$i}_desc", '500'),
        ];
    }
@endphp
<section class="relative z-10 bg-[#0071BC] flex flex-col items-center text-center w-full px-2 overflow-hidden pb-12 md:pb-14" style="{{ $cms->sectionGapStyleAttr('third', ['hx_m' => '40px', 'hx_d' => '56px', 'vy_m' => '40px', 'vy_d' => '56px']) }}">
    <div class="flex items-center justify-center gap-3 md:gap-[22px] mt-10 md:mt-14 mb-6 md:mb-[30px] parallax-self" data-reveal data-parallax="0.05">
        <h2 class="text-[28px] md:text-[42px] lg:text-[48px] leading-[1.08] font-semibold">
            <span class="cms-fs cms-lines text-white" style="{{ $cms->fontInline('third', 'title_1', '700') }}">{{ $title1 }}</span>
            <span class="cms-fs cms-lines text-[#A5E194]" style="{{ $cms->fontInline('third', 'title_2', '700') }}"> {{ $title2 }}</span>
        </h2>
        <div class="w-6 h-6 md:w-7 md:h-8 relative flex justify-center items-center pointer-events-none shrink-0">
            <img
                src="{{ asset('src/images/section 3/star-medium.webp') }}"
                alt=""
                class="w-full h-full object-contain brightness-0 invert"
            >
        </div>
    </div>

    <div class="flex justify-center w-full max-w-6xl mt-2 md:mt-4 mb-8 relative z-10">
        <div class="grid grid-cols-1 md:grid-cols-3 w-full px-4 sm:px-6 md:px-10 pt-6 md:pt-5 pb-6 md:pb-5 cms-gap-grid">
            @foreach ($values as $card)
                <div
                    class="flex flex-col parallax-self"
                    data-reveal
                    data-reveal-delay="{{ number_format($loop->index * 0.12, 2, '.', '') }}"
                    data-parallax="0.1"
                >
                    <h3 class="bv-card-title cms-fs text-white text-[18px] md:text-[22px] lg:text-[26px] mb-4 md:mb-6 text-left px-1 font-semibold" style="{{ $card['titleStyle'] }}">
                        @foreach ($card['title'] as $line)
                            <span class="bv-card-title-line">{{ $line }}</span>
                        @endforeach
                    </h3>
                    <div class="relative bg-white rounded-[24px] md:rounded-3xl p-5 sm:p-6 md:p-8 shadow-xl flex flex-col flex-grow transition-[box-shadow,filter] duration-300 ease-out hover:z-10 hover:shadow-2xl hover:brightness-[1.02]">
                        <div class="absolute -top-4 -right-2 md:-top-5 md:-right-5 w-[35px] md:w-[45px] h-[35px] md:h-[45px] z-20 flex justify-center items-center pointer-events-none">
                            <img
                                src="{{ $card['icon'] }}"
                                alt=""
                                class="object-contain drop-shadow-md w-full h-full"
                            >
                        </div>
                        <p class="cms-fs cms-lines text-left text-[#0071BC] text-[13px] md:text-[15px] leading-[1.5] font-parkinsans font-medium" style="{{ $card['descStyle'] }}">
                            {{ $card['desc'] }}
                        </p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <p class="cms-fs cms-lines text-white text-[20px] md:text-[28px] mt-4 md:mt-[10px] mb-6 md:mb-8 relative z-10 font-bold" style="{{ $cms->fontInline('third', 'tagline', '700') }}">
        {{ $tagline }}
    </p>
</section>
