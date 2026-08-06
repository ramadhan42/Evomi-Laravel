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
    class="admin-modal-root admin-modal-root--shell z-[210]"
    role="dialog"
    aria-modal="true"
    @keydown.escape.window="$store.adminUi.cancelConfirm()"
>
    <div class="bg-white rounded-2xl shadow-2xl border border-gray-100 max-w-sm w-full p-6 space-y-4" role="document" @click.stop>
        <div class="w-11 h-11 rounded-xl bg-red-50 text-red-600 flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
        </div>
        <div>
            <h3 class="text-lg font-bold text-gray-900" x-text="$store.adminUi.confirm.title || 'Konfirmasi'"></h3>
            <p class="text-sm text-gray-500 mt-1" x-text="$store.adminUi.confirm.message"></p>
        </div>
        <div class="flex gap-3">
            <button type="button" class="flex-1 py-2.5 rounded-xl border border-gray-200 text-sm font-semibold text-gray-700 hover:bg-gray-50" @click="$store.adminUi.cancelConfirm()" x-text="$store.adminUi.i18nCancel()"></button>
            <button type="button" class="flex-1 py-2.5 rounded-xl bg-red-600 text-white text-sm font-semibold hover:bg-red-700 shadow-sm" @click="$store.adminUi.runConfirm()" x-text="$store.adminUi.i18nYesDelete()"></button>
        </div>
    </div>
</div>
