@props(['activeMenu' => null])

@php
    $activeMenu = $activeMenu
        ?? (request()->routeIs('profile.chat') ? 'chat' : null)
        ?? (request()->routeIs('profile.cart') ? 'cart' : null)
        ?? (request()->routeIs('profile.payments') ? 'payments' : null)
        ?? (request()->routeIs('profile.history*') ? 'history' : null)
        ?? (request()->routeIs('profile.wishlist') ? 'wishlist' : null)
        ?? 'settings';

    // Menu keranjang, pembayaran, riwayat, dan wishlist disembunyikan sementara
    // seiring pembelian dialihkan ke marketplace. Route, controller, dan view-nya
    // sengaja dibiarkan utuh supaya tinggal didaftarkan ulang di sini bila dipakai lagi.
    $menuItems = [
        ['key' => 'settings', 'href' => route('profile.index'), 'label' => evomi_l('Biodata Diri', 'Personal Info'), 'badge' => null, 'color' => '#1172BA'],
        ['key' => 'chat', 'href' => route('profile.chat'), 'label' => evomi_l('Chat', 'Chat'), 'badge' => 'unread', 'color' => '#1172BA'],
    ];
@endphp

    <div
        class="evomi-profile-shell max-w-7xl mx-auto px-4 py-8 sm:py-12 sm:px-6 lg:px-8 bg-white min-h-[70vh]"
        x-data="evomiProfileShell(@js($activeMenu))"
        data-profile-page="1"
        data-active-menu="{{ $activeMenu }}"
    >
    <div
        x-show="!ready"
        x-cloak
        class="py-24 flex justify-center"
    >
        <div class="w-8 h-8 border-4 border-gray-200 border-t-[#1172BA] rounded-full animate-spin"></div>
    </div>

    <div x-show="ready" x-cloak class="flex flex-col lg:flex-row lg:items-stretch gap-6 lg:gap-8">
        <aside class="w-full lg:w-72 shrink-0">
            <div class="bg-white rounded-2xl sm:rounded-3xl border border-gray-100 p-4 sm:p-5 sticky top-6">
                {{-- Kartu identitas di kepala menu, seperti panel akun marketplace.
                     Tanpa saldo: Evomi tidak punya dompet, jadi barisnya dihilangkan
                     alih-alih ditampilkan kosong. --}}
                <div class="flex items-center gap-3 px-1 pb-4 mb-3 border-b border-gray-100">
                    <div class="h-12 w-12 shrink-0 rounded-full overflow-hidden ring-2 ring-[#1172BA]/15 bg-[#1172BA]/10 flex items-center justify-center">
                        <template x-if="avatarUrl">
                            <img :src="avatarUrl" alt="" class="h-full w-full object-cover">
                        </template>
                        <template x-if="!avatarUrl">
                            <span class="text-base font-bold text-[#1172BA]" x-text="userInitial"></span>
                        </template>
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-bold text-gray-900 truncate" x-text="userName || '{{ evomi_l('Akun Saya', 'My Account') }}'"></p>
                        <p class="text-xs text-gray-500 truncate" x-text="userEmail"></p>
                    </div>
                </div>

                <nav
                    x-ref="profileTrack"
                    class="profile-nav-track relative space-y-1.5"
                >
                    <div
                        class="profile-nav-indicator"
                        :style="indicatorStyle"
                        aria-hidden="true"
                    ></div>

                    @foreach ($menuItems as $item)
                        <a
                            href="{{ $item['href'] }}"
                            data-soft-nav
                            data-profile-key="{{ $item['key'] }}"
                            data-profile-color="{{ $item['color'] }}"
                            class="profile-nav-item relative z-[1] flex items-center gap-3.5 px-4 py-3.5 text-sm font-medium rounded-2xl transition-colors duration-200"
                            :class="activeKey === '{{ $item['key'] }}' ? 'is-active text-white font-semibold' : 'text-gray-600'"
                            style="--profile-item-color: {{ $item['color'] }}"
                            @click="previewTo('{{ $item['key'] }}')"
                        >
                            @include('partials.profile-icon', ['name' => $item['key'], 'active' => false])
                            <span>{{ $item['label'] }}</span>
                            @if ($item['badge'])
                                <span
                                    class="ml-auto flex h-5 min-w-5 px-1 items-center justify-center rounded-full text-[10px] font-bold"
                                    :class="activeKey === '{{ $item['key'] }}' ? 'bg-white text-black' : 'bg-green-500 text-white'"
                                    x-show="badgeLabel('{{ $item['badge'] }}')"
                                    x-text="badgeLabel('{{ $item['badge'] }}')"
                                    x-cloak
                                ></span>
                            @endif
                        </a>
                    @endforeach
                </nav>
            </div>
        </aside>

        <div class="flex-1 min-w-0 profile-content-panel" data-profile-content>
            {{ $slot }}
        </div>
    </div>
</div>
