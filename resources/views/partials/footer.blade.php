@php
    $footerAccent = $themeAccent ?? '#1172BA';
    $footerCms = \App\Support\CmsStorefront::forPage('footer');
    $fTitle = $footerCms->get('bulletin', 'title', evomi_l('Buletin Evomi', 'Evomi Bulletin'));
    $fDesc = $footerCms->richText('bulletin', 'desc', evomi_l(
        'Daftar untuk menerima koleksi terbaru, penawaran eksklusif, dan cerita tentang setiap karakter aroma.',
        'Subscribe to receive the latest collections, exclusive offers, and stories behind every scent character.'
    ));
    $fCta = $footerCms->get('bulletin', 'cta', evomi_l('Daftar', 'Subscribe'));
    $fMenuHeading = $footerCms->get('menu', 'heading', 'Menu');
    $fHelpHeading = $footerCms->get('help', 'heading', evomi_l('Bantuan', 'Help'));
    $fSocialHeading = $footerCms->get('social', 'heading', evomi_l('Social', 'Follow Us'));
    $fCopyright = $footerCms->get('legal', 'copyright', '© '.date('Y').' evomi.id — Every Version of Me');
    $igUrl = $footerCms->get('social', 'instagram_url', 'https://instagram.com/evomi.id');
    $twUrl = $footerCms->get('social', 'twitter_url', 'https://twitter.com/evomi');
    $fbUrl = $footerCms->get('social', 'facebook_url', 'https://facebook.com/evomi');
@endphp
<footer
    class="evomi-footer w-full py-10 md:pt-12 md:pb-8 px-5 md:px-8 lg:px-24 relative font-nohemi text-white"
    style="background-color: {{ $footerAccent }}; --footer-accent: {{ $footerAccent }}"
>
    <div class="flex flex-col lg:flex-row justify-between gap-y-12 lg:gap-y-0 mb-12 md:mb-8">
        {{-- Buletin --}}
        <div class="evomi-footer__bulletin flex flex-col gap-3 md:gap-4 w-full lg:w-[45%] max-w-[400px] mx-auto lg:mx-0 text-center lg:text-left items-center lg:items-start" data-footer-enter="up">
            <h3 class="text-[32px] md:text-[40px] text-white font-bold leading-tight">{{ $fTitle }}</h3>
            <p class="text-[16px] md:text-[18px] text-white opacity-90 leading-relaxed">
                {!! $fDesc !!}
            </p>
            <form
                class="flex flex-col gap-3 w-full mt-3"
                x-data="evomiNewsletter(@js($fCta))"
                @submit.prevent="submit"
            >
                <div class="flex flex-row gap-2 w-full">
                    <input
                        type="email"
                        x-model="email"
                        :disabled="submitting"
                        placeholder="email@kamu.com"
                        class="flex-grow bg-white rounded-full outline-none px-4 md:px-5 h-[50px] md:h-[48px] text-[14px] md:text-[16px] text-gray-600 placeholder-gray-400 shadow-sm min-w-0 disabled:bg-gray-100 disabled:cursor-not-allowed"
                    >
                    <button
                        type="submit"
                        :disabled="submitting"
                        class="footer-daftar-btn flex-shrink-0 w-[100px] md:w-[120px] h-[50px] md:h-[48px] rounded-full text-[14px] md:text-[16px] font-bold transition-all shadow-sm bg-white disabled:opacity-70 disabled:cursor-not-allowed"
                    >
                        <span x-text="submitting ? '...' : ctaLabel">{{ $fCta }}</span>
                    </button>
                </div>

                @include('partials.turnstile-field', ['theme' => 'dark'])

                {{-- Simple toast modal --}}
                <template x-teleport="body">
                    <div
                        x-show="toast"
                        x-cloak
                        class="fixed inset-0 z-[110] flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm"
                        @click="if (toast && toast.type !== 'loading') toast = null"
                    >
                        <div
                            @click.stop
                            class="relative bg-white rounded-[24px] p-8 max-w-[340px] w-full text-center shadow-2xl overflow-hidden"
                            x-show="toast"
                            x-transition
                        >
                            <div
                                class="mx-auto flex items-center justify-center h-20 w-20 rounded-full mb-5"
                                :class="{
                                    'bg-blue-50 text-blue-500': toast?.type === 'loading',
                                    'bg-green-50 text-green-500': toast?.type === 'success',
                                    'bg-red-50 text-red-500': toast?.type === 'error'
                                }"
                            >
                                <template x-if="toast?.type === 'loading'">
                                    <svg class="h-10 w-10 animate-spin text-[#1172BA]" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </template>
                                <template x-if="toast?.type === 'success'">
                                    <svg class="h-10 w-10 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                </template>
                                <template x-if="toast?.type === 'error'">
                                    <svg class="h-10 w-10 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </template>
                            </div>
                            <div class="space-y-3">
                                <h3 class="text-[20px] font-bold text-gray-800 tracking-wide" x-text="toast?.title"></h3>
                                <p class="text-[14px] text-gray-500 leading-relaxed" x-text="toast?.message"></p>
                            </div>
                            <template x-if="toast?.type === 'success' || toast?.type === 'error'">
                                <button
                                    type="button"
                                    @click="toast = null"
                                    class="mt-6 w-full py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-full text-[14px] font-bold transition-colors"
                                >
                                    {{ evomi_l('Tutup', 'Close') }}
                                </button>
                            </template>
                        </div>
                    </div>
                </template>
            </form>
        </div>

        {{-- Menu / Bantuan / Social --}}
        <div class="evomi-footer__nav grid grid-cols-2 sm:grid-cols-3 gap-y-8 gap-x-4 w-full lg:w-[45%] mt-2 lg:mt-0 text-left">
            <div class="flex flex-col gap-3" data-footer-enter="up">
                <span class="text-[14px] md:text-[16px] text-white/70 font-medium tracking-wide">{{ $fMenuHeading }}</span>
                <ul class="flex flex-col gap-2 md:gap-3 text-white">
                    <li>
                        <a href="{{ route('beranda') }}" class="text-[14px] md:text-[16px] inline-block w-fit hover:scale-110 hover:font-bold transition-all origin-left" data-soft-nav>{{ $footerCms->get('menu', 'beranda', evomi_l('Beranda', 'Home')) }}</a>
                    </li>
                    <li>
                        <a href="{{ route('belanja') }}" class="text-[14px] md:text-[16px] inline-block w-fit hover:scale-110 hover:font-bold transition-all origin-left" data-soft-nav>{{ $footerCms->get('menu', 'belanja', evomi_l('Belanja', 'Shop')) }}</a>
                    </li>
                    <li>
                        <a href="{{ route('artikel') }}" class="text-[14px] md:text-[16px] inline-block w-fit hover:scale-110 hover:font-bold transition-all origin-left" data-soft-nav>{{ $footerCms->get('menu', 'artikel', evomi_l('Artikel', 'Articles')) }}</a>
                    </li>
                    <li>
                        <a href="{{ route('kuis') }}" class="text-[14px] md:text-[16px] inline-block w-fit hover:scale-110 hover:font-bold transition-all origin-left" data-soft-nav>{{ $footerCms->get('menu', 'kuis', evomi_l('Temukan Aromamu', 'Find Your Scent')) }}</a>
                    </li>
                </ul>
            </div>

            <div class="flex flex-col gap-3" data-footer-enter="up">
                <span class="text-[14px] md:text-[16px] text-white/70 font-medium tracking-wide">{{ $fHelpHeading }}</span>
                <ul class="flex flex-col gap-2 md:gap-3 text-white">
                    <li>
                        <a href="{{ route('faq') }}" class="text-[14px] md:text-[16px] inline-block w-fit hover:scale-110 hover:font-bold transition-all origin-left" data-soft-nav data-open-faq>{{ $footerCms->get('help', 'faq', 'FAQ') }}</a>
                    </li>
                    <li>
                        <a href="{{ route('kontak') }}" class="text-[14px] md:text-[16px] inline-block w-fit hover:scale-110 hover:font-bold transition-all origin-left" data-soft-nav data-open-kontak>{{ $footerCms->get('help', 'kontak', evomi_l('Kontak', 'Contact')) }}</a>
                    </li>
                </ul>
            </div>

            <div class="flex flex-col gap-3 col-span-2 sm:col-span-1" data-footer-enter="up">
                <span class="text-[14px] md:text-[16px] text-white/70 font-medium tracking-wide">{{ $fSocialHeading }}</span>
                <div class="flex gap-4 text-white">
                    <a
                        href="{{ $igUrl }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        aria-label="Instagram Evomi"
                        class="hover:scale-110 transition-transform"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6 md:w-7 md:h-7"><path d="M7.75 2h8.5A5.75 5.75 0 0 1 22 7.75v8.5A5.75 5.75 0 0 1 16.25 22h-8.5A5.75 5.75 0 0 1 2 16.25v-8.5A5.75 5.75 0 0 1 7.75 2zm0 1.5A4.25 4.25 0 0 0 3.5 7.75v8.5A4.25 4.25 0 0 0 7.75 20.5h8.5a4.25 4.25 0 0 0 4.25-4.25v-8.5A4.25 4.25 0 0 0 16.25 3.5h-8.5zM12 7a5 5 0 1 1 0 10 5 5 0 0 1 0-10zm0 1.5a3.5 3.5 0 1 0 0 7 3.5 3.5 0 0 0 0-7zm5.25-.75a1.25 1.25 0 1 1 0 2.5 1.25 1.25 0 0 1 0-2.5z"/></svg>
                    </a>
                    <a
                        href="{{ $twUrl }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        aria-label="Twitter Evomi"
                        class="hover:scale-110 transition-transform"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6 md:w-7 md:h-7"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.744l7.727-8.924L1.254 2.25H8.08l4.253 5.622L18.244 2.25zm-1.161 17.52h1.833L7.084 4.126H5.117L17.083 19.77z"/></svg>
                    </a>
                    <a
                        href="{{ $fbUrl }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        aria-label="Facebook Evomi"
                        class="hover:scale-110 transition-transform"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6 md:w-7 md:h-7"><path d="M22 12.07C22 6.48 17.52 2 11.93 2S1.86 6.48 1.86 12.07c0 5.02 3.66 9.18 8.44 9.93v-7.03H7.9v-2.9h2.4V9.85c0-2.37 1.41-3.68 3.57-3.68 1.03 0 2.12.18 2.12.18v2.33h-1.19c-1.18 0-1.54.73-1.54 1.48v1.78h2.63l-.42 2.9h-2.21V22c4.78-.75 8.44-4.91 8.44-9.93z"/></svg>
                    </a>
                </div>
                <span class="text-[16px] md:text-[18px] text-white mt-1">evomi.id</span>
            </div>
        </div>
    </div>

    {{-- Bottom bar --}}
    <div class="evomi-footer__rule mb-6 md:mb-8" data-footer-enter="up">
        <div class="w-full h-px rounded-full bg-white/30"></div>
    </div>
    <div class="evomi-footer__legal flex flex-col md:flex-row justify-between items-center text-white text-[14px] opacity-90 gap-y-2 text-center md:text-left" data-footer-enter="up">
        <p>{{ str_contains($fCopyright, '©') ? $fCopyright : ('© '.date('Y').' evomi.id — Every Version of Me') }}</p>
        <p>{{ evomi_l('Discover the scent of every personality', 'Discover the scent of every personality') }}</p>
    </div>
</footer>
