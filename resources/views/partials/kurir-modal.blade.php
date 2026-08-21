{{-- Courier picker — same shape language as chat / help modals --}}
@php($brandExpr = $brandExpr ?? 'accent')
<style>
.evomi-kurir-modal{position:fixed;inset:0;z-index:230}
.evomi-kurir-modal__backdrop{position:absolute;inset:0;background:rgba(15,23,42,.48);backdrop-filter:blur(6px);-webkit-backdrop-filter:blur(6px)}
.evomi-kurir-modal__frame{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;padding:1rem}
.evomi-kurir-modal__panel{
  --kurir-brand:#1172BA;
  position:relative;
  display:flex;
  flex-direction:column;
  width:min(92vw,480px);
  max-height:min(88vh,640px);
  overflow:hidden;
  background:#fff;
  border-radius:24px;
  box-shadow:0 24px 80px rgba(15,23,42,.28);
}
.evomi-kurir-modal__header{
  flex-shrink:0;
  display:flex;
  align-items:flex-start;
  justify-content:space-between;
  gap:.75rem;
  padding:1.05rem 1.1rem .95rem;
  background:linear-gradient(135deg,var(--kurir-brand) 0%,color-mix(in srgb,var(--kurir-brand) 78%,#fff) 55%,color-mix(in srgb,var(--kurir-brand) 82%,#0b3d66) 100%);
  color:#fff;
}
.evomi-kurir-modal__kicker{font-size:11px;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:rgba(255,255,255,.72)}
.evomi-kurir-modal__title{margin-top:.1rem;font-size:1.1rem;font-weight:700;letter-spacing:-.02em;line-height:1.25}
.evomi-kurir-modal__subtitle{margin-top:.35rem;font-size:12px;line-height:1.4;color:rgba(255,255,255,.88)}
.evomi-kurir-modal__avatar{
  position:relative;display:inline-flex;align-items:center;justify-content:center;
  width:42px;height:42px;border-radius:14px;background:rgba(255,255,255,.16);flex-shrink:0;
}
.evomi-kurir-modal__close{
  display:inline-flex;align-items:center;justify-content:center;
  width:34px;height:34px;border-radius:999px;border:0;color:#fff;
  background:rgba(255,255,255,.14);flex-shrink:0;cursor:pointer;
}
.evomi-kurir-modal__close:hover{background:rgba(255,255,255,.22)}
.evomi-kurir-modal__body{
  flex:1;min-height:0;overflow-y:auto;overscroll-behavior:contain;
  padding:.9rem 1rem 1rem;
  background:linear-gradient(180deg,#f8fafc 0%,#fff 28%);
}
.evomi-kurir-modal__list{display:flex;flex-direction:column;gap:.7rem}
.evomi-kurir-modal__option{
  display:flex;align-items:center;gap:.8rem;width:100%;text-align:left;
  padding:.9rem 1rem;border-radius:16px;border:1px solid #eef2f7;background:#fff;
  box-shadow:0 6px 14px rgba(15,23,42,.04);cursor:pointer;transition:border-color .18s,box-shadow .18s,background .18s,transform .18s;
}
.evomi-kurir-modal__option:hover{border-color:#dbe4ee;transform:translateY(-1px);box-shadow:0 10px 22px rgba(15,23,42,.08)}
.evomi-kurir-modal__option.is-selected{
  border-color:var(--kurir-brand);
  background:color-mix(in srgb,var(--kurir-brand) 8%,#fff);
  box-shadow:0 10px 24px color-mix(in srgb,var(--kurir-brand) 18%,transparent);
}
.evomi-kurir-modal__radio{
  width:20px;height:20px;border-radius:999px;border:2px solid #cbd5e1;flex-shrink:0;
  display:inline-flex;align-items:center;justify-content:center;background:#fff;
}
.evomi-kurir-modal__option.is-selected .evomi-kurir-modal__radio{
  border-color:var(--kurir-brand);background:var(--kurir-brand);
}
.evomi-kurir-modal__radio-dot{width:8px;height:8px;border-radius:999px;background:#fff;opacity:0}
.evomi-kurir-modal__option.is-selected .evomi-kurir-modal__radio-dot{opacity:1}
.evomi-kurir-modal__icon{
  width:42px;height:42px;border-radius:14px;flex-shrink:0;
  display:inline-flex;align-items:center;justify-content:center;
  background:#f1f5f9;color:#64748b;
}
.evomi-kurir-modal__option.is-selected .evomi-kurir-modal__icon{
  background:color-mix(in srgb,var(--kurir-brand) 16%,#fff);
  color:var(--kurir-brand);
}
.evomi-kurir-modal__name{display:block;font-size:14px;font-weight:700;color:#0f172a;letter-spacing:-.01em}
.evomi-kurir-modal__meta{display:flex;flex-wrap:wrap;align-items:center;gap:.35rem;margin-top:.2rem}
.evomi-kurir-modal__chip{
  display:inline-flex;align-items:center;border-radius:999px;padding:.15rem .5rem;
  font-size:10px;font-weight:700;letter-spacing:.02em;text-transform:uppercase;
  background:#f1f5f9;color:#64748b;
}
.evomi-kurir-modal__option.is-selected .evomi-kurir-modal__chip{
  background:color-mix(in srgb,var(--kurir-brand) 14%,#fff);
  color:var(--kurir-brand);
}
.evomi-kurir-modal__eta{font-size:12px;color:#64748b;line-height:1.4}
.evomi-kurir-modal__dest{display:block;margin-top:.15rem;font-size:11px;color:#94a3b8;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.evomi-kurir-modal__price{font-size:15px;font-weight:800;color:#0f172a;letter-spacing:-.02em;flex-shrink:0}
.evomi-kurir-modal__option.is-selected .evomi-kurir-modal__price{color:var(--kurir-brand)}
.evomi-kurir-modal__empty{display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;min-height:180px;padding:1.5rem .75rem}
.evomi-kurir-modal__footer{
  flex-shrink:0;border-top:1px solid #eef2f7;background:#fff;
  padding:.75rem .9rem .9rem;
}
.evomi-kurir-modal__confirm{
  width:100%;height:48px;border:0;border-radius:16px;color:#fff;font-weight:700;font-size:14px;cursor:pointer;
  background:var(--kurir-brand);box-shadow:0 8px 18px color-mix(in srgb,var(--kurir-brand) 28%,transparent);
}
.evomi-kurir-modal__confirm:disabled{opacity:.45;cursor:not-allowed;box-shadow:none}
@media (prefers-reduced-motion:reduce){
  .evomi-kurir-modal__option{transition:none}
}
</style>

<template x-teleport="body">
    <div
        class="evomi-kurir-modal"
        x-show="showKurirList"
        x-cloak
        :class="showKurirList ? 'pointer-events-auto' : 'pointer-events-none'"
        @keydown.escape.window="showKurirList && (showKurirList = false)"
    >
        <div
            class="evomi-kurir-modal__backdrop"
            x-show="showKurirList"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="showKurirList = false"
        ></div>

        <div class="evomi-kurir-modal__frame" x-show="showKurirList" @click.self="showKurirList = false">
            <div
                class="evomi-kurir-modal__panel"
                role="dialog"
                aria-modal="true"
                aria-label="{{ evomi_l('Pilih Pengiriman', 'Choose Shipping') }}"
                :style="{ '--kurir-brand': {{ $brandExpr }} }"
                x-show="showKurirList"
                x-transition:enter="ease-[cubic-bezier(0.22,1,0.36,1)] duration-420"
                x-transition:enter-start="opacity-0 scale-[0.96] translate-y-4"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="ease-in duration-220"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-[0.98]"
                @click.stop
            >
                <div class="evomi-kurir-modal__header">
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="evomi-kurir-modal__avatar" aria-hidden="true">
                            @include('partials.icons.truck', ['class' => 'w-5 h-5'])
                        </span>
                        <div class="min-w-0">
                            <p class="evomi-kurir-modal__kicker">{{ evomi_l('Pengiriman', 'Shipping') }}</p>
                            <h2 class="evomi-kurir-modal__title truncate">{{ evomi_l('Pilih Pengiriman', 'Choose Shipping') }}</h2>
                            <p class="evomi-kurir-modal__subtitle truncate">{{ evomi_l('Bandingkan kurir, estimasi, dan ongkir', 'Compare courier, ETA, and shipping fee') }}</p>
                        </div>
                    </div>
                    <button type="button" class="evomi-kurir-modal__close" @click="showKurirList = false" :aria-label="$L('Tutup', 'Close')">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 16 16" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4 4 12M4 4l8 8"/></svg>
                    </button>
                </div>

                <div class="evomi-kurir-modal__body">
                    <div class="evomi-kurir-modal__list" x-show="kurirs.length">
                        <template x-for="kurir in kurirs" :key="kurir.id">
                            <button
                                type="button"
                                class="evomi-kurir-modal__option"
                                :class="{ 'is-selected': selectedKurir?.id === kurir.id }"
                                @click="selectKurir(kurir)"
                            >
                                <span class="evomi-kurir-modal__radio" aria-hidden="true">
                                    <span class="evomi-kurir-modal__radio-dot"></span>
                                </span>
                                <span class="evomi-kurir-modal__icon" aria-hidden="true">
                                    @include('partials.icons.truck', ['class' => 'w-[18px] h-[18px]'])
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="evomi-kurir-modal__name" x-text="kurir.nama"></span>
                                    <span class="evomi-kurir-modal__meta">
                                        <span class="evomi-kurir-modal__chip" x-show="kurir.jenis" x-text="kurir.jenis"></span>
                                        <span class="evomi-kurir-modal__eta">
                                            {{ evomi_l('Tiba', 'Arrives') }}
                                            <span x-text="estimasiTiba(kurir)"></span>
                                            <span x-show="kurir.estimasi_hari" x-text="' · ±' + kurir.estimasi_hari + ' ' + $L('hari', 'days')"></span>
                                        </span>
                                    </span>
                                    <span class="evomi-kurir-modal__dest" x-show="kurir.destinasi" x-text="kurir.destinasi"></span>
                                </span>
                                <span class="evomi-kurir-modal__price" x-text="formatPrice(kurir.harga)"></span>
                            </button>
                        </template>
                    </div>

                    <div class="evomi-kurir-modal__empty" x-show="!kurirs.length" x-cloak>
                        <div class="w-14 h-14 rounded-2xl flex items-center justify-center text-white mb-3" :style="{ backgroundColor: {{ $brandExpr }} }">
                            @include('partials.icons.truck', ['class' => 'w-6 h-6'])
                        </div>
                        <p class="text-[15px] font-bold text-slate-900 mb-1">{{ evomi_l('Kurir belum tersedia', 'No couriers yet') }}</p>
                        <p class="text-[12px] text-slate-500 max-w-sm leading-relaxed" x-text="shippingOptionsError || $L('Memuat data kurir...', 'Loading courier data...')"></p>
                    </div>
                </div>

                <div class="evomi-kurir-modal__footer">
                    <button
                        type="button"
                        class="evomi-kurir-modal__confirm"
                        :disabled="!selectedKurir"
                        @click="showKurirList = false"
                    >
                        {{ evomi_l('Gunakan kurir ini', 'Use this courier') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
