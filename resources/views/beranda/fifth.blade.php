@php
    /** @var \App\Support\CmsStorefront $cms */
    $cms = $cms ?? \App\Support\CmsStorefront::forPage('beranda');
    $title1 = $cms->textLines('fifth', 'title_1', 'Khas', 2);
    $title2 = $cms->textLines('fifth', 'title_2', 'Evomi', 2);
    $subtitle = $cms->textLines('fifth', 'subtitle', 'Empat karakter aroma yang mewakili sisi berbeda dari dirimu.', 3);
    $cta = $cms->textLines('fifth', 'cta_label', 'Lihat Koleksi', 1);

    // Palette & copy mengikuti Next.js FifthSection.tsx
    $nextProducts = [
        1 => [
            'imgBg' => '#1172BA',
            'cardBg' => '#9CD6FF',
            'text' => '#1172BA',
            'descColor' => '#1172BAB2',
            'badge' => 'Optimis',
            'title' => 'Purpose Prestige',
            'desc' => 'Aroma yang merefleksikan ketenangan dan kejelasan tujuan.',
            'price' => 'Rp189.000',
            'image' => '/src/images/section 5/purpose-prestige.webp',
        ],
        2 => [
            'imgBg' => '#5EA14A',
            'cardBg' => '#C6F5B8',
            'text' => '#5EA14A',
            'descColor' => '#5EA14A',
            'badge' => 'Damai',
            'title' => 'Peaceful Calm',
            'desc' => 'Aroma menenangkan yang menyatu dengan diri.',
            'price' => 'Rp199.000',
            'image' => '/src/images/section 5/peaceful-calm.webp',
        ],
        3 => [
            'imgBg' => '#E33D35',
            'cardBg' => '#FFBBB5',
            'text' => '#E33D35',
            'descColor' => '#E33D35',
            'badge' => 'Berani',
            'title' => 'Rebel Brave',
            'desc' => 'Keberanian dan semangat untuk mengekspresikan diri.',
            'price' => 'Rp179.000',
            'image' => '/src/images/section 5/rabel-brave.webp',
        ],
        4 => [
            'imgBg' => '#DD74A5',
            'cardBg' => '#F5D7E7',
            'text' => '#DD74A5',
            'descColor' => '#DD74A5',
            'badge' => 'Manis',
            'title' => 'Sweet Shy',
            'desc' => 'Aroma menenangkan yang menyatu dengan diri.',
            'price' => 'Rp189.000',
            'image' => '/src/images/section 5/sweet-shy.webp',
        ],
    ];

    $catalog = array_values(array_slice(array_values(\App\Support\BelanjaCatalog::all()), 0, 4));
    $products = [];
    for ($i = 1; $i <= 4; $i++) {
        $d = $nextProducts[$i];
        $cat = $catalog[$i - 1] ?? null;
        $products[] = [
            'id' => $cat['id'] ?? $i,
            'imgBg' => $d['imgBg'],
            'cardBg' => $d['cardBg'],
            'text' => $d['text'],
            'descColor' => $d['descColor'],
            'badge' => $cms->textLines('fifth', "card{$i}_badge", $d['badge'], 1),
            'title' => $cms->textLines('fifth', "card{$i}_title", $d['title'], 2),
            'desc' => $cms->textLines('fifth', "card{$i}_desc", $d['desc'], 3),
            'price' => $cms->textLines('fifth', "card{$i}_price", $d['price'], 1),
            'img' => $cms->image('fifth', "card{$i}_image", $d['image']),
        ];
    }
@endphp
{{-- UI selaras Next.js components/beranda/FifthSection.tsx --}}
<section class="relative z-10 bg-[#FAFAFA] md:bg-white flex flex-col items-center text-center w-full pt-10 sm:pt-12 md:pt-14 pb-14 md:pb-16 px-4 sm:px-6 md:px-8 overflow-hidden">
    {{-- Dekorasi sudut --}}
    <div class="absolute top-[12%] left-0 z-0 pointer-events-none w-[40px] sm:w-[70px] md:w-[100px] -translate-x-[20%] md:-translate-x-[15%]">
        <img src="{{ asset('src/images/section 5/purpose.webp') }}" alt="" class="object-contain opacity-90 w-full h-auto" width="100" height="100">
    </div>
    <div class="absolute top-[12%] right-0 z-0 pointer-events-none w-[40px] sm:w-[70px] md:w-[100px] translate-x-[20%] md:translate-x-[15%]">
        <img src="{{ asset('src/images/section 5/sweet.webp') }}" alt="" class="object-contain opacity-90 w-full h-auto" width="100" height="100">
    </div>
    <div class="absolute bottom-[22%] left-0 z-0 pointer-events-none w-[40px] sm:w-[70px] md:w-[100px] -translate-x-[20%] md:-translate-x-[15%]">
        <img src="{{ asset('src/images/section 5/rebel.webp') }}" alt="" class="object-contain opacity-90 w-full h-auto" width="100" height="100">
    </div>
    <div class="absolute bottom-[22%] right-0 z-0 pointer-events-none w-[40px] sm:w-[70px] md:w-[100px] translate-x-[20%] md:translate-x-[15%]">
        <img src="{{ asset('src/images/section 5/peaceful.webp') }}" alt="" class="object-contain opacity-90 w-full h-auto" width="100" height="100">
    </div>

    {{-- Judul --}}
    <div class="relative z-10 mb-6 md:mb-10" data-reveal>
        <h2 class="text-[26px] sm:text-[32px] md:text-[38px] mb-2 md:mb-3 leading-tight">
            <span class="cms-fs cms-lines text-[#1172BA]" style="{{ $cms->fontInline('fifth', 'title_1', '700') }}">{{ $title1 }}</span>{{ ' ' }}
            <span class="cms-fs cms-lines text-[#FF8A84]" style="{{ $cms->fontInline('fifth', 'title_2', '700') }}">{{ $title2 }}</span>
        </h2>
        <p class="cms-fs cms-lines text-[12px] sm:text-[14px] md:text-[16px] text-[#5D5D5D] max-w-xl mx-auto px-2 leading-relaxed" style="{{ $cms->fontInline('fifth', 'subtitle', '400') }}">
            {{ $subtitle }}
        </p>
    </div>

    {{-- Grid produk — 2 kolom mobile, 4 kolom desktop --}}
    <div class="relative z-10 w-full max-w-[1100px] grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-5 md:gap-6 lg:gap-8 mb-8 md:mb-12 px-0 sm:px-2">
        @foreach ($products as $product)
            @php $i = $loop->iteration; @endphp
            <button
                type="button"
                class="fifth-product-card group relative w-full max-w-[260px] mx-auto rounded-[18px] md:rounded-[24px] shadow-sm hover:shadow-xl transition-[box-shadow] duration-300 ease-out overflow-hidden flex flex-col border-2 hover:z-20 cursor-pointer text-left"
                style="border-color: {{ $product['text'] }}"
                data-reveal
                data-reveal-delay="{{ number_format($loop->index * 0.12, 2, '.', '') }}"
                onclick="window.evomiOpenProduct({{ (int) $product['id'] }})"
            >
                {{-- Area gambar --}}
                <div
                    class="relative w-full aspect-[5/4.4] md:aspect-[4/3.75] flex flex-col items-center justify-end overflow-visible"
                    style="background-color: {{ $product['imgBg'] }}"
                >
                    <div class="absolute top-2.5 left-2.5 md:top-3.5 md:left-3.5 z-20">
                        <span
                            class="cms-fs cms-lines inline-flex items-center bg-white px-2.5 py-1 md:px-3 md:py-1.5 rounded-full text-[10px] md:text-[12px] shadow-sm transition-transform duration-300 ease-out group-hover:-translate-y-0.5 leading-[1.3]"
                            style="color: {{ $product['text'] }}; {{ $cms->fontInline('fifth', "card{$i}_badge", '700') }}"
                        >{{ $product['badge'] }}</span>
                    </div>

                    <div class="relative w-full flex justify-center items-end translate-y-[10%] md:translate-y-[12%] z-10 pb-0 pointer-events-none">
                        <img
                            src="{{ $product['img'] }}"
                            alt="{{ $product['title'] }}"
                            width="500"
                            height="500"
                            class="object-contain drop-shadow-xl w-[78%] sm:w-[80%] md:w-[82%] transition-transform duration-500 ease-[cubic-bezier(0.22,1,0.36,1)] group-hover:-translate-y-2 group-hover:scale-[1.05]"
                        >
                    </div>
                </div>

                {{-- Area info --}}
                <div
                    class="relative p-3 sm:p-3.5 md:p-4 flex flex-col flex-grow text-left z-20 pt-5 md:pt-6"
                    style="background-color: {{ $product['cardBg'] }}"
                >
                    <h3
                        class="cms-fs cms-lines text-[13px] sm:text-[14px] md:text-[15px] lg:text-[16px] mb-1 md:mb-1.5 leading-tight"
                        style="color: {{ $product['text'] }}; {{ $cms->fontInline('fifth', "card{$i}_title", '700') }}"
                    >{{ $product['title'] }}</h3>
                    <p
                        class="cms-fs cms-lines text-[10px] md:text-[11px] mb-3 md:mb-4 line-clamp-2 leading-snug"
                        style="color: {{ $product['descColor'] }}; {{ $cms->fontInline('fifth', "card{$i}_desc", '500') }}"
                    >{{ $product['desc'] }}</p>

                    <div class="flex justify-between items-center mt-auto gap-2">
                        <span
                            class="cms-fs cms-lines text-[11px] md:text-[12px] leading-none"
                            style="color: {{ $product['text'] }}; {{ $cms->fontInline('fifth', "card{$i}_price", '700') }}"
                        >{{ $product['price'] }}</span>
                        <span
                            class="w-6 h-6 md:w-7 md:h-7 rounded-full flex justify-center items-center text-white shrink-0 transition-[background-color,box-shadow] duration-300 group-hover:shadow-md"
                            style="background-color: {{ $product['imgBg'] }}"
                            aria-hidden="true"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-3 h-3 pointer-events-none transition-transform duration-300 ease-out group-hover:translate-x-0.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                            </svg>
                        </span>
                    </div>
                </div>
            </button>
        @endforeach
    </div>

    {{-- CTA --}}
    <div class="relative z-10" data-reveal data-reveal-delay="0.1">
        <a
            href="{{ route('belanja') }}"
            class="beranda-cta cms-fs group relative overflow-hidden inline-flex items-center justify-center gap-2 md:gap-3 bg-[#1172BA] text-white text-[13px] md:text-[14px] px-7 py-2.5 md:px-10 md:py-3 rounded-full transition-[background-color,box-shadow] duration-200 hover:bg-[#0e5d99] hover:shadow-lg active:brightness-95 shadow-md"
            style="{{ $cms->fontInline('fifth', 'cta_label', '700') }}"
            data-soft-nav
        >
            <span
                class="pointer-events-none absolute inset-0 rounded-full bg-[linear-gradient(120deg,transparent_0%,rgba(255,255,255,0.22)_45%,transparent_70%)] -translate-x-[120%] transition-transform duration-500 ease-[cubic-bezier(0.22,1,0.36,1)] group-hover:translate-x-[120%]"
                aria-hidden="true"
            ></span>
            <span class="relative w-4 h-4 md:w-[19px] md:h-[19px] transition-transform duration-300 ease-out group-hover:-rotate-12 group-hover:scale-110 shrink-0">
                <img src="{{ asset('src/images/section 5/star-medium.webp') }}" alt="" class="w-full h-full object-contain brightness-0 invert pointer-events-none">
            </span>
            <span class="relative cms-lines">{{ $cta }}</span>
        </a>
    </div>
</section>
