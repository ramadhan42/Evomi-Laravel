<section class="relative z-10 bg-[#FAFAFA] md:bg-white flex flex-col items-center text-center w-full pt-10 sm:pt-12 md:pt-14 pb-14 md:pb-16 px-4 sm:px-6 md:px-8 overflow-hidden">
    {{-- Corner decorations --}}
    <div class="absolute top-[12%] left-0 z-0 pointer-events-none w-[40px] sm:w-[70px] md:w-[100px] -translate-x-[20%] md:-translate-x-[15%]">
        <div class="parallax-self" data-parallax="0.22" data-reveal="left">
            <img src="{{ asset('src/images/section 5/purpose.png') }}" alt="" class="object-contain opacity-90 w-full h-auto">
        </div>
    </div>
    <div class="absolute top-[12%] right-0 z-0 pointer-events-none w-[40px] sm:w-[70px] md:w-[100px] translate-x-[20%] md:translate-x-[15%]">
        <div class="parallax-self" data-parallax="0.22" data-reveal="right">
            <img src="{{ asset('src/images/section 5/sweet.png') }}" alt="" class="object-contain opacity-90 w-full h-auto">
        </div>
    </div>
    <div class="absolute bottom-[22%] left-0 z-0 pointer-events-none w-[40px] sm:w-[70px] md:w-[100px] -translate-x-[20%] md:-translate-x-[15%]">
        <div class="parallax-self" data-parallax="0.18" data-reveal="left">
            <img src="{{ asset('src/images/section 5/rebel.png') }}" alt="" class="object-contain opacity-90 w-full h-auto">
        </div>
    </div>
    <div class="absolute bottom-[22%] right-0 z-0 pointer-events-none w-[40px] sm:w-[70px] md:w-[100px] translate-x-[20%] md:translate-x-[15%]">
        <div class="parallax-self" data-parallax="0.18" data-reveal="right">
            <img src="{{ asset('src/images/section 5/peaceful.png') }}" alt="" class="object-contain opacity-90 w-full h-auto">
        </div>
    </div>

    <div class="relative z-10 mb-6 md:mb-10 parallax-self" data-reveal data-parallax="0.05">
        <h2 class="text-[26px] sm:text-[32px] md:text-[38px] mb-2 md:mb-3 leading-tight font-bold">
            <span class="text-[#1172BA]">Khas </span>
            <span class="text-[#FF8A84]">Evomi</span>
        </h2>
        <p class="text-[12px] sm:text-[14px] md:text-[16px] text-[#5D5D5D] max-w-xl mx-auto px-2 leading-relaxed">
            Empat karakter aroma yang mewakili sisi berbeda dari dirimu.
        </p>
    </div>

    @php
        $products = [
            [
                'id' => 1,
                'title' => 'Purpose Prestige',
                'badge' => 'Optimis',
                'desc' => 'Aroma yang merefleksikan ketenangan dan kejelasan tujuan.',
                'price' => 'Rp189.000',
                'img' => 'purpose-prestige.png',
                'imgBg' => 'bg-[#1172BA]',
                'cardBg' => 'bg-[#9CD6FF]',
                'text' => 'text-[#1172BA]',
                'descColor' => 'text-[#1172BAB2]',
                'border' => 'border-[#1172BA]',
                'btn' => 'bg-[#1172BA]',
            ],
            [
                'id' => 2,
                'title' => 'Peaceful Calm',
                'badge' => 'Damai',
                'desc' => 'Aroma menenangkan yang menyatu dengan diri.',
                'price' => 'Rp199.000',
                'img' => 'peaceful-calm.png',
                'imgBg' => 'bg-[#5EA14A]',
                'cardBg' => 'bg-[#C6F5B8]',
                'text' => 'text-[#5EA14A]',
                'descColor' => 'text-[#5EA14A]',
                'border' => 'border-[#5EA14A]',
                'btn' => 'bg-[#5EA14A]',
            ],
            [
                'id' => 3,
                'title' => 'Rebel Brave',
                'badge' => 'Berani',
                'desc' => 'Keberanian dan semangat untuk mengekspresikan diri.',
                'price' => 'Rp179.000',
                'img' => 'rabel-brave.png',
                'imgBg' => 'bg-[#E33D35]',
                'cardBg' => 'bg-[#FFBBB5]',
                'text' => 'text-[#E33D35]',
                'descColor' => 'text-[#E33D35]',
                'border' => 'border-[#E33D35]',
                'btn' => 'bg-[#E33D35]',
            ],
            [
                'id' => 4,
                'title' => 'Sweet Shy',
                'badge' => 'Manis',
                'desc' => 'Aroma menenangkan yang menyatu dengan diri.',
                'price' => 'Rp189.000',
                'img' => 'sweet-shy.png',
                'imgBg' => 'bg-[#DD74A5]',
                'cardBg' => 'bg-[#F5D7E7]',
                'text' => 'text-[#DD74A5]',
                'descColor' => 'text-[#DD74A5]',
                'border' => 'border-[#DD74A5]',
                'btn' => 'bg-[#DD74A5]',
            ],
        ];
    @endphp

    <div class="relative z-10 w-full max-w-[1100px] grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-5 md:gap-6 lg:gap-8 mb-8 md:mb-12 px-0 sm:px-2">
        @foreach ($products as $product)
            <a
                href="{{ route('belanja') }}"
                class="product-card group relative w-full max-w-[260px] mx-auto rounded-[18px] md:rounded-[24px] shadow-sm hover:shadow-xl transition-[box-shadow] duration-300 ease-out overflow-hidden flex flex-col border-2 {{ $product['border'] }} hover:z-20 parallax-self"
                data-reveal
                data-reveal-delay="{{ number_format($loop->index * 0.12, 2, '.', '') }}"
                data-parallax="0.1"
            >
                <div class="relative w-full aspect-[5/4.4] md:aspect-[4/3.75] flex flex-col items-center justify-end overflow-visible {{ $product['imgBg'] }}">
                    <div class="absolute top-2.5 left-2.5 md:top-3.5 md:left-3.5 z-20">
                        <span class="bg-white px-2.5 py-0.5 md:px-3 md:py-1 rounded-full text-[10px] md:text-[12px] shadow-sm font-bold {{ $product['text'] }} transition-transform duration-300 ease-out group-hover:-translate-y-0.5">
                            {{ $product['badge'] }}
                        </span>
                    </div>
                    <div class="relative w-full flex justify-center items-end translate-y-[10%] md:translate-y-[12%] z-10 pb-0 pointer-events-none">
                        <img
                            src="{{ asset('src/images/section 5/' . $product['img']) }}"
                            alt="{{ $product['title'] }}"
                            class="object-contain drop-shadow-xl w-[78%] sm:w-[80%] md:w-[82%] transition-transform duration-500 ease-[cubic-bezier(0.22,1,0.36,1)] group-hover:-translate-y-2 group-hover:scale-[1.05]"
                        >
                    </div>
                </div>
                <div class="relative p-3 sm:p-3.5 md:p-4 flex flex-col flex-grow text-left {{ $product['cardBg'] }} z-20 pt-5 md:pt-6">
                    <h3 class="text-[13px] sm:text-[14px] md:text-[15px] lg:text-[16px] mb-1 md:mb-1.5 font-bold {{ $product['text'] }} leading-tight">
                        {{ $product['title'] }}
                    </h3>
                    <p class="text-[10px] md:text-[11px] mb-3 md:mb-4 line-clamp-2 leading-snug font-medium {{ $product['descColor'] }}">
                        {{ $product['desc'] }}
                    </p>
                    <div class="flex justify-between items-center mt-auto gap-2">
                        <span class="text-[11px] md:text-[12px] font-bold {{ $product['text'] }}">{{ $product['price'] }}</span>
                        <span class="w-6 h-6 md:w-7 md:h-7 rounded-full flex justify-center items-center text-white shrink-0 transition-[background-color,box-shadow] duration-300 group-hover:shadow-md {{ $product['btn'] }}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-3 h-3 pointer-events-none transition-transform duration-300 ease-out group-hover:translate-x-0.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                            </svg>
                        </span>
                    </div>
                </div>
            </a>
        @endforeach
    </div>

    <a
        href="{{ route('belanja') }}"
        class="beranda-cta group relative z-10 overflow-hidden inline-flex items-center justify-center gap-2 md:gap-3 bg-[#1172BA] text-white text-[13px] md:text-[14px] px-7 py-2.5 md:px-10 md:py-3 rounded-full transition-[background-color,box-shadow] duration-200 hover:bg-[#0e5d99] hover:shadow-lg active:brightness-95 shadow-md font-bold"
        data-reveal
        data-reveal-delay="0.1"
    >
        <span
            class="pointer-events-none absolute inset-0 rounded-full bg-[linear-gradient(120deg,transparent_0%,rgba(255,255,255,0.22)_45%,transparent_70%)] -translate-x-[120%] transition-transform duration-500 ease-[cubic-bezier(0.22,1,0.36,1)] group-hover:translate-x-[120%]"
            aria-hidden="true"
        ></span>
        <img src="{{ asset('src/images/section 5/star-medium.png') }}" alt="" class="relative w-4 h-4 md:w-[19px] md:h-[19px] brightness-0 invert transition-transform duration-300 ease-out group-hover:-rotate-12 group-hover:scale-110">
        <span class="relative">Lihat Koleksi</span>
    </a>
</section>
