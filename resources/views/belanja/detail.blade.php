@php
    $detailPayload = [
        'id' => $product['id'],
        'title' => $product['title'],
        'accent' => $product['accent'],
        'price' => $product['price'],
        'stock' => $product['stock'],
        'gallery' => $gallery,
        'characterUrl' => $characterUrl,
        'kurirs' => $kurirs,
        'promo' => $promo,
        'loginUrl' => route('login'),
    ];
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
                <h4 class="font-nohemi text-[18px] md:text-[20px] font-bold mb-1" style="color: {{ $product['accent'] }}">Harga</h4>
                <span class="font-nohemi text-[26px] md:text-[32px] font-semibold text-[#1A1A1A]">{{ $product['price_label'] }}</span>
            </div>

            <div class="mb-8">
                <h4 class="font-nohemi text-[20px] font-semibold mb-4" style="color: {{ $product['accent'] }}">Detail Produk</h4>
                <div class="grid grid-cols-2 gap-y-3.5 gap-x-4 text-[14px] font-parkinsans font-normal">
                    <div class="flex"><span class="w-28 text-[#99A1AF] shrink-0">Kondisi</span><span class="text-[#364153]">{{ $product['kondisi'] }}</span></div>
                    <div class="flex"><span class="w-28 text-[#99A1AF] shrink-0">Berat Satuan</span><span class="text-[#364153]">{{ $product['berat_satuan'] }} g</span></div>
                    <div class="flex"><span class="w-28 text-[#99A1AF] shrink-0">Kategori</span><span class="text-[#364153]">{{ $product['kategori'] }}</span></div>
                    <div class="flex"><span class="w-28 text-[#99A1AF] shrink-0">Brand</span><span class="text-[#364153]">{{ $product['brand'] }}</span></div>
                    <div class="flex"><span class="w-28 text-[#99A1AF] shrink-0">Min. Beli</span><span class="text-[#364153]">1 Buah</span></div>
                    <div class="flex"><span class="w-28 text-[#99A1AF] shrink-0">Etalase</span><span class="text-[#364153]">{{ $product['etalase'] }}</span></div>
                </div>
            </div>

            <div class="mb-8">
                <h4 class="font-nohemi text-[20px] font-semibold mb-3" style="color: {{ $product['accent'] }}">Disclaimer untuk Ketentuan COMPLAIN</h4>
                <div class="text-[14px] font-parkinsans font-normal text-[#4A5565] leading-relaxed flex flex-col gap-1.5">
                    @foreach ($disclaimers as $i => $text)
                        <p>{{ $i + 1 }}. {{ $text }}</p>
                    @endforeach
                </div>
            </div>

            <div class="bg-white border border-gray-100 rounded-[16px] p-6 shadow-sm flex flex-col gap-5">
                <h4 class="font-nohemi text-[20px] font-semibold text-[#1E2939]">Pengiriman</h4>

                <div class="flex gap-4 items-start">
                    <svg class="text-gray-400 mt-0.5 shrink-0 w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/></svg>
                    <div>
                        <p class="text-[15px] text-[#6A7282] font-parkinsans">Dikirim dari</p>
                        <p class="text-[16px] font-semibold text-[#364153] font-parkinsans">{{ $product['alamat_awal_pengiriman'] }}</p>
                    </div>
                </div>

                <div class="flex gap-4 items-start">
                    <svg class="text-gray-400 mt-0.5 shrink-0 w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m9.75 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h.375c.621 0 1.125-.504 1.125-1.125V14.25m-9.75 0h9.75"/></svg>
                    <div class="w-full">
                        <p class="text-[15px] text-[#6A7282] font-parkinsans" x-text="selectedKurir ? `${selectedKurir.nama} - ${selectedKurir.jenis}` : 'Ongkir mulai'"></p>
                        <p class="text-[16px] font-semibold text-[#364153] font-parkinsans" x-text="selectedKurir ? formatPrice(selectedKurir.harga) : '-'"></p>
                        <p class="text-[15px] text-[#99A1AF] font-parkinsans mt-0.5">
                            Estimasi tiba <span x-text="selectedKurir ? estimasiTiba(selectedKurir) : '-'"></span>
                        </p>
                        <p class="text-[13px] text-[#99A1AF] font-parkinsans mt-0.5" x-show="selectedKurir?.destinasi" x-text="selectedKurir?.destinasi" x-cloak></p>
                        <button
                            type="button"
                            @click="showKurirList = true"
                            class="mt-3 text-left font-parkinsans text-[15px] font-semibold underline-offset-4 hover:underline"
                            :style="accentTextStyle"
                        >Lihat Kurir Lainnya</button>
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
                        Diskusi Terbuka
                    </div>

                    <div class="p-5 flex flex-col gap-5">
                        <div>
                            <div class="font-nohemi text-[28px] font-bold text-[#101828] leading-none" x-text="formatPrice(price)"></div>
                            <p class="mt-1 text-[12px] font-parkinsans text-[#CA3500]" x-show="promoDiscount > 0" x-cloak>
                                Promo −<span x-text="formatPrice(promoDiscount)"></span>
                            </p>
                        </div>

                        <div>
                            <p class="font-parkinsans text-[14px] text-[#6A7282] mb-2">Atur jumlah dan catatan</p>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center border border-gray-300 rounded-[8px] h-[38px]">
                                    <button type="button" @click="changeQty('dec')" :disabled="quantity <= 1 || isOutOfStock" class="px-3 text-gray-500 hover:text-black transition flex h-full items-center justify-center disabled:opacity-40">−</button>
                                    <input type="text" readonly :value="isOutOfStock ? 0 : quantity" class="w-12 text-center text-[15px] font-bold border-x border-gray-300 h-full focus:outline-none text-[#1A1A1A]">
                                    <button type="button" @click="changeQty('inc')" :disabled="isOutOfStock || quantity >= stock" class="px-3 text-gray-500 hover:text-black transition flex h-full items-center justify-center disabled:opacity-40">+</button>
                                </div>
                                <span class="font-parkinsans text-[14px] text-[#6A7282]">
                                    Stok: <span x-text="isOutOfStock ? 'Stok habis' : stock"></span>
                                </span>
                            </div>
                        </div>

                        <div class="flex flex-col gap-1.5 mt-1">
                            <div class="flex justify-between items-center">
                                <span class="text-[14px] font-parkinsans text-[#6A7282]">Subtotal</span>
                                <span class="text-[14px] font-nohemi font-semibold text-[#101828]" x-text="formatPrice(productSubtotal)"></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-[14px] font-parkinsans text-[#6A7282]">
                                    Ongkir
                                    <span class="text-[#99A1AF]" x-show="selectedKurir?.nama" x-text="'(' + selectedKurir.nama + ')'" x-cloak></span>
                                </span>
                                <span class="text-[14px] font-nohemi font-semibold text-[#101828]" x-text="formatPrice(shippingCost)"></span>
                            </div>
                            <div class="flex justify-between items-center" x-show="promoDiscount > 0" x-cloak>
                                <span class="text-[14px] font-parkinsans text-[#CA3500]">Promo</span>
                                <span class="text-[14px] font-nohemi font-semibold text-[#CA3500]" x-text="'−' + formatPrice(promoDiscount)"></span>
                            </div>
                            <div class="flex justify-between items-center pt-1 border-t border-gray-100 mt-0.5">
                                <span class="text-[17px] font-parkinsans text-[#6A7282]">Total</span>
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
                                x-text="isOutOfStock ? 'Stok habis' : 'Beli Langsung'"
                            ></button>
                            <button
                                type="button"
                                @click="addToCart()"
                                :disabled="isOutOfStock || actionBusy"
                                class="w-full bg-white font-nohemi text-[16px] font-semibold py-3 rounded-full border shadow-sm hover:bg-gray-50 active:scale-95 transition-all disabled:opacity-50"
                                :style="{ ...accentTextStyle, borderColor: accent }"
                                x-text="isOutOfStock ? 'Stok habis' : (statusMessage || '+ Keranjang')"
                            ></button>
                        </div>

                        <div class="flex justify-center gap-12 mt-2 font-parkinsans font-medium text-[14px] text-[#6A7282]" :style="{ '--hover-color': accent }">
                            <button type="button" @click="isChatOpen = true" class="flex flex-col items-center gap-1.5 hover:text-[var(--hover-color)] transition">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z"/></svg>
                                Chat
                            </button>
                            <button type="button" @click="toggleWishlist()" class="flex flex-col items-center gap-1.5 transition" :class="isWishlisted ? 'text-pink-500' : 'hover:text-[var(--hover-color)]'">
                                <svg class="w-5 h-5" :class="isWishlisted ? 'fill-pink-500 text-pink-500' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z"/></svg>
                                Wishlist
                            </button>
                            <button type="button" @click="showShareModal = true" class="flex flex-col items-center gap-1.5 hover:text-[var(--hover-color)] transition">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M7.217 10.907a2.25 2.25 0 1 0 0 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186 9.566-5.314m-9.566 7.5 9.566 5.314m0 0a2.25 2.25 0 1 0 3.935 2.186 2.25 2.25 0 0 0-3.935-2.186Zm0-12.814a2.25 2.25 0 1 0 3.933-2.185 2.25 2.25 0 0 0-3.933 2.185Z"/></svg>
                                Share
                            </button>
                        </div>

                        <div class="bg-[#FFF4E5] border border-[#FFE8CC] text-[#CA3500] rounded-[8px] p-3 flex gap-2.5 items-center mt-2 font-parkinsans" x-show="promoDiscount > 0" x-cloak>
                            <svg class="w-[18px] h-[18px] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                            <p class="text-[14px] leading-snug">Promo aktif! Hemat <span x-text="formatPrice(promoDiscount)"></span></p>
                        </div>

                        <div class="text-center text-[14px] font-medium text-green-600" x-show="statusMessage" x-text="statusMessage" x-cloak></div>
                    </div>
                </div>

                <div x-ref="jaminanBox" class="bg-white border border-gray-100 rounded-[16px] p-5 shadow-sm flex flex-col gap-4">
                    <div class="flex gap-3 items-start">
                        <svg class="w-5 h-5 shrink-0 mt-0.5" :style="accentTextStyle" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 12 2.25c-2.663 0-5.176.736-7.312 2.018C3.226 5.07 2.25 6.63 2.25 8.25v.75c0 6.212 4.23 11.49 9.75 12.75 5.52-1.26 9.75-6.538 9.75-12.75v-.75c0-1.62-.976-3.18-2.438-3.982A11.959 11.959 0 0 0 12 1.714Z"/></svg>
                        <div>
                            <h5 class="font-parkinsans font-semibold text-[14px] text-[#364153]">Jaminan Produk</h5>
                            <p class="font-parkinsans text-[14px] text-[#99A1AF] mt-0.5">Uang kembali bila produk tidak sesuai</p>
                        </div>
                    </div>
                    <hr class="border-gray-100">
                    <div class="flex gap-3 items-start">
                        <svg class="w-5 h-5 shrink-0 mt-0.5" :style="accentTextStyle" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                        <div>
                            <h5 class="font-parkinsans font-semibold text-[14px] text-[#364153]">Bebas Ongkir</h5>
                            <p class="font-parkinsans text-[14px] text-[#99A1AF] mt-0.5">Syarat & ketentuan berlaku</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Chat modal (dummy) --}}
    <div
        x-show="isChatOpen"
        x-cloak
        class="fixed inset-0 z-[120] flex items-end sm:items-center justify-center sm:justify-end p-0 sm:p-6 bg-black/40 backdrop-blur-sm"
        @keydown.escape.window="isChatOpen = false"
    >
        <div class="absolute inset-0" @click="isChatOpen = false"></div>
        <div class="relative w-full sm:max-w-[380px] h-[70vh] sm:h-[520px] bg-white rounded-t-[24px] sm:rounded-[24px] shadow-2xl flex flex-col overflow-hidden">
            <div class="px-4 py-3 text-white flex items-center justify-between" :style="accentSurfaceStyle">
                <div>
                    <p class="font-nohemi font-semibold text-[15px]">Chat Admin Evomi</p>
                    <p class="text-[12px] opacity-90 font-parkinsans">Biasanya membalas dalam beberapa menit</p>
                </div>
                <button type="button" @click="isChatOpen = false" class="p-1 rounded-full hover:bg-white/15">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="flex-1 overflow-y-auto p-4 bg-[#F8FAFC] space-y-3">
                <div class="bg-white border border-gray-100 rounded-[16px] p-3 text-[13px] font-parkinsans text-[#6A7282]">
                    Halo! Ada yang bisa kami bantu terkait <span class="font-semibold text-[#364153]" x-text="title"></span>?
                </div>
                <template x-for="bubble in chatBubbles" :key="bubble.id">
                    <div class="flex" :class="bubble.type === 'user' ? 'justify-end' : 'justify-start'">
                        <div
                            class="max-w-[80%] rounded-[16px] px-3 py-2 text-[13px] font-parkinsans"
                            :class="bubble.type === 'user' ? 'text-white' : 'bg-white border border-gray-100 text-[#364153]'"
                            :style="bubble.type === 'user' ? accentSurfaceStyle : {}"
                            x-text="bubble.text"
                        ></div>
                    </div>
                </template>
            </div>
            <div class="p-3 border-t border-gray-100 space-y-2">
                <div class="flex flex-wrap gap-2">
                    <template x-for="tpl in chatTemplates" :key="tpl">
                        <button type="button" @click="draft = tpl" class="text-[11px] px-2.5 py-1 rounded-full border border-gray-200 text-[#6A7282] hover:bg-gray-50 font-parkinsans" x-text="tpl"></button>
                    </template>
                </div>
                <div class="flex gap-2">
                    <input type="text" x-model="draft" @keydown.enter.prevent="sendChat()" placeholder="Ketik pesan Anda ke admin di sini..." class="flex-1 rounded-full border border-gray-200 px-4 py-2.5 text-[13px] font-parkinsans outline-none focus:border-gray-300">
                    <button type="button" @click="sendChat()" class="px-4 rounded-full text-white font-semibold text-[13px]" :style="accentSurfaceStyle">Kirim</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Kurir modal --}}
    <div x-show="showKurirList" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
        <div class="absolute inset-0" @click="showKurirList = false"></div>
        <div class="relative bg-white w-full max-w-[450px] rounded-[24px] shadow-2xl flex flex-col overflow-hidden">
            <div class="flex justify-between items-center p-5 border-b border-gray-100">
                <h3 class="font-nohemi text-[18px] font-bold text-[#1E2939]">Pilih Pengiriman</h3>
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
                                Estimasi tiba <span x-text="estimasiTiba(kurir)"></span>
                                <span x-show="kurir.estimasi_hari" x-text="' · ±' + kurir.estimasi_hari + ' hari'"></span>
                            </span>
                            <span class="font-parkinsans text-[12px] text-[#99A1AF] truncate" x-show="kurir.destinasi" x-text="kurir.destinasi"></span>
                        </div>
                        <span class="font-parkinsans font-bold text-[16px]" :style="accentTextStyle" x-text="formatPrice(kurir.harga)"></span>
                    </button>
                </template>
            </div>
        </div>
    </div>

    {{-- Share modal --}}
    <div x-show="showShareModal" x-cloak class="fixed inset-0 z-[110] flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
        <div class="absolute inset-0" @click="showShareModal = false"></div>
        <div class="relative bg-white w-full max-w-[400px] rounded-[24px] shadow-2xl overflow-hidden">
            <div class="flex justify-between items-center p-5 border-b border-gray-100">
                <h3 class="font-nohemi text-[18px] font-bold text-[#1E2939]">Bagikan Produk</h3>
                <button type="button" @click="showShareModal = false" class="text-gray-400 hover:text-gray-700 p-1.5 rounded-full hover:bg-gray-100">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-4 gap-4 mb-6">
                    <a :href="shareLinks.whatsapp" target="_blank" rel="noopener noreferrer" class="flex flex-col items-center gap-2 group">
                        <div class="w-12 h-12 bg-green-50 text-green-500 rounded-full flex items-center justify-center group-hover:bg-green-500 group-hover:text-white transition-colors">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z"/></svg>
                        </div>
                        <span class="text-[12px] font-parkinsans text-[#6A7282]">WhatsApp</span>
                    </a>
                    <a :href="shareLinks.facebook" target="_blank" rel="noopener noreferrer" class="flex flex-col items-center gap-2 group">
                        <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center group-hover:bg-blue-600 group-hover:text-white transition-colors">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
                        </div>
                        <span class="text-[12px] font-parkinsans text-[#6A7282]">Facebook</span>
                    </a>
                    <a :href="shareLinks.twitter" target="_blank" rel="noopener noreferrer" class="flex flex-col items-center gap-2 group">
                        <div class="w-12 h-12 bg-gray-100 text-gray-800 rounded-full flex items-center justify-center group-hover:bg-gray-800 group-hover:text-white transition-colors">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M22 4s-.7 2.1-2 3.4c1.6 10-9.4 17.3-18 11.6 2.2.1 4.4-.6 6-2C3 15.5.5 9.6 3 5c2.2 2.6 5.6 4.1 9 4-.9-4.2 4-6.6 7-3.8 1.1 0 3-1.2 3-1.2z"/></svg>
                        </div>
                        <span class="text-[12px] font-parkinsans text-[#6A7282]">Twitter</span>
                    </a>
                    <button type="button" @click="copyLink()" class="flex flex-col items-center gap-2 group">
                        <div class="w-12 h-12 bg-pink-50 text-pink-500 rounded-full flex items-center justify-center group-hover:bg-pink-500 group-hover:text-white transition-colors">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>
                        </div>
                        <span class="text-[12px] font-parkinsans text-[#6A7282]">Instagram</span>
                    </button>
                </div>
                <div class="flex items-center gap-2 bg-[#F8F9FA] p-1.5 rounded-[12px] border border-[#E5E7EB]">
                    <input type="text" :value="productUrl" readonly class="bg-transparent outline-none flex-1 text-[13px] text-[#6A7282] font-parkinsans px-3 overflow-hidden text-ellipsis whitespace-nowrap">
                    <button type="button" @click="copyLink()" class="px-4 py-2 bg-white border border-gray-200 shadow-sm rounded-[8px] text-[13px] font-semibold shrink-0" :style="isCopied ? accentTextStyle : {}" x-text="isCopied ? 'Disalin' : 'Salin'"></button>
                </div>
            </div>
        </div>
    </div>

    {{-- Login alert — teleport ke body agar fixed selalu di tengah viewport (page-shell punya transform) --}}
    <template x-teleport="body">
        <div x-show="alert.show" x-cloak class="fixed inset-0 z-[200] flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
            <div class="absolute inset-0" @click="alert.show = false"></div>
            <div class="relative bg-white w-full max-w-[360px] rounded-[24px] shadow-2xl p-6 text-center">
                <div class="w-14 h-14 rounded-full flex items-center justify-center mx-auto mb-4 bg-amber-50 text-amber-500">
                    <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 12 2.25c-2.663 0-5.176.736-7.312 2.018C3.226 5.07 2.25 6.63 2.25 8.25v.75c0 6.212 4.23 11.49 9.75 12.75 5.52-1.26 9.75-6.538 9.75-12.75v-.75c0-1.62-.976-3.18-2.438-3.982A11.959 11.959 0 0 0 12 1.714Z"/></svg>
                </div>
                <h3 class="font-nohemi text-[18px] font-bold text-[#1E2939] mb-2">Perlu Login</h3>
                <p class="font-parkinsans text-[14px] text-[#6A7282] mb-6" x-text="alert.message"></p>
                <div class="flex gap-3">
                    <button type="button" @click="alert.show = false" class="flex-1 py-3 rounded-[12px] font-parkinsans font-semibold text-[14px] bg-gray-100 hover:bg-gray-200">Tutup</button>
                    <a :href="loginUrl" class="flex-1 py-3 rounded-[12px] font-parkinsans font-semibold text-[14px] text-white text-center" :style="accentSurfaceStyle" data-soft-nav>Login Sekarang</a>
                </div>
            </div>
        </div>
    </template>

    {{-- Bubble divider: half sits on white, half overlaps footer so no white hairline --}}
    <div class="belanja-detail-bubbles absolute bottom-0 left-0 w-full h-0 overflow-visible pointer-events-none z-20">
        <div class="flex w-max gap-[10px] md:gap-[15px] animate-slide-left absolute left-0 bottom-0 translate-y-1/2">
            @for ($i = 0; $i < 80; $i++)
                <div class="w-[30px] h-[30px] md:w-[46px] md:h-[46px] rounded-full flex-shrink-0" style="background-color: {{ $product['accent'] }}"></div>
            @endfor
        </div>
    </div>
</section>
