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

                    {{-- SEO & meta --}}
                    <section class="doc-side-card">
                        <button type="button" class="doc-side-toggle" @click="seoOpen = !seoOpen">
                            <span x-text="t('articles','side_seo')"></span>
                            <svg class="h-4 w-4 transition-transform" :class="seoOpen ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                        </button>

                        {{-- How the entry would read on Google, always visible. --}}
                        <div class="rounded-xl border border-gray-200 bg-gray-50/60 p-3">
                            <p class="truncate text-[11px] text-emerald-700" x-text="seoPreviewUrl()"></p>
                            <p class="mt-1 truncate text-[15px] leading-snug text-[#1a0dab]" x-text="seoEffective('meta_title') || t('articles','doc_untitled')"></p>
                            <p class="mt-1 text-[12px] leading-relaxed text-gray-600 line-clamp-2" x-text="seoEffective('meta_description') || t('articles','seo_hint')"></p>
                        </div>

                        <div x-show="seoOpen" x-cloak class="flex flex-col gap-2.5">
                            <p class="doc-side-hint" x-text="t('articles','seo_hint')"></p>

                            <label class="doc-field-row">
                                <span class="flex items-center justify-between gap-2">
                                    <span x-text="t('articles','meta_title')"></span>
                                    <span class="text-[11px] font-semibold tabular-nums" :class="seoStateClass('meta_title')">
                                        <span x-text="seoLength('meta_title') + '/' + seoMax('meta_title')"></span>
                                        <span class="font-normal" x-text="'· ' + seoStateLabel('meta_title')"></span>
                                    </span>
                                </span>
                                <input x-model="form.meta_title" :placeholder="t('articles','meta_title_ph')" maxlength="255" class="doc-input">
                            </label>

                            <label class="doc-field-row">
                                <span class="flex items-center justify-between gap-2">
                                    <span x-text="t('articles','meta_description')"></span>
                                    <span class="text-[11px] font-semibold tabular-nums" :class="seoStateClass('meta_description')">
                                        <span x-text="seoLength('meta_description') + '/' + seoMax('meta_description')"></span>
                                        <span class="font-normal" x-text="'· ' + seoStateLabel('meta_description')"></span>
                                    </span>
                                </span>
                                <textarea
                                    x-model="form.meta_description"
                                    :placeholder="t('articles','meta_description_ph')"
                                    maxlength="500"
                                    rows="3"
                                    class="doc-input h-auto py-2 leading-relaxed resize-y"
                                ></textarea>
                            </label>

                            <label class="doc-field-row">
                                <span x-text="t('articles','meta_keywords')"></span>
                                <input x-model="form.meta_keywords" :placeholder="t('articles','meta_keywords_ph')" maxlength="255" class="doc-input">
                                <span class="doc-side-hint mt-1 block" x-text="t('articles','meta_keywords_hint')"></span>
                            </label>

                            <label class="doc-field-row">
                                <span x-text="t('articles','canonical_url')"></span>
                                <input x-model="form.canonical_url" type="url" :placeholder="t('articles','canonical_url_ph')" maxlength="255" class="doc-input">
                            </label>

                            <label class="doc-switch">
                                <input type="checkbox" x-model="form.noindex" true-value="1" false-value="0" class="sr-only peer">
                                <span class="doc-switch-track"><span class="doc-switch-thumb"></span></span>
                                <span class="doc-switch-label" x-text="t('articles','noindex')"></span>
                            </label>
                            <p class="doc-side-hint" x-text="t('articles','noindex_hint')"></p>

                            <div class="border-t border-gray-100 pt-2.5">
                                <p class="doc-side-hint mb-2" x-text="t('articles','faq_translations')"></p>
                                <label class="doc-field-row">
                                    <span x-text="t('articles','meta_title_en')"></span>
                                    <input x-model="form.meta_title_en" maxlength="255" class="doc-input">
                                </label>
                                <label class="doc-field-row mt-2">
                                    <span x-text="t('articles','meta_description_en')"></span>
                                    <textarea x-model="form.meta_description_en" maxlength="500" rows="2" class="doc-input h-auto py-2 leading-relaxed resize-y"></textarea>
                                </label>
                            </div>
                        </div>
                    </section>

                    {{-- Schema markup --}}
                    <section class="doc-side-card">
                        <p class="doc-side-title" x-text="t('articles','side_schema')"></p>

                        <label class="doc-field-row">
                            <span x-text="t('articles','schema_type')"></span>
                            <select x-model="form.schema_type" class="doc-input">
                                <template x-for="type in schemaTypeOptions" :key="type">
                                    <option :value="type" x-text="type"></option>
                                </template>
                            </select>
                        </label>

                        <label class="doc-field-row">
                            <span class="flex items-center justify-between gap-2">
                                <span x-text="t('articles','schema_json')"></span>
                                <button
                                    type="button"
                                    x-show="schemaJsonState() === 'valid'"
                                    @click="formatSchemaJson()"
                                    class="text-[11px] font-semibold text-gray-500 underline hover:text-gray-800"
                                >&#123;&nbsp;&#125;</button>
                            </span>
                            <textarea
                                x-model="form.schema_json"
                                :placeholder="t('articles','schema_json_ph')"
                                rows="5"
                                spellcheck="false"
                                class="doc-input h-auto py-2 font-mono text-[11px] leading-relaxed resize-y"
                                :class="schemaJsonState() === 'invalid' ? 'border-rose-400' : ''"
                            ></textarea>
                        </label>

                        <p
                            class="text-[11px] leading-relaxed"
                            :class="{
                                'text-rose-600': schemaJsonState() === 'invalid',
                                'text-emerald-600': schemaJsonState() === 'valid',
                                'text-gray-400': schemaJsonState() === 'empty',
                            }"
                            x-text="schemaJsonState() === 'invalid'
                                ? t('articles','schema_json_invalid')
                                : (schemaJsonState() === 'valid' ? t('articles','schema_json_valid') : t('articles','schema_json_hint'))"
                        ></p>
                    </section>

                    {{-- FAQ --}}
                    <section class="doc-side-card">
                        <button type="button" class="doc-side-toggle" @click="faqOpen = !faqOpen">
                            <span>
                                <span x-text="t('articles','side_faq')"></span>
                                <span
                                    x-show="faqReadyCount() > 0"
                                    class="ml-1.5 rounded-full bg-emerald-50 px-1.5 py-0.5 text-[10px] font-bold text-emerald-700"
                                    x-text="faqReadyCount()"
                                ></span>
                            </span>
                            <svg class="h-4 w-4 transition-transform" :class="faqOpen ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                        </button>

                        <div x-show="faqOpen" x-cloak class="flex flex-col gap-2.5">
                            <p class="doc-side-hint" x-text="t('articles','faq_hint')"></p>

                            <p
                                x-show="faqRows().length === 0"
                                class="rounded-xl border border-dashed border-gray-200 px-3 py-4 text-center text-[11px] text-gray-400"
                                x-text="t('articles','faq_empty')"
                            ></p>

                            <template x-for="(faq, index) in faqRows()" :key="index">
                                <div class="rounded-xl border border-gray-200 bg-gray-50/60 p-2.5">
                                    <div class="mb-1.5 flex items-center justify-between gap-1">
                                        <span class="text-[11px] font-bold text-gray-400" x-text="'#' + (index + 1)"></span>
                                        <div class="flex items-center gap-0.5">
                                            <button
                                                type="button"
                                                @click="moveFaq(index, -1)"
                                                :disabled="index === 0"
                                                class="rounded-md p-1 text-gray-400 hover:bg-white hover:text-gray-700 disabled:opacity-30 disabled:pointer-events-none"
                                                aria-label="Move up"
                                            >
                                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="m18 15-6-6-6 6"/></svg>
                                            </button>
                                            <button
                                                type="button"
                                                @click="moveFaq(index, 1)"
                                                :disabled="index === faqRows().length - 1"
                                                class="rounded-md p-1 text-gray-400 hover:bg-white hover:text-gray-700 disabled:opacity-30 disabled:pointer-events-none"
                                                aria-label="Move down"
                                            >
                                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                                            </button>
                                            <button
                                                type="button"
                                                @click="removeFaq(index)"
                                                class="rounded-md p-1 text-gray-400 hover:bg-white hover:text-rose-600"
                                                :aria-label="t('articles','faq_remove')"
                                            >
                                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                                            </button>
                                        </div>
                                    </div>

                                    <input
                                        x-model="faq.question"
                                        :placeholder="t('articles','faq_question_ph')"
                                        maxlength="300"
                                        class="doc-input mb-1.5"
                                    >
                                    <textarea
                                        x-model="faq.answer"
                                        :placeholder="t('articles','faq_answer_ph')"
                                        maxlength="1200"
                                        rows="3"
                                        class="doc-input h-auto py-2 leading-relaxed resize-y"
                                    ></textarea>

                                    <button
                                        type="button"
                                        @click="toggleFaqTranslation(index)"
                                        class="mt-1.5 text-[11px] font-semibold text-gray-500 underline hover:text-gray-800"
                                        x-text="t('articles','faq_translations')"
                                    ></button>

                                    <div x-show="faqTranslationOpen === index" x-cloak class="mt-1.5 flex flex-col gap-1.5">
                                        <input
                                            x-model="faq.question_en"
                                            :placeholder="t('articles','faq_question_en')"
                                            maxlength="300"
                                            class="doc-input"
                                        >
                                        <textarea
                                            x-model="faq.answer_en"
                                            :placeholder="t('articles','faq_answer_en')"
                                            maxlength="1200"
                                            rows="2"
                                            class="doc-input h-auto py-2 leading-relaxed resize-y"
                                        ></textarea>
                                    </div>
                                </div>
                            </template>

                            <button
                                type="button"
                                @click="addFaq()"
                                class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-dashed border-gray-300 px-3 py-2 text-xs font-semibold text-gray-600 transition hover:border-gray-900 hover:text-gray-900"
                            >
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
                                <span x-text="t('articles','faq_add')"></span>
                            </button>
                        </div>
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
