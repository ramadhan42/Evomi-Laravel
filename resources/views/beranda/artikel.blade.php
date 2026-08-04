<section class="relative w-full bg-[#0d5f9c] py-14 md:py-20 overflow-hidden">
    <div class="relative z-10 max-w-6xl mx-auto px-5 md:px-8">
        <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-8 md:mb-10">
            <div>
                <h2 class="text-white text-[26px] md:text-[38px] font-bold leading-tight">Artikel Parfum</h2>
                <p class="text-white/75 text-[13px] md:text-[15px] mt-2 max-w-lg">
                    Cerita aroma, tips wewangian, dan inspirasi karakter Evomi.
                </p>
            </div>
            <a
                href="{{ route('artikel') }}"
                class="inline-flex items-center gap-2 self-start rounded-full bg-white text-[#1172BA] px-5 py-2.5 text-sm font-semibold hover:bg-[#9CD6FF] transition-colors"
            >
                Lihat Semua
            </a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 md:gap-6">
            @foreach ([1, 2, 3] as $i)
                <a href="{{ route('artikel') }}" class="group block overflow-hidden rounded-[24px] border border-white/10 bg-white/5 hover:bg-white/10 transition-colors">
                    <div class="aspect-[16/10] bg-[#1172BA]/40 flex items-center justify-center">
                        <img
                            src="{{ asset('src/images/section 1/star-medium.png') }}"
                            alt=""
                            class="w-12 h-12 opacity-40 brightness-0 invert"
                        >
                    </div>
                    <div class="p-5">
                        <span class="text-[11px] uppercase tracking-wide text-[#9CD6FF] font-semibold">Parfum</span>
                        <h3 class="mt-2 text-white font-semibold text-lg leading-snug group-hover:text-[#9CD6FF] transition-colors">
                            Inspirasi aroma #{{ $i }}
                        </h3>
                        <p class="mt-2 text-white/70 text-[13px] line-clamp-2">
                            Jelajahi kisah di balik karakter wewangian Evomi.
                        </p>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</section>
