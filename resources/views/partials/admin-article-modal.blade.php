{{-- Article create/edit modal — Next.js AdminModal + articles form parity --}}
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
        class="admin-modal-panel max-w-3xl relative z-10"
        role="document"
        @click.stop
        x-show="modalOpen"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-[0.94] translate-y-4"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
        x-transition:leave-end="opacity-0 scale-[0.96] translate-y-3"
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

        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 shrink-0 bg-white">
            <h3
                class="font-bold text-lg text-gray-900"
                x-text="modalMode === 'add' ? t('articles','add') : t('articles','edit')"
            ></h3>
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

        <form @submit.prevent="save" enctype="multipart/form-data" class="flex flex-col flex-1 min-h-0">
            <div class="flex-1 min-h-0 overflow-y-auto p-6 space-y-4">
                <div class="grid md:grid-cols-2 gap-3">
                    <label class="block text-sm">
                        <span class="mb-1 block text-gray-600" x-text="t('articles','title_id')"></span>
                        <input
                            required
                            x-model="form.title"
                            class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-gray-900 focus:border-gray-400 focus:ring-2 focus:ring-gray-900/10 outline-none"
                        >
                    </label>
                    <label class="block text-sm">
                        <span class="mb-1 block text-gray-600" x-text="t('articles','title_en')"></span>
                        <input
                            x-model="form.title_en"
                            class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-gray-900 focus:border-gray-400 focus:ring-2 focus:ring-gray-900/10 outline-none"
                        >
                    </label>
                </div>

                @include('partials.admin-article-typography', ['prefix' => 'title', 'withLevel' => true])

                <div class="grid md:grid-cols-3 gap-3">
                    <label class="block text-sm">
                        <span class="mb-1 block text-gray-600" x-text="t('articles','slug')"></span>
                        <input
                            x-model="form.slug"
                            :placeholder="t('articles','slug_ph')"
                            class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-gray-900 placeholder:text-gray-400 focus:border-gray-400 focus:ring-2 focus:ring-gray-900/10 outline-none"
                        >
                    </label>
                    <label class="block text-sm">
                        <span class="mb-1 block text-gray-600" x-text="t('articles','category')"></span>
                        <input
                            x-model="form.category"
                            class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-gray-900 focus:border-gray-400 focus:ring-2 focus:ring-gray-900/10 outline-none"
                        >
                    </label>
                    <label class="block text-sm">
                        <span class="mb-1 block text-gray-600" x-text="t('articles','author')"></span>
                        <input
                            x-model="form.author"
                            class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-gray-900 focus:border-gray-400 focus:ring-2 focus:ring-gray-900/10 outline-none"
                        >
                    </label>
                </div>

                <label class="block text-sm">
                    <span class="mb-1 block text-gray-600" x-text="t('articles','excerpt_id')"></span>
                    <textarea
                        rows="2"
                        x-model="form.excerpt"
                        class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-gray-900 resize-y focus:border-gray-400 focus:ring-2 focus:ring-gray-900/10 outline-none"
                    ></textarea>
                </label>
                <label class="block text-sm">
                    <span class="mb-1 block text-gray-600" x-text="t('articles','excerpt_en')"></span>
                    <textarea
                        rows="2"
                        x-model="form.excerpt_en"
                        class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-gray-900 resize-y focus:border-gray-400 focus:ring-2 focus:ring-gray-900/10 outline-none"
                    ></textarea>
                </label>

                @include('partials.admin-article-typography', ['prefix' => 'excerpt', 'withLevel' => true, 'levelOptions' => 'blockLevelOptions', 'levelKind' => 'block_level', 'levelHint' => 'block_level_hint'])

                <label class="block text-sm">
                    <span class="mb-1 block text-gray-600" x-text="t('articles','content_id')"></span>
                    <textarea
                        required
                        rows="6"
                        x-model="form.content"
                        class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-gray-900 resize-y focus:border-gray-400 focus:ring-2 focus:ring-gray-900/10 outline-none"
                    ></textarea>
                </label>
                <label class="block text-sm">
                    <span class="mb-1 block text-gray-600" x-text="t('articles','content_en')"></span>
                    <textarea
                        rows="5"
                        x-model="form.content_en"
                        class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-gray-900 resize-y focus:border-gray-400 focus:ring-2 focus:ring-gray-900/10 outline-none"
                    ></textarea>
                </label>

                @include('partials.admin-article-typography', ['prefix' => 'content', 'withLevel' => true, 'levelOptions' => 'blockLevelOptions', 'levelKind' => 'block_level', 'levelHint' => 'block_level_hint'])

                {{-- Heading typography used by "#"-prefixed lines inside the content --}}
                <div class="rounded-2xl border border-gray-200 bg-white p-3 space-y-3">
                    <div>
                        <p class="text-sm font-bold text-gray-900" x-text="t('articles','typography_headings')"></p>
                        <p class="mt-1 text-[11px] leading-relaxed text-gray-500" x-text="t('articles','heading_hint')"></p>
                    </div>
                    @foreach (['h1', 'h2', 'h3', 'h4', 'h5', 'h6'] as $level)
                        @include('partials.admin-article-typography', ['prefix' => $level])
                    @endforeach
                </div>

                <div class="grid md:grid-cols-2 gap-3">
                    <label class="block text-sm">
                        <span class="mb-1 block text-gray-600" x-text="t('articles','published_at')"></span>
                        <input
                            type="date"
                            x-model="form.published_at"
                            class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-gray-900 focus:border-gray-400 focus:ring-2 focus:ring-gray-900/10 outline-none"
                        >
                    </label>
                    <label class="flex items-center gap-2 text-sm mt-7">
                        <input
                            type="checkbox"
                            x-model="form.is_published"
                            true-value="1"
                            false-value="0"
                            class="rounded border-gray-300 text-gray-900 focus:ring-gray-900"
                        >
                        <span class="text-gray-700" x-text="t('articles','publish_flag')"></span>
                    </label>
                </div>

                <div class="space-y-2">
                    <p class="text-sm text-gray-600" x-text="t('articles','image')"></p>
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 rounded-xl border border-gray-200 bg-gray-50/60 p-3">
                        <div class="h-44 sm:h-40 sm:w-56 w-full rounded-lg bg-white border border-gray-200 overflow-hidden flex items-center justify-center shrink-0 p-2">
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
                        <div class="flex-1 min-w-0 space-y-2">
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
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-2 px-6 py-4 border-t border-gray-100 bg-white shrink-0">
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