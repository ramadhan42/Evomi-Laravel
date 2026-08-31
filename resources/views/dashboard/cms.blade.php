@extends('layouts.admin')

@section('title', 'CMS | Evomi Admin')

@section('content')
<div x-data="evomiAdminCms" class="space-y-6 text-gray-900 pb-24 md:pb-28" x-on:keydown.escape.window="closeFontOpen()">
    <div>
        <h1 class="text-2xl md:text-3xl font-extrabold text-gray-900 tracking-tight">CMS</h1>
        <p class="text-gray-500 mt-1 text-sm">Kelola konten website, FAQ, dan hasil kuis.</p>
    </div>

    <div class="flex gap-2 overflow-x-auto pb-1">
        <template x-for="t in tabs" :key="t.key">
            <button
                type="button"
                x-on:click="setTab(t.key)"
                class="shrink-0 px-4 py-2 rounded-xl text-sm font-semibold transition-colors"
                :class="tab === t.key ? 'bg-gray-900 text-white' : 'bg-white text-gray-600 border border-gray-100 hover:bg-gray-50'"
                x-text="t.label"
            ></button>
        </template>
    </div>

    <div x-show="loading" class="min-h-[60vh] flex items-center justify-center">
        <div class="w-8 h-8 border-4 border-gray-200 border-t-gray-900 rounded-full animate-spin"></div>
    </div>

    {{-- Konten halaman (beranda, belanja, nav/footer, tipografi FAQ, dll) --}}
    <div x-show="!loading && tab !== 'kuis_hasil'" class="space-y-6">
        <template x-for="sec in sectionsForActiveTab()" :key="sec.name">
            <div class="bg-white rounded-2xl border border-gray-100 shadow-[0_2px_20px_rgb(0,0,0,0.04)] p-5 md:p-6 space-y-4">
                <div class="flex items-center justify-between gap-3">
                    <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wide" x-text="sec.label"></h3>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <template x-for="field in sec.items" :key="sec.name + '-' + field.key">
                        <div
                            class="space-y-1.5"
                            :class="(kind(field) === 'image' || kind(field) === 'rich' || kind(field) === 'text' || kind(field) === 'copy') ? 'md:col-span-2' : ''"
                        >
                            <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider" x-text="fieldLabel(field.key)"></label>

                            {{-- IMAGE --}}
                            <template x-if="kind(field) === 'image'">
                                <div class="flex items-center gap-3 rounded-xl border border-gray-200 bg-gray-50/60 p-3">
                                    <div
                                        class="rounded-lg border border-gray-200 overflow-hidden flex items-center justify-center shrink-0"
                                        :class="isWaveIcon(field.key) ? 'h-24 w-36 bg-[#0071BC] p-2' : 'h-16 w-16 bg-white'"
                                    >
                                        <img
                                            x-show="imgUrl(field.value)"
                                            :src="imgUrl(field.value)"
                                            :alt="field.key"
                                            loading="lazy"
                                            class="h-full w-full object-contain p-1"
                                            x-on:error="$el.style.opacity=0.3"
                                        >
                                        <svg x-show="!imgUrl(field.value)" class="h-7 w-7 text-gray-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>
                                    </div>
                                    <div class="flex-1 min-w-0 space-y-1">
                                        <input
                                            type="file"
                                            accept="image/*,.svg,image/svg+xml"
                                            class="w-full text-xs text-gray-600 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-gray-900 file:text-white"
                                            x-on:change="uploadImage(field, $event)"
                                        >
                                        <div class="flex items-center gap-2">
                                            <input
                                                x-model="field.value"
                                                class="flex-1 h-9 px-3 rounded-lg border border-gray-200 text-xs text-gray-900 font-mono"
                                                placeholder="path gambar"
                                            >
                                            <button
                                                type="button"
                                                x-show="field.value"
                                                class="text-[11px] font-semibold text-gray-500 hover:text-gray-800 underline shrink-0"
                                                x-on:click="clearImage(field)"
                                            >Hapus</button>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            {{-- COLOR --}}
                            <template x-if="kind(field) === 'color'">
                                <div class="flex gap-2">
                                    <input type="color" x-model="field.value" class="h-11 w-14 rounded-xl border border-gray-200 bg-white shrink-0">
                                    <input
                                        x-model="field.value"
                                        class="flex-1 h-11 px-3 rounded-xl border border-gray-200 text-sm text-gray-900 font-mono uppercase outline-none focus:ring-2 focus:ring-gray-900/15"
                                    >
                                </div>
                            </template>

                            {{-- FONT FAMILY / WEIGHT / STYLE (preview like Next AdminSelect) --}}
                            <template x-if="kind(field) === 'font_family' || kind(field) === 'font_weight' || kind(field) === 'font_style'">
                                <div class="relative" x-on:click.outside="isFontOpen(field) && closeFontOpen()">
                                    <button
                                        type="button"
                                        class="w-full h-11 px-3 rounded-xl border border-gray-200 bg-white text-sm text-gray-900 flex items-center justify-between gap-2 hover:border-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-900/15"
                                        x-on:click="toggleFontOpen(field)"
                                    >
                                        <span class="truncate text-left" :style="fontTriggerStyle(field)" x-text="selectedFontLabel(field)"></span>
                                        <svg class="w-4 h-4 text-gray-400 shrink-0 transition-transform" :class="isFontOpen(field) && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                                    </button>
                                    <div
                                        x-show="isFontOpen(field)"
                                        x-cloak
                                        x-transition.opacity.duration.100ms
                                        class="absolute z-50 mt-1.5 w-full max-h-64 overflow-y-auto rounded-xl border border-gray-200 bg-white shadow-lg py-1"
                                    >
                                        <template x-for="group in fontGroupsFor(field)" :key="(group.key || 'all') + '-' + field.key">
                                            <div>
                                                <p
                                                    x-show="group.label"
                                                    class="px-3 pt-2 pb-1 text-[10px] font-bold uppercase tracking-wider text-gray-400"
                                                    x-text="group.label"
                                                ></p>
                                                <template x-for="opt in group.options" :key="opt.value">
                                                    <button
                                                        type="button"
                                                        class="w-full text-left px-3 py-2.5 text-sm text-gray-900 hover:bg-gray-50 flex items-center justify-between gap-2"
                                                        :class="String(field.value) === String(opt.value) && 'bg-gray-50'"
                                                        :style="fontOptionStyle(field, opt)"
                                                        x-on:click="pickFont(field, opt.value)"
                                                    >
                                                        <span x-text="opt.label"></span>
                                                        <svg x-show="String(field.value) === String(opt.value)" class="w-4 h-4 text-gray-900 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                                    </button>
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>

                            {{-- NUMBER px / % / deg --}}
                            <template x-if="kind(field) === 'number'">
                                <div class="flex h-11 overflow-hidden rounded-xl border border-gray-200 bg-white focus-within:border-gray-900 focus-within:ring-2 focus-within:ring-gray-900/15">
                                    <input
                                        type="text"
                                        inputmode="decimal"
                                        class="min-w-0 flex-1 bg-transparent px-3.5 text-sm font-semibold tabular-nums text-gray-900 outline-none"
                                        :value="numDisplay(field)"
                                        x-on:input="setNumeric(field, $event.target.value)"
                                        placeholder="0"
                                    >
                                    <span
                                        x-show="unitLabel(field)"
                                        class="flex shrink-0 items-center border-l border-gray-100 bg-gray-50/70 px-2.5 text-xs font-bold uppercase tracking-wide text-gray-400 select-none"
                                        x-text="unitLabel(field)"
                                    ></span>
                                    <div class="flex w-9 shrink-0 flex-col border-l border-gray-200 bg-gray-50/80">
                                        <button type="button" tabindex="-1" class="flex flex-1 items-center justify-center text-gray-500 hover:bg-gray-100 hover:text-gray-900" x-on:mousedown.prevent="bumpNumeric(field, 1)">▲</button>
                                        <div class="h-px bg-gray-200"></div>
                                        <button type="button" tabindex="-1" class="flex flex-1 items-center justify-center text-gray-500 hover:bg-gray-100 hover:text-gray-900" x-on:mousedown.prevent="bumpNumeric(field, -1)">▼</button>
                                    </div>
                                </div>
                            </template>

                            {{-- Teks berformat: editor seperti di menu artikel --}}
                            <template x-if="kind(field) === 'rich'">
                                <div class="space-y-1.5">
                                    @include('partials.admin-cms-editor')
                                    <p class="text-[10px] text-gray-400">Enter = baris baru · maks <span x-text="maxLinesFor(field)"></span> baris (ubah di setting Max Baris)</p>
                                </div>
                            </template>

                            {{-- COPY / MULTILINE biasa (nilainya dicetak polos di halaman) --}}
                            <template x-if="kind(field) === 'copy' || kind(field) === 'text'">
                                <div class="space-y-1.5">
                                    <textarea
                                        :value="field.value"
                                        :rows="Math.max(1, maxLinesFor(field))"
                                        x-on:input="onCopyInput(field, $event)"
                                        x-on:keydown="onCopyKeydown(field, $event)"
                                        class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm text-gray-900 resize-y outline-none focus:ring-2 focus:ring-gray-900/15 whitespace-pre-wrap leading-relaxed"
                                        :placeholder="'Maks ' + maxLinesFor(field) + ' baris — tekan Enter untuk baris baru'"
                                    ></textarea>
                                    <p class="text-[10px] text-gray-400">Enter = baris baru · maks <span x-text="maxLinesFor(field)"></span> baris</p>
                                </div>
                            </template>

                            {{-- STRING (non-typography) --}}
                            <template x-if="kind(field) === 'string'">
                                <input
                                    x-model="field.value"
                                    class="w-full h-11 px-3 rounded-xl border border-gray-200 text-sm text-gray-900 outline-none focus:ring-2 focus:ring-gray-900/15"
                                >
                            </template>
                        </div>
                    </template>
                </div>
            </div>
        </template>
        <p x-show="tab !== 'faq' && !sectionsForActiveTab().length" class="text-sm text-gray-500">Belum ada field CMS untuk halaman ini.</p>
    </div>

    {{-- FAQ daftar item --}}
    <div x-show="!loading && tab === 'faq'" class="space-y-4">
        <div class="flex justify-end">
            <button type="button" x-on:click="openFaqAdd()" class="px-4 py-2 rounded-xl bg-gray-900 text-white text-sm font-semibold">+ Tambah FAQ</button>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
            <table class="w-full text-sm text-gray-900">
                <thead class="bg-gray-50 text-gray-500 text-left">
                    <tr>
                        <th class="px-4 py-3 font-medium">Kategori</th>
                        <th class="px-4 py-3 font-medium">Pertanyaan</th>
                        <th class="px-4 py-3 font-medium">Aktif</th>
                        <th class="px-4 py-3 font-medium text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="f in faqs" :key="f.id">
                        <tr class="border-t border-gray-50">
                            <td class="px-4 py-3" x-text="f.category"></td>
                            <td class="px-4 py-3" x-text="f.question"></td>
                            <td class="px-4 py-3" x-text="f.is_active ? 'Ya' : 'Tidak'"></td>
                            <td class="px-4 py-3 text-right space-x-2">
                                <button type="button" class="text-gray-900 font-semibold" x-on:click="openFaqEdit(f)">Edit</button>
                                <button type="button" class="text-rose-600 font-semibold" x-on:click="removeFaq(f.id)">Hapus</button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Quiz results --}}
    <div x-show="!loading && tab === 'kuis_hasil'" class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <template x-for="r in results" :key="r.personality_key || r.key || r.id">
            <div class="bg-white rounded-2xl border border-gray-100 p-5 space-y-3 text-gray-900">
                <h3 class="font-bold" x-text="r.personality_key || r.key || r.title"></h3>
                <input x-model="r.title" placeholder="Judul" class="w-full h-10 px-3 rounded-xl border border-gray-200 text-sm text-gray-900">
                <textarea x-model="r.description" rows="2" placeholder="Deskripsi" class="w-full p-3 rounded-xl border border-gray-200 text-sm text-gray-900"></textarea>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2 pt-1">
                    <template x-for="field in resultTypographyFields(r.personality_key || r.key)" :key="(r.personality_key || r.key) + '-' + field.key">
                        <div class="space-y-1.5">
                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider" x-text="fieldLabel(field.key)"></label>
                            <template x-if="kind(field) === 'number'">
                                <div class="flex h-10 overflow-hidden rounded-xl border border-gray-200 bg-white">
                                    <button type="button" class="w-9 shrink-0 text-gray-500 hover:bg-gray-50 text-lg leading-none" x-on:click="bumpNumeric(field, -1)">−</button>
                                    <input
                                        type="text"
                                        inputmode="decimal"
                                        class="min-w-0 flex-1 border-x border-gray-200 px-2 text-center text-xs text-gray-900 outline-none"
                                        :value="numDisplay(field)"
                                        x-on:input="setNumeric(field, $event.target.value)"
                                    >
                                    <span class="flex w-8 items-center justify-center text-[10px] font-semibold text-gray-400" x-text="unitLabel(field)"></span>
                                    <button type="button" class="w-9 shrink-0 text-gray-500 hover:bg-gray-50 text-lg leading-none" x-on:click="bumpNumeric(field, 1)">+</button>
                                </div>
                            </template>
                            <template x-if="kind(field) === 'font_family' || kind(field) === 'font_weight' || kind(field) === 'font_style'">
                                <div class="relative" x-on:click.outside="isFontOpen(field) && closeFontOpen()">
                                    <button
                                        type="button"
                                        class="w-full h-10 px-2.5 rounded-xl border border-gray-200 bg-white text-xs text-gray-900 flex items-center justify-between gap-1 hover:border-gray-300"
                                        x-on:click="toggleFontOpen(field)"
                                    >
                                        <span class="truncate text-left" :style="fontTriggerStyle(field)" x-text="selectedFontLabel(field)"></span>
                                        <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                                    </button>
                                    <div
                                        x-show="isFontOpen(field)"
                                        x-cloak
                                        class="absolute z-50 mt-1 w-full max-h-56 overflow-y-auto rounded-xl border border-gray-200 bg-white shadow-lg py-1"
                                    >
                                        <template x-for="group in fontGroupsFor(field)" :key="(group.key || 'all') + '-' + field.key">
                                            <div>
                                                <p x-show="group.label" class="px-3 pt-2 pb-1 text-[10px] font-bold uppercase tracking-wider text-gray-400" x-text="group.label"></p>
                                                <template x-for="opt in group.options" :key="opt.value">
                                                    <button
                                                        type="button"
                                                        class="w-full text-left px-3 py-2 text-xs text-gray-900 hover:bg-gray-50"
                                                        :style="fontOptionStyle(field, opt)"
                                                        x-on:click="pickFont(field, opt.value)"
                                                    >
                                                        <span x-text="opt.label"></span>
                                                    </button>
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>

                <input type="color" x-model="r.color" class="h-10 w-full rounded-xl border border-gray-200">
                <div class="flex gap-3 items-center">
                    <div class="h-16 w-16 rounded-lg border border-gray-200 bg-gray-50 overflow-hidden flex items-center justify-center shrink-0">
                        <img x-show="imgUrl(r.bg_image)" :src="imgUrl(r.bg_image)" class="h-full w-full object-cover" alt="" x-on:error="$el.style.display='none'">
                        <svg x-show="!imgUrl(r.bg_image)" class="h-7 w-7 text-gray-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>
                    </div>
                    <label class="text-sm font-medium text-gray-600">
                        BG Image
                        <input type="file" accept="image/*" class="block mt-1 text-xs" x-on:change="uploadResultImage(r, 'bg_image', $event)">
                    </label>
                </div>
                <div class="flex gap-3 items-center">
                    <div class="h-16 w-16 rounded-lg border border-gray-200 bg-gray-50 overflow-hidden flex items-center justify-center shrink-0">
                        <img x-show="imgUrl(r.product_image)" :src="imgUrl(r.product_image)" class="h-full w-full object-contain p-1.5" alt="" x-on:error="$el.style.display='none'">
                        <svg x-show="!imgUrl(r.product_image)" class="h-7 w-7 text-gray-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>
                    </div>
                    <label class="text-sm font-medium text-gray-600">
                        Product Image
                        <input type="file" accept="image/*" class="block mt-1 text-xs" x-on:change="uploadResultImage(r, 'product_image', $event)">
                    </label>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <label class="text-[11px] font-bold text-gray-400 uppercase tracking-wider space-y-1">
                        <span>Lebar BG Mobile (px)</span>
                        <input type="number" x-model="r.bg_image_width_mobile" class="w-full h-10 px-3 rounded-xl border border-gray-200 text-sm text-gray-900">
                    </label>
                    <label class="text-[11px] font-bold text-gray-400 uppercase tracking-wider space-y-1">
                        <span>Lebar BG Desktop (px)</span>
                        <input type="number" x-model="r.bg_image_width_desktop" class="w-full h-10 px-3 rounded-xl border border-gray-200 text-sm text-gray-900">
                    </label>
                    <label class="text-[11px] font-bold text-gray-400 uppercase tracking-wider space-y-1">
                        <span>Lebar Produk Mobile (px)</span>
                        <input type="number" x-model="r.product_image_width_mobile" class="w-full h-10 px-3 rounded-xl border border-gray-200 text-sm text-gray-900">
                    </label>
                    <label class="text-[11px] font-bold text-gray-400 uppercase tracking-wider space-y-1">
                        <span>Lebar Produk Desktop (px)</span>
                        <input type="number" x-model="r.product_image_width_desktop" class="w-full h-10 px-3 rounded-xl border border-gray-200 text-sm text-gray-900">
                    </label>
                </div>
            </div>
        </template>
    </div>

    <template x-teleport="body">
    <div x-show="faqModal" x-cloak class="admin-modal-root" role="dialog" aria-modal="true">
        <div class="bg-white rounded-2xl w-full max-w-lg p-6 space-y-3 text-gray-900" x-on:click.stop>
            <h2 class="text-xl font-bold" x-text="faqMode === 'add' ? 'Tambah FAQ' : 'Edit FAQ'"></h2>
            <input x-model="faqForm.category" placeholder="Kategori" class="w-full h-11 px-4 rounded-xl border border-gray-200 text-sm">
            <input x-model="faqForm.question" placeholder="Pertanyaan" class="w-full h-11 px-4 rounded-xl border border-gray-200 text-sm">
            <input x-model="faqForm.question_en" placeholder="Pertanyaan EN" class="w-full h-11 px-4 rounded-xl border border-gray-200 text-sm">
            <textarea x-model="faqForm.answer" rows="3" placeholder="Jawaban" class="w-full p-4 rounded-xl border border-gray-200 text-sm"></textarea>
            <textarea x-model="faqForm.answer_en" rows="2" placeholder="Jawaban EN" class="w-full p-4 rounded-xl border border-gray-200 text-sm"></textarea>
            <input type="number" x-model="faqForm.sort_order" placeholder="Urutan" class="w-full h-11 px-4 rounded-xl border border-gray-200 text-sm">
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" x-model="faqForm.is_active"> Aktif</label>
            <div class="flex justify-end gap-3">
                <button type="button" class="px-4 py-2 rounded-xl text-sm text-gray-600" x-on:click="faqModal=false">Batal</button>
                <button type="button" class="px-5 py-2.5 rounded-xl bg-gray-900 text-white text-sm font-semibold" x-on:click="saveFaq()">Simpan</button>
            </div>
        </div>
    </div>
    </template>

    {{-- Floating Simpan (teleport ke body agar selalu bisa diklik) --}}
    <template x-teleport="body">
        <div
            x-show="!loading && !faqModal"
            x-cloak
            class="cms-float-save fixed z-[160]"
            style="bottom: 1.5rem; right: 1.5rem;"
        >
            <button
                type="button"
                :disabled="saving"
                x-on:click.prevent="savePage()"
                class="cms-float-save-btn group relative inline-flex h-12 items-center gap-2.5 rounded-full bg-[#111827] pl-4 pr-5 text-sm font-semibold text-white shadow-[0_10px_40px_rgba(17,24,39,0.35)] ring-1 ring-white/10 transition hover:bg-black hover:shadow-[0_14px_44px_rgba(17,24,39,0.45)] active:scale-[0.97] disabled:cursor-wait disabled:opacity-60 md:h-14 md:pl-5 md:pr-6 md:text-[15px]"
            >
                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-white/10 transition group-hover:bg-white/15">
                    <svg x-show="!saving" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 3H7a2 2 0 0 0-2 2v14l7-3 7 3V5a2 2 0 0 0-2-2Z"/>
                    </svg>
                    <svg x-show="saving" x-cloak class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"></circle>
                        <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8v3a5 5 0 00-5 5H4z"></path>
                    </svg>
                </span>
                <span x-text="saving ? 'Menyimpan…' : 'Simpan'"></span>
            </button>
        </div>
    </template>
</div>
@endsection
