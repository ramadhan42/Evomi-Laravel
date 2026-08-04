<section class="hero-section relative bg-[#0071BC] md:mb-6 md:mt-10 text-white pt-4 pb-10 md:pt-0 md:pb-10 px-4 flex flex-col items-center justify-center text-center select-none overflow-x-clip overflow-y-visible">
    {{-- Scroll parallax layer (Next: useScroll opacity + y) --}}
    <div class="hero-parallax-layer w-full flex flex-col items-center justify-center flex-1 z-10 gap-0 m-0 p-0">
        <h1 class="hero-headline font-semibold">
            <span class="hero-hl-1 text-white">Temukan </span>
            <span class="hero-hl-2 text-[#5CB2ED]">karakter</span>
            <br>
            <span class="hero-hl-3 text-[#FFA3CB]">aromamu </span>
            <span class="hero-hl-4 text-white">di Evomi</span>
        </h1>

        <div class="hero-visual-stage relative mt-2 mb-0 md:mt-3 w-[100%] md:w-[90%] lg:w-full max-w-7xl mx-auto aspect-[1280/412]">
            {{-- Waves / sayap --}}
            <div class="hero-wave-layer" aria-hidden="true">
                <div class="hero-wave-svg hero-wave-left">
                    <svg class="w-full h-auto block overflow-visible hero-wave-float-l" viewBox="0 0 394 269" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <defs>
                            <linearGradient id="heroWaveLeftGrad" x1="-16.1182" y1="57.6073" x2="385.318" y2="143.822" gradientUnits="userSpaceOnUse">
                                <stop offset="0.339313" stop-color="#60BBFF" />
                                <stop offset="1" stop-color="#FF8A84" />
                            </linearGradient>
                        </defs>
                        <path d="M249.353 208.572C227.104 254.765 336.005 229.301 393.236 210.795L391.597 225.206C240.842 287.445 208.166 270.156 206.182 247.054C204.198 223.951 268.222 182.812 179.508 180.809C90.7932 178.807 64.5628 160.794 64.6262 140.17C64.6895 119.546 109.343 90.8905 73.5016 87.1579C44.8283 84.1719 19.1086 93.2575 9.8329 98.1736L-34.5957 0C39.6156 62.1945 77.1964 34.9117 133.299 67.9779C189.402 101.044 75.6897 118.705 125.496 141.25C175.302 163.794 277.164 150.83 249.353 208.572Z" fill="url(#heroWaveLeftGrad)" />
                    </svg>
                </div>
                <div class="hero-wave-svg hero-wave-right">
                    <svg class="w-full h-auto block overflow-visible hero-wave-float-r" viewBox="0 0 418 449" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <defs>
                            <linearGradient id="heroWaveRightGradOuter" x1="446.42" y1="74.4447" x2="-7.16213" y2="352.017" gradientUnits="userSpaceOnUse">
                                <stop offset="0.333877" stop-color="#A5E194" />
                                <stop offset="1" stop-color="#F899C6" />
                            </linearGradient>
                            <linearGradient id="heroWaveRightGradInner" x1="470.42" y1="79.4447" x2="16.8379" y2="357.017" gradientUnits="userSpaceOnUse">
                                <stop offset="0.333877" stop-color="#A5E194" />
                                <stop offset="1" stop-color="#F899C6" />
                            </linearGradient>
                        </defs>
                        <path d="M167.875 341.839C203.726 388.975 72.4872 382.731 2.3867 373.718L7.45438 389.505C195.083 428.343 228.966 402.325 226.161 376.073C223.356 349.821 140.429 316.873 242.326 296.511C344.223 276.148 370.51 250.637 365.89 227.577C361.27 204.516 303.441 181.578 343.964 170.08C376.382 160.882 408.055 165.793 419.839 169.399L449.445 50.4874C377.55 135.229 328.182 112.382 270.754 160.837C213.326 209.291 348.395 205.822 295.91 241.219C243.426 276.615 123.062 282.919 167.875 341.839Z" fill="url(#heroWaveRightGradOuter)" />
                        <path d="M191.875 346.839C227.726 393.975 96.4872 387.731 26.3867 378.718L31.4544 394.505C219.083 433.343 252.966 407.325 250.161 381.073C247.356 354.821 164.429 321.873 266.326 301.511C368.223 281.148 394.51 255.637 389.89 232.577C385.27 209.516 327.441 186.578 367.964 175.08C400.382 165.882 432.055 170.793 443.839 174.399L473.445 55.4874C401.55 140.229 352.182 117.382 294.754 165.837C237.326 214.291 372.395 210.822 319.91 246.219C267.426 281.615 147.062 287.919 191.875 346.839Z" fill="url(#heroWaveRightGradInner)" />
                    </svg>
                </div>
            </div>

            {{-- Product labels (small icons above bottles) --}}
            @php
                $labels = [
                    ['img' => 'purpose-prestige.png', 'class' => 'left-[30%] md:left-[31%] top-[2%] md:top-[4%] w-[9.3%] h-[9.3%] md:w-[8.2%] md:h-[8.2%]'],
                    ['img' => 'rabel-brave.png', 'class' => 'left-[43%] top-[10.8%] md:top-[12.8%] w-[7.2%] h-[7.2%] md:w-[6.2%] md:h-[6.2%]'],
                    ['img' => 'peaceful-calm.png', 'class' => 'right-[38%] top-[10%] md:top-[6.8%] w-[8.2%] h-[8.2%] md:w-[7.2%] md:h-[7.2%]'],
                    ['img' => 'sweet-shy.png', 'class' => 'right-[27%] md:right-[28%] top-[10.4%] md:top-[10.8%] w-[7.2%] h-[7.2%] md:w-[6.2%] md:h-[6.2%]'],
                ];
            @endphp
            @foreach ($labels as $label)
                <a href="{{ route('belanja') }}" class="hero-label-hit absolute z-40 {{ $label['class'] }}">
                    <img src="{{ asset('src/images/section 1/' . $label['img']) }}" alt="" class="w-full h-full object-contain pointer-events-none hero-label-float">
                </a>
            @endforeach

            {{-- Side badges --}}
            <div class="hero-badge-left origin-bottom-right absolute inline-flex items-center justify-center gap-1 md:gap-2 bg-white text-[#0071BC] px-2 py-1 md:px-7 md:py-3 rounded-md md:rounded-xl shadow-md select-none whitespace-nowrap z-30 rotate-[15deg] scale-[0.8]">
                <div class="hero-badge-left-icon relative shrink-0">
                    <img src="{{ asset('src/images/section 1/badge-left-star.svg') }}" alt="" class="w-full h-full object-contain">
                </div>
                <p class="whitespace-nowrap font-bold">Eau de Parfum</p>
            </div>
            <div class="hero-badge-right origin-bottom-left absolute inline-flex items-center justify-center gap-1 md:gap-2 bg-white text-[#0071BC] px-2 py-1 md:px-7 md:py-3 rounded-md md:rounded-xl shadow-md select-none whitespace-nowrap z-30 -rotate-[12deg] scale-[0.8]">
                <div class="hero-badge-right-icon relative shrink-0">
                    <img src="{{ asset('src/images/section 1/recycle.png') }}" alt="" class="w-full h-full object-contain">
                </div>
                <p class="whitespace-nowrap font-bold">Recycle Bottle Cap</p>
            </div>

            {{-- Bottles row --}}
            <div class="relative top-20 md:top-65 left-1/2 -translate-x-2/5 -translate-y-[46%] z-10 w-[80%] h-[63%] flex items-center justify-between gap-1 md:gap-4 bg-transparent overflow-visible">
                @php
                    $bottles = [
                        ['img' => 'botol-purpose-prestige.png', 'title' => 'Purpose Prestige', 'n' => 1, 'z' => 'z-20', 'delay' => '0s'],
                        ['img' => 'botol-rabel-brave.png', 'title' => 'Rebel Brave', 'n' => 2, 'z' => 'z-30', 'delay' => '0.3s'],
                        ['img' => 'botol-peaceful-calm.png', 'title' => 'Peaceful Calm', 'n' => 3, 'z' => '', 'delay' => '0.6s'],
                        ['img' => 'botol-sweet-shy.png', 'title' => 'Sweet Shy', 'n' => 4, 'z' => 'z-30', 'delay' => '0.9s'],
                    ];
                @endphp
                @foreach ($bottles as $bottle)
                    <div class="relative w-full h-full hero-bottle-{{ $bottle['n'] }} {{ $bottle['z'] }}">
                        <div class="relative flex h-full w-full items-center justify-center hero-bottle-float" style="animation-delay: {{ $bottle['delay'] }}">
                            <a href="{{ route('belanja') }}" class="hero-product-hit relative inline-flex h-full max-w-full items-center justify-center" aria-label="{{ $bottle['title'] }}">
                                <img
                                    src="{{ asset('src/images/section 1/' . $bottle['img']) }}"
                                    alt="Botol {{ $bottle['title'] }}"
                                    class="pointer-events-none h-full w-auto max-w-full object-contain gambar-utama-hover"
                                >
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Marquee divider (outside parallax, same as Next) --}}
    <div class="hero-divider-marquee absolute left-0 w-full overflow-hidden py-1.5 md:py-4 border-y border-white/10 z-40 bg-[#0071BC]">
        <div class="animate-marquee flex items-center gap-4 sm:gap-6 md:gap-8">
            @for ($i = 0; $i < 4; $i++)
                <div class="flex items-center gap-4 sm:gap-6 md:gap-8 shrink-0">
                    @foreach (['purpose.png', 'peaceful.png', 'rab.png', 'sweetshy.png'] as $idx => $icon)
                        <div class="flex items-center gap-4 sm:gap-6 md:gap-8">
                            <span class="hero-marquee-text whitespace-nowrap text-white font-medium">Every Version of Me</span>
                            <div class="hero-div-icon-{{ $idx + 1 }} relative shrink-0">
                                <img src="{{ asset('src/images/section 1/' . $icon) }}" alt="" class="w-full h-full object-contain">
                            </div>
                        </div>
                    @endforeach
                </div>
            @endfor
        </div>
    </div>
</section>
