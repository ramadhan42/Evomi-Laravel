{{-- Article editor modal: Docs-style document on the left, settings on the right --}}
<template x-teleport="body">
<div
    x-show="modalOpen"
    x-cloak
    class="admin-modal-root"
    role="dialog"
    aria-modal="true"
    @keydown.escape.window="closeArticleModal()"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
>
    <div
        class="absolute inset-0"
        @click="closeArticleModal()"
        aria-hidden="true"
    ></div>

    <div
        class="admin-modal-panel doc-modal-panel relative z-10"
        role="document"
        @click.stop
        x-show="modalOpen"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-[0.97] translate-y-3"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
        x-transition:leave-end="opacity-0 scale-[0.98] translate-y-2"
    >
        {{-- Upload/saving progress --}}
        <div
            x-show="saving"
            x-cloak
            class="absolute inset-0 z-30 bg-white/75 backdrop-blur-[1px] flex items-center justify-center p-6"
        >
            <div class="w-full max-w-sm rounded-2xl border border-gray-200 bg-white p-5 shadow-xl">
                <div class="flex items-center gap-3 mb-3">
                    <svg class="h-5 w-5 animate-spin text-gray-900 shrink-0" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>
                    <div>
                        <p class="text-sm font-bold text-gray-900" x-text="uploadHeadline()"></p>
                        <p class="text-xs text-gray-500 mt-0.5" x-text="t('articles','upload_please_wait')"></p>
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

        <div class="doc-modal-header">
            <div class="min-w-0">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400" x-text="modalMode === 'add' ? t('articles','add') : t('articles','edit')"></p>
                <h3 class="font-bold text-lg text-gray-900 truncate" x-text="form.title || t('articles','doc_untitled')"></h3>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <span
                    class="hidden sm:inline-flex rounded-full px-2.5 py-1 text-[11px] font-semibold"
                    :class="String(form.is_published) === '1' ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-600'"
                    x-text="String(form.is_published) === '1' ? t('articles','published') : t('articles','draft')"
                ></span>
                <button
                    type="button"
                    :disabled="saving"
                    @click="closeArticleModal()"
                    class="p-1.5 rounded-full hover:bg-gray-100 text-gray-400 hover:text-gray-700 disabled:opacity-40 disabled:pointer-events-none transition-colors"
                    aria-label="Close"
                >
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                </button>
            </div>
        </div>

        <form @submit.prevent="save" enctype="multipart/form-data" class="flex flex-col flex-1 min-h-0">
            <div class="doc-modal-body">
                <div class="doc-modal-main">
                    @include('partials.admin-doc-editor')
                </div>

                <aside class="doc-modal-side">
                    {{-- Publikasi --}}
                    <section class="doc-side-card">
                        <p class="doc-side-title" x-text="t('articles','side_publish')"></p>

                        <label class="doc-switch">
                            <input
                                type="checkbox"
                                x-model="form.is_published"
                                true-value="1"
                                false-value="0"
                                class="sr-only peer"
                            >
                            <span class="doc-switch-track"><span class="doc-switch-thumb"></span></span>
                            <span class="doc-switch-label" x-text="String(form.is_published) === '1' ? t('articles','published') : t('articles','draft')"></span>
                        </label>

                        <label class="doc-field-row">
                            <span x-text="t('articles','published_at')"></span>
                            <input type="date" x-model="form.published_at" class="doc-input">
                        </label>
                    </section>

                    {{-- Detail --}}
                    <section class="doc-side-card">
                        <p class="doc-side-title" x-text="t('articles','side_details')"></p>

                        <label class="doc-field-row">
                            <span x-text="t('articles','slug')"></span>
                            <input x-model="form.slug" :placeholder="t('articles','slug_ph')" class="doc-input">
                        </label>
                        <label class="doc-field-row">
                            <span x-text="t('articles','category')"></span>
                            <input x-model="form.category" list="doc-category-list" class="doc-input">
                            <datalist id="doc-category-list">
                                <template x-for="name in categoryOptions()" :key="name">
                                    <option :value="name"></option>
                                </template>
                            </datalist>
                        </label>
                        <label class="doc-field-row">
                            <span x-text="t('articles','author')"></span>
                            <input x-model="form.author" class="doc-input">
                        </label>
                    </section>

                    {{-- Gambar sampul --}}
                    <section class="doc-side-card">
                        <p class="doc-side-title" x-text="t('articles','image')"></p>
                        <div class="rounded-xl border border-gray-200 bg-gray-50/60 p-3 space-y-2">
                            <div class="h-36 w-full rounded-lg bg-white border border-gray-200 overflow-hidden flex items-center justify-center p-2">
                                <img
                                    x-show="imagePreview"
                                    :src="imagePreview"
                                    alt="Preview artikel"
                                    class="max-h-full max-w-full object-contain"
                                    x-on:error="$el.style.display='none'"
                                >
                                <div x-show="!imagePreview" class="flex flex-col items-center gap-1 text-gray-400">
                                    <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>
                                    <span class="text-[11px]" x-text="t('articles','image_empty')"></span>
                                </div>
                            </div>
                            <input
                                type="file"
                                accept="image/jpeg,image/png,image/jpg,image/webp"
                                @change="onImage($event)"
                                class="w-full text-xs text-gray-500 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-gray-900 file:text-white hover:file:bg-gray-800"
                            >
                            <p class="text-[11px] text-gray-400 leading-relaxed" x-text="t('articles','image_hint')"></p>
                            <button
                                type="button"
                                x-show="imagePreview && imageFile"
                                @click="resetImage()"
                                class="text-[11px] font-semibold text-gray-500 hover:text-gray-800 underline"
                                x-text="t('articles','image_reset')"
                            ></button>
                        </div>
                    </section>

                    {{-- Tipografi --}}
                    <section class="doc-side-card" x-data="{ open: false }">
                        <button type="button" class="doc-side-toggle" @click="open = !open">
                            <span x-text="t('articles','typography')"></span>
                            <svg class="w-4 h-4 transition-transform" :class="open && 'rotate-180'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                        </button>
                        <p class="doc-side-hint" x-show="!open" x-cloak x-text="t('articles','typography_hint')"></p>

                        <div class="space-y-3" x-show="open" x-cloak x-transition.opacity.duration.150ms>
                            @include('partials.admin-article-typography', ['prefix' => 'title'])
                            @include('partials.admin-article-typography', ['prefix' => 'excerpt'])
                            @include('partials.admin-article-typography', ['prefix' => 'content'])

                            <div class="rounded-xl border border-gray-200 bg-white p-3 space-y-3">
                                <div>
                                    <p class="text-sm font-bold text-gray-900" x-text="t('articles','typography_headings')"></p>
                                    <p class="mt-1 text-[11px] leading-relaxed text-gray-500" x-text="t('articles','heading_hint')"></p>
                                </div>
                                @foreach (['h1', 'h2', 'h3', 'h4', 'h5', 'h6'] as $level)
                                    @include('partials.admin-article-typography', ['prefix' => $level])
                                @endforeach
                            </div>
                        </div>
                    </section>
                </aside>
            </div>

            <div class="flex justify-end gap-2 px-6 py-3.5 border-t border-gray-100 bg-white shrink-0">
                <button
                    type="button"
                    :disabled="saving"
                    @click="closeArticleModal()"
                    class="px-4 py-2.5 rounded-xl text-sm font-semibold text-gray-600 hover:bg-gray-100 transition disabled:opacity-50 disabled:pointer-events-none"
                    x-text="common().cancel"
                ></button>
                <button
                    type="submit"
                    :disabled="saving"
                    class="inline-flex min-w-[8rem] items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-gray-900 text-white text-sm font-semibold disabled:opacity-60 disabled:cursor-wait"
                >
                    <svg x-show="saving" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>
                    <span x-text="saving ? (imageFile ? uploadProgress + '%' : common().saving) : common().save"></span>
                </button>
            </div>
        </form>
    </div>
</div>
</template>
