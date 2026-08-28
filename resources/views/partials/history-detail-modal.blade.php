{{-- Square order history detail modal — UI aligned with profile/history-show --}}
<style>
.evomi-history-detail-modal{position:fixed;inset:0;z-index:235}
.evomi-history-detail-modal__backdrop{position:absolute;inset:0;background:rgba(15,23,42,.48);backdrop-filter:blur(6px);-webkit-backdrop-filter:blur(6px)}
.evomi-history-detail-modal__frame{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;padding:1rem}
.evomi-history-detail-modal__panel{
  position:relative;display:flex;flex-direction:column;
  width:min(92vw,92vh,640px);height:min(92vw,92vh,640px);
  max-width:640px;max-height:640px;aspect-ratio:1/1;overflow:hidden;
  background:#f8fafc;border-radius:24px;box-shadow:0 24px 80px rgba(15,23,42,.28);border:0;
}
.evomi-history-detail-modal__header{
  flex-shrink:0;position:relative;overflow:hidden;
  padding:1.05rem 1.15rem .95rem;
  background:linear-gradient(135deg,#1172BA 0%,#1a7fc4 55%,#0e6aad 100%);color:#fff;
  transition:background .35s ease;
}
.evomi-history-detail-modal__header::before{
  content:"";position:absolute;inset:0;opacity:.3;pointer-events:none;
  background-image:radial-gradient(circle at 12% 20%,rgba(255,255,255,.35),transparent 40%),radial-gradient(circle at 90% 0%,rgba(255,255,255,.18),transparent 35%);
}
.evomi-history-detail-modal__close{
  position:relative;display:inline-flex;align-items:center;justify-content:center;
  width:34px;height:34px;border-radius:999px;border:0;color:#fff;
  background:rgba(255,255,255,.14);flex-shrink:0;cursor:pointer;
}
.evomi-history-detail-modal__body{flex:1;min-height:0;overflow-y:auto;overscroll-behavior:contain;padding:1rem 1.1rem 1.25rem}
</style>

<template x-teleport="body">
    <div
        class="evomi-history-detail-modal"
        x-show="$store.evomiHistoryDetailModal.open"
        x-cloak
        :class="$store.evomiHistoryDetailModal.open ? 'pointer-events-auto' : 'pointer-events-none'"
        @keydown.escape.window="$store.evomiHistoryDetailModal.open && closeHistoryDetailModal()"
    >
        <div
            class="evomi-history-detail-modal__backdrop"
            x-show="$store.evomiHistoryDetailModal.open"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="closeHistoryDetailModal()"
        ></div>

        <div class="evomi-history-detail-modal__frame" x-show="$store.evomiHistoryDetailModal.open" @click.self="closeHistoryDetailModal()">
            <div
                class="evomi-history-detail-modal__panel"
                role="dialog"
                aria-modal="true"
                aria-label="{{ evomi_l('Detail Pesanan', 'Order Details') }}"
                x-show="$store.evomiHistoryDetailModal.open"
                x-transition:enter="ease-[cubic-bezier(0.22,1,0.36,1)] duration-420"
                x-transition:enter-start="opacity-0 scale-[0.96] translate-y-4"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="ease-in duration-220"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-[0.98]"
                x-data="evomiProfileHistoryShow()"
                @evomi-history-detail-reload.window="onModalReload($event)"
                @click.stop
            >
                <div class="evomi-history-detail-modal__header" :style="headerStyle">
                    <div class="relative flex items-start justify-between gap-3">
                        <div class="min-w-0 flex items-start gap-3">
                            <button
                                type="button"
                                class="w-10 h-10 rounded-2xl bg-white/15 border border-white/25 flex items-center justify-center shrink-0 hover:bg-white/25 transition"
                                @click="closeHistoryDetailModal({ backToList: true })"
                                :aria-label="$L('Kembali', 'Back')"
                            >
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
                            </button>
                            <div class="min-w-0 pt-0.5">
                                <h2 class="text-[1.15rem] font-bold tracking-tight leading-tight">{{ evomi_l('Detail Pesanan', 'Order Details') }}</h2>
                                <p class="text-[12px] text-white/80 font-medium mt-0.5 truncate" x-text="group?.invoice || $L('Rincian produk & pembayaran', 'Product & payment details')"></p>
                            </div>
                        </div>
                        <button type="button" class="evomi-history-detail-modal__close" @click="closeHistoryDetailModal()" :aria-label="$L('Tutup', 'Close')">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 16 16" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4 4 12M4 4l8 8"/></svg>
                        </button>
                    </div>
                </div>

                <div class="evomi-history-detail-modal__body">
                    <div x-show="!group && !error" x-cloak class="min-h-[280px] flex flex-col items-center justify-center gap-3">
                        <div class="w-8 h-8 border-4 border-gray-200 border-t-[#1172BA] rounded-full animate-spin"></div>
                        <p class="text-gray-500 font-medium text-sm">{{ evomi_l('Memuat detail...', 'Loading details...') }}</p>
                    </div>

                    <div x-show="error && !group" x-cloak class="flex flex-col items-center justify-center min-h-[280px] p-4 text-center">
                        <svg class="w-10 h-10 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9"/></svg>
                        <h3 class="text-lg font-bold text-gray-800 mb-2" x-text="error || @js(evomi_l('Pesanan tidak ditemukan', 'Order not found'))"></h3>
                        <button type="button" @click="closeHistoryDetailModal({ backToList: true })" class="mt-2 px-6 py-2.5 bg-[#1172BA] text-white rounded-xl font-medium text-sm">{{ evomi_l('Kembali ke Riwayat', 'Back to History') }}</button>
                    </div>

                    {{-- x-if, bukan x-show: x-show hanya menyembunyikan elemennya,
                         sedangkan isinya tetap dibuat dan dievaluasi Alpine. Selama
                         modal belum dibuka, `group` masih null - dan setiap ekspresi
                         group.* di dalam sini melempar TypeError di SETIAP halaman,
                         karena modal ini disertakan lewat navbar. Dengan x-if,
                         isinya baru dibuat setelah `group` benar-benar ada. --}}
                    <template x-if="group">
                    <div class="space-y-3.5">
                        <div class="bg-white rounded-2xl border border-gray-100 p-4 flex flex-col gap-3">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="p-2.5 bg-gray-50 rounded-xl border border-gray-100 text-gray-600 shrink-0">
                                        <svg x-show="statusIcon === 'clock'" class="w-5 h-5 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                                        <svg x-show="statusIcon === 'box'" class="w-5 h-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9"/></svg>
                                        <svg x-show="statusIcon === 'truck'" class="w-5 h-5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.131 1.131 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"/></svg>
                                        <svg x-show="statusIcon === 'check'" class="w-5 h-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-[11px] text-gray-500 mb-0.5">{{ evomi_l('Status Pesanan', 'Order Status') }}</p>
                                        <div class="flex flex-wrap items-center gap-1.5">
                                            <span class="inline-block px-2.5 py-1 rounded-full text-[10px] font-bold border" :class="group.statusClass" x-text="group.statusLabel"></span>
                                            <span
                                                x-show="group.showPaymentBadge"
                                                x-cloak
                                                class="inline-block px-2.5 py-1 rounded-full text-[10px] font-bold border"
                                                :class="group.paymentClass"
                                                x-text="group.paymentLabel"
                                            ></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-right shrink-0">
                                    <p class="text-[10px] text-gray-400 mb-0.5">{{ evomi_l('No. Invoice', 'Invoice No.') }}</p>
                                    <p class="font-bold bg-gray-50 px-2.5 py-1 rounded-lg inline-block border border-gray-100 text-[12px]" :style="{ color: group.accent || '#1172BA' }" x-text="group.invoice"></p>
                                </div>
                            </div>
                            <button
                                type="button"
                                x-show="group.canConfirm"
                                x-cloak
                                @click="requestConfirm()"
                                class="w-full px-4 py-2.5 bg-green-500 hover:bg-green-600 text-white rounded-xl text-[12px] font-bold transition-colors inline-flex items-center justify-center gap-1.5"
                            >
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                                {{ evomi_l('Konfirmasi Pesanan', 'Confirm Order') }}
                            </button>
                        </div>

                        <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
                            <div class="p-4 border-b border-gray-100">
                                <h3 class="font-bold text-gray-900 text-[15px] mb-4">{{ evomi_l('Informasi Produk', 'Product Information') }}</h3>
                                <div class="space-y-4">
                                    <template x-for="(item, index) in group.items" :key="item.id">
                                        <div class="flex gap-3.5" :class="index !== 0 ? 'pt-4 border-t border-gray-100' : ''">
                                            <div class="w-20 h-20 bg-gray-50 rounded-xl p-2 flex-shrink-0 border border-gray-100 flex items-center justify-center overflow-hidden">
                                                <img :src="item.imageUrl" :alt="item.title" class="max-h-full max-w-full object-contain" x-on:error="$el.style.display='none'">
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="flex justify-between items-start gap-2">
                                                    <div class="min-w-0">
                                                        <h4 class="text-[14px] font-bold text-gray-900 mb-1 line-clamp-2" x-text="item.title"></h4>
                                                        <p class="text-gray-500 text-[11px] mb-2 line-clamp-2" x-text="item.description || @js(evomi_l('Tidak ada deskripsi produk.', 'No product description.'))"></p>
                                                    </div>
                                                    <button
                                                        type="button"
                                                        x-show="item.canDeleteItem"
                                                        x-cloak
                                                        @click="requestRemoveItem(item)"
                                                        class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors flex-shrink-0"
                                                    >
                                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                                                    </button>
                                                </div>
                                                <div class="grid grid-cols-2 gap-2 text-[11px] bg-gray-50 p-2.5 rounded-xl border border-gray-100">
                                                    <div>
                                                        <p class="text-gray-500 mb-0.5">{{ evomi_l('Harga Satuan', 'Unit Price') }}</p>
                                                        <p class="font-bold text-gray-900" x-text="item.priceLabel"></p>
                                                    </div>
                                                    <div>
                                                        <p class="text-gray-500 mb-0.5">{{ evomi_l('Kuantitas', 'Quantity') }}</p>
                                                        <p class="font-bold text-gray-900"><span x-text="item.quantity || 1"></span> {{ evomi_l('Item', 'Items') }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <div class="p-4 bg-white">
                                <h3 class="font-bold text-gray-900 text-[15px] mb-3">{{ evomi_l('Rincian Pembayaran', 'Payment Details') }}</h3>
                                <div class="space-y-2.5 text-[12px]">
                                    <div class="flex justify-between items-center text-gray-600">
                                        <span>{{ evomi_l('Metode Pembayaran', 'Payment Method') }}</span>
                                        <span class="font-medium text-gray-900 bg-gray-100 px-2.5 py-1 rounded-md" x-text="group.paymentMethod || @js(evomi_l('Tidak diketahui', 'Unknown'))"></span>
                                    </div>
                                    <div
                                        class="flex justify-between items-center text-gray-600"
                                        x-show="group.showPaymentBadge"
                                        x-cloak
                                    >
                                        <span>{{ evomi_l('Status Pembayaran', 'Payment Status') }}</span>
                                        <span class="font-medium px-2.5 py-1 rounded-md border text-[10px]" :class="group.paymentClass" x-text="group.paymentLabel"></span>
                                    </div>
                                    <div class="flex justify-between items-center text-gray-600">
                                        <span>{{ evomi_l('Tanggal Pembelian', 'Purchase Date') }}</span>
                                        <span class="font-medium text-gray-900" x-text="group.dateTimeLabel"></span>
                                    </div>
                                    <div class="flex justify-between items-center text-gray-600">
                                        <span>{{ evomi_l('Total Subtotal Produk', 'Product Subtotal') }}</span>
                                        <span class="font-medium text-gray-900" x-text="group.subtotalLabel"></span>
                                    </div>
                                    <div class="flex justify-between items-center text-gray-600">
                                        <span>{{ evomi_l('Ongkos Kirim', 'Shipping Cost') }}</span>
                                        <span class="font-medium text-gray-900" x-text="group.shippingLabel"></span>
                                    </div>
                                    <div class="flex justify-between items-center text-gray-600">
                                        <span>{{ evomi_l('Promo', 'Promo') }}</span>
                                        <span class="font-medium text-gray-900" x-text="group.promoLabel"></span>
                                    </div>
                                    <div class="flex justify-between items-center font-bold text-[13px] border-t border-gray-100 pt-3 mt-1">
                                        <span class="text-gray-900">{{ evomi_l('Total Belanja', 'Order Total') }}</span>
                                        <span :style="{ color: themeColor }" x-text="group.totalLabel"></span>
                                    </div>
                                    <p class="text-[10px] text-gray-400 pt-0.5">{{ evomi_l('Sudah termasuk PPN jika ada', 'Includes VAT if applicable') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    </template>
                </div>

                <div
                    x-show="toast"
                    x-cloak
                    x-transition
                    class="absolute bottom-4 left-1/2 -translate-x-1/2 z-10 px-3.5 py-2 rounded-full bg-slate-900 text-white text-[12px] font-medium shadow-lg"
                    x-text="toast"
                ></div>

                <template x-teleport="body">
                    <div x-show="modal.open" x-cloak class="fixed inset-0 z-[260] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
                        <div class="absolute inset-0" @click="modal.type !== 'loading' && closeModal()"></div>
                        <div class="relative bg-white rounded-3xl p-6 max-w-sm w-full text-center space-y-4 shadow-2xl border border-slate-100">
                            <div class="mx-auto flex items-center justify-center h-14 w-14 rounded-full" :class="modal.variant === 'delete' ? 'bg-rose-50' : 'bg-emerald-50'">
                                <template x-if="modal.type === 'confirm' && modal.variant === 'delete'">
                                    <svg class="w-7 h-7 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                                </template>
                                <template x-if="modal.type === 'confirm' && modal.variant !== 'delete'">
                                    <svg class="w-7 h-7 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                                </template>
                                <template x-if="modal.type === 'loading'">
                                    <div class="w-7 h-7 border-2 border-slate-200 border-t-[#1172BA] rounded-full animate-spin"></div>
                                </template>
                                <template x-if="modal.type === 'error'">
                                    <svg class="w-7 h-7 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                                </template>
                            </div>
                            <div class="space-y-2">
                                <h3 class="text-lg font-bold text-slate-900 tracking-tight" x-text="modal.title"></h3>
                                <p class="text-sm text-slate-600 leading-relaxed" x-text="modal.message"></p>
                            </div>
                            <div class="flex gap-3 pt-1" x-show="modal.type === 'confirm'">
                                <button type="button" @click="closeModal()" class="w-full font-semibold py-3 rounded-xl text-sm border border-slate-200 text-slate-700 hover:bg-slate-50 transition">{{ evomi_l('Batal', 'Cancel') }}</button>
                                <button type="button" @click="runModalAction()" class="w-full font-semibold py-3 rounded-xl text-sm text-white transition" :class="modal.variant === 'delete' ? 'bg-rose-500 hover:bg-rose-600' : 'bg-emerald-500 hover:bg-emerald-600'" x-text="modal.confirmText"></button>
                            </div>
                            <button type="button" x-show="modal.type === 'error'" @click="closeModal()" class="w-full mt-1 bg-[#1172BA] font-bold py-3 rounded-xl text-sm text-white">{{ evomi_l('Tutup', 'Close') }}</button>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</template>
