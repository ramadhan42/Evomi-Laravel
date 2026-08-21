@extends('layouts.admin')

@section('title', 'Kurir | Evomi Admin')

@section('content')
<div x-data="evomiAdminKurirs" class="space-y-6 pb-12">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 tracking-tight" x-text="t('kurirs','title')"></h1>
            <p class="text-gray-500 mt-1 text-sm" x-text="t('kurirs','subtitle')"></p>
        </div>
        <button type="button" @click="openAdd()" class="inline-flex items-center justify-center gap-2 bg-gray-900 hover:bg-black text-white px-5 py-2.5 rounded-xl text-sm font-semibold shadow-sm transition">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
            <span x-text="t('kurirs','add')"></span>
        </button>
    </div>

    <div x-show="loading" class="min-h-[60vh] flex items-center justify-center"><div class="w-8 h-8 border-4 border-gray-200 border-t-gray-900 rounded-full animate-spin"></div></div>
    <div x-show="!loading && error" class="bg-red-50 text-red-700 rounded-2xl px-5 py-4 text-sm border border-red-100" x-text="error"></div>

    <div x-show="!loading && !error" class="admin-table-card">
        <div class="p-5 sm:p-6 border-b border-gray-100">
            <div class="relative w-full max-w-md">
                <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400 pointer-events-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                <input type="search" x-model="search" :placeholder="t('kurirs','search_ph')" class="admin-search-input">
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full border-collapse min-w-[860px]">
                <thead>
                    <tr class="bg-gray-50/80 border-b border-gray-100">
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-left" x-text="t('kurirs','col_name')"></th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-center" x-text="t('kurirs','col_type')"></th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-center" x-text="t('kurirs','col_price')"></th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-center" x-text="t('kurirs','col_dest')"></th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-center" x-text="t('kurirs','col_eta')"></th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-center" x-text="common().status"></th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-center" x-text="common().actions"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <template x-if="pagedItems().length === 0">
                        <tr><td colspan="7" class="px-6 py-14 text-center text-sm text-gray-400" x-text="t('kurirs','empty')"></td></tr>
                    </template>
                    <template x-for="k in pagedItems()" :key="k.id">
                        <tr>
                            <td class="px-6 py-4 text-sm font-semibold text-gray-900" x-text="k.nama"></td>
                            <td class="px-6 py-4 text-center text-sm capitalize" x-text="k.jenis || '-'"></td>
                            <td class="px-6 py-4 text-center text-sm font-bold" x-text="formatRupiah(k.harga)"></td>
                            <td class="px-6 py-4 text-center text-sm text-gray-600" x-text="k.destinasi || '-'"></td>
                            <td class="px-6 py-4 text-center text-sm font-semibold" x-text="k.estimasi_hari ?? '-'"></td>
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex px-2.5 py-1 rounded-lg text-[11px] font-bold uppercase tracking-wider border" :class="k.is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-gray-100 text-gray-500 border-gray-200'" x-text="k.is_active ? t('kurirs','active') : t('kurirs','inactive')"></span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex justify-center gap-2">
                                    <button type="button" class="admin-btn-icon" @click="openEdit(k)"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg></button>
                                    <button type="button" class="admin-btn-icon admin-btn-icon--danger" @click="remove(k.id)"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/></svg></button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        @include('partials.admin-pagination', ['countExpr' => "filteredItems().length + ' ' + t('kurirs','items')"])
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
        <div class="admin-modal-panel max-w-lg" role="document" @click.stop
            x-show="modalOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-[0.96] translate-y-3"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0 scale-[0.98]"
        >
            <div class="admin-modal-panel__header">
                <h2 class="text-lg font-bold text-gray-900" x-text="modalMode === 'add' ? t('kurirs','add') : t('kurirs','edit')"></h2>
                <button type="button" class="p-1.5 rounded-full hover:bg-gray-100 text-gray-400 hover:text-gray-700" @click="closeModal()" aria-label="Close">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                </button>
            </div>
            <form @submit.prevent="save" class="flex flex-col flex-1 min-h-0">
                <div class="admin-modal-panel__body space-y-4">
                    <label class="block">
                        <span class="admin-field-label" x-text="t('kurirs','col_name')"></span>
                        <input required x-model="form.nama" class="admin-field-input">
                    </label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <label class="block">
                            <span class="admin-field-label" x-text="t('kurirs','col_type')"></span>
                            <input required x-model="form.jenis" class="admin-field-input" placeholder="reguler / express">
                        </label>
                        <label class="block">
                            <span class="admin-field-label" x-text="t('kurirs','col_price')"></span>
                            <input type="number" min="0" required x-model="form.harga" class="admin-field-input">
                        </label>
                    </div>
                    <label class="block">
                        <span class="admin-field-label" x-text="t('kurirs','col_dest')"></span>
                        <input x-model="form.destinasi" class="admin-field-input">
                    </label>
                    <label class="block">
                        <span class="admin-field-label" x-text="t('kurirs','col_eta')"></span>
                        <input type="number" min="1" x-model="form.estimasi_hari" class="admin-field-input">
                    </label>
                    <label class="flex items-center gap-2 text-sm font-semibold text-gray-700">
                        <input type="checkbox" x-model="form.is_active" class="rounded border-gray-300 text-gray-900 focus:ring-gray-900">
                        <span x-text="t('kurirs','active')"></span>
                    </label>
                </div>
                <div class="admin-modal-panel__footer">
                    <button type="button" class="px-4 py-2.5 rounded-xl text-sm font-semibold text-gray-600 hover:bg-gray-100" @click="closeModal()" x-text="common().cancel"></button>
                    <button type="submit" :disabled="saving" class="px-5 py-2.5 rounded-xl bg-gray-900 text-white text-sm font-semibold disabled:opacity-60" x-text="saving ? common().saving : common().save"></button>
                </div>
            </form>
        </div>
    </div>
</template>

{{-- FREE SHIPPING TOGGLE --}}
<div x-data="evomiAdminFreeShipping" class="mt-10 space-y-4 pt-6 border-t border-gray-100">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 tracking-tight">Gratis Ongkir</h2>
            <p class="text-gray-500 mt-1 text-sm">Aktifkan untuk menghilangkan pilihan kurir & ongkir di halaman belanja dan checkout. Akan muncul tulisan "Gratis Ongkir".</p>
        </div>
    </div>

    <div class="bg-white border border-gray-100 rounded-2xl p-6 shadow-sm">
        <div x-show="loading" class="flex items-center gap-2 text-sm text-gray-400"><div class="w-4 h-4 border-2 border-gray-200 border-t-gray-600 rounded-full animate-spin"></div> Memuat...</div>
        <div x-show="!loading" class="flex items-center gap-4">
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" x-model="enabled" @change="toggle()" class="sr-only peer">
                <div class="w-11 h-6 bg-gray-200 peer-focus:ring-2 peer-focus:ring-emerald-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
            </label>
            <span class="text-sm font-semibold" :class="enabled ? 'text-emerald-700' : 'text-gray-500'" x-text="enabled ? 'Gratis Ongkir Aktif' : 'Gratis Ongkir Nonaktif'"></span>
            <span x-show="saving" class="text-xs text-gray-400 ml-2">Menyimpan...</span>
        </div>
    </div>
</div>

{{-- Kurir Tarifs: per kota & rentang berat --}}
<div x-data="evomiAdminKurirTarifs" class="mt-10 space-y-6 pt-6 border-t border-gray-100">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 tracking-tight">Tarif Ongkir Per Kota & Berat</h2>
            <p class="text-gray-500 mt-1 text-sm">Atur harga ongkir manual berdasarkan kota tujuan dan total berat.</p>
        </div>
        <button type="button" @click="openAdd()" class="inline-flex items-center justify-center gap-2 bg-gray-900 hover:bg-black text-white px-5 py-2.5 rounded-xl text-sm font-semibold shadow-sm transition">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
            <span>Tambah Tarif</span>
        </button>
    </div>

    <div x-show="loading" class="min-h-[40vh] flex items-center justify-center"><div class="w-8 h-8 border-4 border-gray-200 border-t-gray-900 rounded-full animate-spin"></div></div>
    <div x-show="!loading && error" class="bg-red-50 text-red-700 rounded-2xl px-5 py-4 text-sm border border-red-100" x-text="error"></div>

    <div x-show="!loading && !error" class="admin-table-card">
        <div class="p-5 sm:p-6 border-b border-gray-100">
            <div class="relative w-full max-w-md">
                <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400 pointer-events-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                <input type="search" x-model="search" placeholder="Cari kota atau kurir..." class="admin-search-input">
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full border-collapse min-w-[980px]">
                <thead>
                    <tr class="bg-gray-50/80 border-b border-gray-100">
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-left">Kurir</th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-center">Kota asal</th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-center">Kota tujuan</th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-center">Berat (g)</th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-center">Harga</th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-center">ETA (hari)</th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-center" x-text="common().status"></th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-center" x-text="common().actions"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <template x-if="pagedItems().length === 0">
                        <tr><td colspan="8" class="px-6 py-14 text-center text-sm text-gray-400">Belum ada data tarif.</td></tr>
                    </template>
                    <template x-for="tarif in pagedItems()" :key="tarif.id">
                        <tr>
                            <td class="px-6 py-4 text-sm font-semibold text-gray-900" x-text="tarif.nama + ' ' + (tarif.jenis || '')"></td>
                            <td class="px-6 py-4 text-center text-sm text-gray-600" x-text="tarif.kota_asal || '-'"></td>
                            <td class="px-6 py-4 text-center text-sm text-gray-600" x-text="tarif.kota_tujuan || '-'"></td>
                            <td class="px-6 py-4 text-center text-sm text-gray-700" x-text="tarif.berat_min_gram + ' - ' + tarif.berat_max_gram"></td>
                            <td class="px-6 py-4 text-center text-sm font-bold" x-text="formatRupiah(tarif.harga)"></td>
                            <td class="px-6 py-4 text-center text-sm font-semibold" x-text="tarif.estimasi_hari ?? '-'"></td>
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex px-2.5 py-1 rounded-lg text-[11px] font-bold uppercase tracking-wider border"
                                    :class="tarif.is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-gray-100 text-gray-500 border-gray-200'"
                                    x-text="tarif.is_active ? t('kurirs','active') : t('kurirs','inactive')"
                                ></span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex justify-center gap-2">
                                    <button type="button" class="admin-btn-icon" @click="openEdit(tarif)"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg></button>
                                    <button type="button" class="admin-btn-icon admin-btn-icon--danger" @click="remove(tarif.id)"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/></svg></button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        @include('partials.admin-pagination', ['countExpr' => "filteredItems().length + ' item'"])
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
            <div class="admin-modal-panel max-w-lg" role="document" @click.stop
                x-show="modalOpen"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-[0.96] translate-y-3"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0 scale-[0.98]"
            >
                <div class="admin-modal-panel__header">
                    <h2 class="text-lg font-bold text-gray-900" x-text="modalMode === 'add' ? 'Tambah Tarif' : 'Edit Tarif'"></h2>
                    <button type="button" class="p-1.5 rounded-full hover:bg-gray-100 text-gray-400 hover:text-gray-700" @click="closeModal()" aria-label="Close">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                    </button>
                </div>
                <form @submit.prevent="save" class="flex flex-col flex-1 min-h-0">
                    <div class="admin-modal-panel__body space-y-4">
                        <label class="block">
                            <span class="admin-field-label">Kurir</span>
                            <select required x-model="form.kurir_id" class="admin-field-input">
                                <option value="" disabled>Pilih kurir</option>
                                <template x-for="k in kurirOptions" :key="k.id">
                                    <option :value="k.id" x-text="k.nama + ' ' + (k.jenis || '')"></option>
                                </template>
                            </select>
                        </label>
                        <label class="block">
                            <span class="admin-field-label">Kota asal</span>
                            <input required x-model="form.kota_asal" class="admin-field-input" placeholder="contoh: Cisauk">
                        </label>
                        <label class="block">
                            <span class="admin-field-label">Kota tujuan</span>
                            <input required x-model="form.kota_tujuan" class="admin-field-input" placeholder="contoh: Jakarta">
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <label class="block">
                                <span class="admin-field-label">Berat min (g)</span>
                                <input type="number" min="0" required x-model="form.berat_min_gram" class="admin-field-input">
                            </label>
                            <label class="block">
                                <span class="admin-field-label">Berat max (g)</span>
                                <input type="number" min="0" required x-model="form.berat_max_gram" class="admin-field-input">
                            </label>
                        </div>
                        <label class="block">
                            <span class="admin-field-label">Harga (Rp)</span>
                            <input type="number" min="0" required step="1" x-model="form.harga" class="admin-field-input">
                        </label>
                        <label class="block">
                            <span class="admin-field-label">ETA (hari)</span>
                            <input type="number" min="1" x-model="form.estimasi_hari" class="admin-field-input">
                        </label>
                        <label class="flex items-center gap-2 text-sm font-semibold text-gray-700">
                            <input type="checkbox" x-model="form.is_active" class="rounded border-gray-300 text-gray-900 focus:ring-gray-900">
                            <span>Aktif</span>
                        </label>
                    </div>
                    <div class="admin-modal-panel__footer">
                        <button type="button" class="px-4 py-2.5 rounded-xl text-sm font-semibold text-gray-600 hover:bg-gray-100" @click="closeModal()">
                            <span x-text="common().cancel"></span>
                        </button>
                        <button type="submit" :disabled="saving" class="px-5 py-2.5 rounded-xl bg-gray-900 text-white text-sm font-semibold disabled:opacity-60"
                            x-text="saving ? common().saving : common().save"></button>
                    </div>
                </form>
            </div>
        </div>
    </template>
</div>

</div>
@endsection