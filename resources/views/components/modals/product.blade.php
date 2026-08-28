@php
    // Tombol marketplace menggantikan alur keranjang, sama seperti di halaman
    // detail belanja. Tautan per varian tetap bersumber dari config/evomi.php,
    // dikunci oleh personality_type produk.
    $marketplaceChannels = config('evomi.marketplaces.channels', []);
    $marketplaceLabel = evomi_l('Beli di', 'Buy on');

    // Modal memuat produk lewat AJAX, jadi tautannya dikirim sekali sebagai data
    // dan dipilih di sisi klien - bukan inline script, supaya tetap aman di CSP.
    $marketplacePayload = [
        'channels' => collect($marketplaceChannels)
            ->map(fn ($meta, $key) => [
                'key' => $key,
                'label' => $meta['label'] ?? ucfirst($key),
                'color' => $meta['color'] ?? '#111111',
            ])
            ->values(),
        'links' => config('evomi.marketplaces.links', []),
    ];

    // Harga promo dari config/evomi.php - sama sumbernya dengan halaman detail
    // belanja, jadi angka di modal dan di halaman tidak akan berbeda.
    $comparePrice = (float) (config('evomi.pricing.compare_at') ?? 0);
    $comparePriceLabel = $comparePrice > 0 ? 'Rp' . number_format($comparePrice, 0, ',', '.') : '';

    // Produk dimuat lewat AJAX, jadi harga jual pengganti dikirim sebagai data.
    $pricingPayload = ['display' => config('evomi.pricing.display')];
@endphp

<script type="application/json" id="evomi-marketplace-config">@json($marketplacePayload)</script>
<script type="application/json" id="evomi-pricing-config">@json($pricingPayload)</script>

{{-- Product detail modal — Figma-style split panel --}}
<div
    class="evomi-product-modal fixed inset-0 z-[220] flex items-center justify-center p-3 sm:p-5"
    x-data
    :class="$store.evomiProductModal.open ? 'pointer-events-auto' : 'pointer-events-none'"
    @keydown.escape.window="$store.evomiProductModal.open && $store.evomiProductModal.close()"
>
    <div
        class="evomi-product-modal__backdrop absolute inset-0"
        x-show="$store.evomiProductModal.open"
        x-cloak
        x-transition:enter="evomi-product-modal-fade-enter"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="evomi-product-modal-fade-leave"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="$store.evomiProductModal.close()"
    ></div>

    <div
        class="evomi-product-modal__panel relative z-10 flex w-full max-w-[860px] max-h-[92vh] flex-col overflow-hidden bg-white md:h-[560px] md:flex-row"
        role="dialog"
        aria-modal="true"
        :aria-label="$store.evomiProductModal.product?.title || $L('Detail produk', 'Product detail')"
        @click.stop
        x-show="$store.evomiProductModal.open"
        x-cloak
        x-transition:enter="evomi-product-modal-panel-enter"
        x-transition:enter-start="opacity-0 translate-y-4 scale-[0.97]"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="evomi-product-modal-panel-leave"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    >
        {{-- Left: soft accent + product visual --}}
        <div
            class="relative h-[220px] w-full shrink-0 sm:h-[260px] md:h-full md:w-[46%]"
            :style="{ backgroundColor: $store.evomiProductModal.softAccent }"
            :class="$store.evomiProductModal.loading ? 'evomi-product-modal__media--loading' : ''"
        >
            <template x-if="$store.evomiProductModal.loading">
                <div class="evomi-product-modal__skeleton-media absolute inset-0 flex flex-col items-center justify-center gap-4 p-8">
                    <div class="evomi-product-modal__skeleton-bottle" aria-hidden="true"></div>
                    <div class="evomi-product-modal__skeleton-pulse" aria-hidden="true"></div>
                    <p class="text-[11px] font-semibold tracking-[0.14em] uppercase text-slate-400">{{ evomi_l('Memuat...', 'Loading...') }}</p>
                </div>
            </template>

            <template x-if="!$store.evomiProductModal.loading">
                <div class="absolute inset-0 flex items-center justify-center p-6 sm:p-8 md:p-10">
                    <img
                        x-show="$store.evomiProductModal.product"
                        :src="$store.evomiProductModal.imageUrl"
                        :alt="$store.evomiProductModal.product?.title || 'Evomi'"
                        class="evomi-product-modal__image max-h-full max-w-full object-contain"
                    >
                </div>
            </template>

            <span
                class="absolute left-4 top-4 inline-flex items-center rounded-full px-3 py-1 text-[11px] font-bold text-white shadow-sm"
                :style="{ backgroundColor: $store.evomiProductModal.accent }"
                x-show="$store.evomiProductModal.product && !$store.evomiProductModal.loading"
                x-cloak
                x-text="$store.evomiProductModal.badge"
            ></span>
        </div>

        {{-- Right: details --}}
        <div class="relative flex w-full flex-1 flex-col overflow-y-auto px-5 py-5 sm:px-7 sm:py-6 md:w-[54%] md:px-8 md:py-7">
            <button
                type="button"
                class="evomi-product-modal__close absolute right-4 top-4 z-10 flex h-8 w-8 items-center justify-center rounded-full"
                @click="$store.evomiProductModal.close()"
                :aria-label="$L('Tutup', 'Close')"
            >
                <svg class="w-4 h-4" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4 4 12M4 4l8 8"/>
                </svg>
            </button>

            <template x-if="$store.evomiProductModal.error">
                <div class="flex flex-1 flex-col items-center justify-center text-center py-10 pr-6">
                    <p class="text-sm text-rose-600 font-medium mb-4" x-text="$store.evomiProductModal.error"></p>
                    <button
                        type="button"
                        class="px-5 py-2.5 rounded-xl text-sm font-semibold text-white"
                        :style="{ backgroundColor: $store.evomiProductModal.accent }"
                        @click="$store.evomiProductModal.close()"
                    >{{ evomi_l('Tutup', 'Close') }}</button>
                </div>
            </template>

            <template x-if="!$store.evomiProductModal.error && $store.evomiProductModal.loading">
                <div class="evomi-product-modal__skeleton-copy flex flex-1 flex-col min-h-0 pr-6" aria-hidden="true">
                    <div class="evomi-skel evomi-skel--lg w-[78%] mb-3"></div>
                    <div class="evomi-skel evomi-skel--sm w-[42%] mb-5"></div>
                    <div class="evomi-skel evomi-skel--line w-full mb-2"></div>
                    <div class="evomi-skel evomi-skel--line w-[92%] mb-2"></div>
                    <div class="evomi-skel evomi-skel--line w-[68%] mb-6"></div>
                    <div class="grid grid-cols-2 gap-2.5 sm:gap-3 mb-6">
                        <div class="evomi-skel evomi-skel--block"></div>
                        <div class="evomi-skel evomi-skel--block"></div>
                        <div class="evomi-skel evomi-skel--block"></div>
                        <div class="evomi-skel evomi-skel--block"></div>
                    </div>
                    <div class="mt-auto mb-3">
                        <div class="space-y-2">
                            <div class="evomi-skel evomi-skel--xs w-14"></div>
                            <div class="evomi-skel evomi-skel--md w-28"></div>
                        </div>
                        <div class="evomi-skel evomi-skel--pill w-28"></div>
                    </div>
                    <div class="evomi-skel evomi-skel--cta w-full"></div>
                </div>
            </template>

            <template x-if="!$store.evomiProductModal.error && $store.evomiProductModal.product">
                <div class="flex flex-1 flex-col min-h-0 pr-2">
                    <h2
                        class="text-[26px] sm:text-[30px] font-bold leading-tight tracking-tight pr-8"
                        :style="{ color: $store.evomiProductModal.accent }"
                        x-text="$store.evomiProductModal.product.title"
                    ></h2>

                    <div class="mt-2.5 mb-3.5 flex items-center gap-2">
                        <div class="flex items-center gap-0.5" :style="{ color: $store.evomiProductModal.accent }" aria-hidden="true">
                            <template x-for="i in 5" :key="'star-' + i">
                                <svg class="w-3.5 h-3.5" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                            </template>
                        </div>
                        <span class="text-[12px] text-slate-400" x-text="$store.evomiProductModal.ratingLabel"></span>
                    </div>

                    <p
                        class="text-[13px] sm:text-[14px] leading-relaxed text-slate-500 mb-5 line-clamp-3"
                        x-text="$store.evomiProductModal.product.description || ''"
                    ></p>

                    <div class="grid grid-cols-2 gap-2.5 sm:gap-3 mb-6">
                        <div class="evomi-product-modal__spec">
                            <p class="evomi-product-modal__spec-label">{{ evomi_l('VOLUME', 'VOLUME') }}</p>
                            <p class="evomi-product-modal__spec-value" x-text="$store.evomiProductModal.volumeLabel"></p>
                        </div>
                        <div class="evomi-product-modal__spec">
                            <p class="evomi-product-modal__spec-label">{{ evomi_l('KETAHANAN', 'LONGEVITY') }}</p>
                            <p class="evomi-product-modal__spec-value" x-text="$store.evomiProductModal.longevityLabel"></p>
                        </div>
                        <div class="evomi-product-modal__spec">
                            <p class="evomi-product-modal__spec-label">{{ evomi_l('JENIS', 'TYPE') }}</p>
                            <p class="evomi-product-modal__spec-value" x-text="$store.evomiProductModal.typeLabel"></p>
                        </div>
                        <div class="evomi-product-modal__spec">
                            <p class="evomi-product-modal__spec-label">{{ evomi_l('GENDER', 'GENDER') }}</p>
                            <p class="evomi-product-modal__spec-value" x-text="$store.evomiProductModal.genderLabel"></p>
                        </div>
                    </div>

                    <div class="mt-auto mb-3">
                        <div>
                            <p class="text-[10px] font-semibold tracking-[1.5px] text-slate-400 mb-1">{{ evomi_l('HARGA', 'PRICE') }}</p>
                            @if ($comparePriceLabel !== '')
                                <span class="evomi-price__compare">{{ $comparePriceLabel }}</span>
                            @endif
                            <p
                                class="text-[24px] sm:text-[26px] font-bold leading-none tabular-nums"
                                :style="{ color: $store.evomiProductModal.accent }"
                                x-text="$store.evomiProductModal.priceLabel"
                            ></p>
                        </div>
                    </div>

                    {{-- Pembelian diarahkan ke marketplace, sama seperti halaman detail belanja --}}
                    <div class="evomi-mp evomi-mp--modal" x-show="$store.evomiProductModal.hasMarketplaces" x-cloak>
                        <p class="evomi-mp__lead">{{ $marketplaceLabel }}</p>

                        <div class="evomi-mp__list">
                            @foreach ($marketplaceChannels as $mpKey => $mpMeta)
                                <a
                                    x-show="$store.evomiProductModal.marketplaceUrl('{{ $mpKey }}')"
                                    x-cloak
                                    :href="$store.evomiProductModal.marketplaceUrl('{{ $mpKey }}')"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="evomi-mp__btn evomi-mp__btn--{{ $mpKey }}"
                                    style="--mp-color: {{ $mpMeta['color'] ?? '#111111' }}"
                                    aria-label="{{ $marketplaceLabel }} {{ $mpMeta['label'] ?? ucfirst($mpKey) }}"
                                >
                                    <span class="evomi-mp__icon" aria-hidden="true">
                                        @include('partials.icons.marketplace-' . $mpKey)
                                    </span>

                                    <span class="evomi-mp__text">{{ $mpMeta['label'] ?? ucfirst($mpKey) }}</span>

                                    <svg class="evomi-mp__arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M5 12h13" />
                                        <path d="m12 5 7 7-7 7" />
                                    </svg>
                                </a>
                            @endforeach
                        </div>
                    </div>

                    {{-- Varian tanpa tautan: beri keterangan, jangan sisakan ruang kosong --}}
                    <p
                        class="evomi-mp__empty"
                        x-show="!$store.evomiProductModal.hasMarketplaces"
                        x-cloak
                    >{{ evomi_l('Belum tersedia di marketplace.', 'Not available on marketplaces yet.') }}</p>

                    <p
                        class="mt-3 text-xs font-medium min-h-[1rem] text-center"
                        :class="{
                            'text-emerald-600': $store.evomiProductModal.statusTone === 'success',
                            'text-rose-600': $store.evomiProductModal.statusTone === 'error',
                            'text-slate-500': $store.evomiProductModal.statusTone === 'info'
                        }"
                        x-text="$store.evomiProductModal.statusMessage"
                    ></p>
                </div>
            </template>
        </div>
    </div>
</div>
