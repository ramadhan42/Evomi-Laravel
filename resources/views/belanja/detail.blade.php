@php
    $detailPayload = [
        'id' => $product['id'],
        'title' => $product['title'],
        'description' => $product['description'] ?? '',
        'accent' => $product['accent'],
        'price' => $product['price'],
        'stock' => $product['stock'],
        'gallery' => $gallery,
        'characterUrl' => $characterUrl,
        'kurirs' => $kurirs,
        'promo' => $promo,
        'loginUrl' => route('login'),
        'applyTheme' => $applyTheme ?? true,
        'shareImage' => $gallery[0] ?? ($product['img'] ?? ''),
    ];
    $detailCms = \App\Support\CmsStorefront::forPage('belanja_details');
    $lbl = fn (string $key, string $id, string $en = '') => $detailCms->get('labels', $key, $en !== '' ? evomi_l($id, $en) : evomi_l($id, $id));
    $buyNowLabel = $lbl('buy_now', 'Beli Langsung', 'Buy Now');
    $addCartLabel = $lbl('add_cart', '+ Keranjang', '+ Cart');
    $stockLabel = $lbl('stock', 'Stok:', 'Stock:');
    $outOfStockLabel = evomi_l('Stok habis', 'Out of stock');
@endphp

<section
    class="bg-white w-full pt-6 sm:pt-8 pb-12 md:pb-16 px-4 md:px-8 relative overflow-x-hidden flex flex-col items-center"
    x-data="evomiProductDetail(@js($detailPayload))"
    style="--detail-accent: {{ $product['accent'] }}"
>
    <div class="w-full max-w-7xl grid grid-cols-1 lg:grid-cols-12 gap-6 md:gap-10 items-start mt-2 md:mt-4 mb-10 z-10">
        {{-- KIRI: Gallery --}}
        <div class="lg:col-span-4 flex flex-col items-center w-full select-none">
            <div
                data-belanja-hero-image
                class="w-full aspect-square rounded-[24px] overflow-hidden flex justify-center items-center relative shadow-sm"
                :style="accentSurfaceStyle"
            >
                <template x-for="(imgSrc, index) in gallery" :key="index">
                    <div
                        class="absolute inset-0 transition-opacity duration-700 ease-in-out flex justify-center items-center"
                        :class="currentIndex === index ? 'opacity-100 z-10' : 'opacity-0 z-0 pointer-events-none'"
                    >
                        <img :src="imgSrc" :alt="title + ' gambar ' + (index + 1)" class="absolute inset-0 w-full h-full object-contain p-4">
                    </div>
                </template>

                <div class="absolute bottom-2 right-2 w-16 h-16 md:w-20 md:h-20 z-20 pointer-events-none opacity-90">
                    <img src="{{ $characterUrl }}" alt="" class="w-full h-full object-contain drop-shadow-md">
                </div>
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

            <div class="grid gap-3 w-full mt-2" :class="gallery.length >= 3 ? 'grid-cols-3' : 'grid-cols-2'">
                <template x-for="(image, index) in gallery" :key="'thumb-' + index">
                    <button
                        type="button"
                        @click="currentIndex = index"
                        class="relative w-full aspect-square rounded-[16px] overflow-hidden border-2 transition-all duration-300 bg-white"
                        :style="{ borderColor: currentIndex === index ? accent : 'transparent', opacity: currentIndex === index ? 1 : 0.6 }"
                    >
                        <div class="absolute inset-0" :style="accentSurfaceStyle"></div>
                        <img :src="image" :alt="title + ' thumbnail ' + (index + 1)" class="relative z-10 w-full h-full object-contain p-2">
                    </button>
                </template>
            </div>
        </div>

        {{-- TENGAH: Info --}}
        <div
            id="detail-info-scroll"
            class="lg:col-span-5 flex flex-col text-left w-full relative lg:overflow-y-auto [scrollbar-width:none] [-ms-overflow-style:none]"
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
                <span class="font-nohemi text-[26px] md:text-[32px] font-semibold text-[#1A1A1A]">{{ $product['price_label'] }}</span>
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
                <h4 class="font-nohemi text-[20px] font-semibold mb-3" style="color: {{ $product['accent'] }}">{{ $detailCms->get('disclaimer', 'title', evomi_l('Disclaimer untuk Ketentuan COMPLAIN', 'Disclaimer for Complaint Terms')) }}</h4>
                <div class="text-[14px] font-parkinsans font-normal text-[#4A5565] leading-relaxed flex flex-col gap-1.5">
                    @foreach ($disclaimers as $i => $text)
                        <p>{{ $i + 1 }}. {{ $text }}</p>
                    @endforeach
                </div>
            </div>

            <div class="bg-white border border-gray-100 rounded-[16px] p-6 shadow-sm flex flex-col gap-5">
                <h4 class="font-nohemi text-[20px] font-semibold text-[#1E2939]">{{ evomi_l('Pengiriman', 'Shipping') }}</h4>

                <div class="flex gap-4 items-start">
                    <svg class="text-gray-400 mt-0.5 shrink-0 w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/></svg>
                    <div>
                        <p class="text-[15px] text-[#6A7282] font-parkinsans">{{ evomi_l('Dikirim dari', 'Ships from') }}</p>
                        <p class="text-[16px] font-semibold text-[#364153] font-parkinsans">{{ $product['alamat_awal_pengiriman'] }}</p>
                    </div>
                </div>

                <div class="flex gap-4 items-start">
                    <svg class="text-gray-400 mt-0.5 shrink-0 w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m9.75 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h.375c.621 0 1.125-.504 1.125-1.125V14.25m-9.75 0h9.75"/></svg>
                    <div class="w-full">
                        <p class="text-[15px] text-[#6A7282] font-parkinsans" x-text="selectedKurir ? `${selectedKurir.nama} - ${selectedKurir.jenis}` : $L('Ongkir mulai', 'Shipping from')"></p>
                        <p class="text-[16px] font-semibold text-[#364153] font-parkinsans" x-text="selectedKurir ? formatPrice(selectedKurir.harga) : '-'"></p>
                        <p class="text-[15px] text-[#99A1AF] font-parkinsans mt-0.5">
                            {{ evomi_l('Estimasi tiba', 'Estimated arrival') }} <span x-text="selectedKurir ? estimasiTiba(selectedKurir) : '-'"></span>
                        </p>
                        <p class="text-[13px] text-[#99A1AF] font-parkinsans mt-0.5" x-show="selectedKurir?.destinasi" x-text="selectedKurir?.destinasi" x-cloak></p>
                        <button
                            type="button"
                            @click="showKurirList = true"
                            class="mt-3 text-left font-parkinsans text-[15px] font-semibold underline-offset-4 hover:underline"
                            :style="accentTextStyle"
                        >{{ $lbl('other_couriers', 'Lihat Kurir Lainnya', 'See Other Couriers') }}</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- KANAN: Cart box --}}
        <div class="lg:col-span-3 w-full relative">
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
                            <div class="font-nohemi text-[28px] font-bold text-[#101828] leading-none" x-text="formatPrice(price)"></div>
                            <p class="mt-1 text-[12px] font-parkinsans text-[#CA3500]" x-show="promoDiscount > 0" x-cloak>
                                Promo −<span x-text="formatPrice(promoDiscount)"></span>
                            </p>
                        </div>

                        <div>
                            <p class="font-parkinsans text-[14px] text-[#6A7282] mb-2">{{ $lbl('qty_hint', 'Atur jumlah dan catatan', 'Set quantity and notes') }}</p>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center border border-gray-300 rounded-[8px] h-[38px]">
                                    <button type="button" @click="changeQty('dec')" :disabled="quantity <= 1 || isOutOfStock" class="px-3 text-gray-500 hover:text-black transition flex h-full items-center justify-center disabled:opacity-40">−</button>
                                    <input type="text" readonly :value="isOutOfStock ? 0 : quantity" class="w-12 text-center text-[15px] font-bold border-x border-gray-300 h-full focus:outline-none text-[#1A1A1A]">
                                    <button type="button" @click="changeQty('inc')" :disabled="isOutOfStock || quantity >= stock" class="px-3 text-gray-500 hover:text-black transition flex h-full items-center justify-center disabled:opacity-40">+</button>
                                </div>
                                <span class="font-parkinsans text-[14px] text-[#6A7282]">
                                    {{ $stockLabel }} <span x-text="isOutOfStock ? @js($outOfStockLabel) : stock"></span>
                                </span>
                            </div>
                        </div>

                        <div class="flex flex-col gap-1.5 mt-1">
                            <div class="flex justify-between items-center">
                                <span class="text-[14px] font-parkinsans text-[#6A7282]">{{ $lbl('subtotal', 'Subtotal', 'Subtotal') }}</span>
                                <span class="text-[14px] font-nohemi font-semibold text-[#101828]" x-text="formatPrice(productSubtotal)"></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-[14px] font-parkinsans text-[#6A7282]">
                                    {{ $lbl('shipping', 'Ongkir', 'Shipping') }}
                                    <span class="text-[#99A1AF]" x-show="selectedKurir?.nama" x-text="'(' + selectedKurir.nama + ')'" x-cloak></span>
                                </span>
                                <span class="text-[14px] font-nohemi font-semibold text-[#101828]" x-text="formatPrice(shippingCost)"></span>
                            </div>
                            <div class="flex justify-between items-center" x-show="promoDiscount > 0" x-cloak>
                                <span class="text-[14px] font-parkinsans text-[#CA3500]">{{ $lbl('promo', 'Promo', 'Promo') }}</span>
                                <span class="text-[14px] font-nohemi font-semibold text-[#CA3500]" x-text="'−' + formatPrice(promoDiscount)"></span>
                            </div>
                            <div class="flex justify-between items-center pt-1 border-t border-gray-100 mt-0.5">
                                <span class="text-[17px] font-parkinsans text-[#6A7282]">{{ $lbl('total', 'Total', 'Total') }}</span>
                                <span class="text-[17px] font-nohemi font-bold" :style="accentTextStyle" x-text="formatPrice(totalWithShipping)"></span>
                            </div>
                        </div>

                        <div class="flex flex-col gap-3 mt-1">
                            <button
                                type="button"
                                @click="buyNow()"
                                :disabled="isOutOfStock || actionBusy"
                                class="w-full text-white font-nohemi text-[16px] font-semibold py-3 rounded-full shadow-sm hover:opacity-90 active:scale-95 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                                :style="accentSurfaceStyle"
                                x-text="isOutOfStock ? @js($outOfStockLabel) : @js($buyNowLabel)"
                            ></button>
                            <button
                                type="button"
                                data-belanja-add-cart
                                @click="addToCart($event)"
                                :disabled="isOutOfStock || actionBusy"
                                class="w-full bg-white font-nohemi text-[16px] font-semibold py-3 rounded-full border shadow-sm hover:bg-gray-50 active:scale-95 transition-all disabled:opacity-50"
                                :style="{ ...accentTextStyle, borderColor: accent }"
                                x-text="isOutOfStock ? @js($outOfStockLabel) : (statusMessage || @js($addCartLabel))"
                            ></button>
                        </div>

                        <div class="flex justify-center gap-12 mt-2 font-parkinsans font-medium text-[14px] text-[#6A7282]" :style="{ '--hover-color': accent }">
                            <button type="button" @click="isChatOpen = true" class="flex flex-col items-center gap-1.5 hover:text-[var(--hover-color)] transition">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z"/></svg>
                                {{ $lbl('chat', 'Chat', 'Chat') }}
                            </button>
                            <button
                                type="button"
                                data-wishlist-btn
                                @click="toggleWishlist($event)"
                                :disabled="wishlistBusy"
                                class="evomi-wishlist-btn flex flex-col items-center gap-1.5 transition disabled:opacity-60"
                                :class="isWishlisted ? 'is-wishlisted' : 'hover:text-[var(--hover-color)]'"
                                :style="isWishlisted
                                    ? { color: accent, '--wishlist-color': accent }
                                    : { '--hover-color': accent, '--wishlist-color': accent }"
                            >
                                <span class="evomi-wishlist-btn__icon relative inline-flex items-center justify-center">
                                    <svg
                                        class="w-5 h-5 relative z-[1] transition-colors duration-200"
                                        :class="isWishlisted ? 'evomi-wishlist-btn__heart-filled' : ''"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                        stroke-width="1.5"
                                    ><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z"/></svg>
                                </span>
                                {{ $lbl('wishlist', 'Wishlist', 'Wishlist') }}
                            </button>
                            <button type="button" @click="showShareModal = true" class="flex flex-col items-center gap-1.5 hover:text-[var(--hover-color)] transition">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M7.217 10.907a2.25 2.25 0 1 0 0 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186 9.566-5.314m-9.566 7.5 9.566 5.314m0 0a2.25 2.25 0 1 0 3.935 2.186 2.25 2.25 0 0 0-3.935-2.186Zm0-12.814a2.25 2.25 0 1 0 3.933-2.185 2.25 2.25 0 0 0-3.933 2.185Z"/></svg>
                                {{ $lbl('share', 'Share', 'Share') }}
                            </button>
                        </div>

                        <div class="bg-[#FFF4E5] border border-[#FFE8CC] text-[#CA3500] rounded-[8px] p-3 flex gap-2.5 items-center mt-2 font-parkinsans" x-show="promoDiscount > 0" x-cloak>
                            <svg class="w-[18px] h-[18px] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                            <p class="text-[14px] leading-snug">{{ evomi_l('Promo aktif! Hemat', 'Promo active! Save') }} <span x-text="formatPrice(promoDiscount)"></span></p>
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
            @keydown.escape.window="isChatOpen && (isChatOpen = false)"
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
                @click="isChatOpen = false"
            ></div>

            <div class="evomi-product-modal__frame evomi-product-modal__frame--chat" x-show="isChatOpen" @click.self="isChatOpen = false">
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
                        <button type="button" class="evomi-overlay-close" @click="isChatOpen = false" :aria-label="$L('Tutup', 'Close')">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 16 16" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4 4 12M4 4l8 8"/></svg>
                        </button>
                    </div>

                    <div class="evomi-chat-panel__body">
                        <div class="evomi-chat-panel__welcome">
                            <p class="text-[12px] font-semibold text-slate-500 mb-1">{{ evomi_l('Tentang produk', 'About this product') }}</p>
                            <p class="text-[13px] text-slate-800 leading-snug">
                                {{ evomi_l('Halo! Ada yang bisa kami bantu terkait', 'Hi! How can we help with') }}
                                <span class="font-semibold" :style="accentTextStyle" x-text="title"></span>?
                            </p>
                        </div>

                        <template x-for="bubble in chatBubbles" :key="bubble.id">
                            <div class="evomi-chat-panel__row" :class="bubble.type === 'user' ? 'is-user' : 'is-admin'">
                                <div
                                    class="evomi-chat-panel__bubble"
                                    :class="bubble.type === 'user' ? 'is-user' : 'is-admin'"
                                    :style="bubble.type === 'user' ? accentSurfaceStyle : {}"
                                    x-text="bubble.text"
                                ></div>
                            </div>
                        </template>
                    </div>

                    <div class="evomi-chat-panel__footer">
                        <div class="evomi-chat-panel__chips">
                            <template x-for="tpl in chatTemplates" :key="tpl">
                                <button type="button" class="evomi-chat-panel__chip" @click="draft = tpl" x-text="tpl"></button>
                            </template>
                        </div>
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

    {{-- Kurir modal — teleport ke body agar fixed di tengah viewport --}}
    <template x-teleport="body">
        <div x-show="showKurirList" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 backdrop-blur-sm p-4" @keydown.escape.window="showKurirList = false">
            <div class="absolute inset-0" @click="showKurirList = false"></div>
            <div class="relative bg-white w-full max-w-[450px] rounded-[24px] shadow-2xl flex flex-col overflow-hidden">
                <div class="flex justify-between items-center p-5 border-b border-gray-100">
                    <h3 class="font-nohemi text-[18px] font-bold text-[#1E2939]">{{ evomi_l('Pilih Pengiriman', 'Choose Shipping') }}</h3>
                    <button type="button" @click="showKurirList = false" class="text-gray-400 hover:text-gray-700 p-1 rounded-full hover:bg-gray-100">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="p-5 overflow-y-auto max-h-[60vh] flex flex-col gap-3">
                    <template x-for="kurir in kurirs" :key="kurir.id">
                        <button
                            type="button"
                            @click="selectKurir(kurir)"
                            class="cursor-pointer p-4 flex justify-between items-center rounded-[16px] border transition-all text-left"
                            :style="{ borderColor: selectedKurir?.id === kurir.id ? accent : undefined, borderWidth: selectedKurir?.id === kurir.id ? '2px' : '1px' }"
                            :class="selectedKurir?.id === kurir.id ? 'bg-blue-50/30' : 'border-gray-200 hover:bg-gray-50'"
                        >
                            <div class="flex flex-col gap-1 min-w-0 pr-3">
                                <span class="font-parkinsans font-semibold text-[15px] text-[#364153]">
                                    <span x-text="kurir.nama"></span>
                                    <span class="font-normal text-gray-500" x-text="'(' + kurir.jenis + ')'"></span>
                                </span>
                                <span class="font-parkinsans text-[13px] text-[#6A7282]">
                                    {{ evomi_l('Estimasi tiba', 'Estimated arrival') }} <span x-text="estimasiTiba(kurir)"></span>
                                    <span x-show="kurir.estimasi_hari" x-text="' · ±' + kurir.estimasi_hari + ' ' + $L('hari', 'days')"></span>
                                </span>
                                <span class="font-parkinsans text-[12px] text-[#99A1AF] truncate" x-show="kurir.destinasi" x-text="kurir.destinasi"></span>
                            </div>
                            <span class="font-parkinsans font-bold text-[16px]" :style="accentTextStyle" x-text="formatPrice(kurir.harga)"></span>
                        </button>
                    </template>
                    <div x-show="!kurirs.length" x-cloak class="p-4 text-center text-[14px] text-gray-500 font-parkinsans">
                        {{ $detailCms->get('disclaimer', 'empty_hint', evomi_l('Memuat data kurir...', 'Loading courier data...')) }}
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
