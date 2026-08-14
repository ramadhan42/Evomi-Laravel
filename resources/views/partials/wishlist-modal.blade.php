{{-- Wishlist modal sized for 4 horizontal product rows --}}
<style>
.evomi-wishlist-modal{position:fixed;inset:0;z-index:230}
.evomi-wishlist-modal__backdrop{position:absolute;inset:0;background:rgba(15,23,42,.48);backdrop-filter:blur(6px);-webkit-backdrop-filter:blur(6px)}
.evomi-wishlist-modal__frame{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;padding:1rem}
.evomi-wishlist-modal__panel{
  --wl-row:6.5rem;
  --wl-gap:.625rem;
  --wl-pad-y:1.7rem;
  --wl-header:5.5rem;
  position:relative;display:flex;flex-direction:column;
  width:min(92vw,640px);
  height:min(92vh, calc(var(--wl-header) + var(--wl-pad-y) + (4 * var(--wl-row)) + (3 * var(--wl-gap))));
  max-width:640px;
  max-height:min(92vh, calc(var(--wl-header) + var(--wl-pad-y) + (4 * var(--wl-row)) + (3 * var(--wl-gap))));
  overflow:hidden;
  background:#fff;border-radius:24px;box-shadow:0 24px 80px rgba(15,23,42,.28);border:0;
}
.evomi-wishlist-modal__header{
  flex-shrink:0;position:relative;overflow:hidden;
  min-height:var(--wl-header);box-sizing:border-box;
  padding:1rem 1.15rem .95rem;
  background:linear-gradient(135deg,#1172BA 0%,#1a7fc4 55%,#0e6aad 100%);color:#fff;
}
.evomi-wishlist-modal__header::before{
  content:"";position:absolute;inset:0;opacity:.3;pointer-events:none;
  background-image:radial-gradient(circle at 12% 20%,rgba(255,255,255,.35),transparent 40%),radial-gradient(circle at 90% 0%,rgba(255,255,255,.18),transparent 35%);
}
.evomi-wishlist-modal__close{
  position:relative;display:inline-flex;align-items:center;justify-content:center;
  width:34px;height:34px;border-radius:999px;border:0;color:#fff;
  background:rgba(255,255,255,.14);flex-shrink:0;cursor:pointer;
}
.evomi-wishlist-modal__body{flex:1;min-height:0;overflow-y:auto;overscroll-behavior:contain;padding:.85rem 1rem 1rem;background:#fff}
.evomi-wishlist-modal__list{display:flex;flex-direction:column;gap:var(--wl-gap)}
.evomi-wishlist-modal__item{
  display:flex;align-items:center;gap:.85rem;
  height:var(--wl-row);min-height:var(--wl-row);max-height:var(--wl-row);
  padding:.7rem .8rem;box-sizing:border-box;
  border:1px solid #f3f4f6;border-radius:1rem;background:#fff;
  transition:border-color .15s ease;
}
.evomi-wishlist-modal__item:hover{border-color:#e2e8f0}
.evomi-wishlist-modal__thumb{
  width:3.5rem;height:3.5rem;border-radius:.75rem;flex-shrink:0;
  display:flex;align-items:center;justify-content:center;overflow:hidden;padding:.35rem;
}
</style>

<template x-teleport="body">
    <div
        class="evomi-wishlist-modal"
        x-show="$store.evomiWishlistModal.open"
        x-cloak
        :class="$store.evomiWishlistModal.open ? 'pointer-events-auto' : 'pointer-events-none'"
        @keydown.escape.window="$store.evomiWishlistModal.open && closeWishlistModal()"
    >
        <div
            class="evomi-wishlist-modal__backdrop"
            x-show="$store.evomiWishlistModal.open"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="closeWishlistModal()"
        ></div>

        <div class="evomi-wishlist-modal__frame" x-show="$store.evomiWishlistModal.open" @click.self="closeWishlistModal()">
            <div
                class="evomi-wishlist-modal__panel"
                role="dialog"
                aria-modal="true"
                aria-label="Wishlist"
                x-show="$store.evomiWishlistModal.open"
                x-transition:enter="ease-[cubic-bezier(0.22,1,0.36,1)] duration-420"
                x-transition:enter-start="opacity-0 scale-[0.96] translate-y-4"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="ease-in duration-220"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-[0.98]"
                x-data="evomiProfileWishlist"
                @evomi-wishlist-reload.window="load()"
                @click.stop
            >
                <div class="evomi-wishlist-modal__header">
                    <div class="relative flex items-start justify-between gap-3">
                        <div class="min-w-0 flex items-start gap-3">
                            <span class="w-10 h-10 rounded-2xl bg-white/15 border border-white/25 flex items-center justify-center shrink-0">
                                <svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z"/></svg>
                            </span>
                            <div class="min-w-0 pt-0.5">
                                <h2 class="text-[1.15rem] font-bold tracking-tight leading-tight">Wishlist</h2>
                                <p class="text-[12px] text-white/80 font-medium mt-0.5 leading-snug">{{ evomi_l('Koleksi aroma favoritmu — siap dipindah ke keranjang kapan saja.', 'Your favorite scents — ready to move to cart anytime.') }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <span
                                x-show="!loading && items.length > 0"
                                x-cloak
                                class="hidden sm:inline-flex text-[11px] font-semibold px-2.5 py-1.5 rounded-full bg-white/15 border border-white/25"
                            >
                                <span x-text="items.length"></span>&nbsp;{{ evomi_l('produk', 'products') }}
                            </span>
                            <button type="button" class="evomi-wishlist-modal__close" @click="closeWishlistModal()" :aria-label="$L('Tutup', 'Close')">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 16 16" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4 4 12M4 4l8 8"/></svg>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="evomi-wishlist-modal__body">
                    <div x-show="loading" x-cloak class="h-full min-h-[16rem] flex flex-col items-center justify-center gap-3">
                        <div class="w-8 h-8 border-4 border-gray-200 border-t-[#1172BA] rounded-full animate-spin"></div>
                        <p class="text-gray-500 font-medium text-sm">{{ evomi_l('Memuat wishlist...', 'Loading wishlist...') }}</p>
                    </div>

                    <div x-show="!loading" x-cloak class="h-full">
                        <div x-show="error" x-cloak class="rounded-2xl bg-red-50 border border-red-100 text-red-700 px-4 py-3 text-sm mb-4" x-text="error"></div>

                        <div x-show="!error && items.length === 0" x-cloak class="h-full rounded-2xl border border-dashed border-gray-200 px-6 py-10 text-center flex flex-col items-center justify-center">
                            <div class="w-14 h-14 rounded-2xl bg-[#1172BA]/10 flex items-center justify-center mb-3">
                                <svg class="w-6 h-6 text-[#1172BA]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z"/></svg>
                            </div>
                            <h3 class="text-base font-bold text-slate-900 mb-1.5">{{ evomi_l('Wishlist masih kosong', 'Your wishlist is empty') }}</h3>
                            <p class="text-slate-500 text-sm mb-5 max-w-sm mx-auto">{{ evomi_l('Simpan produk favoritmu di sini biar gampang ditemukan lagi.', 'Save your favorite products here for easy access later.') }}</p>
                            <a href="{{ route('belanja') }}" data-soft-nav @click="closeWishlistModal()" class="inline-flex items-center gap-2 px-5 py-2.5 bg-[#1172BA] text-white rounded-xl font-semibold text-sm hover:bg-[#0d5a94]">{{ evomi_l('Mulai Belanja', 'Start Shopping') }}</a>
                        </div>

                        <div x-show="items.length > 0" x-cloak class="evomi-wishlist-modal__list">
                            <template x-for="item in items" :key="item.id">
                                <div class="evomi-wishlist-modal__item group/card">
                                    <div
                                        class="evomi-wishlist-modal__thumb"
                                        :style="{ backgroundColor: (item.accent || '#1172BA') + '14' }"
                                    >
                                        <img
                                            :src="item.imageUrl"
                                            :alt="item.title"
                                            class="max-h-full max-w-full w-auto h-auto object-contain group-hover/card:scale-105 transition-transform duration-300"
                                            x-on:error="$el.style.display='none'"
                                        >
                                    </div>

                                    <div class="flex-1 min-w-0">
                                        <p class="font-semibold text-slate-900 text-[13px] truncate" x-text="item.title"></p>
                                        <div class="flex items-center gap-2 mt-0.5 min-w-0">
                                            <p class="text-[11px] text-slate-500 truncate leading-snug" x-text="item.sizeLabel"></p>
                                            <span class="text-[11px] text-slate-300 shrink-0">·</span>
                                            <p class="text-[11px] text-slate-500 truncate leading-snug" x-text="item.genderLabel"></p>
                                        </div>
                                        <div class="flex flex-wrap items-center gap-2 mt-1.5">
                                            <p class="text-[13px] font-bold" :style="{ color: item.accent || '#1172BA' }" x-text="item.priceLabel"></p>
                                            <button
                                                type="button"
                                                class="inline-flex items-center gap-1 text-white px-2.5 py-1 rounded-lg text-[10px] font-semibold transition hover:opacity-90 disabled:opacity-60 bg-[#1172BA]"
                                                :disabled="addingId === item.id"
                                                @click="moveToCart(item, $event)"
                                            >
                                                <span x-text="addingId === item.id ? $L('...', '...') : $L('Ke Keranjang', 'Add to Cart')"></span>
                                            </button>
                                            <button
                                                type="button"
                                                @click="requestRemove(item)"
                                                class="p-1 text-rose-500 hover:text-white hover:bg-rose-500 rounded-lg transition-colors bg-rose-50 border border-rose-100"
                                                :aria-label="$L('Hapus dari wishlist', 'Remove from wishlist')"
                                            >
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                                            </button>
                                        </div>
                                    </div>

                                    <a
                                        :href="'/belanja/' + item.product_id"
                                        :data-accent="item.accent"
                                        data-soft-nav
                                        @click="closeWishlistModal()"
                                        class="shrink-0 self-center inline-flex items-center gap-0.5 text-[11px] font-semibold text-slate-400 hover:text-slate-700 transition-colors whitespace-nowrap"
                                    >
                                        {{ evomi_l('Lihat detail', 'View details') }}
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                                    </a>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <div
                    x-show="toast"
                    x-cloak
                    x-transition
                    class="absolute bottom-4 left-1/2 -translate-x-1/2 z-10 px-3.5 py-2 rounded-full bg-slate-900 text-white text-[12px] font-medium shadow-lg"
                    x-text="toast"
                ></div>

                <template x-teleport="body">
                    <div x-show="modal.open" x-cloak class="fixed inset-0 z-[250] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
                        <div class="absolute inset-0" @click="closeModal()"></div>
                        <div class="relative bg-white rounded-2xl p-6 max-w-sm w-full shadow-2xl border border-slate-100">
                            <div class="text-center mt-1">
                                <div class="flex justify-center mb-4">
                                    <svg x-show="modal.type === 'confirm'" class="w-12 h-12 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/></svg>
                                    <svg x-show="modal.type === 'error'" class="w-12 h-12 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                                </div>
                                <h3 class="text-lg font-bold text-slate-900 mb-2" x-text="modal.type === 'confirm' ? $L('Hapus dari wishlist?', 'Remove from wishlist?') : $L('Gagal', 'Failed')"></h3>
                                <p class="text-sm text-slate-600 mb-6" x-text="modal.message"></p>
                                <div class="flex gap-2" x-show="modal.type === 'confirm'">
                                    <button type="button" @click="closeModal()" class="flex-1 py-2.5 rounded-xl border border-slate-200 hover:bg-slate-50 text-sm font-medium">{{ evomi_l('Batal', 'Cancel') }}</button>
                                    <button type="button" @click="confirmRemove()" class="flex-1 py-2.5 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-sm font-semibold">{{ evomi_l('Ya, Hapus', 'Yes, Remove') }}</button>
                                </div>
                                <button type="button" x-show="modal.type === 'error'" @click="closeModal()" class="w-full py-2.5 rounded-xl text-sm font-semibold text-white bg-[#1172BA]">{{ evomi_l('Mengerti', 'Got it') }}</button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</template>
