@php
    $products = $products ?? [];
    $cms = \App\Support\CmsStorefront::forPage('belanja');
    $emptyTitle = $cms->get('list', 'empty_title', evomi_l('Belum ada produk', 'No products yet'));
    $emptyHint = $cms->get('list', 'empty_hint', evomi_l('Produk akan muncul di sini setelah tersedia.', 'Products will appear here when available.'));
@endphp

<section class="belanja-products bg-[#f6f6f6] flex flex-col items-center w-full pt-0 pb-0 px-3 md:px-6 relative overflow-visible">
    @if (count($products) === 0)
        <div class="relative z-10 w-full max-w-xl mx-auto px-4 py-16 text-center">
            <h2 class="text-lg font-semibold text-gray-900">{{ $emptyTitle }}</h2>
            <p class="mt-2 text-sm text-gray-600">{{ $emptyHint }}</p>
        </div>
    @else
        {{-- Figma: gap ~32px, container 920 — jarak antar produk seperti semula --}}
        <div class="belanja-products__grid relative z-10 w-full max-w-[920px] grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-5 lg:gap-8 justify-items-center py-4 md:py-6">
            @foreach ($products as $index => $product)
                @php
                    $accent = $product['accent'] ?? '#1172BA';
                    $soft = $product['soft_accent'] ?? '#E6F3FB';
                    $imgSrc = ! empty($product['img_url'])
                        ? $product['img']
                        : asset('src/images/'.$product['img']);
                    $tilt = $index % 2 === 0 ? 'belanja-card--tilt-right' : 'belanja-card--tilt-left';
                @endphp
                <a
                    href="{{ route('belanja.show', $product['id']) }}"
                    class="belanja-card group font-nohemi relative {{ $tilt }}"
                    style="--card-accent: {{ $accent }}; --card-soft: {{ $soft }}; border-color: {{ $accent }}; background-color: {{ $soft }}"
                    data-soft-nav
                >
                    <div class="belanja-card__media" style="background-color: {{ $accent }}">
                        <span class="belanja-card__badge" style="color: {{ $accent }}">
                            {{ $product['badge'] }}
                        </span>

                        {{-- Figma bottle stage: oversized rotated frame + object-cover --}}
                        <div class="belanja-card__bottle-stage" aria-hidden="true">
                            <div class="belanja-card__bottle-rot">
                                <div class="belanja-card__bottle-frame">
                                    <img
                                        src="{{ $imgSrc }}"
                                        alt=""
                                        class="belanja-card__bottle"
                                        loading="lazy"
                                        decoding="async"
                                    >
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="belanja-card__body" style="background-color: {{ $soft }}">
                        <h3 class="belanja-card__title" style="color: {{ $accent }}">
                            {{ $product['title'] }}
                        </h3>
                        <p class="belanja-card__desc font-parkinsans" style="color: {{ $accent }}">
                            {{ $product['description'] }}
                        </p>
                        <div class="belanja-card__footer">
                            <span class="belanja-card__price" style="color: {{ $accent }}">
                                {{ $product['price_label'] }}
                            </span>
                            <span class="belanja-card__cta" style="background-color: {{ $accent }}" aria-hidden="true">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-[10.5px] h-[10.5px]">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                                </svg>
                            </span>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</section>
