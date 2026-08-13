{{-- Centered Lacak Pesanan modal — $store visibility; navbar scope for data. Sidebar track stays in drawer. --}}
@include('partials.track-modal-styles')
<template x-teleport="body">
    <div
        class="evomi-track-modal"
        x-show="$store.evomiTrackModal.open"
        x-cloak
        :class="$store.evomiTrackModal.open ? 'pointer-events-auto' : 'pointer-events-none'"
        @keydown.escape.window="$store.evomiTrackModal.open && closeTrackModal()"
    >
        <div
            class="evomi-track-modal__backdrop"
            x-show="$store.evomiTrackModal.open"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="closeTrackModal()"
        ></div>

        <div
            class="evomi-track-modal__frame"
            x-show="$store.evomiTrackModal.open"
            @click.self="closeTrackModal()"
        >
            <div
                class="evomi-track-modal__panel"
                role="dialog"
                aria-modal="true"
                aria-label="{{ evomi_l('Lacak Pesanan', 'Track Order') }}"
                x-show="$store.evomiTrackModal.open"
                x-transition:enter="ease-[cubic-bezier(0.22,1,0.36,1)] duration-420"
                x-transition:enter-start="opacity-0 translate-y-8 sm:translate-y-4 sm:scale-[0.97]"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-220"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-6 sm:scale-[0.98]"
                @click.stop
            >
                <div class="evomi-track-modal__hero">
                    <div class="evomi-track-modal__hero-glow" aria-hidden="true"></div>
                    <div class="relative z-[1] flex items-start justify-between gap-3">
                        <div class="flex items-center gap-3 min-w-0">
                            <span class="evomi-track-modal__icon">
                                @include('partials.icons.truck', ['class' => 'w-5 h-5'])
                            </span>
                            <div class="min-w-0">
                                <p class="evomi-track-modal__kicker">Evomi</p>
                                <h2 class="evomi-track-modal__title">{{ evomi_l('Lacak Pesanan', 'Track Order') }}</h2>
                            </div>
                        </div>
                        <button
                            type="button"
                            class="evomi-track-modal__close"
                            @click="closeTrackModal()"
                            :aria-label="$L('Tutup', 'Close')"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 16 16" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4 4 12M4 4l8 8"/>
                            </svg>
                        </button>
                    </div>
                    <p class="evomi-track-modal__subtitle relative z-[1]">{{ evomi_l('Masukkan nomor pesanan atau resi untuk melihat status pengiriman.', 'Enter an order or tracking number to view shipping status.') }}</p>
                    <form class="evomi-track-modal__search relative z-[1]" @submit.prevent="submitDrawerGuestResi()">
                        <input
                            type="text"
                            class="evomi-track-modal__input"
                            x-model="drawerGuestResi"
                            :placeholder="$L('INV-1234AG atau no. resi…', 'INV-1234AG or tracking no…')"
                            autocomplete="off"
                        >
                        <button
                            type="submit"
                            class="evomi-track-modal__submit"
                            :disabled="drawerTrackLoading"
                        >
                            <span x-text="drawerTrackLoading ? $L('Mencari…', 'Searching…') : $L('Lacak', 'Track')"></span>
                        </button>
                    </form>
                    <p
                        class="relative z-[1] mt-2 text-[12px] text-rose-100/90"
                        x-show="drawerGuestResiError"
                        x-cloak
                        x-text="drawerGuestResiError"
                        x-transition:enter="ease-out duration-300"
                        x-transition:enter-start="opacity-0"
                        x-transition:enter-end="opacity-100"
                        x-transition:leave="ease-in duration-200"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                    ></p>
                </div>

                <div class="evomi-track-modal__body">
                    <div
                        x-show="drawerTrackLoading && drawerTrackItems.length === 0"
                        x-cloak
                        x-transition:enter="ease-out duration-300"
                        x-transition:enter-start="opacity-0"
                        x-transition:enter-end="opacity-100"
                        x-transition:leave="ease-in duration-200"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                        class="py-16 flex flex-col items-center justify-center gap-3"
                    >
                        <div class="w-8 h-8 border-[3px] border-slate-200 border-t-[#1172BA] rounded-full animate-spin"></div>
                        <p class="text-[12px] text-slate-400 font-medium">{{ evomi_l('Memuat pesanan...', 'Loading orders...') }}</p>
                    </div>

                    <div
                        x-show="!drawerTrackLoading && drawerTrackItems.length === 0 && !drawerGuestResiError && isLoggedIn"
                        class="evomi-track-modal__empty"
                        x-cloak
                        x-transition:enter="ease-out duration-300"
                        x-transition:enter-start="opacity-0"
                        x-transition:enter-end="opacity-100"
                        x-transition:leave="ease-in duration-200"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                    >
                        <div class="evomi-track-modal__empty-icon">
                            @include('partials.icons.truck', ['class' => 'w-7 h-7'])
                        </div>
                        <p class="text-[15px] font-semibold text-slate-800">{{ evomi_l('Belum ada pesanan', 'No orders yet') }}</p>
                        <p class="text-[13px] text-slate-500 mt-1 max-w-sm mx-auto leading-relaxed">{{ evomi_l('Pesanan Anda akan muncul di sini setelah checkout, atau masukkan nomor di atas untuk melacak.', 'Your orders will appear here after checkout, or enter a number above to track.') }}</p>
                    </div>

                    <div
                        x-show="!drawerTrackLoading && drawerTrackItems.length === 0 && !drawerGuestResiError && !isLoggedIn"
                        class="evomi-track-modal__empty"
                        x-cloak
                        x-transition:enter="ease-out duration-300"
                        x-transition:enter-start="opacity-0"
                        x-transition:enter-end="opacity-100"
                        x-transition:leave="ease-in duration-200"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                    >
                        <div class="evomi-track-modal__empty-icon">
                            @include('partials.icons.truck', ['class' => 'w-7 h-7'])
                        </div>
                        <p class="text-[15px] font-semibold text-slate-800">{{ evomi_l('Siap dilacak', 'Ready to track') }}</p>
                        <p class="text-[13px] text-slate-500 mt-1 max-w-sm mx-auto leading-relaxed">{{ evomi_l('Masukkan nomor pesanan seperti INV-1234AG, atau nomor resi kurir.', 'Enter an order number like INV-1234AG, or a courier tracking number.') }}</p>
                    </div>

                    @include('partials.track-result')
                </div>
            </div>
        </div>
    </div>
</template>
