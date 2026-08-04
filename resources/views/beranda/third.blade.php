<section class="relative z-10 bg-[#0071BC] flex flex-col items-center text-center w-full px-2 overflow-hidden pb-12 md:pb-14">
    <div class="flex items-center justify-center gap-3 md:gap-[22px] mt-10 md:mt-14 mb-6 md:mb-[30px] parallax-self" data-reveal data-parallax="0.05">
        <h2 class="text-[28px] md:text-[42px] lg:text-[48px] leading-[1.08] font-semibold">
            <span class="text-white">Brand</span>
            <span class="text-[#A5E194]"> Value</span>
        </h2>
        <div class="w-6 h-6 md:w-7 md:h-8 relative flex justify-center items-center pointer-events-none shrink-0">
            <img
                src="{{ asset('src/images/section 3/star-medium.png') }}"
                alt=""
                class="w-full h-full object-contain brightness-0 invert"
            >
        </div>
    </div>

    @php
        $values = [
            [
                'title' => ['Self', 'Awareness'],
                'icon' => 'star-medium.png',
                'desc' => [
                    ['t' => 'Setiap aroma dirancang untuk ', 'b' => false],
                    ['t' => 'merepresentasikan versi diri', 'b' => true],
                    ['t' => ', emosi, dan karakter manusia yang berbeda, sehingga parfum menjadi medium ekspresi personal, ', 'b' => false],
                    ['t' => 'bukan sekadar wewangian', 'b' => true],
                    ['t' => '.', 'b' => false],
                ],
            ],
            [
                'title' => ['Environment', 'Friendly'],
                'icon' => 'peaceful-calm.png',
                'desc' => [
                    ['t' => 'Mengusung ', 'b' => false],
                    ['t' => 'kepedulian terhadap lingkungan', 'b' => true],
                    ['t' => ' melalui pemanfaatan daur ulang tutup botol plastik menjadi bagian dari identitas produk, sebagai bentuk kontribusi kecil dalam mengurangi limbah plastik sekaligus menghadirkan nilai ', 'b' => false],
                    ['t' => 'sustainability', 'b' => true],
                    ['t' => '.', 'b' => false],
                ],
            ],
            [
                'title' => ['Playful Design', 'Concept'],
                'icon' => 'triangle.png',
                'desc' => [
                    ['t' => 'Dikemas dengan pendekatan ', 'b' => false],
                    ['t' => 'visual yang playful, ekspresif', 'b' => true],
                    ['t' => ', dan dekat dengan generasi muda agar pengalaman menggunakan parfum terasa lebih personal dan menyenangkan.', 'b' => false],
                ],
            ],
        ];
    @endphp

    <div class="flex justify-center w-full max-w-6xl mt-2 md:mt-4 mb-8 relative z-10">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-10 md:gap-14 w-full px-4 sm:px-6 md:px-10 pt-6 md:pt-5 pb-6 md:pb-5">
            @foreach ($values as $card)
                <div
                    class="flex flex-col parallax-self"
                    data-reveal
                    data-reveal-delay="{{ number_format($loop->index * 0.12, 2, '.', '') }}"
                    data-parallax="0.1"
                >
                    <h3 class="bv-card-title text-white text-[18px] md:text-[22px] lg:text-[26px] mb-4 md:mb-6 text-left px-1 font-semibold">
                        @foreach ($card['title'] as $line)
                            <span class="bv-card-title-line">{{ $line }}</span>
                        @endforeach
                    </h3>
                    <div class="relative bg-white rounded-[24px] md:rounded-3xl p-5 sm:p-6 md:p-8 shadow-xl flex flex-col flex-grow transition-[box-shadow,filter] duration-300 ease-out hover:z-10 hover:shadow-2xl hover:brightness-[1.02]">
                        <div class="absolute -top-4 -right-2 md:-top-5 md:-right-5 w-[35px] md:w-[45px] h-[35px] md:h-[45px] z-20 flex justify-center items-center pointer-events-none">
                            <img
                                src="{{ asset('src/images/section 3/' . $card['icon']) }}"
                                alt=""
                                class="object-contain drop-shadow-md w-full h-full"
                            >
                        </div>
                        <p class="text-left text-[#0071BC] text-[13px] md:text-[15px] leading-[1.5] font-parkinsans font-medium">
                            @foreach ($card['desc'] as $part)
                                @if ($part['b'])
                                    <span class="font-bold">{{ $part['t'] }}</span>
                                @else
                                    {{ $part['t'] }}
                                @endif
                            @endforeach
                        </p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <p class="text-white text-[20px] md:text-[28px] mt-4 md:mt-[10px] mb-6 md:mb-8 relative z-10 font-bold">
        Every Version of Me
    </p>
</section>
