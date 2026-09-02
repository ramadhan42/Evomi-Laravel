@php
    // Harga promo diatur di config/evomi.php: harga coret + harga jual yang
    // ditampilkan. Dipakai bersama oleh halaman ini dan modal produk di beranda
    // supaya angkanya tidak pernah berbeda antar halaman.
    $comparePrice = (float) (config('evomi.pricing.compare_at') ?? 0);
    $displayPrice = (float) (config('evomi.pricing.display') ?? ($product['price'] ?? 0));
    $comparePriceLabel = $comparePrice > 0 ? 'Rp' . number_format($comparePrice, 0, ',', '.') : '';
    $displayPriceLabel = 'Rp' . number_format($displayPrice, 0, ',', '.');

    $detailPayload = [
        'id' => $product['id'],
        'title' => $product['title'],
        'description' => $product['description'] ?? '',
        'accent' => $product['accent'],
        'price' => $displayPrice,
        'stock' => $product['stock'],
        'gallery' => $gallery,
        'characterUrl' => $characterUrl,
        'kurirs' => $kurirs,
        'promo' => $promo,
        'checkoutPromo' => $checkoutPromo ?? null,
        'freeShipping' => (bool) ($freeShipping ?? false),
        'loginUrl' => route('login'),
        'applyTheme' => $applyTheme ?? true,
        'shareImage' => $gallery[0] ?? ($product['img'] ?? ''),
    ];
    $detailCms = \App\Support\CmsStorefront::forPage('belanja_details');
    $lbl = fn (string $key, string $id, string $en = '') => $detailCms->get('labels', $key, $en !== '' ? evomi_l($id, $en) : evomi_l($id, $id));
    // Tombol marketplace menggantikan alur keranjang/checkout internal.
    // Tautan per varian ada di config/evomi.php, dikunci personality_type.
    $marketplaceLabel = $lbl('marketplace', 'Beli di', 'Buy on');
    $marketplaceLinks = config('evomi.marketplaces.links.' . ($product['personality_type'] ?? ''), []);

    $marketplaceButtons = [];

    foreach (config('evomi.marketplaces.channels', []) as $mpKey => $mpMeta) {
        $mpUrl = trim((string) ($marketplaceLinks[$mpKey] ?? ''));

        // Tautan kosong -> tombol tidak dirender, supaya varian tanpa toko aman.
        if ($mpUrl === '') {
            continue;
        }

        $marketplaceButtons[] = [
            'key' => $mpKey,
            'label' => $mpMeta['label'] ?? ucfirst($mpKey),
            'color' => $mpMeta['color'] ?? '#111111',
            'url' => $mpUrl,
        ];
    }

    // Disclaimer bilingual — model `disclaimers` is ID-only; CMS has EN items.
    $detailCmsId = \App\Support\CmsStorefront::forPage('belanja_details', 'id');
    $disclaimerEnRows = \App\Models\SiteContent::query()
        ->where('page', 'belanja_details')
        ->where('section', 'disclaimer')
        ->where('locale', 'en')
        ->pluck('value', 'key');
    $discTitleId = $detailCmsId->get('disclaimer', 'title', 'Disclaimer untuk Ketentuan COMPLAIN');
    $discTitleEn = trim((string) ($disclaimerEnRows['title'] ?? ''));
    if ($discTitleEn === '') {
        $discTitleEn = 'Disclaimer for Complaint Terms';
    }
    $discItemsId = [];
    for ($n = 1; $n <= 6; $n++) {
        $v = trim($detailCmsId->get('disclaimer', 'item_'.$n, ''));
        if ($v !== '') {
            $discItemsId[] = $v;
        }
    }
    if ($discItemsId === [] && is_array($disclaimers ?? null)) {
        $discItemsId = array_values(array_filter(array_map('strval', $disclaimers)));
    }
    $discItemsEn = [];
    for ($n = 1; $n <= 6; $n++) {
        $v = trim((string) ($disclaimerEnRows['item_'.$n] ?? ''));
        if ($v !== '') {
            $discItemsEn[] = $v;
        }
    }
    if ($discItemsEn === []) {
        $discItemsEn = [
            'Actual color and scent may vary slightly depending on the production batch.',
            'Store in a cool place, away from direct sunlight.',
            'Complaints are only accepted within 2x24 hours after delivery, with an unboxing video.',
            'Products with broken seals cannot be returned except for manufacturing defects.',
        ];
    }
@endphp

<section
    class="belanja-detail-enter bg-white w-full pt-6 sm:pt-8 pb-12 md:pb-16 px-4 md:px-8 relative overflow-x-hidden flex flex-col items-center"
    x-data="evomiProductDetail(@js($detailPayload))"
    style="--detail-accent: {{ $product['accent'] }}"
>
    <div class="w-full max-w-7xl grid grid-cols-1 lg:grid-cols-12 gap-6 md:gap-10 items-start mt-2 md:mt-4 mb-10 z-10">
        {{-- KIRI: Gallery --}}
        <div class="lg:col-span-4 flex flex-col items-center w-full select-none" data-belanja-enter="bottle">
            <div
                data-belanja-hero-image
                class="belanja-detail__hero w-full aspect-square rounded-[24px] overflow-hidden relative shadow-sm"
                :style="accentSurfaceStyle"
            >
                <template x-for="(imgSrc, index) in gallery" :key="index">
                    <div
                        class="absolute inset-0 transition-opacity duration-700 ease-in-out"
                        :class="currentIndex === index ? 'opacity-100 z-10' : 'opacity-0 z-0 pointer-events-none'"
                    >
                        {{-- Full-bleed: image edge merges with rounded accent frame (no inset/padding ring) --}}
                        <img
                            :src="imgSrc"
                            :alt="title + ' gambar ' + (index + 1)"
                            class="belanja-detail__hero-img absolute inset-0 h-full w-full object-cover"
                            draggable="false"
                        >
                    </div>
                </template>

            </div>

            <div class="flex justify-center items-center gap-2 my-5" x-show="gallery.length > 1" x-cloak>
                <template x-for="(imgSrc, index) in gallery" :key="'dot-' + index">
                    <button
                        type="button"
                        @click="currentIndex = index"
                        class="h-[8px] rounded-full transition-all duration-300"
                        :class="currentIndex === index ? 'w-[24px]' : 'w-[8px] opacity-30'"
                        :style="accentSurfaceStyle"
                        :aria-label="'Go to slide ' + (index + 1)"
                    ></button>
                </template>
            </div>

            {{-- Tiga thumbnail terlihat sekaligus; sisanya dijangkau dengan
                 menggeser jendela ini, bukan dengan menumpuk baris kedua. --}}
            <div class="belanja-detail__thumbs-wrap relative w-full mt-2" x-show="gallery.length > 1" x-cloak>
                <div
                    class="belanja-detail__thumbs-viewport"
                    x-ref="thumbViewport"
                    @touchstart.passive="onThumbTouchStart($event)"
                    @touchend.passive="onThumbTouchEnd($event)"
                >
                    <div class="belanja-detail__thumbs-track" x-ref="thumbTrack" :style="thumbTrackStyle">
                        <template x-for="(image, index) in gallery" :key="'thumb-' + index">
                            <button
                                type="button"
                                @click="selectImage(index)"
                                class="belanja-detail__thumb relative aspect-square overflow-hidden border-2 transition-all duration-200"
                                :style="{
                                    backgroundColor: accent,
                                    borderColor: currentIndex === index ? accent : 'transparent',
                                    opacity: currentIndex === index ? 1 : 0.65,
                                }"
                                :aria-current="currentIndex === index ? 'true' : 'false'"
                            >
                                <img
                                    :src="image"
                                    :alt="title + ' thumbnail ' + (index + 1)"
                                    class="absolute inset-0 w-full h-full object-cover"
                                    draggable="false"
                                >
                            </button>
                        </template>
                    </div>
                </div>

                <template x-if="canSlideThumbs">
                    <div>
                        <button
                            type="button"
                            class="belanja-detail__thumb-nav belanja-detail__thumb-nav--prev"
                            @click="slideThumbs(-1)"
                            :disabled="thumbStart === 0"
                            :style="{ color: accent }"
                            :aria-label="'Thumbnail sebelumnya'"
                        >
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                        </button>
                        <button
                            type="button"
                            class="belanja-detail__thumb-nav belanja-detail__thumb-nav--next"
                            @click="slideThumbs(1)"
                            :disabled="thumbStart >= maxThumbStart"
                            :style="{ color: accent }"
                            :aria-label="'Thumbnail berikutnya'"
                        >
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                        </button>
                    </div>
                </template>
            </div>
        </div>

        {{-- TENGAH: Info --}}
        <div
            id="detail-info-scroll"
            class="lg:col-span-5 flex flex-col text-left w-full relative lg:overflow-y-auto [scrollbar-width:none] [-ms-overflow-style:none]"
            data-belanja-enter="up"
            :style="detailScrollStyle"
        >
            <h1
                class="font-nohemi text-[28px] sm:text-[34px] md:text-[42px] font-semibold leading-tight mb-2 tracking-tight"
                style="color: {{ $product['accent'] }}"
            >
                {{ $product['title'] }}
            </h1>

            <p class="font-nohemi text-[16px] sm:text-[18px] md:text-[23px] font-semibold text-[#5D5D5D] mb-4 md:mb-6">
                {{ $product['bottle_size'] }}ml • {{ $product['perfume_type'] }}
            </p>

            <p class="font-parkinsans text-[14px] md:text-[17px] font-normal text-[#5D5D5D] leading-[1.6] mb-6 md:mb-8">
                {{ $product['description'] }}
            </p>

            <div class="bg-white border border-gray-100 rounded-[20px] p-4 sm:p-6 shadow-sm mb-6 md:mb-8 flex flex-col gap-4 md:gap-5">
                <h4 class="font-nohemi text-[18px] md:text-[20.36px] font-semibold tracking-tight" style="color: {{ $product['accent'] }}">
                    Notes {{ $product['title'] }}
                </h4>
                <div class="flex flex-col gap-3.5">
                    @foreach ([
                        ['Top Note', $product['top_note']],
                        ['Middle Note', $product['middle_note']],
                        ['Base Note', $product['base_note']],
                    ] as [$label, $value])
                        <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4">
                            <span
                                class="text-white font-nohemi text-[11px] md:text-[11.42px] px-3 md:px-4 py-1.5 rounded-full w-fit sm:min-w-[100px] text-center"
                                style="background-color: {{ $product['accent'] }}"
                            >{{ $label }}</span>
                            <span class="text-[13px] md:text-[14.15px] font-parkinsans font-normal opacity-80" style="color: {{ $product['accent'] }}">
                                {{ $value }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="mb-6">
                <h4 class="font-nohemi text-[18px] md:text-[20px] font-bold mb-1" style="color: {{ $product['accent'] }}">{{ $lbl('price', 'Harga', 'Price') }}</h4>
                <div class="evomi-price">
                    @if ($comparePriceLabel !== '')
                        <span class="evomi-price__compare">{{ $comparePriceLabel }}</span>
                    @endif
                    <span class="font-nohemi text-[26px] md:text-[32px] font-semibold text-[#1A1A1A]">{{ $displayPriceLabel }}</span>
                </div>
            </div>

            <div class="mb-8">
                <h4 class="font-nohemi text-[20px] font-semibold mb-4" style="color: {{ $product['accent'] }}">{{ $lbl('detail_title', 'Detail Produk', 'Product Details') }}</h4>
                <div class="grid grid-cols-2 gap-y-3.5 gap-x-4 text-[14px] font-parkinsans font-normal">
                    <div class="flex"><span class="w-28 text-[#99A1AF] shrink-0">{{ $lbl('condition', 'Kondisi', 'Condition') }}</span><span class="text-[#364153]">{{ $product['kondisi'] }}</span></div>
                    <div class="flex"><span class="w-28 text-[#99A1AF] shrink-0">{{ $lbl('weight', 'Berat Satuan', 'Unit Weight') }}</span><span class="text-[#364153]">{{ $product['berat_satuan'] }} g</span></div>
                    <div class="flex"><span class="w-28 text-[#99A1AF] shrink-0">{{ $lbl('category', 'Kategori', 'Category') }}</span><span class="text-[#364153]">{{ $product['kategori'] }}</span></div>
                    <div class="flex"><span class="w-28 text-[#99A1AF] shrink-0">{{ evomi_l('Brand', 'Brand') }}</span><span class="text-[#364153]">{{ $product['brand'] }}</span></div>
                    <div class="flex"><span class="w-28 text-[#99A1AF] shrink-0">{{ $lbl('min_buy', 'Min. Beli', 'Min. Order') }}</span><span class="text-[#364153]">{{ $detailCms->get('labels', 'min_buy_value', evomi_l('1 Buah', '1 Piece')) }}</span></div>
                    <div class="flex"><span class="w-28 text-[#99A1AF] shrink-0">{{ $lbl('showcase', 'Etalase', 'Showcase') }}</span><span class="text-[#364153]">{{ $product['etalase'] }}</span></div>
                </div>
            </div>

            <div class="mb-8">
                <h4
                    class="font-nohemi text-[20px] font-semibold mb-3"
                    style="color: {{ $product['accent'] }}"
                    x-text="$store.i18n.locale === 'en' ? @js($discTitleEn) : @js($discTitleId)"
                >{{ $discTitleId }}</h4>
                <div class="text-[14px] font-parkinsans font-normal text-[#4A5565] leading-relaxed flex flex-col gap-1.5">
                    <template
                        x-for="(text, i) in ($store.i18n.locale === 'en' ? @js($discItemsEn) : @js($discItemsId))"
                        :key="'disc-' + i"
                    >
                        <p x-text="(Number(i) + 1) + '. ' + text"></p>
                    </template>
                </div>
            </div>
        </div>

        {{-- KANAN: Cart box --}}
        <div class="lg:col-span-3 w-full relative" data-belanja-enter="fade">
            <div class="sticky top-24 flex flex-col gap-4">
                <div
                    x-ref="diskusiBox"
                    class="bg-white border border-gray-200 rounded-[16px] shadow-sm overflow-hidden w-full lg:w-[295px] flex flex-col"
                >
                    <div class="px-4 py-2 text-white flex items-center gap-2 font-parkinsans font-medium text-[14px]" :style="accentSurfaceStyle">
                        <svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z"/></svg>
                        {{ $lbl('discussion', 'Diskusi Terbuka', 'Open Discussion') }}
                    </div>

                    <div class="p-5 flex flex-col gap-5">
                        <div>
                            @if ($comparePriceLabel !== '')
                                <span class="evomi-price__compare">{{ $comparePriceLabel }}</span>
                            @endif
                            <div class="font-nohemi text-[28px] font-bold text-[#101828] leading-none" x-text="formatPrice(price)"></div>
                            <p class="mt-1 text-[12px] font-parkinsans text-[#CA3500]" x-show="hasCheckoutPromo" x-cloak>
                                {{ evomi_l('Promo tersedia di marketplace', 'Promo available on marketplaces') }}
                            </p>
                        </div>

                        @if (count($marketplaceButtons))
                            <div class="evomi-mp mt-1">
                                <p class="evomi-mp__lead">{{ $marketplaceLabel }}</p>

                                <div class="evomi-mp__list">
                                    @foreach ($marketplaceButtons as $mp)
                                        <a
                                            href="{{ $mp['url'] }}"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="evomi-mp__btn evomi-mp__btn--{{ $mp['key'] }}"
                                            style="--mp-color: {{ $mp['color'] }}"
                                            aria-label="{{ $marketplaceLabel }} {{ $mp['label'] }}"
                                        >
                                            <span class="evomi-mp__icon" aria-hidden="true">
                                                @include('partials.icons.marketplace-' . $mp['key'])
                                            </span>

                                            <span class="evomi-mp__text">{{ $mp['label'] }}</span>

                                            <svg class="evomi-mp__arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="M5 12h13" />
                                                <path d="m12 5 7 7-7 7" />
                                            </svg>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <div class="flex justify-center gap-12 mt-2 font-parkinsans font-medium text-[14px] text-[#6A7282]" :style="{ '--hover-color': accent }">
                            <button type="button" @click="openChat()" class="flex flex-col items-center gap-1.5 hover:text-[var(--hover-color)] transition">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z"/></svg>
                                {{ $lbl('chat', 'Chat', 'Chat') }}
                            </button>
                            <button type="button" @click="showShareModal = true" class="flex flex-col items-center gap-1.5 hover:text-[var(--hover-color)] transition">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M7.217 10.907a2.25 2.25 0 1 0 0 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186 9.566-5.314m-9.566 7.5 9.566 5.314m0 0a2.25 2.25 0 1 0 3.935 2.186 2.25 2.25 0 0 0-3.935-2.186Zm0-12.814a2.25 2.25 0 1 0 3.933-2.185 2.25 2.25 0 0 0-3.933 2.185Z"/></svg>
                                {{ $lbl('share', 'Share', 'Share') }}
                            </button>
                        </div>

                        <div class="bg-[#FFF4E5] border border-[#FFE8CC] text-[#CA3500] rounded-[8px] p-3 flex gap-2.5 items-center mt-2 font-parkinsans" x-show="hasCheckoutPromo" x-cloak>
                            <svg class="w-[18px] h-[18px] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                            <p class="text-[14px] leading-snug">{{ evomi_l('Promo aktif! Potongan berlaku saat belanja lewat marketplace di atas.', 'Promo active! The discount applies when you shop through the marketplaces above.') }}</p>
                        </div>

                        <div
                            class="text-center text-[14px] font-medium"
                            x-show="statusMessage || wishlistMessage"
                            x-cloak
                            :class="{
                                'text-green-600': (statusMessage && statusTone === 'success') || (!statusMessage && wishlistMessage && !/gagal|error/i.test(wishlistMessage)),
                                'text-amber-600': statusMessage && statusTone === 'info',
                                'text-red-600': (statusMessage && statusTone === 'error') || (wishlistMessage && /gagal|error/i.test(wishlistMessage)),
                            }"
                            x-text="statusMessage || wishlistMessage"
                        ></div>
                    </div>
                </div>

                <div x-ref="jaminanBox" class="bg-white border border-gray-100 rounded-[16px] p-5 shadow-sm flex flex-col gap-4">
                    <div class="flex gap-3 items-start">
                        <svg class="w-5 h-5 shrink-0 mt-0.5" :style="accentTextStyle" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 12 2.25c-2.663 0-5.176.736-7.312 2.018C3.226 5.07 2.25 6.63 2.25 8.25v.75c0 6.212 4.23 11.49 9.75 12.75 5.52-1.26 9.75-6.538 9.75-12.75v-.75c0-1.62-.976-3.18-2.438-3.982A11.959 11.959 0 0 0 12 1.714Z"/></svg>
                        <div>
                            <h5 class="font-parkinsans font-semibold text-[14px] text-[#364153]">{{ $detailCms->get('guarantee', 'title', evomi_l('Jaminan Produk', 'Product Guarantee')) }}</h5>
                            <p class="font-parkinsans text-[14px] text-[#99A1AF] mt-0.5">{{ $detailCms->get('guarantee', 'money_back', evomi_l('Uang kembali bila produk tidak sesuai', "Money back if the product doesn't match")) }}</p>
                        </div>
                    </div>
                    <hr class="border-gray-100">
                    <div class="flex gap-3 items-start">
                        <svg class="w-5 h-5 shrink-0 mt-0.5" :style="accentTextStyle" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                        <div>
                            <h5 class="font-parkinsans font-semibold text-[14px] text-[#364153]">{{ $detailCms->get('guarantee', 'free_shipping', evomi_l('Bebas Ongkir', 'Free Shipping')) }}</h5>
                            <p class="font-parkinsans text-[14px] text-[#99A1AF] mt-0.5">{{ $detailCms->get('guarantee', 'terms', evomi_l('Syarat & ketentuan berlaku', 'Terms & conditions apply')) }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Chat modal --}}
    <template x-teleport="body">
        <div
            class="evomi-product-modal"
            x-show="isChatOpen"
            x-cloak
            :class="isChatOpen ? 'pointer-events-auto' : 'pointer-events-none'"
            @keydown.escape.window="isChatOpen && closeChat()"
        >
            <div
                class="evomi-product-modal__backdrop"
                x-show="isChatOpen"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                @click="closeChat()"
            ></div>

            <div class="evomi-product-modal__frame evomi-product-modal__frame--chat" x-show="isChatOpen" @click.self="closeChat()">
                <div
                    class="evomi-chat-panel"
                    role="dialog"
                    aria-modal="true"
                    aria-label="{{ $detailCms->get('chat', 'admin_name', evomi_l('Chat Admin Evomi', 'Chat Evomi Admin')) }}"
                    x-show="isChatOpen"
                    x-transition:enter="ease-[cubic-bezier(0.22,1,0.36,1)] duration-420"
                    x-transition:enter-start="opacity-0 scale-[0.96] translate-y-6 sm:translate-y-4 sm:translate-x-4"
                    x-transition:enter-end="opacity-100 scale-100 translate-y-0 sm:translate-x-0"
                    x-transition:leave="ease-in duration-220"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-[0.98] translate-y-3"
                    @click.stop
                >
                    <div class="evomi-chat-panel__header" :style="{ background: `linear-gradient(145deg, color-mix(in srgb, ${accent} 88%, #0b3d66) 0%, ${accent} 52%, color-mix(in srgb, ${accent} 72%, #fff) 100%)` }">
                        <div class="flex items-center gap-3 min-w-0">
                            <span class="evomi-chat-panel__avatar" aria-hidden="true">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z"/></svg>
                                <span class="evomi-chat-panel__online"></span>
                            </span>
                            <div class="min-w-0">
                                <p class="evomi-chat-panel__kicker">Evomi Support</p>
                                <p class="evomi-chat-panel__title truncate">{{ $detailCms->get('chat', 'admin_name', evomi_l('Chat Admin Evomi', 'Chat Evomi Admin')) }}</p>
                                <p class="evomi-chat-panel__subtitle truncate">{{ $detailCms->get('chat', 'reply_hint', evomi_l('Biasanya membalas dalam beberapa menit', 'Usually replies within a few minutes')) }}</p>
                            </div>
                        </div>
                        <button type="button" class="evomi-overlay-close" @click="closeChat()" :aria-label="$L('Tutup', 'Close')">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 16 16" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4 4 12M4 4l8 8"/></svg>
                        </button>
                    </div>

                    <div class="evomi-chat-panel__body" x-ref="chatThread">
                        <div class="evomi-chat-panel__welcome">
                            <p class="text-[12px] font-semibold text-slate-500 mb-1">{{ evomi_l('Tentang produk', 'About this product') }}</p>
                            <p class="text-[13px] text-slate-800 leading-snug">
                                {{ evomi_l('Halo! Ada yang bisa kami bantu terkait', 'Hi! How can we help with') }}
                                <span class="font-semibold" :style="accentTextStyle" x-text="title"></span>?
                            </p>
                            <p class="text-[11px] text-slate-400 mt-1.5">{{ evomi_l('Percakapan ini menyatu untuk semua produk, jadi riwayat chat Anda tidak hilang saat pindah halaman.', 'This conversation is shared across products, so your chat history stays when you switch pages.') }}</p>
                        </div>

                        <div x-show="chatLoading && !chatBubbles.length" class="py-6 flex justify-center">
                            <div class="w-6 h-6 border-[3px] border-slate-200 border-t-[#1172BA] rounded-full animate-spin"></div>
                        </div>

                        <template x-for="bubble in chatBubbles" :key="bubble.id">
                            <div class="evomi-chat-panel__row" :class="bubble.type === 'user' ? 'is-user' : 'is-admin'">
                                <div
                                    class="evomi-chat-panel__bubble"
                                    :class="bubble.type === 'user' ? 'is-user' : 'is-admin'"
                                    :style="bubble.type === 'user' ? accentSurfaceStyle : {}"
                                >
                                    <p x-show="bubble.subject && bubble.type === 'user'" class="text-[10px] opacity-80 mb-1 font-medium" x-text="bubble.subject"></p>
                                    <p class="whitespace-pre-wrap" x-text="bubble.text"></p>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div class="evomi-chat-panel__footer">
                        <div class="evomi-chat-panel__chips">
                            <template x-for="tpl in chatTemplates" :key="tpl">
                                <button type="button" class="evomi-chat-panel__chip" @click="draft = tpl" x-text="tpl"></button>
                            </template>
                        </div>

                        @include('partials.turnstile-field', [
                            'theme' => 'light',
                            'mountId' => 'evomi-chat-turnstile-produk-'.$product['id'],
                        ])

                        <p x-show="chatError" x-cloak class="text-[11px] font-medium text-rose-600 px-1" x-text="chatError"></p>

                        <div class="evomi-chat-panel__composer">
                            <input
                                type="text"
                                x-model="draft"
                                @keydown.enter.prevent="sendChat()"
                                placeholder="{{ evomi_l('Ketik pesan Anda ke admin...', 'Type your message to admin...') }}"
                                class="evomi-chat-panel__input"
                            >
                            <button
                                type="button"
                                class="evomi-chat-panel__send"
                                :style="accentSurfaceStyle"
                                :disabled="chatSending"
                                @click="sendChat()"
                                :aria-label="$L('Kirim', 'Send')"
                            >
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5"/></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </template>

    {{-- Share modal --}}
    <template x-teleport="body">
        <div
            class="evomi-product-modal"
            x-show="showShareModal"
            x-cloak
            :class="showShareModal ? 'pointer-events-auto' : 'pointer-events-none'"
            @keydown.escape.window="showShareModal && (showShareModal = false)"
        >
            <div
                class="evomi-product-modal__backdrop"
                x-show="showShareModal"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                @click="showShareModal = false"
            ></div>

            <div class="evomi-product-modal__frame" x-show="showShareModal" @click.self="showShareModal = false">
                <div
                    class="evomi-share-panel"
                    role="dialog"
                    aria-modal="true"
                    aria-label="{{ evomi_l('Bagikan Produk', 'Share Product') }}"
                    x-show="showShareModal"
                    x-transition:enter="ease-[cubic-bezier(0.22,1,0.36,1)] duration-420"
                    x-transition:enter-start="opacity-0 scale-[0.96] translate-y-4"
                    x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                    x-transition:leave="ease-in duration-220"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-[0.98]"
                    @click.stop
                >
                    <div class="evomi-share-panel__header" :style="{ background: `linear-gradient(145deg, color-mix(in srgb, ${accent} 88%, #0b3d66) 0%, ${accent} 52%, color-mix(in srgb, ${accent} 72%, #fff) 100%)` }">
                        <div class="min-w-0">
                            <p class="evomi-chat-panel__kicker">Evomi</p>
                            <h3 class="evomi-chat-panel__title">{{ evomi_l('Bagikan Produk', 'Share Product') }}</h3>
                            <p class="evomi-chat-panel__subtitle">{{ evomi_l('Sebarkan aroma favoritmu ke teman.', 'Share your favorite scent with friends.') }}</p>
                        </div>
                        <button type="button" class="evomi-overlay-close" @click="showShareModal = false" :aria-label="$L('Tutup', 'Close')">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 16 16" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4 4 12M4 4l8 8"/></svg>
                        </button>
                    </div>

                    <div class="evomi-share-panel__body">
                        <div class="evomi-share-panel__preview">
                            <div class="evomi-share-panel__thumb" :style="{ backgroundColor: accent }">
                                <img :src="shareImageUrl || gallery[0] || ''" alt="" class="w-full h-full object-contain" x-show="shareImageUrl || gallery[0]">
                            </div>
                            <div class="min-w-0">
                                <p class="text-[14px] font-semibold text-slate-900 leading-snug truncate" x-text="title"></p>
                                <p class="text-[12px] text-slate-500 mt-1 line-clamp-2" x-text="sharePreviewDesc"></p>
                                <p class="text-[13px] font-bold mt-2" :style="accentTextStyle" x-text="formatPrice(price)"></p>
                            </div>
                        </div>

                        <div class="evomi-share-panel__grid">
                            <a :href="shareLinks.whatsapp" target="_blank" rel="noopener noreferrer" class="evomi-share-panel__item is-wa">
                                <span class="evomi-share-panel__icon">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.435 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                </span>
                                <span>WhatsApp</span>
                            </a>
                            <a :href="shareLinks.facebook" target="_blank" rel="noopener noreferrer" class="evomi-share-panel__item is-fb">
                                <span class="evomi-share-panel__icon">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M24 12.073C24 5.446 18.627.073 12 .073S0 5.446 0 12.073C0 18.063 4.388 23.027 10.125 23.927v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                                </span>
                                <span>Facebook</span>
                            </a>
                            <a :href="shareLinks.twitter" target="_blank" rel="noopener noreferrer" class="evomi-share-panel__item is-x">
                                <span class="evomi-share-panel__icon">
                                    <svg class="w-[18px] h-[18px]" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.74l7.726-8.835L1.254 2.25H8.08l4.251 5.647L18.244 2.25zm-1.161 17.52h1.833L7.084 4.126H5.117L17.083 19.77z"/></svg>
                                </span>
                                <span>X / Twitter</span>
                            </a>
                            <button type="button" @click="shareInstagram()" class="evomi-share-panel__item is-ig">
                                <span class="evomi-share-panel__icon">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5"/><path d="M16.5 11.37A4.5 4.5 0 1 1 12.13 7a4.5 4.5 0 0 1 4.37 4.37z"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/></svg>
                                </span>
                                <span>Instagram</span>
                            </button>
                        </div>

                        <p class="evomi-share-panel__hint" x-show="shareHint" x-text="shareHint" x-cloak x-transition.opacity.duration.200ms></p>

                        <div class="evomi-share-panel__copy">
                            <input type="text" :value="productUrl" readonly class="evomi-share-panel__url" :aria-label="$L('Link produk', 'Product link')">
                            <button
                                type="button"
                                class="evomi-share-panel__copy-btn"
                                :class="isCopied ? 'is-copied' : ''"
                                :style="isCopied ? accentSurfaceStyle : {}"
                                @click="copyLink()"
                                x-text="isCopied ? $L('Disalin', 'Copied') : $L('Salin', 'Copy')"
                            ></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </template>

    {{-- Login alert — teleport ke body agar fixed selalu di tengah viewport (page-shell punya transform) --}}
    <template x-teleport="body">
        <div x-show="alert.show" x-cloak class="fixed inset-0 z-[200] flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
            <div class="absolute inset-0" @click="alert.show = false"></div>
            <div class="relative bg-white w-full max-w-[360px] rounded-[24px] shadow-2xl p-6 text-center">
                <div class="w-14 h-14 rounded-full flex items-center justify-center mx-auto mb-4 bg-amber-50 text-amber-500">
                    <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 12 2.25c-2.663 0-5.176.736-7.312 2.018C3.226 5.07 2.25 6.63 2.25 8.25v.75c0 6.212 4.23 11.49 9.75 12.75 5.52-1.26 9.75-6.538 9.75-12.75v-.75c0-1.62-.976-3.18-2.438-3.982A11.959 11.959 0 0 0 12 1.714Z"/></svg>
                </div>
                <h3 class="font-nohemi text-[18px] font-bold text-[#1E2939] mb-2">{{ evomi_l('Perlu Login', 'Login Required') }}</h3>
                <p class="font-parkinsans text-[14px] text-[#6A7282] mb-6" x-text="alert.message"></p>
                <div class="flex gap-3">
                    <button type="button" @click="alert.show = false" class="flex-1 py-3 rounded-[12px] font-parkinsans font-semibold text-[14px] bg-gray-100 hover:bg-gray-200">{{ evomi_l('Tutup', 'Close') }}</button>
                    <a :href="loginUrl" class="flex-1 py-3 rounded-[12px] font-parkinsans font-semibold text-[14px] text-white text-center" :style="accentSurfaceStyle" data-soft-nav>{{ evomi_l('Login Sekarang', 'Login Now') }}</a>
                </div>
            </div>
        </div>
    </template>

    {{-- Bubble divider: half sits on white, half overlaps footer so no white hairline --}}
    @if ($showDivider ?? true)
        <div class="belanja-detail-bubbles absolute bottom-0 left-0 w-full h-0 overflow-visible pointer-events-none z-20">
            <div class="flex w-max gap-[10px] md:gap-[15px] animate-slide-left absolute left-0 bottom-0 translate-y-1/2">
                @for ($i = 0; $i < 80; $i++)
                    <div class="w-[30px] h-[30px] md:w-[46px] md:h-[46px] rounded-full flex-shrink-0" style="background-color: {{ $product['accent'] }}"></div>
                @endfor
            </div>
        </div>
    @endif
</section>
