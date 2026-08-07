@extends('layouts.admin')

@section('title', 'Pelacakan | Evomi Admin')

@section('content')
<div x-data="evomiAdminTrackings" class="space-y-6 pb-12">
    <div>
        <h1 class="text-3xl font-bold text-gray-900 tracking-tight" x-text="t('trackings','title')"></h1>
        <p class="text-gray-500 mt-1 text-sm" x-text="t('trackings','subtitle')"></p>
    </div>

    <div x-show="loading" class="min-h-[60vh] flex items-center justify-center"><div class="w-8 h-8 border-4 border-gray-200 border-t-gray-900 rounded-full animate-spin"></div></div>
    <div x-show="!loading && error" class="bg-red-50 text-red-700 rounded-2xl px-5 py-4 text-sm border border-red-100" x-text="error"></div>

    <div x-show="!loading && !error" class="admin-table-card">
        <div class="p-5 sm:p-6 border-b border-gray-100">
            <div class="relative w-full max-w-md">
                <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400 pointer-events-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                <input type="search" x-model="search" :placeholder="t('trackings','search_ph','Cari ID pesanan, no resi, atau nama penerima...','Search order ID, tracking no., or recipient...')" class="admin-search-input">
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full border-collapse min-w-[900px]">
                <thead>
                    <tr class="bg-gray-50/80 border-b border-gray-100">
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-left" x-text="common().product"></th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-left" x-text="t('trackings','col_recipient','Penerima','Recipient')"></th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-left" x-text="t('trackings','col_resi','No. Resi','Tracking No.')"></th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-left" x-text="t('trackings','col_courier','Kurir','Courier')"></th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-center" x-text="common().status"></th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-center" x-text="common().actions"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <template x-if="pagedItems().length === 0">
                        <tr><td colspan="6" class="px-6 py-14 text-center text-sm text-gray-400" x-text="t('trackings','empty','Belum ada data pelacakan.','No tracking data yet.')"></td></tr>
                    </template>
                    <template x-for="trk in pagedItems()" :key="trk.order_id || trk.id">
                        <tr class="hover:bg-gray-50/40 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-start gap-3 min-w-0">
                                    @include('partials.admin-thumb', [
                                        'src' => 'productThumb(trk.product)',
                                        'alt' => "trk.product?.title || ''",
                                        'size' => 'h-12 w-12',
                                        'fit' => 'contain',
                                    ])
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-gray-900 truncate max-w-[240px]" x-text="trk.product?.title || t('orders','no_name','Tanpa Nama','No Name')"></p>
                                        <p class="mt-1 text-[11px] font-bold font-mono text-gray-500 truncate" x-text="'#' + trk.order_id"></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4"><p class="font-bold text-gray-900" x-text="trk.recipient_name || '-'"></p><p class="text-xs text-gray-500 mt-1 max-w-[260px] truncate" x-text="trk.recipient_address || '-'"></p></td>
                            <td class="px-6 py-4"><span class="text-xs font-mono font-semibold px-2.5 py-1.5 rounded-lg border" :class="trk.tracking_number ? 'bg-gray-100 border-gray-200 text-gray-900' : 'bg-amber-50 border-amber-100 text-amber-700'" x-text="trk.tracking_number || t('trackings','no_tracking_number','Belum ada no resi','No tracking number yet')"></span></td>
                            <td class="px-6 py-4 font-semibold text-sm" x-text="trk.courier || '-'"></td>
                            <td class="px-6 py-4 text-center"><span class="inline-flex px-3 py-1.5 rounded-lg text-[11px] font-bold uppercase tracking-wider border bg-gray-50 text-gray-700 border-gray-200" x-text="trk.status || '-'"></span></td>
                            <td class="px-6 py-4 text-center">
                                <button type="button" class="admin-btn-icon" :title="common().edit" @click="openEdit(trk)"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg></button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        @include('partials.admin-pagination', [
            'countExpr' => "filteredItems().length + ' ' + t('trackings','items')",
        ])
    </div>

<template x-teleport="body">
    <div
        x-show="modalOpen"
        x-cloak
        class="admin-modal-root"
        role="dialog"
        aria-modal="true"
        @keydown.escape.window="closeModal()"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    >
        <div class="admin-modal-panel max-w-2xl" role="document" @click.stop
            x-show="modalOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-[0.96] translate-y-3"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        >
            <div class="admin-modal-panel__header">
                <h2 class="text-lg font-bold text-gray-900" x-text="t('trackings','modal_title','Edit Data Tracking','Edit Tracking Data')"></h2>
                <button type="button" class="p-1.5 rounded-full hover:bg-gray-100 text-gray-400" @click="closeModal()" aria-label="Close">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                </button>
            </div>
            <form @submit.prevent="save" class="flex flex-col flex-1 min-h-0">
                <div class="admin-modal-panel__body space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <label class="block">
                            <span class="admin-field-label" x-text="t('trackings','field_order_id')"></span>
                            <input x-model="form.order_id" readonly class="admin-field-input bg-gray-50">
                        </label>
                        <label class="block">
                            <span class="admin-field-label" x-text="t('trackings','field_tracking_number')"></span>
                            <input x-model="form.tracking_number" class="admin-field-input">
                        </label>
                        <label class="block">
                            <span class="admin-field-label" x-text="t('trackings','field_courier')"></span>
                            <input x-model="form.courier" list="kurir-list" class="admin-field-input">
                            <datalist id="kurir-list">
                                <template x-for="k in kurirs" :key="k.id">
                                    <option :value="k.nama"></option>
                                </template>
                            </datalist>
                        </label>
                        <label class="block">
                            <span class="admin-field-label" x-text="t('trackings','field_status')"></span>
                            <input x-model="form.status" class="admin-field-input">
                        </label>
                        <label class="block">
                            <span class="admin-field-label" x-text="t('trackings','field_eta')"></span>
                            <input type="date" x-model="form.estimated_delivery" class="admin-field-input">
                        </label>
                        <label class="block">
                            <span class="admin-field-label" x-text="t('trackings','field_recipient_name')"></span>
                            <input x-model="form.recipient_name" class="admin-field-input">
                        </label>
                        <label class="block">
                            <span class="admin-field-label" x-text="t('trackings','field_recipient_phone')"></span>
                            <input x-model="form.recipient_phone" class="admin-field-input">
                        </label>
                        <label class="block md:col-span-2">
                            <span class="admin-field-label" x-text="t('trackings','field_recipient_address')"></span>
                            <textarea x-model="form.recipient_address" rows="2" class="admin-field-textarea"></textarea>
                        </label>
                    </div>

                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-bold text-gray-900" x-text="t('trackings','timeline')"></h3>
                            <button type="button" class="text-sm font-semibold text-gray-900 hover:underline" @click="addTimeline()" x-text="t('trackings','add_row')"></button>
                        </div>
                        <template x-for="(row, i) in form.timeline" :key="i">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 rounded-xl border border-gray-100 bg-gray-50/50 p-3">
                                <input x-model="row.status" :placeholder="t('trackings','ph_status')" class="admin-field-input h-10">
                                <input type="datetime-local" :value="timelineInput(row.time)" @input="setTimelineTime(row, $event.target.value)" class="admin-field-input h-10">
                                <input x-model="row.description" :placeholder="t('trackings','ph_description')" class="admin-field-input h-10">
                                <button type="button" class="md:col-span-3 justify-self-end text-xs font-semibold text-red-600 hover:underline" @click="removeTimeline(i)" x-text="t('trackings','delete_log')"></button>
                            </div>
                        </template>
                    </div>
                </div>
                <div class="admin-modal-panel__footer">
                    <button type="button" class="px-4 py-2.5 rounded-xl text-sm font-semibold text-gray-600 hover:bg-gray-100" @click="closeModal()" x-text="common().cancel"></button>
                    <button type="submit" :disabled="saving" class="px-5 py-2.5 rounded-xl bg-gray-900 text-white text-sm font-semibold disabled:opacity-60" x-text="saving ? common().saving : common().save_changes"></button>
                </div>
            </form>
        </div>
    </div>
</template>
</div>
@endsection