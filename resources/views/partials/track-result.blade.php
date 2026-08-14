{{-- Shared tracking result: chips + detail (drawer + modal) --}}
<div
    x-show="!drawerTrackLoading && drawerTrackItems.length > 0"
    class="space-y-4"
    x-cloak
    x-transition:enter="ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
>
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
                        :style="{ '--track-item-accent': item.accent || '#1172BA' }"
                        @click="selectDrawerTrack(item.id)"
                    >
                        <div class="evomi-track-chip__img" :style="{ backgroundColor: item.accent || '#1172BA' }">
                            <img :src="item.imageUrl" :alt="item.title" x-on:error="$el.style.display='none'">
                        </div>
                        <div class="evomi-track-chip__body">
                            <p class="evomi-track-chip__code" x-text="item.order_number || item.code"></p>
                            <p class="evomi-track-chip__title" x-text="item.title"></p>
                            <p class="evomi-track-chip__status" :class="drawerTrackToneClass(item.status_tone)" x-text="item.status_label"></p>
                        </div>
                    </button>
                </template>
            </div>
        </div>
    </div>

    <template x-if="drawerTrackSelected">
        <div
            class="space-y-4"
            :key="'track-detail-' + drawerTrackSelected.id"
            :style="{ '--track-item-accent': drawerTrackSelected.accent || '#1172BA' }"
            x-show="true"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
        >
            <div class="evomi-track-summary">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-[11px] text-slate-400 font-medium">{{ evomi_l('Nomor Pesanan', 'Order Number') }}</p>
                        <p class="text-[16px] font-bold text-slate-900 mt-0.5" x-text="drawerTrackSelected.order_number || drawerTrackSelected.code"></p>
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
                        <p class="text-[13px] font-semibold text-slate-800 mt-0.5 break-all" x-text="drawerTrackSelected.tracking_number || drawerTrackSelected.order_number || drawerTrackSelected.code || '—'"></p>
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
                    <svg class="w-3.5 h-3.5 evomi-track-history__icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
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
                                <p class="text-[12px] font-semibold text-slate-800 truncate" x-text="drawerTrackSelected.tracking_number || drawerTrackSelected.order_number || drawerTrackSelected.code || '—'"></p>
                                <button
                                    type="button"
                                    class="evomi-track-copy"
                                    x-show="drawerTrackSelected.tracking_number || drawerTrackSelected.order_number || drawerTrackSelected.code"
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
