<div
    x-show="$store.adminUi?.toast?.open"
    x-cloak
    x-transition
    class="fixed bottom-6 right-6 z-[220] max-w-sm w-auto"
>
    <div
        class="rounded-2xl px-4 py-3 shadow-lg border text-sm font-medium flex items-start gap-3"
        :class="$store.adminUi.toast.type === 'success'
            ? 'bg-emerald-50 border-emerald-100 text-emerald-800'
            : 'bg-red-50 border-red-100 text-red-800'"
    >
        <span
            class="mt-0.5 h-5 w-5 rounded-full flex items-center justify-center shrink-0 text-white text-xs"
            :class="$store.adminUi.toast.type === 'success' ? 'bg-emerald-500' : 'bg-red-500'"
            x-text="$store.adminUi.toast.type === 'success' ? '✓' : '!'"
        ></span>
        <p class="leading-snug" x-text="$store.adminUi.toast.message"></p>
    </div>
</div>

<div
    x-show="$store.adminUi?.confirm?.open"
    x-cloak
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-[210] flex items-center justify-center p-4"
    style="background:rgba(17,24,39,.45);backdrop-filter:blur(6px);-webkit-backdrop-filter:blur(6px)"
    role="dialog"
    aria-modal="true"
    @keydown.escape.window="$store.adminUi.cancelConfirm()"
    @click.self="$store.adminUi.cancelConfirm()"
>
    <div
        x-show="$store.adminUi?.confirm?.open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95 translate-y-2"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
        x-transition:leave-end="opacity-0 scale-95 translate-y-2"
        class="bg-white rounded-2xl shadow-2xl border border-gray-100 max-w-[360px] w-full overflow-hidden"
        role="document"
        @click.stop
    >
        <div class="flex flex-col items-center text-center px-6 pt-7 pb-5">
            <div class="w-14 h-14 rounded-full bg-red-50 text-red-500 flex items-center justify-center mb-4 ring-4 ring-red-50/60">
                <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
            </div>
            <h3 class="text-[17px] font-bold text-gray-900 leading-snug" x-text="$store.adminUi.confirm.title || 'Konfirmasi'"></h3>
            <p class="text-[13px] text-gray-500 mt-1.5 leading-relaxed max-w-[280px]" x-text="$store.adminUi.confirm.message"></p>
        </div>
        <div class="flex border-t border-gray-100">
            <button
                type="button"
                class="flex-1 py-3 text-[13px] font-semibold text-gray-600 hover:bg-gray-50 transition-colors border-r border-gray-100"
                @click="$store.adminUi.cancelConfirm()"
                x-text="$store.adminUi.i18nCancel()"
            ></button>
            <button
                type="button"
                class="flex-1 py-3 text-[13px] font-semibold text-red-600 hover:bg-red-50 transition-colors"
                @click="$store.adminUi.runConfirm()"
                x-text="$store.adminUi.i18nYesDelete()"
            ></button>
        </div>
    </div>
</div>
