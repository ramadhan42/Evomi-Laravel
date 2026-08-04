@php
    $products = $products ?? [];
@endphp

<section class="bg-white flex flex-col items-center text-center w-full pt-10 md:pt-10 pb-10 md:pb-10 px-2 md:px-4 relative overflow-hidden">
    {{-- Bubble divider (Next SecondSectionBelanja) --}}
    <div class="absolute top-0 left-0 w-full overflow-hidden h-[12px] md:h-[23px] pointer-events-none z-10">
        <div class="flex w-max gap-[6px] md:gap-[10px] animate-slide-right">
            @for ($i = 0; $i < 80; $i++)
                <div class="w-[24px] h-[24px] md:w-[46px] md:h-[46px] bg-[#1172BA] rounded-full flex-shrink-0 -mt-[12px] md:-mt-[23px]"></div>
            @endfor
        </div>
    </div>

    <div class="relative z-10 w-full max-w-5xl grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-5 md:gap-6 px-3 sm:px-4 py-5 mt-1">
        @foreach ($products as $index => $product)
            <a
                href="{{ route('belanja.show', $product['id']) }}"
                class="belanja-card group font-nohemi relative rounded-[16px] md:rounded-[24px] shadow-sm hover:shadow-xl transition-all duration-300 ease-out overflow-hidden flex flex-col border-2 {{ $product['border'] }} cursor-pointer {{ $index % 2 === 0 ? 'belanja-card--tilt-right' : 'belanja-card--tilt-left' }}"
                data-soft-nav
            >
                <div class="relative w-full md:h-[240px] aspect-square overflow-hidden {{ $product['imgBg'] }}">
                    <span class="absolute top-2 left-2 md:top-5 md:left-5 bg-white px-2 py-1 rounded-full text-[10px] font-bold z-20 {{ $product['text'] }}">
                        {{ $product['badge'] }}
                    </span>

                    <img
                        src="{{ !empty($product['img_url']) ? $product['img'] : asset('src/images/' . $product['img']) }}"
                        alt="{{ $product['title'] }}"
                        class="absolute bottom-[-18%] md:bottom-[-21%] left-[8%] md:left-[4%] w-[75%] md:w-[80%] max-w-none object-contain drop-shadow-xl rotate-[35deg] transition-transform duration-300"
                    >
                </div>

                <div class="p-2.5 sm:p-3 md:p-4 flex flex-col flex-grow text-left {{ $product['cardBg'] }}">
                    <h3 class="text-[12px] sm:text-[13px] md:text-[16px] font-bold mb-1 md:mb-2 {{ $product['text'] }}">
                        {{ $product['title'] }}
                    </h3>

                    <p class="text-[10px] font-medium mb-3 md:mb-2 leading-[1.2] md:leading-[1.25] flex-grow line-clamp-3 {{ $product['descColor'] }}">
                        {{ $product['description'] }}
                    </p>

                    <div class="flex justify-between items-center mt-auto gap-1">
                        <span class="text-[11px] md:text-[10px] font-bold {{ $product['text'] }}">
                            {{ $product['price_label'] }}
                        </span>

                        <span
                            class="w-7 h-7 rounded-full flex justify-center items-center text-white transition-transform group-hover:scale-105 {{ $product['btn'] }}"
                            aria-hidden="true"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-3 h-3">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                            </svg>
                        </span>
                    </div>
                </div>
            </a>
        @endforeach
    </div>
</section>
