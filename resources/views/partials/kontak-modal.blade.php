<template x-teleport="body">
    <div
        class="evomi-help-modal"
        x-show="$store.evomiKontakModal.open"
        x-cloak
        :class="$store.evomiKontakModal.open ? 'pointer-events-auto' : 'pointer-events-none'"
        @keydown.escape.window="$store.evomiKontakModal.open && closeKontakModal()"
    >
        <div
            class="evomi-help-modal__backdrop"
            x-show="$store.evomiKontakModal.open"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="closeKontakModal()"
        ></div>

        <div class="evomi-help-modal__frame" x-show="$store.evomiKontakModal.open" @click.self="closeKontakModal()">
            <div
                class="evomi-help-modal__panel"
                role="dialog"
                aria-modal="true"
                aria-label="{{ evomi_l('Kontak', 'Contact') }}"
                x-show="$store.evomiKontakModal.open"
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
                        <h2 class="evomi-help-modal__title" x-text="$store.evomiKontakModal.cms.title"></h2>
                        <p class="evomi-help-modal__subtitle" x-text="$store.evomiKontakModal.cms.subtitle"></p>
                    </div>
                    <button type="button" class="evomi-help-modal__close" @click="closeKontakModal()" :aria-label="$L('Tutup', 'Close')">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 16 16" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4 4 12M4 4l8 8"/>
                        </svg>
                    </button>
                </div>

                <div class="evomi-help-modal__body space-y-4">
                    <form class="space-y-3" @submit.prevent="$store.evomiKontakModal.submit()">
                        <div
                            x-show="$store.evomiKontakModal.status.message"
                            x-cloak
                            class="p-3 rounded-xl text-[12px] font-medium"
                            :class="$store.evomiKontakModal.status.type === 'success' ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700'"
                            x-text="$store.evomiKontakModal.status.message"
                        ></div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                            <input type="text" class="evomi-help-modal__input" required x-model="$store.evomiKontakModal.form.name" :placeholder="$L('Nama Anda', 'Your name')">
                            <input type="email" class="evomi-help-modal__input" required x-model="$store.evomiKontakModal.form.email" :placeholder="$L('Email Anda', 'Your email')">
                        </div>
                        <input type="text" class="evomi-help-modal__input" required x-model="$store.evomiKontakModal.form.subject" :placeholder="$L('Subjek', 'Subject')">
                        <textarea class="evomi-help-modal__textarea" required x-model="$store.evomiKontakModal.form.message" :placeholder="$L('Tulis pesan Anda di sini...', 'Write your message here...')"></textarea>

                        @include('partials.turnstile-field', [
                            'theme' => 'light',
                            'scope' => '$store.evomiKontakModal.',
                            'mountId' => 'evomi-kontak-modal-turnstile',
                        ])

                        <button type="submit" class="evomi-help-modal__submit" :disabled="$store.evomiKontakModal.loading">
                            <span x-text="$store.evomiKontakModal.loading ? $L('Mengirim...', 'Sending...') : $L('Kirim Pesan', 'Send Message')"></span>
                        </button>
                    </form>

                    <div class="space-y-2.5 pt-1">
                        <div class="evomi-help-modal__info">
                            <span class="evomi-help-modal__info-icon">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/></svg>
                            </span>
                            <div class="min-w-0">
                                <p class="text-[12px] font-bold text-gray-900" x-text="$store.evomiKontakModal.cms.email_label"></p>
                                <p class="text-[12px] text-gray-600 mt-0.5 break-all" x-text="$store.evomiKontakModal.cms.email_value"></p>
                            </div>
                        </div>
                        <div class="evomi-help-modal__info">
                            <span class="evomi-help-modal__info-icon">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z"/></svg>
                            </span>
                            <div class="min-w-0">
                                <p class="text-[12px] font-bold text-gray-900" x-text="$store.evomiKontakModal.cms.phone_label"></p>
                                <p class="text-[12px] text-gray-600 mt-0.5" x-text="$store.evomiKontakModal.cms.phone_value"></p>
                            </div>
                        </div>
                        <div class="evomi-help-modal__info">
                            <span class="evomi-help-modal__info-icon">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/></svg>
                            </span>
                            <div class="min-w-0">
                                <p class="text-[12px] font-bold text-gray-900" x-text="$store.evomiKontakModal.cms.address_label"></p>
                                <p class="text-[12px] text-gray-600 mt-0.5" x-text="$store.evomiKontakModal.cms.address_value"></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
