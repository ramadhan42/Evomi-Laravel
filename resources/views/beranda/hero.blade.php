@php
    /** @var \App\Support\CmsStorefront $cms */
    $cms = $cms ?? \App\Support\CmsStorefront::forPage('beranda');
    $hl1 = $cms->textLines('hero', 'headline_1', 'Temukan', 1);
    $hl2 = $cms->textLines('hero', 'headline_2', 'karakter', 1);
    $hl3 = $cms->textLines('hero', 'headline_3', 'aromamu', 1);
    $hl4 = $cms->textLines('hero', 'headline_4', 'di Evomi', 1);
    $hl1c = $cms->get('hero', 'headline_1_color', '#FFFFFF');
    $hl2c = $cms->get('hero', 'headline_2_color', '#5CB2ED');
    $hl3c = $cms->get('hero', 'headline_3_color', '#FFA3CB');
    $hl4c = $cms->get('hero', 'headline_4_color', '#FFFFFF');
    $badgeLeft = $cms->textLines('hero', 'badge_left', 'Eau de Parfum', 2);
    $badgeRight = $cms->textLines('hero', 'badge_right', 'Recycle Bottle Cap', 2);
    $badgeLeftIcon = $cms->image('hero', 'badge_left_icon', '/src/images/section 1/badge-left-star.svg');
    $badgeRightIcon = $cms->image('hero', 'badge_right_icon', '/src/images/section 1/recycle.png');
    $marqueeText = $cms->textLines('hero', 'marquee_text', 'Every Version of Me', 1);
    $products = [
        [
            'img' => $cms->image('hero', 'product1_image', '/src/images/section 1/botol-purpose-prestige.png'),
            'label' => $cms->image('hero', 'product1_badge_icon', '/src/images/section 1/purpose-prestige.png'),
            'labelClass' => 'left-[30%] md:left-[31%] top-[2%] md:top-[4%] w-[9.3%] h-[9.3%] md:w-[8.2%] md:h-[8.2%]',
            'title' => $cms->get('hero', 'product1_badge_label', 'Purpose Prestige'),
            'personality' => 'prestige',
            'n' => 1, 'z' => 'z-20', 'delay' => '1.1s',
        ],
        [
            'img' => $cms->image('hero', 'product2_image', '/src/images/section 1/botol-rabel-brave.png'),
            'label' => $cms->image('hero', 'product2_badge_icon', '/src/images/section 1/rabel-brave.png'),
            'labelClass' => 'left-[43%] top-[10.8%] md:top-[12.8%] w-[7.2%] h-[7.2%] md:w-[6.2%] md:h-[6.2%]',
            'title' => $cms->get('hero', 'product2_badge_label', 'Rebel Brave'),
            'personality' => 'rebel_brave',
            'n' => 2, 'z' => 'z-30', 'delay' => '1.4s',
        ],
        [
            'img' => $cms->image('hero', 'product3_image', '/src/images/section 1/botol-peaceful-calm.png'),
            'label' => $cms->image('hero', 'product3_badge_icon', '/src/images/section 1/peaceful-calm.png'),
            'labelClass' => 'right-[38%] top-[10%] md:top-[6.8%] w-[8.2%] h-[8.2%] md:w-[7.2%] md:h-[7.2%]',
            'title' => $cms->get('hero', 'product3_badge_label', 'Peaceful Calm'),
            'personality' => 'peaceful_calm',
            'n' => 3, 'z' => '', 'delay' => '1.7s',
        ],
        [
            'img' => $cms->image('hero', 'product4_image', '/src/images/section 1/botol-sweet-shy.png'),
            'label' => $cms->image('hero', 'product4_badge_icon', '/src/images/section 1/sweet-shy.png'),
            'labelClass' => 'right-[27%] md:right-[28%] top-[10.4%] md:top-[10.8%] w-[7.2%] h-[7.2%] md:w-[6.2%] md:h-[6.2%]',
            'title' => $cms->get('hero', 'product4_badge_label', 'Sweet Shy'),
            'personality' => 'sweet_shy',
            'n' => 4, 'z' => 'z-30', 'delay' => '2.0s',
        ],
    ];
    $catalogByPersonality = [];
    foreach (\App\Support\BelanjaCatalog::all() as $catProduct) {
        $key = (string) ($catProduct['personality_type'] ?? '');
        if ($key === 'purpose_prestige') {
            $key = 'prestige';
        }
        if ($key !== '' && ! isset($catalogByPersonality[$key])) {
            $catalogByPersonality[$key] = $catProduct;
        }
    }
    foreach ($products as &$heroProduct) {
        $match = $catalogByPersonality[$heroProduct['personality']] ?? null;
        $heroProduct['id'] = $match['id'] ?? null;
    }
    unset($heroProduct);
    $dividerIcons = [
        $cms->image('hero', 'divider_icon_1', '/src/images/section 1/purpose.png'),
        $cms->image('hero', 'divider_icon_2', '/src/images/section 1/peaceful.png'),
        $cms->image('hero', 'divider_icon_3', '/src/images/section 1/rab.png'),
        $cms->image('hero', 'divider_icon_4', '/src/images/section 1/sweetshy.png'),
    ];
    $waveLeftCms = $cms->image('hero', 'wave_left_icon', '');
    $waveRightCms = $cms->image('hero', 'wave_right_icon', '');
    $waveLeftRaster = $waveLeftCms !== '' && preg_match('/\.(png|jpe?g|webp|gif)(\?|$)/i', $waveLeftCms);
    $waveRightRaster = $waveRightCms !== '' && preg_match('/\.(png|jpe?g|webp|gif)(\?|$)/i', $waveRightCms);
@endphp
<section
    class="hero-section relative bg-[#0071BC] md:mb-6 md:mt-10 text-white pt-4 pb-10 md:pt-0 md:pb-10 px-4 flex flex-col items-center justify-center text-center select-none overflow-x-clip overflow-y-visible"
    style="{{ $cms->heroCssStyleAttr() }}"
>
    {{-- Scroll parallax layer (Next: useScroll opacity + y) — entrance stagger via data-hero-enter --}}
    <div class="hero-parallax-layer w-full flex flex-col items-center justify-center flex-1 z-10 gap-0 m-0 p-0">
        <h1 class="hero-headline font-semibold">
            <span class="hero-hl-1 cms-lines" data-hero-enter="up" style="--hero-enter-delay: 0s; --hero-enter-dur: 0.7s; color: {{ $hl1c }}">{{ trim($hl1) }}</span><span class="hero-hl-2 cms-lines" data-hero-enter="up" style="--hero-enter-delay: 0.1s; --hero-enter-dur: 0.7s; color: {{ $hl2c }}">{{ trim($hl2) }}</span>
            <br>
            <span class="hero-hl-3 cms-lines" data-hero-enter="up" style="--hero-enter-delay: 0.2s; --hero-enter-dur: 0.7s; color: {{ $hl3c }}">{{ trim($hl3) }}</span><span class="hero-hl-4 cms-lines" data-hero-enter="up" style="--hero-enter-delay: 0.3s; --hero-enter-dur: 0.7s; color: {{ $hl4c }}">{{ trim($hl4) }}</span>
        </h1>

        <div class="hero-visual-stage relative mt-2 mb-0 md:mt-3 w-[100%] md:w-[90%] lg:w-full max-w-7xl mx-auto aspect-[1280/412]">
            {{-- Waves / sayap --}}
            <div class="hero-wave-layer" aria-hidden="true">
                <div class="hero-wave-svg hero-wave-left" data-hero-enter="fade" style="--hero-enter-delay: 0.65s; --hero-enter-dur: 1.15s;">
                    @if ($waveLeftRaster)
                        <img src="{{ $waveLeftCms }}" alt="" class="w-full h-auto block overflow-visible hero-wave-float-l">
                    @else
                        <svg class="w-full h-auto block overflow-visible hero-wave-float-l" viewBox="0 0 394 269" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <defs>
                                <linearGradient id="heroWaveLeftGrad" x1="-16.1182" y1="57.6073" x2="385.318" y2="143.822" gradientUnits="userSpaceOnUse">
                                    <stop offset="0.339313" stop-color="#60BBFF" />
                                    <stop offset="1" stop-color="#FF8A84" />
                                </linearGradient>
                            </defs>
                            <path d="M249.353 208.572C227.104 254.765 336.005 229.301 393.236 210.795L391.597 225.206C240.842 287.445 208.166 270.156 206.182 247.054C204.198 223.951 268.222 182.812 179.508 180.809C90.7932 178.807 64.5628 160.794 64.6262 140.17C64.6895 119.546 109.343 90.8905 73.5016 87.1579C44.8283 84.1719 19.1086 93.2575 9.8329 98.1736L-34.5957 0C39.6156 62.1945 77.1964 34.9117 133.299 67.9779C189.402 101.044 75.6897 118.705 125.496 141.25C175.302 163.794 277.164 150.83 249.353 208.572Z" fill="url(#heroWaveLeftGrad)" />
                        </svg>
                    @endif
                </div>
                <div class="hero-wave-svg hero-wave-right" data-hero-enter="fade" style="--hero-enter-delay: 0.85s; --hero-enter-dur: 1.15s;">
                    @if ($waveRightRaster)
                        <img src="{{ $waveRightCms }}" alt="" class="w-full h-auto block overflow-visible hero-wave-float-r">
                    @else
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
                    @endif
                </div>
            </div>

            @foreach ($products as $i => $product)
                <button
                    type="button"
                    class="hero-label-hit absolute z-40 {{ $product['labelClass'] }}"
                    data-hero-enter="label"
                    style="--hero-enter-delay: {{ 1.0 + ($i * 0.08) }}s; --hero-enter-dur: 0.6s;"
                    aria-label="{{ $product['title'] }}"
                    @if (! empty($product['id']))
                        onclick="window.evomiOpenProduct({{ (int) $product['id'] }})"
                    @else
                        onclick="window.location.href='{{ route('belanja') }}'"
                    @endif
                >
                    {{-- Same float keyframes + delay as the bottle below (Next floatDelay sync) --}}
                    <div class="hero-product-float relative w-full h-full" style="animation-delay: {{ $product['delay'] }}">
                        <img src="{{ $product['label'] }}" alt="" class="w-full h-full object-contain pointer-events-none">
                    </div>
                </button>
            @endforeach

            {{-- Side badges --}}
            <div
                class="hero-badge-left origin-bottom-right absolute inline-flex items-center justify-center gap-1 md:gap-2 bg-white text-[#0071BC] px-2 py-1 md:px-7 md:py-3 rounded-md md:rounded-xl shadow-md select-none whitespace-nowrap z-30"
                data-hero-enter="badge-l"
                style="--hero-enter-delay: 0.8s; --hero-enter-dur: 0.5s;"
            >
                <div class="hero-badge-left-icon relative shrink-0">
                    <img src="{{ $badgeLeftIcon }}" alt="" class="w-full h-full object-contain">
                </div>
                <p class="cms-lines font-bold text-center leading-tight">{{ $badgeLeft }}</p>
            </div>
            <div
                class="hero-badge-right origin-bottom-left absolute inline-flex items-center justify-center gap-1 md:gap-2 bg-white text-[#0071BC] px-2 py-1 md:px-7 md:py-3 rounded-md md:rounded-xl shadow-md select-none whitespace-nowrap z-30"
                data-hero-enter="badge-r"
                style="--hero-enter-delay: 0.9s; --hero-enter-dur: 0.5s;"
            >
                <div class="hero-badge-right-icon relative shrink-0">
                    <img src="{{ $badgeRightIcon }}" alt="" class="w-full h-full object-contain">
                </div>
                <p class="cms-lines font-bold text-center leading-tight">{{ $badgeRight }}</p>
            </div>

            {{-- Bottles row --}}
            <div class="relative top-20 md:top-65 left-1/2 -translate-x-2/5 -translate-y-[46%] z-10 w-[80%] h-[63%] flex items-center justify-between gap-1 md:gap-4 bg-transparent overflow-visible">
                @foreach ($products as $product)
                    <div
                        class="relative w-full h-full hero-bottle-{{ $product['n'] }} {{ $product['z'] }}"
                        data-hero-enter="bottle"
                        style="--hero-enter-delay: {{ 0.3 + (($product['n'] - 1) * 0.12) }}s; --hero-enter-dur: 0.8s;"
                    >
                        <div class="relative flex h-full w-full items-center justify-center hero-product-float" style="animation-delay: {{ $product['delay'] }}">
                            <button
                                type="button"
                                class="hero-product-hit relative inline-flex h-full max-w-full items-center justify-center"
                                aria-label="{{ $product['title'] }}"
                                @if (! empty($product['id']))
                                    onclick="window.evomiOpenProduct({{ (int) $product['id'] }})"
                                @else
                                    onclick="window.location.href='{{ route('belanja') }}'"
                                @endif
                            >
                                <img
                                    src="{{ $product['img'] }}"
                                    alt="Botol {{ $product['title'] }}"
                                    class="pointer-events-none h-full w-auto max-w-full object-contain gambar-utama-hover"
                                >
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Marquee divider --}}
    <div
        class="hero-divider-marquee absolute left-0 w-full overflow-hidden py-1.5 md:py-4 border-y border-white/10 z-40 bg-[#0071BC]"
        data-hero-enter="fade"
        style="--hero-enter-delay: 1.15s; --hero-enter-dur: 0.7s;"
    >
        {{-- Exactly 2 equal halves + trailing pad = gap → seamless -50% loop (40s). --}}
        <div class="animate-marquee flex items-center">
            @for ($dup = 0; $dup < 2; $dup++)
                <div
                    class="marquee-group gap-4 sm:gap-6 md:gap-8 pr-4 sm:pr-6 md:pr-8"
                    @if ($dup === 1) aria-hidden="true" @endif
                >
                    @for ($i = 0; $i < 2; $i++)
                        @foreach ($dividerIcons as $idx => $icon)
                            <div class="flex items-center gap-4 sm:gap-6 md:gap-8">
                                <span class="hero-marquee-text whitespace-nowrap text-white font-medium">{{ $marqueeText }}</span>
                                <div class="hero-div-icon-{{ $idx + 1 }} relative shrink-0">
                                    <img src="{{ $icon }}" alt="" class="w-full h-full object-contain">
                                </div>
                            </div>
                        @endforeach
                    @endfor
                </div>
            @endfor
        </div>
    </div>
</section>
