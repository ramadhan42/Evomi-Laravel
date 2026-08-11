{{-- Figma CartSidebar — teleport to body (header transform breaks fixed) --}}
<template x-teleport="body">
    <div
        class="evomi-account-drawer fixed inset-0 z-[210]"
        :class="accountDrawerOpen ? 'pointer-events-auto is-open' : 'pointer-events-none'"
        @keydown.escape.window="accountDrawerOpen && closeAccountDrawer()"
    >
        <div
            class="evomi-account-drawer__backdrop absolute inset-0"
            x-show="accountDrawerOpen"
            x-cloak
            x-transition:enter="ease-[cubic-bezier(0.22,1,0.36,1)] duration-400"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-[cubic-bezier(0.4,0,1,1)] duration-280"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="closeAccountDrawer()"
        ></div>

        <aside
            class="evomi-account-drawer__panel absolute inset-y-0 right-0 flex w-[min(100%,400px)] flex-col"
            role="dialog"
            aria-modal="true"
            aria-label="{{ evomi_l('Keranjang Evomi', 'Evomi Cart') }}"
            x-show="accountDrawerOpen"
            x-cloak
            x-transition:enter="ease-[cubic-bezier(0.22,1,0.36,1)] duration-450"
            x-transition:enter-start="translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="ease-[cubic-bezier(0.4,0,0.2,1)] duration-300"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="translate-x-full"
            @click.stop
        >
            <div class="evomi-account-drawer__shell flex h-full min-h-0 flex-col bg-white">
                {{-- Header: Evomi · Close --}}
                <div class="evomi-account-drawer__header shrink-0 flex items-center justify-between gap-3 px-5 pt-5 pb-4">
                    <p class="evomi-account-drawer__brand text-[22px] font-bold leading-none tracking-tight">Evomi</p>
                    <button
                        type="button"
                        class="evomi-account-drawer__close shrink-0 flex h-8 w-8 items-center justify-center rounded-full"
                        @click="closeAccountDrawer()"
                        :aria-label="$L('Tutup', 'Close')"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 16 16" stroke="currentColor" stroke-width="1.333" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4 4 12M4 4l8 8"/>
                        </svg>
                    </button>
                </div>

                {{-- Tabs: Keranjang | Lacak Pesanan --}}
                <div class="shrink-0 px-5 pb-4">
                    <div class="evomi-account-drawer__tabs" role="tablist">
                        <button
                            type="button"
                            role="tab"
                            class="evomi-account-drawer__tab"
                            :class="{ 'is-active': drawerTab === 'cart' }"
                            :aria-selected="(drawerTab === 'cart').toString()"
                            @click="drawerTab = 'cart'"
                        >
                            @include('partials.icons.bag', ['class' => 'w-3.5 h-3.5 shrink-0'])
                            <span>{{ evomi_l('Keranjang', 'Cart') }}</span>
                            <span
                                class="evomi-account-drawer__tab-count"
                                x-show="drawerCartCount > 0"
                                x-cloak
                                x-text="drawerCartCount"
                            ></span>
                        </button>
                        <button
                            type="button"
                            role="tab"
                            class="evomi-account-drawer__tab"
                            :class="{ 'is-active': drawerTab === 'track' }"
                            :aria-selected="(drawerTab === 'track').toString()"
                            @click="drawerTab = 'track'"
                        >
                            @include('partials.icons.truck', ['class' => 'w-3.5 h-3.5 shrink-0'])
                            <span>{{ evomi_l('Lacak Pesanan', 'Track Order') }}</span>
                            <span
                                class="evomi-account-drawer__tab-count"
                                x-show="drawerTrackCount > 0"
                                x-cloak
                                x-text="drawerTrackCount"
                            ></span>
                        </button>
                    </div>
                </div>

                <div class="evomi-account-drawer__body flex-1 min-h-0 overflow-y-auto overscroll-contain px-5">
                    {{-- Keranjang tab --}}
                    <div x-show="drawerTab === 'cart'" x-cloak class="pb-4 min-h-full flex flex-col">
                        <div
                            x-show="!isLoggedIn"
                            x-cloak
                            class="mb-4 rounded-2xl border border-amber-200 bg-amber-50 px-3.5 py-3"
                        >
                            <p class="text-[12px] font-semibold text-amber-900 leading-snug">
                                {{ evomi_l('Peringatan guest', 'Guest warning') }}
                            </p>
                            <p class="text-[11px] text-amber-800/90 mt-1 leading-relaxed">
                                {{ evomi_l('Produk / pesanan yang Anda masukkan ke keranjang dan checkout bisa hilang dari keranjang Anda. Segera daftar dan login untuk checkout dan lainnya.', 'Products / orders you add to the cart and checkout can disappear from your cart. Please register and log in for checkout and more.') }}
                            </p>
                            <div class="mt-2.5 flex flex-wrap gap-2">
                                <button type="button" class="text-[11px] font-bold text-[#1172BA]" @click="goGuestAuth('{{ route('register') }}')">{{ evomi_l('Daftar', 'Register') }}</button>
                                <button type="button" class="text-[11px] font-bold text-slate-700" @click="goGuestAuth('{{ route('login') }}')">{{ evomi_l('Login', 'Log in') }}</button>
                            </div>
                            <div class="mt-3 flex gap-2" x-show="drawerCartItems.length > 0" x-cloak>
                                <input
                                    type="email"
                                    class="evomi-account-drawer__resi-input flex-1 text-[12px]"
                                    x-model="guestEmailInput"
                                    :placeholder="$L('Email untuk salinan keranjang', 'Email for cart copy')"
                                >
                                <button
                                    type="button"
                                    class="evomi-account-drawer__btn-primary shrink-0 rounded-xl px-3 py-2 text-[11px] font-bold text-white"
                                    :disabled="guestCartEmailBusy"
                                    @click="sendGuestCartEmailFromDrawer()"
                                >
                                    <span x-text="guestCartEmailBusy ? $L('Mengirim...', 'Sending...') : $L('Kirim', 'Send')"></span>
                                </button>
                            </div>
                            <p class="mt-1.5 text-[11px] text-amber-900/80" x-show="guestCartEmailStatus" x-cloak x-text="guestCartEmailStatus"></p>
                        </div>

                        <div x-show="drawerCartLoading" class="py-16 flex flex-col items-center justify-center gap-3">
                            <div class="w-8 h-8 border-[3px] border-slate-200 border-t-[#1172BA] rounded-full animate-spin"></div>
                            <p class="text-[12px] text-slate-400 font-medium">{{ evomi_l('Memuat keranjang...', 'Loading cart...') }}</p>
                        </div>

                        <div
                            x-show="!drawerCartLoading && drawerCartItems.length === 0"
                            class="evomi-account-drawer__empty flex flex-1 flex-col items-center justify-center py-16 text-center"
                        >
                            <div class="evomi-account-drawer__empty-icon mb-4 flex h-20 w-20 items-center justify-center rounded-full">
                                @include('partials.icons.bag', ['class' => 'w-8 h-8'])
                            </div>
                            <p class="text-[14px] font-normal text-[#99a1af]">{{ evomi_l('Keranjang kamu masih kosong', 'Your cart is still empty') }}</p>
                        </div>

                        <div x-show="!drawerCartLoading && drawerCartItems.length > 0" class="space-y-3 pb-2">
                            <template x-for="(item, idx) in drawerCartItems" :key="item.id">
                                <div class="evomi-account-drawer__cart-item" :style="{ '--stagger': idx }">
                                    <div class="evomi-account-drawer__cart-thumb" :style="{ backgroundColor: (item.accent || '#1172BA') + '14' }">
                                        <img :src="item.imageUrl" :alt="item.title" x-on:error="$el.style.display='none'">
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-start justify-between gap-2">
                                            <div class="min-w-0">
                                                <p class="evomi-account-drawer__cart-title truncate" x-text="item.title"></p>
                                                <p class="evomi-account-drawer__cart-meta" x-text="item.meta || '30ml · EDP'"></p>
                                                <p class="evomi-account-drawer__cart-price" x-text="item.priceLabel"></p>
                                            </div>
                                            <button
                                                type="button"
                                                class="evomi-account-drawer__trash shrink-0"
                                                :disabled="drawerUpdatingId === item.id"
                                                @click="drawerRemoveItem(item)"
                                                :aria-label="$L('Hapus', 'Remove')"
                                            >
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                                                </svg>
                                            </button>
                                        </div>
                                        <div class="evomi-account-drawer__qty mt-3">
                                            <button type="button" :disabled="drawerUpdatingId === item.id" @click="drawerChangeQty(item, -1)" aria-label="-">−</button>
                                            <span x-text="item.quantity"></span>
                                            <button type="button" :disabled="drawerUpdatingId === item.id" @click="drawerChangeQty(item, 1)" aria-label="+">+</button>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Lacak Pesanan tab (data dari dashboard tracking/status) --}}
                    <div x-show="drawerTab === 'track'" x-cloak class="pb-6 space-y-4">
                        {{-- Guest: form no resi / no pesanan (hasil tetap di sidebar) --}}
                        <div x-show="!isLoggedIn" x-cloak class="evomi-account-drawer__track-card">
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#1172BA]/10 text-[#1172BA] mb-3">
                                @include('partials.icons.truck', ['class' => 'w-5 h-5'])
                            </div>
                            <h3 class="text-[15px] font-bold text-slate-900">{{ evomi_l('Lacak pengiriman', 'Track shipping') }}</h3>
                            <p class="text-[12px] text-slate-500 mt-1.5 leading-relaxed">{{ evomi_l('Masukkan nomor resi atau nomor pesanan untuk melihat status di sini.', 'Enter a tracking or order number to view status here.') }}</p>
                            <div class="mt-4 flex gap-2">
                                <input
                                    type="text"
                                    class="evomi-account-drawer__resi-input flex-1"
                                    x-model="drawerGuestResi"
                                    :placeholder="$L('No. resi / no. pesanan...', 'Tracking / order no...')"
                                    @keydown.enter.prevent="submitDrawerGuestResi()"
                                >
                                <button
                                    type="button"
                                    class="evomi-account-drawer__btn-primary shrink-0 rounded-xl px-4 py-2.5 text-[12px] font-bold text-white"
                                    :disabled="drawerTrackLoading"
                                    @click="submitDrawerGuestResi()"
                                >
                                    <span x-text="drawerTrackLoading ? $L('Mencari...', 'Searching...') : $L('Lacak', 'Track')"></span>
                                </button>
                            </div>
                            <p class="mt-2 text-[11px] text-rose-500" x-show="drawerGuestResiError" x-cloak x-text="drawerGuestResiError"></p>
                            <div class="mt-3 flex flex-wrap gap-3">
                                <button type="button" class="text-[12px] font-semibold text-slate-600" @click="goGuestAuth('{{ route('login') }}')">{{ evomi_l('Login', 'Log in') }}</button>
                                <button type="button" class="text-[12px] font-semibold text-[#1172BA]" @click="goGuestAuth('{{ route('register') }}')">{{ evomi_l('Daftar', 'Register') }}</button>
                            </div>
                        </div>

                        <div x-show="drawerTrackLoading && (isLoggedIn || drawerTrackItems.length === 0)" class="py-16 flex flex-col items-center justify-center gap-3">
                            <div class="w-8 h-8 border-[3px] border-slate-200 border-t-[#1172BA] rounded-full animate-spin"></div>
                            <p class="text-[12px] text-slate-400 font-medium">{{ evomi_l('Memuat pesanan...', 'Loading orders...') }}</p>
                        </div>

                        <div
                            x-show="isLoggedIn && !drawerTrackLoading && drawerTrackItems.length === 0"
                            class="evomi-account-drawer__empty flex flex-col items-center justify-center py-14 text-center"
                        >
                            <div class="evomi-account-drawer__empty-icon mb-4 flex h-20 w-20 items-center justify-center rounded-full text-[#cccccc]">
                                @include('partials.icons.truck', ['class' => 'w-8 h-8'])
                            </div>
                            <p class="text-[14px] font-medium text-[#99a1af]">{{ evomi_l('Belum ada pesanan untuk dilacak', 'No orders to track yet') }}</p>
                            <button
                                type="button"
                                class="evomi-account-drawer__btn-primary mt-4 rounded-2xl px-5 py-2.5 text-[12px] font-bold text-white"
                                @click="drawerTab = 'cart'"
                            >{{ evomi_l('Belanja dulu', 'Shop first') }}</button>
                        </div>

                        <div x-show="!drawerTrackLoading && drawerTrackItems.length > 0" class="space-y-4" x-cloak>
                            <div>
                                <p class="text-[12px] font-semibold text-slate-500 mb-2 px-0.5">{{ evomi_l('Pilih Pesanan', 'Select Order') }}</p>
                                <div
                                    class="evomi-track-chips -mx-1 px-1 overflow-x-auto"
                                    @wheel="scrollTrackChipsWheel($event)"
                                >
                                    <div class="flex gap-2 min-w-min pb-1">
                                        <template x-for="item in drawerTrackItems" :key="item.id">
                                            <button
                                                type="button"
                                                class="evomi-track-chip"
                                                :class="{ 'is-active': drawerTrackSelected?.id === item.id }"
                                                @click="selectDrawerTrack(item.id)"
                                            >
                                                <div class="evomi-track-chip__img" :style="{ backgroundColor: (item.accent || '#1172BA') + '22' }">
                                                    <img :src="item.imageUrl" :alt="item.title" x-on:error="$el.style.display='none'">
                                                </div>
                                                <div class="evomi-track-chip__body">
                                                    <p class="evomi-track-chip__code" x-text="item.code"></p>
                                                    <p class="evomi-track-chip__title" x-text="item.title"></p>
                                                    <p class="evomi-track-chip__status" :class="drawerTrackToneClass(item.status_tone)" x-text="item.status_label"></p>
                                                </div>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            <template x-if="drawerTrackSelected">
                                <div class="space-y-4">
                                    <div class="evomi-track-summary">
                                        <div class="flex items-start justify-between gap-3">
                                            <div>
                                                <p class="text-[11px] text-slate-400 font-medium">{{ evomi_l('Nomor Pesanan', 'Order Number') }}</p>
                                                <p class="text-[16px] font-bold text-slate-900 mt-0.5" x-text="drawerTrackSelected.code"></p>
                                            </div>
                                            <span
                                                class="evomi-track-badge"
                                                :class="drawerTrackToneClass(drawerTrackSelected.status_tone)"
                                                x-text="drawerTrackSelected.status_label"
                                            ></span>
                                        </div>
                                        <div class="grid grid-cols-2 gap-3 mt-4 pt-3 border-t border-slate-100">
                                            <div>
                                                <p class="text-[11px] text-slate-400">{{ evomi_l('Kurir', 'Courier') }}</p>
                                                <p class="text-[13px] font-semibold text-slate-800 mt-0.5" x-text="drawerTrackSelected.courier || '—'"></p>
                                            </div>
                                            <div>
                                                <p class="text-[11px] text-slate-400">{{ evomi_l('Tujuan', 'Destination') }}</p>
                                                <p class="text-[13px] font-semibold text-slate-800 mt-0.5" x-text="drawerTrackSelected.destination || '—'"></p>
                                            </div>
                                            <div class="col-span-2">
                                                <p class="text-[11px] text-slate-400">{{ evomi_l('No. Resi / No. Pesanan', 'Tracking / Order No.') }}</p>
                                                <p class="text-[13px] font-semibold text-slate-800 mt-0.5 break-all" x-text="drawerTrackSelected.tracking_number || drawerTrackSelected.id || '—'"></p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="evomi-track-progress">
                                        <div class="evomi-track-progress__steps">
                                            <template x-for="(step, idx) in drawerTrackSteps" :key="step.key">
                                                <div class="evomi-track-progress__step" :class="{ 'is-done': drawerTrackStepDone(idx), 'is-current': drawerTrackStepCurrent(idx) }">
                                                    <span class="evomi-track-progress__dot" x-text="drawerTrackStepDone(idx) && !drawerTrackStepCurrent(idx) ? '✓' : (idx + 1)"></span>
                                                    <span class="evomi-track-progress__label" x-text="step.label"></span>
                                                </div>
                                            </template>
                                        </div>
                                        <div class="evomi-track-progress__bar">
                                            <span :style="{ width: drawerTrackProgressPct() + '%' }"></span>
                                        </div>
                                    </div>

                                    <div>
                                        <div class="flex items-center gap-2 mb-3">
                                            <svg class="w-3.5 h-3.5 text-[#1172BA]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                                            <p class="text-[13px] font-semibold text-slate-800">{{ evomi_l('Riwayat Perjalanan', 'Shipment History') }}</p>
                                        </div>

                                        <div x-show="!drawerTrackSelected.timeline?.length" class="text-[12px] text-slate-400 py-2">
                                            {{ evomi_l('Riwayat diperbarui otomatis saat status diubah di dashboard.', 'History updates automatically when status is changed in the dashboard.') }}
                                        </div>

                                        <div class="evomi-track-timeline space-y-0">
                                            <template x-for="(entry, idx) in drawerTrackSelected.timeline" :key="'tl-' + idx">
                                                <div class="evomi-track-timeline__item" :class="{ 'is-first': idx === 0 }">
                                                    <div class="evomi-track-timeline__rail">
                                                        <span class="evomi-track-timeline__dot"></span>
                                                        <span class="evomi-track-timeline__line" x-show="idx < drawerTrackSelected.timeline.length - 1"></span>
                                                    </div>
                                                    <div class="evomi-track-timeline__content pb-5">
                                                        <div class="flex items-start justify-between gap-3">
                                                            <p class="text-[13px] font-semibold text-slate-900" x-text="entry.status"></p>
                                                            <div class="text-right shrink-0">
                                                                <p class="text-[11px] font-semibold text-slate-500" x-text="entry.timeLabel"></p>
                                                                <p class="text-[10px] text-slate-400" x-text="entry.dateLabel"></p>
                                                            </div>
                                                        </div>
                                                        <p class="text-[12px] text-slate-500 mt-1" x-show="entry.place" x-text="entry.place"></p>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </div>

                                    <div class="evomi-track-details">
                                        <p class="text-[13px] font-semibold text-slate-800 mb-3">{{ evomi_l('Detail Pengiriman', 'Shipping Details') }}</p>
                                        <div class="space-y-3">
                                            <div>
                                                <p class="text-[11px] text-slate-400">{{ evomi_l('Alamat', 'Address') }}</p>
                                                <p class="text-[12px] text-slate-700 mt-0.5 leading-relaxed" x-text="drawerTrackSelected.recipient?.address || '—'"></p>
                                            </div>
                                            <div class="grid grid-cols-2 gap-3">
                                                <div>
                                                    <p class="text-[11px] text-slate-400">{{ evomi_l('Estimasi', 'ETA') }}</p>
                                                    <p class="text-[12px] font-semibold text-slate-800 mt-0.5" x-text="drawerTrackSelected.estimated_delivery || '—'"></p>
                                                </div>
                                                <div>
                                                    <p class="text-[11px] text-slate-400">{{ evomi_l('No. Resi / No. Pesanan', 'Tracking / Order No.') }}</p>
                                                    <div class="mt-0.5 flex items-center gap-1.5">
                                                        <p class="text-[12px] font-semibold text-slate-800 truncate" x-text="drawerTrackSelected.tracking_number || drawerTrackSelected.id || '—'"></p>
                                                        <button
                                                            type="button"
                                                            class="evomi-track-copy"
                                                            x-show="drawerTrackSelected.tracking_number || drawerTrackSelected.id"
                                                            @click="copyDrawerResi()"
                                                            :aria-label="$L('Salin', 'Copy')"
                                                        >
                                                            <span x-text="drawerTrackCopied ? '✓' : '⧉'"></span>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- Footer checkout (cart tab + has items) --}}
                <div
                    class="evomi-account-drawer__footer shrink-0 px-5 pt-4 pb-5"
                    x-show="drawerTab === 'cart' && !drawerCartLoading && drawerCartItems.length > 0"
                    x-cloak
                >
                    <div class="space-y-2.5 mb-4">
                        <div class="flex items-center justify-between text-[13px]">
                            <span class="text-slate-500">{{ evomi_l('Subtotal', 'Subtotal') }}</span>
                            <span class="font-semibold text-slate-800 tabular-nums" x-text="drawerCartSubtotalLabel"></span>
                        </div>
                        <div class="flex items-center justify-between text-[13px]">
                            <span class="text-slate-500">{{ evomi_l('Ongkos kirim', 'Shipping') }}</span>
                            <span class="text-slate-400 font-medium">{{ evomi_l('Dihitung saat checkout', 'Calculated at checkout') }}</span>
                        </div>
                        <div class="evomi-account-drawer__total-row flex items-center justify-between pt-1">
                            <span class="text-[15px] font-bold text-slate-900">{{ evomi_l('Total', 'Total') }}</span>
                            <span class="text-[18px] font-bold text-slate-900 tabular-nums" x-text="drawerCartSubtotalLabel"></span>
                        </div>
                    </div>
                    <button
                        type="button"
                        class="evomi-account-drawer__btn-checkout w-full inline-flex items-center justify-center gap-2 rounded-2xl py-3.5 text-[14px] font-bold text-white"
                        @click="goDrawerCheckout()"
                    >
                        {{ evomi_l('Checkout Sekarang', 'Checkout Now') }}
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/>
                        </svg>
                    </button>
                </div>
            </div>
        </aside>
    </div>
</template>

{{-- Figma OrdersModal — Pesanan Saya --}}
<template x-teleport="body">
    <div
        class="evomi-orders-modal fixed inset-0 z-[220]"
        :class="ordersModalOpen ? 'pointer-events-auto is-open' : 'pointer-events-none'"
        @keydown.escape.window="ordersModalOpen && closeOrdersModal()"
    >
        <div
            class="evomi-orders-modal__backdrop absolute inset-0"
            x-show="ordersModalOpen"
            x-cloak
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="closeOrdersModal()"
        ></div>

        <div
            class="evomi-orders-modal__panel absolute inset-y-0 right-0 flex w-[min(100%,768px)] flex-col"
            role="dialog"
            aria-modal="true"
            aria-label="{{ evomi_l('Pesanan Saya', 'My Orders') }}"
            x-show="ordersModalOpen"
            x-cloak
            x-transition:enter="ease-[cubic-bezier(0.22,1,0.36,1)] duration-450"
            x-transition:enter-start="translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="ease-[cubic-bezier(0.4,0,0.2,1)] duration-300"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="translate-x-full"
            @click.stop
        >
            <div class="evomi-orders-modal__shell flex h-full min-h-0 flex-col">
                <div class="evomi-orders-modal__header shrink-0 flex items-center justify-between px-5 sm:px-7 py-5">
                    <h2 class="evomi-orders-modal__title">{{ evomi_l('Pesanan Saya', 'My Orders') }}</h2>
                    <button
                        type="button"
                        class="evomi-orders-modal__close flex size-9 items-center justify-center"
                        @click="closeOrdersModal()"
                        :aria-label="$L('Tutup', 'Close')"
                    >
                        <img src="{{ asset('src/images/orders/icon-close.svg') }}" alt="" class="size-7" width="28" height="28">
                    </button>
                </div>

                <div class="evomi-orders-modal__filters shrink-0 overflow-x-auto">
                    <div class="flex items-stretch min-w-max px-2" role="tablist">
                        <template x-for="tab in ordersFilterTabs" :key="tab.key">
                            <button
                                type="button"
                                role="tab"
                                class="evomi-orders-modal__filter"
                                :class="{ 'is-active': ordersFilter === tab.key }"
                                :aria-selected="(ordersFilter === tab.key).toString()"
                                @click="ordersFilter = tab.key"
                            >
                                <span class="evomi-orders-modal__filter-label" x-text="tab.label"></span>
                                <span
                                    class="evomi-orders-modal__filter-count"
                                    x-show="ordersFilterCount(tab.key) > 0"
                                    x-cloak
                                    x-text="ordersFilterCount(tab.key)"
                                ></span>
                            </button>
                        </template>
                    </div>
                </div>

                <div class="evomi-orders-modal__body flex-1 min-h-0 overflow-y-auto overscroll-contain p-5">
                    <div
                        x-show="!isLoggedIn"
                        x-cloak
                        class="mb-4 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3"
                    >
                        <p class="text-[12px] font-semibold text-amber-900">{{ evomi_l('Pesanan guest', 'Guest orders') }}</p>
                        <p class="text-[11px] text-amber-800/90 mt-1 leading-relaxed">
                            {{ evomi_l('Masukkan email yang dipakai saat checkout. Data guest bisa hilang di perangkat — kirim ringkasan ke email agar aman.', 'Enter the email used at checkout. Guest data can disappear on this device — email yourself a summary to keep it safe.') }}
                        </p>
                        <div class="mt-3 flex gap-2">
                            <input
                                type="email"
                                class="evomi-account-drawer__resi-input flex-1"
                                x-model="guestOrdersEmail"
                                :placeholder="$L('Email checkout guest', 'Guest checkout email')"
                                @keydown.enter.prevent="loadGuestOrders({ notify: guestOrdersNotify })"
                            >
                            <button
                                type="button"
                                class="evomi-orders-modal__retry shrink-0 !min-h-[40px] !px-4"
                                @click="loadGuestOrders({ notify: guestOrdersNotify })"
                            >{{ evomi_l('Cari', 'Find') }}</button>
                        </div>
                        <label class="mt-2.5 flex items-center gap-2 text-[11px] text-amber-900/90">
                            <input type="checkbox" class="rounded border-amber-300 text-[#1172BA]" x-model="guestOrdersNotify">
                            <span>{{ evomi_l('Kirim ringkasan pesanan ke email', 'Email me an order summary') }}</span>
                        </label>
                        <div class="mt-2.5 flex flex-wrap gap-3">
                            <button type="button" class="text-[12px] font-bold text-[#1172BA]" @click="goGuestAuth('{{ route('register') }}')">{{ evomi_l('Daftar', 'Register') }}</button>
                            <button type="button" class="text-[12px] font-bold text-slate-700" @click="goGuestAuth('{{ route('login') }}')">{{ evomi_l('Login', 'Log in') }}</button>
                        </div>
                    </div>

                    <div x-show="ordersLoading" class="py-20 flex flex-col items-center justify-center gap-3">
                        <div class="w-8 h-8 border-[3px] border-slate-200 border-t-[#1172BA] rounded-full animate-spin"></div>
                        <p class="text-[13px] text-slate-400 font-medium">{{ evomi_l('Memuat pesanan...', 'Loading orders...') }}</p>
                    </div>

                    <div x-show="!ordersLoading && ordersError" x-cloak class="rounded-xl border border-rose-100 bg-rose-50/60 px-5 py-10 text-center">
                        <p class="text-sm text-rose-600 font-medium mb-4" x-text="ordersError"></p>
                        <button type="button" class="evomi-orders-modal__retry" @click="loadOrders()">{{ evomi_l('Coba Lagi', 'Try Again') }}</button>
                    </div>

                    <div x-show="!ordersLoading && !ordersError && filteredOrders.length === 0" x-cloak class="rounded-xl border border-dashed border-slate-200 px-5 py-14 text-center">
                        <p class="text-[15px] font-semibold text-[#5d5d5d] mb-2">{{ evomi_l('Belum ada pesanan', 'No orders yet') }}</p>
                        <p class="text-[12px] text-slate-400 mb-5">{{ evomi_l('Pesananmu akan muncul di sini setelah checkout.', 'Your orders will appear here after checkout.') }}</p>
                        <button type="button" class="evomi-orders-modal__retry" @click="closeOrdersModal(); typeof softNavigate === 'function' ? softNavigate('/belanja') : (window.location.href='/belanja')">{{ evomi_l('Mulai Belanja', 'Start Shopping') }}</button>
                    </div>

                    <div x-show="!ordersLoading && !ordersError && filteredOrders.length > 0" x-cloak class="flex flex-col gap-[11px]">
                        <template x-for="group in filteredOrders" :key="group.groupId">
                            <div class="evomi-orders-modal__card" :class="{ 'is-expanded': ordersExpandedId === group.groupId }">
                                <button type="button" class="evomi-orders-modal__card-main" @click="toggleOrderDetail(group)">
                                    <div class="evomi-orders-modal__thumb">
                                        <img :src="group.imageUrl" :alt="group.productTitle" x-on:error="$el.style.display='none'">
                                        <span class="evomi-orders-modal__qty" x-text="group.quantity"></span>
                                    </div>
                                    <div class="evomi-orders-modal__info min-w-0 flex-1">
                                        <div class="flex items-start justify-between gap-2">
                                            <p class="evomi-orders-modal__invoice" x-text="(group.invoice || '').replace(/^#/, '')"></p>
                                            <span class="evomi-orders-modal__badge" :style="{ backgroundColor: orderBadge(group).bg }">
                                                <img
                                                    class="size-2.5 shrink-0"
                                                    :src="orderBadge(group).icon"
                                                    alt=""
                                                    width="10"
                                                    height="10"
                                                >
                                                <span x-text="orderBadge(group).label"></span>
                                            </span>
                                        </div>
                                        <p class="evomi-orders-modal__product truncate" x-text="group.productTitle"></p>
                                        <p class="evomi-orders-modal__meta" x-text="orderMetaLine(group)"></p>
                                        <div class="evomi-orders-modal__footer-row">
                                            <p class="evomi-orders-modal__price" x-text="group.totalLabel"></p>
                                            <span class="evomi-orders-modal__detail-link">
                                                {{ evomi_l('Detail', 'Detail') }}
                                                <img
                                                    src="{{ asset('src/images/orders/icon-chevron.svg') }}"
                                                    alt=""
                                                    class="size-[13px] transition-transform duration-200"
                                                    :class="{ 'rotate-180': ordersExpandedId === group.groupId }"
                                                    width="13"
                                                    height="13"
                                                >
                                            </span>
                                        </div>
                                    </div>
                                </button>

                                <div
                                    class="evomi-orders-modal__expand"
                                    x-show="ordersExpandedId === group.groupId"
                                    x-cloak
                                >
                                    <div class="evomi-orders-modal__expand-inner">
                                        <div class="flex flex-wrap items-center gap-2 text-[11px] text-[#5d5d5d]">
                                            <span class="rounded-md bg-slate-100 px-2 py-1 font-medium" x-text="group.paymentLabel"></span>
                                            <span class="text-slate-300">·</span>
                                            <span x-text="group.dateTimeLabel"></span>
                                        </div>
                                        <div class="mt-3 space-y-2">
                                            <template x-for="item in group.items" :key="item.id">
                                                <div class="flex items-center justify-between gap-3 text-[12px] text-[#5d5d5d]">
                                                    <span class="truncate min-w-0"><span x-text="item.title"></span> × <span x-text="item.quantity || 1"></span></span>
                                                    <span class="shrink-0 font-semibold" x-text="item.lineTotalLabel"></span>
                                                </div>
                                            </template>
                                        </div>
                                        <div class="mt-4 flex flex-wrap gap-2">
                                            <a
                                                x-show="group.isAwaitingPayment && group.paymentUrl"
                                                x-cloak
                                                :href="group.paymentUrl"
                                                data-soft-nav
                                                class="evomi-orders-modal__action is-pay"
                                                @click="closeOrdersModal()"
                                            >{{ evomi_l('Bayar Sekarang', 'Pay Now') }}</a>
                                            <button
                                                type="button"
                                                x-show="isLoggedIn && group.canConfirm"
                                                x-cloak
                                                class="evomi-orders-modal__action is-confirm"
                                                @click.stop="requestOrderConfirm(group)"
                                            >{{ evomi_l('Pesanan Diterima', 'Order Received') }}</button>
                                            <button
                                                type="button"
                                                class="evomi-orders-modal__action is-ghost"
                                                @click.stop="goOrderDetailPage(group)"
                                            >{{ evomi_l('Lihat selengkapnya', 'View full details') }}</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <div
            x-show="ordersToast"
            x-cloak
            x-transition
            class="fixed bottom-6 left-1/2 -translate-x-1/2 z-[230] px-4 py-2.5 rounded-full bg-slate-900 text-white text-sm font-medium shadow-lg"
            x-text="ordersToast"
        ></div>
    </div>
</template>

{{-- Guest cart risk warning --}}
<template x-teleport="body">
    <div
        class="fixed inset-0 z-[240]"
        x-show="guestWarnOpen"
        x-cloak
        @keydown.escape.window="guestWarnOpen && closeGuestCartWarning()"
    >
        <div class="absolute inset-0 bg-slate-900/45 backdrop-blur-[2px]" @click="closeGuestCartWarning()"></div>
        <div class="relative mx-auto mt-[12vh] w-[min(100%-1.5rem,420px)] rounded-3xl bg-white p-6 shadow-2xl border border-slate-100">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-amber-50 text-amber-600">
                <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>
                </svg>
            </div>
            <h3 class="text-center text-lg font-bold text-slate-900 tracking-tight">{{ evomi_l('Peringatan Keranjang Guest', 'Guest Cart Warning') }}</h3>
            <p class="mt-2 text-center text-[13px] text-slate-600 leading-relaxed">
                {{ evomi_l('Produk / pesanan yang Anda masukkan ke keranjang dan checkout bisa hilang dari keranjang Anda. Segera daftar dan login untuk checkout dan lainnya.', 'Products / orders you add to the cart and checkout can disappear from your cart. Please register and log in for checkout and more.') }}
            </p>
            <div class="mt-4">
                <label class="block text-[11px] font-semibold text-slate-700 mb-1.5">{{ evomi_l('Kirim salinan keranjang ke email (opsional)', 'Email a cart copy (optional)') }}</label>
                <input
                    type="email"
                    class="evomi-account-drawer__resi-input w-full"
                    x-model="guestWarnEmail"
                    :placeholder="$L('nama@email.com', 'name@email.com')"
                >
                <p class="mt-1.5 text-[11px] text-rose-500" x-show="guestWarnError" x-cloak x-text="guestWarnError"></p>
                <p class="mt-1.5 text-[11px] text-emerald-600" x-show="guestWarnStatus" x-cloak x-text="guestWarnStatus"></p>
            </div>
            <div class="mt-5 grid grid-cols-2 gap-2">
                <button type="button" class="rounded-xl border border-slate-200 py-2.5 text-[12px] font-bold text-slate-700 hover:bg-slate-50" @click="goGuestAuth('{{ route('register') }}')">{{ evomi_l('Daftar', 'Register') }}</button>
                <button type="button" class="rounded-xl border border-slate-200 py-2.5 text-[12px] font-bold text-slate-700 hover:bg-slate-50" @click="goGuestAuth('{{ route('login') }}')">{{ evomi_l('Login', 'Log in') }}</button>
            </div>
            <button
                type="button"
                class="mt-2 w-full rounded-xl bg-[#1172BA] py-2.5 text-[12px] font-bold text-white hover:bg-[#0d5a94] disabled:opacity-60"
                :disabled="guestWarnBusy"
                @click="continueAsGuestFromWarning({ sendEmail: Boolean(String(guestWarnEmail || '').trim()) })"
            >
                <span x-text="guestWarnBusy ? $L('Memproses...', 'Processing...') : $L('Lanjut sebagai guest', 'Continue as guest')"></span>
            </button>
            <button type="button" class="mt-2 w-full py-2 text-[11px] font-semibold text-slate-400" @click="closeGuestCartWarning(); openAccountDrawer('cart')">{{ evomi_l('Lewati, buka keranjang', 'Skip, open cart') }}</button>
        </div>
    </div>
</template>
