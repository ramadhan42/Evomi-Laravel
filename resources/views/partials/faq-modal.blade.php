@include('partials.help-modal-styles')
<template x-teleport="body">
    <div
        class="evomi-help-modal"
        x-show="$store.evomiFaqModal.open"
        x-cloak
        :class="$store.evomiFaqModal.open ? 'pointer-events-auto' : 'pointer-events-none'"
        @keydown.escape.window="$store.evomiFaqModal.open && closeFaqModal()"
    >
        <div
            class="evomi-help-modal__backdrop"
            x-show="$store.evomiFaqModal.open"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="closeFaqModal()"
        ></div>

        <div class="evomi-help-modal__frame" x-show="$store.evomiFaqModal.open" @click.self="closeFaqModal()">
            <div
                class="evomi-help-modal__panel"
                role="dialog"
                aria-modal="true"
                aria-label="{{ evomi_l('FAQ', 'FAQ') }}"
                x-show="$store.evomiFaqModal.open"
                x-transition:enter="ease-[cubic-bezier(0.22,1,0.36,1)] duration-420"
                x-transition:enter-start="opacity-0 scale-[0.96] translate-y-4"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="ease-in duration-220"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-[0.98]"
                @click.stop
            >
                <div class="evomi-help-modal__header">
                    <div class="min-w-0">
                        <p class="evomi-help-modal__kicker">Evomi</p>
                        <h2 class="evomi-help-modal__title">{{ evomi_l('Pusat Bantuan', 'Help Center') }}</h2>
                        <p class="evomi-help-modal__subtitle">{{ evomi_l('Cari jawaban seputar pesanan, pengiriman, dan produk Evomi.', 'Find answers about orders, shipping, and Evomi products.') }}</p>
                    </div>
                    <button type="button" class="evomi-help-modal__close" @click="closeFaqModal()" :aria-label="$L('Tutup', 'Close')">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 16 16" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4 4 12M4 4l8 8"/>
                        </svg>
                    </button>
                </div>

                <div class="evomi-help-modal__body space-y-4">
                    <div class="relative">
                        <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M11 18a7 7 0 1 1 0-14 7 7 0 0 1 0 14Z"/></svg>
                        <input
                            type="search"
                            class="evomi-help-modal__search"
                            x-model="$store.evomiFaqModal.query"
                            :placeholder="$L('Cari topik bantuan...', 'Search help topics...')"
                        >
                    </div>

                    <div x-show="$store.evomiFaqModal.loading" class="py-10 flex flex-col items-center gap-3">
                        <div class="w-8 h-8 border-[3px] border-slate-200 border-t-[#1172BA] rounded-full animate-spin"></div>
                        <p class="text-[12px] text-slate-400">{{ evomi_l('Memuat FAQ...', 'Loading FAQ...') }}</p>
                    </div>

                    <div x-show="!$store.evomiFaqModal.loading" class="space-y-5" x-cloak>
                        <template x-for="group in $store.evomiFaqModal.visibleGroups" :key="group.category">
                            <div>
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-[#E8F4FC] text-[#1172BA] text-sm font-bold">?</span>
                                    <h3 class="text-[14px] font-bold text-gray-900" x-text="group.category"></h3>
                                </div>
                                <div>
                                    <template x-for="(item, idx) in group.items" :key="group.category + '-' + idx">
                                        <div class="border-b border-gray-100" x-data="{ open: false }">
                                            <button type="button" class="flex justify-between items-center w-full py-3 text-left gap-3" @click="open = !open">
                                                <span class="text-[13px] font-medium text-gray-800" x-text="item.q"></span>
                                                <svg class="w-4 h-4 text-gray-400 shrink-0 transition-transform" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                                            </button>
                                            <div x-show="open" x-cloak x-transition.opacity.duration.200ms>
                                                <p class="pb-3 text-[12px] text-gray-600 leading-relaxed" x-text="item.a"></p>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>

                        <p class="text-center text-[12px] text-gray-500 py-4" x-show="$store.evomiFaqModal.visibleGroups.length === 0" x-cloak>
                            {{ evomi_l('Tidak ada hasil untuk pencarian Anda.', 'No results for your search.') }}
                        </p>
                    </div>

                    <div class="rounded-2xl bg-blue-50 p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div>
                            <p class="text-[13px] font-bold text-gray-900">{{ evomi_l('Masih butuh bantuan?', 'Still need help?') }}</p>
                            <p class="text-[12px] text-gray-600 mt-0.5">{{ evomi_l('Tim support Evomi siap membantu.', 'Evomi support is ready to help.') }}</p>
                        </div>
                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-full bg-[#1172BA] text-white px-4 py-2.5 text-[12px] font-bold shrink-0"
                            @click="closeFaqModal(); openKontakModal()"
                        >{{ evomi_l('Hubungi Kami', 'Contact Us') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
