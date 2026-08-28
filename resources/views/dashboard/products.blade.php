@extends('layouts.admin')

@section('title', 'Produk | Evomi Admin')

@section('content')
<div x-data="evomiAdminProducts" class="space-y-6 pb-12">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900" x-text="t('products','title')"></h1>
            <p class="text-gray-500 mt-1" x-text="t('products','subtitle')"></p>
        </div>
        <button
            type="button"
            @click="openAdd()"
            class="flex items-center justify-center gap-2 bg-gray-900 hover:bg-gray-800 text-white px-5 py-2.5 rounded-xl text-sm font-semibold transition-all shadow-[0_4px_14px_0_rgb(0,0,0,0.1)]"
        >
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
            <span x-text="t('products','add')"></span>
        </button>
    </div>

    <div x-show="loading" class="min-h-[60vh] flex items-center justify-center">
        <div class="w-8 h-8 border-4 border-gray-200 border-t-gray-900 rounded-full animate-spin"></div>
    </div>
    <div x-show="!loading && error" class="bg-red-50 text-red-700 rounded-2xl px-5 py-4 text-sm border border-red-100" x-text="error"></div>

    <div x-show="!loading && !error" class="admin-table-card">
        <div class="p-5 sm:p-6 border-b border-gray-100">
            <div class="relative w-full max-w-md">
                <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400 pointer-events-none" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                <input
                    type="search"
                    x-model="search"
                    :placeholder="t('products','search_placeholder')"
                    class="block w-full pl-10 pr-3 py-2 border border-gray-200 rounded-xl text-sm text-gray-900 placeholder:text-gray-400 bg-white focus:outline-none focus:ring-2 focus:ring-gray-900/10 focus:border-gray-400 transition-all"
                >
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full border-collapse min-w-[800px]">
                <thead>
                    <tr class="bg-gray-50/80 border-b border-gray-100">
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-center w-[300px]" x-text="common().product"></th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-center" x-text="t('products','type_size')"></th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-center" x-text="common().price"></th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-center" x-text="t('products','stock')"></th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-center" x-text="common().status"></th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-center" x-text="common().actions"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <template x-if="filteredItems().length === 0">
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-sm text-gray-400 font-medium" x-text="t('products','empty')"></td>
                        </tr>
                    </template>
                    <template x-for="p in filteredItems()" :key="p.id">
                        <tr class="hover:bg-gray-50/40 transition-colors group">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-4 justify-center max-w-xs mx-auto text-left">
                                    @include('partials.admin-thumb', [
                                        'src' => 'productThumb(p)',
                                        'alt' => 'p.title',
                                        'size' => 'h-14 w-14',
                                        'fit' => 'contain',
                                    ])
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-semibold text-gray-900 truncate" x-text="p.title || t('products','no_name')"></p>
                                        <p class="text-xs text-gray-500 mt-0.5 capitalize truncate" x-text="(p.personality_type || '').replace('_', ' ')"></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <p class="text-sm font-semibold text-gray-900" x-text="p.perfume_type || '-'"></p>
                                <p class="text-xs text-gray-500 mt-0.5 font-medium" x-text="(p.bottle_size || '-') + ' ml | ' + (p.gender || '-')"></p>
                            </td>
                            <td class="px-6 py-4 text-center text-sm font-bold text-gray-900" x-text="formatRupiah(p.price)"></td>
                            <td class="px-6 py-4 text-center text-sm font-bold text-gray-600">
                                <span x-text="p.quantity ?? 0"></span>
                                <span class="text-xs font-medium text-gray-400">pcs</span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span
                                    class="inline-flex items-center px-2.5 py-1 rounded-lg text-[11px] font-bold uppercase tracking-wider border shadow-sm"
                                    :class="stockBadgeClass(p)"
                                    x-text="p.stock_status || '-'"
                                ></span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <button
                                        type="button"
                                        class="p-2 rounded-lg text-gray-400 hover:text-gray-900 hover:bg-gray-100 border border-gray-200 shadow-sm bg-white transition-colors"
                                        :title="common().edit"
                                        @click="openEdit(p)"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                                    </button>
                                    <button
                                        type="button"
                                        class="p-2 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 border border-gray-200 shadow-sm bg-white transition-colors"
                                        :title="common().delete"
                                        @click="remove(p.id)"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    {{-- CREATE / EDIT MODAL — Next.js parity --}}
<template x-teleport="body">
    <div
        x-show="modalOpen"
        x-cloak
        class="admin-modal-root"
        role="dialog"
        aria-modal="true"
        @keydown.escape.window="closeModal()"
        @click.self="closeModal()"
    >
        <div
            class="bg-white rounded-2xl w-full max-w-3xl h-[80vh] max-h-[80vh] flex flex-col shadow-2xl border border-gray-100 overflow-hidden relative"
            role="document"
            @click.stop
        >
            {{-- Upload progress overlay --}}
            <div
                x-show="saving"
                x-cloak
                class="absolute inset-0 z-20 bg-white/70 backdrop-blur-[1px] flex items-center justify-center p-6"
            >
                <div class="w-full max-w-sm rounded-2xl border border-gray-200 bg-white p-5 shadow-lg">
                    <div class="flex items-center gap-3 mb-3">
                        <svg class="h-5 w-5 animate-spin text-gray-900 shrink-0" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                        </svg>
                        <div>
                            <p class="text-sm font-bold text-gray-900" x-text="uploadHeadline()"></p>
                            <p class="text-xs text-gray-500 mt-0.5" x-text="t('products','upload_please_wait')"></p>
                        </div>
                    </div>
                    <div class="h-2.5 w-full rounded-full bg-gray-100 overflow-hidden">
                        <div
                            class="h-full rounded-full bg-gray-900 transition-[width] duration-150 ease-out"
                            :style="'width:' + Math.max(2, uploadProgress) + '%'"
                        ></div>
                    </div>
                    <p class="mt-2 text-right text-sm font-semibold tabular-nums text-gray-900" x-text="uploadProgress + '%'"></p>
                </div>
            </div>

            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between shrink-0 bg-white">
                <h3
                    class="text-lg font-bold text-gray-900"
                    x-text="modalMode === 'add' ? t('products','modal_add') : t('products','modal_edit')"
                ></h3>
                <button
                    type="button"
                    @click="closeModal()"
                    :disabled="saving"
                    class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-xl transition-colors disabled:opacity-40 disabled:pointer-events-none"
                >
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                </button>
            </div>

            <form @submit.prevent="save" class="flex flex-col flex-1 min-h-0" enctype="multipart/form-data">
                <div class="flex-1 min-h-0 overflow-y-auto p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {{-- LEFT COLUMN --}}
                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5" x-text="t('products','field_title_id')"></label>
                                <input
                                    type="text"
                                    x-model="form.title"
                                    required
                                    :placeholder="t('products','ph_title')"
                                    class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm font-medium text-gray-900 focus:ring-2 focus:ring-gray-900 outline-none transition-all"
                                >
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5" x-text="t('products','field_title_en')"></label>
                                <input
                                    type="text"
                                    x-model="form.title_en"
                                    :placeholder="t('products','ph_title')"
                                    class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm font-medium text-gray-900 focus:ring-2 focus:ring-gray-900 outline-none transition-all"
                                >
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5" x-text="t('products','field_price')"></label>
                                    <input type="number" x-model="form.price" required min="0" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm font-medium text-gray-900 focus:ring-2 focus:ring-gray-900 outline-none transition-all">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5" x-text="t('products','field_quantity')"></label>
                                    <input type="number" x-model="form.quantity" required min="0" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm font-medium text-gray-900 focus:ring-2 focus:ring-gray-900 outline-none transition-all">
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5" x-text="t('products','field_personality')"></label>
                                    <select x-model="form.personality_type" required class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm font-medium text-gray-900 focus:ring-2 focus:ring-gray-900 outline-none transition-all bg-white">
                                        <option value="" x-text="t('products','ph_personality')"></option>
                                        <option value="prestige">Prestige</option>
                                        <option value="peaceful_calm">Peaceful Calm</option>
                                        <option value="rebel_brave">Rebel Brave</option>
                                        <option value="sweet_shy">Sweet Shy</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5" x-text="t('products','field_stock_status')"></label>
                                    <select x-model="form.stock_status" required class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm font-medium text-gray-900 focus:ring-2 focus:ring-gray-900 outline-none transition-all bg-white">
                                        <option value="tersedia">Tersedia</option>
                                        <option value="minim">Minim</option>
                                        <option value="habis">Habis</option>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5" x-text="t('products','field_description_id')"></label>
                                <textarea
                                    x-model="form.description"
                                    required
                                    rows="4"
                                    :placeholder="t('products','ph_description')"
                                    class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm font-medium text-gray-900 focus:ring-2 focus:ring-gray-900 outline-none transition-all resize-none"
                                ></textarea>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5" x-text="t('products','field_description_en')"></label>
                                <textarea
                                    x-model="form.description_en"
                                    rows="4"
                                    :placeholder="t('products','ph_description')"
                                    class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm font-medium text-gray-900 focus:ring-2 focus:ring-gray-900 outline-none transition-all resize-none"
                                ></textarea>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2" x-text="t('products','images_group')"></label>
                                <div class="space-y-3">
                                    <template x-for="img in imageFields" :key="img.key">
                                        <div class="rounded-xl border border-gray-200 bg-gray-50/60 p-3">
                                            <div class="flex items-center justify-between gap-2 mb-2">
                                                <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wide">
                                                    <span x-text="t('products', img.label)"></span>
                                                    <span
                                                        x-show="modalMode === 'add' && img.requiredOnCreate"
                                                        class="normal-case font-semibold text-gray-400"
                                                        x-text="' ' + t('products','image_required_mark')"
                                                    ></span>
                                                </p>
                                                <span
                                                    x-show="modalMode === 'edit' && hasStoredImage(img.key)"
                                                    class="text-[10px] text-emerald-600 font-medium"
                                                    x-text="t('products','image_in_database')"
                                                ></span>
                                            </div>
                                            <div class="flex items-center gap-3">
                                                <div class="h-16 w-16 rounded-lg bg-white border border-gray-200 overflow-hidden flex items-center justify-center shrink-0 p-1">
                                                    <img
                                                        x-show="previews[img.key]"
                                                        :src="previews[img.key]"
                                                        :alt="img.key"
                                                        class="max-h-full max-w-full"
                                                        :class="img.key === 'image_produk_belanja' ? 'object-contain' : 'h-full w-full object-cover'"
                                                        x-on:error="$el.style.display='none'"
                                                    >
                                                    <svg
                                                        x-show="!previews[img.key]"
                                                        class="h-5 w-5 text-gray-300"
                                                        viewBox="0 0 24 24"
                                                        fill="none"
                                                        stroke="currentColor"
                                                        stroke-width="1.5"
                                                        aria-hidden="true"
                                                    ><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>
                                                </div>
                                                <input
                                                    type="file"
                                                    accept="image/jpeg,image/png,image/jpg,image/webp"
                                                    :required="modalMode === 'add' && img.requiredOnCreate"
                                                    class="flex-1 text-xs text-gray-500 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-gray-900 file:text-white hover:file:bg-gray-800"
                                                    @change="onFile(img.key, $event)"
                                                >
                                            </div>
                                        </div>
                                    </template>
                                </div>
                                <p class="text-[11px] text-gray-400 mt-2">
                                    <span x-text="t('products','image_max_hint')"></span>
                                    <span x-show="modalMode === 'edit'" x-text="' ' + t('products','image_keep_hint')"></span>
                                </p>
                            </div>
                        </div>

                        {{-- RIGHT COLUMN --}}
                        <div class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5" x-text="t('products','field_perfume_type')"></label>
                                    <input type="text" x-model="form.perfume_type" required class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm font-medium text-gray-900 focus:ring-2 focus:ring-gray-900 outline-none transition-all">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5" x-text="t('products','field_gender')"></label>
                                    <select x-model="form.gender" required class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm font-medium text-gray-900 focus:ring-2 focus:ring-gray-900 outline-none transition-all bg-white">
                                        <option value="unisex">Unisex</option>
                                        <option value="male">Pria</option>
                                        <option value="female">Wanita</option>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5" x-text="t('products','field_bottle_size')"></label>
                                <input type="number" x-model="form.bottle_size" required min="1" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm font-medium text-gray-900 focus:ring-2 focus:ring-gray-900 outline-none transition-all">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5" x-text="t('products','field_berat_satuan')"></label>
                                <input type="number" x-model="form.berat_satuan" min="0" step="1" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm font-medium text-gray-900 focus:ring-2 focus:ring-gray-900 outline-none transition-all">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5" x-text="t('products','field_color')"></label>
                                <div class="flex items-center gap-3">
                                    <input type="color" x-model="form.color" class="h-11 w-14 rounded-xl border border-gray-200 bg-white p-1 cursor-pointer">
                                    <input type="text" x-model="form.color" class="flex-1 px-3 py-2.5 border border-gray-200 rounded-xl text-sm font-mono text-gray-900 focus:ring-2 focus:ring-gray-900 outline-none transition-all uppercase">
                                </div>
                            </div>

                            <div class="p-4 bg-gray-50 border border-gray-100 rounded-xl space-y-3">
                                <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider" x-text="t('products','fragrance_notes')"></h4>
                                <div>
                                    <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wide mb-1" x-text="t('products','field_top_note')"></label>
                                    <input type="text" x-model="form.top_note" required :placeholder="t('products','ph_top_note')" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm font-medium text-gray-900 focus:ring-2 focus:ring-gray-900 outline-none transition-all">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wide mb-1" x-text="t('products','field_middle_note')"></label>
                                    <input type="text" x-model="form.middle_note" required :placeholder="t('products','ph_middle_note')" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm font-medium text-gray-900 focus:ring-2 focus:ring-gray-900 outline-none transition-all">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wide mb-1" x-text="t('products','field_base_note')"></label>
                                    <input type="text" x-model="form.base_note" required :placeholder="t('products','ph_base_note')" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm font-medium text-gray-900 focus:ring-2 focus:ring-gray-900 outline-none transition-all">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="shrink-0 px-6 py-4 flex items-center justify-end gap-3 border-t border-gray-100 bg-white">
                    <button
                        type="button"
                        @click="closeModal()"
                        :disabled="saving"
                        class="px-5 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50 border border-gray-200 rounded-xl shadow-sm transition-colors disabled:opacity-50 disabled:pointer-events-none"
                        x-text="common().cancel"
                    ></button>
                    <button
                        type="submit"
                        :disabled="saving"
                        class="inline-flex items-center justify-center gap-2 min-w-[9.5rem] px-5 py-2.5 text-sm font-semibold text-white bg-gray-900 hover:bg-gray-800 rounded-xl transition-all shadow-sm disabled:opacity-80 disabled:cursor-wait"
                    >
                        <template x-if="saving">
                            <span class="inline-flex items-center gap-2">
                                <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                                </svg>
                                <span x-text="uploadProgress + '%'"></span>
                            </span>
                        </template>
                        <template x-if="!saving">
                            <span x-text="modalMode === 'add' ? t('products','save_product') : common().save_changes"></span>
                        </template>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
</div>
@endsection
