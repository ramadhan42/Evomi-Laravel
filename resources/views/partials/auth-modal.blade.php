{{-- Modal status untuk halaman auth (login, lupa password, reset password). --}}
<div
    x-show="modal.show"
    x-cloak
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-[140] flex items-center justify-center p-4 bg-black/60 backdrop-blur-md"
    @keydown.escape.window="closeModal()"
>
    <div
        class="bg-[#1172ba] border border-white/20 rounded-3xl p-6 max-w-sm w-full text-center space-y-4 shadow-2xl"
        @click.outside="closeModal()"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
    >
        <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-white/10 backdrop-blur-sm">
            <template x-if="modal.type === 'success'">
                <svg class="h-8 w-8 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
            </template>
            <template x-if="modal.type === 'warning'">
                <svg class="h-8 w-8 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </template>
            <template x-if="modal.type === 'error'">
                <svg class="h-8 w-8 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </template>
        </div>

        <div class="space-y-2">
            <h3 class="text-xl font-bold text-white uppercase tracking-wide" x-text="modal.title"></h3>
            <p class="text-sm text-blue-100/80 font-light leading-relaxed" x-text="modal.message"></p>
        </div>

        <button
            type="button"
            class="w-full font-bold py-3 rounded-xl transition-all uppercase tracking-wider text-xs shadow-md active:scale-[0.98]"
            :class="modal.type === 'success'
                ? 'bg-green-500 text-white hover:bg-green-600'
                : modal.type === 'warning'
                    ? 'bg-amber-500 text-white hover:bg-amber-600'
                    : 'bg-white text-[#1172ba] hover:bg-blue-50'"
            @click="closeModal()"
            x-text="modal.cta"
        ></button>
    </div>
</div>
