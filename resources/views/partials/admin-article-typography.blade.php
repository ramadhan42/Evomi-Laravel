{{-- Typography row — Next.js renderFontRow + AdminSelect parity --}}
@php
    /**
     * Heading level select: `title` may only be h1–h6, while excerpt/content
     * also offer "normal" (rendered as plain text, no heading).
     */
    $levelKey = $levelKey ?? $prefix.'_heading_level';
    $levelOptions = $levelOptions ?? 'headingLevelOptions';
    $levelKind = $levelKind ?? 'level';
    $levelHint = $levelHint ?? 'heading_level_hint';
@endphp
<div class="rounded-xl border border-gray-200 bg-gray-50/70 p-3 space-y-3">
    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">
        <span x-text="t('articles','typography')"></span>
        <span> — </span>
        <span x-text="typographyLabel('{{ $prefix }}')"></span>
    </p>

    {{-- Heading level — picks the HTML tag used when this field is rendered --}}
    @if ($withLevel ?? false)
        <div class="block text-xs space-y-1 min-w-0 sm:max-w-[14rem]">
            <span class="text-gray-500" x-text="t('articles','heading_level')"></span>
            <div class="relative" @click.outside="closeFontSelect('{{ $levelKey }}')">
                <button
                    type="button"
                    class="admin-select-trigger w-full h-10 px-3 rounded-xl border border-gray-200 bg-white text-sm text-gray-900 flex items-center justify-between gap-2 hover:border-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-900/10"
                    @click="toggleFontSelect('{{ $levelKey }}')"
                >
                    <span class="truncate text-left font-semibold" x-text="fontSelectLabel('{{ $levelKey }}', '{{ $levelKind }}')"></span>
                    <svg class="w-4 h-4 text-gray-400 shrink-0 transition-transform" :class="fontSelectOpen === '{{ $levelKey }}' && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                </button>
                <div
                    x-show="fontSelectOpen === '{{ $levelKey }}'"
                    x-cloak
                    x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="opacity-0 translate-y-1"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="admin-select-menu absolute z-50 mt-1.5 w-full max-h-64 overflow-y-auto rounded-xl border border-gray-200 bg-white shadow-lg py-1"
                >
                    <template x-for="opt in {{ $levelOptions }}" :key="opt.value">
                        <button
                            type="button"
                            class="w-full text-left px-3 py-2.5 text-sm flex items-center justify-between gap-2"
                            :class="String(form['{{ $levelKey }}']) === String(opt.value) ? 'admin-select-option-selected' : 'admin-select-option-idle'"
                            @click="pickFontSelect('{{ $levelKey }}', opt.value)"
                        >
                            <span class="font-semibold" x-text="opt.label"></span>
                            <svg
                                class="admin-select-check w-4 h-4 shrink-0"
                                :class="String(form['{{ $levelKey }}']) === String(opt.value) && 'admin-select-check-active'"
                                x-show="String(form['{{ $levelKey }}']) === String(opt.value)"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"
                            ><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                        </button>
                    </template>
                </div>
            </div>
            <p class="text-[11px] text-gray-400" x-text="t('articles','{{ $levelHint }}')"></p>
        </div>
    @endif

    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-2">
        {{-- Font family --}}
        <div class="block text-xs space-y-1 min-w-0">
            <span class="text-gray-500" x-text="t('articles','font_family')"></span>
            <div
                class="relative"
                @click.outside="closeFontSelect('{{ $prefix }}_font_family')"
            >
                <button
                    type="button"
                    class="admin-select-trigger w-full h-10 px-3 rounded-xl border border-gray-200 bg-white text-sm text-gray-900 flex items-center justify-between gap-2 hover:border-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-900/10"
                    @click="toggleFontSelect('{{ $prefix }}_font_family')"
                >
                    <span
                        class="truncate text-left"
                        :style="fontOptionStyle(form['{{ $prefix }}_font_family'], 'family')"
                        x-text="fontSelectLabel('{{ $prefix }}_font_family', 'family')"
                    ></span>
                    <svg class="w-4 h-4 text-gray-400 shrink-0 transition-transform" :class="fontSelectOpen === '{{ $prefix }}_font_family' && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                </button>
                <div
                    x-show="fontSelectOpen === '{{ $prefix }}_font_family'"
                    x-cloak
                    x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="opacity-0 translate-y-1"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="admin-select-menu absolute z-50 mt-1.5 w-full max-h-64 overflow-y-auto rounded-xl border border-gray-200 bg-white shadow-lg py-1"
                >
                    <template x-for="group in fontFamilyGroups" :key="group.key">
                        <div>
                            <p class="admin-select-group px-3 pt-2 pb-1 text-[10px] font-bold uppercase tracking-wider text-gray-400" x-text="group.label"></p>
                            <template x-for="opt in group.options" :key="opt.value">
                                <button
                                    type="button"
                                    class="w-full text-left px-3 py-2.5 text-sm flex items-center justify-between gap-2"
                                    :class="String(form['{{ $prefix }}_font_family']) === String(opt.value) ? 'admin-select-option-selected' : 'admin-select-option-idle'"
                                    :style="fontOptionStyle(opt.value, 'family')"
                                    @click="pickFontSelect('{{ $prefix }}_font_family', opt.value)"
                                >
                                    <span x-text="opt.label"></span>
                                    <svg
                                        class="admin-select-check w-4 h-4 shrink-0"
                                        :class="String(form['{{ $prefix }}_font_family']) === String(opt.value) && 'admin-select-check-active'"
                                        x-show="String(form['{{ $prefix }}_font_family']) === String(opt.value)"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"
                                    ><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                </button>
                            </template>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        {{-- Weight --}}
        <div class="block text-xs space-y-1 min-w-0">
            <span class="text-gray-500" x-text="t('articles','font_weight')"></span>
            <div class="relative" @click.outside="closeFontSelect('{{ $prefix }}_font_weight')">
                <button
                    type="button"
                    class="admin-select-trigger w-full h-10 px-3 rounded-xl border border-gray-200 bg-white text-sm text-gray-900 flex items-center justify-between gap-2 hover:border-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-900/10"
                    @click="toggleFontSelect('{{ $prefix }}_font_weight')"
                >
                    <span
                        class="truncate text-left"
                        :style="fontOptionStyle(form['{{ $prefix }}_font_weight'], 'weight')"
                        x-text="fontSelectLabel('{{ $prefix }}_font_weight', 'weight')"
                    ></span>
                    <svg class="w-4 h-4 text-gray-400 shrink-0 transition-transform" :class="fontSelectOpen === '{{ $prefix }}_font_weight' && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                </button>
                <div
                    x-show="fontSelectOpen === '{{ $prefix }}_font_weight'"
                    x-cloak
                    x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="opacity-0 translate-y-1"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="admin-select-menu absolute z-50 mt-1.5 w-full max-h-64 overflow-y-auto rounded-xl border border-gray-200 bg-white shadow-lg py-1"
                >
                    <template x-for="opt in fontWeightOptions" :key="opt.value">
                        <button
                            type="button"
                            class="w-full text-left px-3 py-2.5 text-sm flex items-center justify-between gap-2"
                            :class="String(form['{{ $prefix }}_font_weight']) === String(opt.value) ? 'admin-select-option-selected' : 'admin-select-option-idle'"
                            :style="fontOptionStyle(opt.value, 'weight')"
                            @click="pickFontSelect('{{ $prefix }}_font_weight', opt.value)"
                        >
                            <span x-text="opt.label"></span>
                            <svg
                                class="admin-select-check w-4 h-4 shrink-0"
                                :class="String(form['{{ $prefix }}_font_weight']) === String(opt.value) && 'admin-select-check-active'"
                                x-show="String(form['{{ $prefix }}_font_weight']) === String(opt.value)"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"
                            ><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                        </button>
                    </template>
                </div>
            </div>
        </div>

        {{-- Style --}}
        <div class="block text-xs space-y-1 min-w-0">
            <span class="text-gray-500" x-text="t('articles','font_style')"></span>
            <div class="relative" @click.outside="closeFontSelect('{{ $prefix }}_font_style')">
                <button
                    type="button"
                    class="admin-select-trigger w-full h-10 px-3 rounded-xl border border-gray-200 bg-white text-sm text-gray-900 flex items-center justify-between gap-2 hover:border-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-900/10"
                    @click="toggleFontSelect('{{ $prefix }}_font_style')"
                >
                    <span
                        class="truncate text-left"
                        :style="fontOptionStyle(form['{{ $prefix }}_font_style'], 'style')"
                        x-text="fontSelectLabel('{{ $prefix }}_font_style', 'style')"
                    ></span>
                    <svg class="w-4 h-4 text-gray-400 shrink-0 transition-transform" :class="fontSelectOpen === '{{ $prefix }}_font_style' && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                </button>
                <div
                    x-show="fontSelectOpen === '{{ $prefix }}_font_style'"
                    x-cloak
                    x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="opacity-0 translate-y-1"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="admin-select-menu absolute z-50 mt-1.5 w-full max-h-64 overflow-y-auto rounded-xl border border-gray-200 bg-white shadow-lg py-1"
                >
                    <template x-for="opt in fontStyleOptions" :key="opt.value">
                        <button
                            type="button"
                            class="w-full text-left px-3 py-2.5 text-sm flex items-center justify-between gap-2"
                            :class="String(form['{{ $prefix }}_font_style']) === String(opt.value) ? 'admin-select-option-selected' : 'admin-select-option-idle'"
                            :style="fontOptionStyle(opt.value, 'style')"
                            @click="pickFontSelect('{{ $prefix }}_font_style', opt.value)"
                        >
                            <span x-text="opt.label"></span>
                            <svg
                                class="admin-select-check w-4 h-4 shrink-0"
                                :class="String(form['{{ $prefix }}_font_style']) === String(opt.value) && 'admin-select-check-active'"
                                x-show="String(form['{{ $prefix }}_font_style']) === String(opt.value)"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"
                            ><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                        </button>
                    </template>
                </div>
            </div>
        </div>

        {{-- Size --}}
        <div class="block text-xs space-y-1 min-w-0">
            <span class="text-gray-500" x-text="t('articles','font_size')"></span>
            <div class="relative" @click.outside="closeFontSelect('{{ $prefix }}_font_size')">
                <button
                    type="button"
                    class="admin-select-trigger w-full h-10 px-3 rounded-xl border border-gray-200 bg-white text-sm text-gray-900 flex items-center justify-between gap-2 hover:border-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-900/10"
                    @click="toggleFontSelect('{{ $prefix }}_font_size')"
                >
                    <span class="truncate text-left" x-text="fontSelectLabel('{{ $prefix }}_font_size', 'size')"></span>
                    <svg class="w-4 h-4 text-gray-400 shrink-0 transition-transform" :class="fontSelectOpen === '{{ $prefix }}_font_size' && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                </button>
                <div
                    x-show="fontSelectOpen === '{{ $prefix }}_font_size'"
                    x-cloak
                    x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="opacity-0 translate-y-1"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="admin-select-menu absolute z-50 mt-1.5 w-full max-h-64 overflow-y-auto rounded-xl border border-gray-200 bg-white shadow-lg py-1"
                >
                    <template x-for="opt in fontSizeOptions" :key="opt.value">
                        <button
                            type="button"
                            class="w-full text-left px-3 py-2.5 text-sm flex items-center justify-between gap-2"
                            :class="String(form['{{ $prefix }}_font_size']) === String(opt.value) ? 'admin-select-option-selected' : 'admin-select-option-idle'"
                            @click="pickFontSelect('{{ $prefix }}_font_size', opt.value)"
                        >
                            <span x-text="opt.label"></span>
                            <svg
                                class="admin-select-check w-4 h-4 shrink-0"
                                :class="String(form['{{ $prefix }}_font_size']) === String(opt.value) && 'admin-select-check-active'"
                                x-show="String(form['{{ $prefix }}_font_size']) === String(opt.value)"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"
                            ><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                        </button>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <div
        class="rounded-lg border border-dashed border-gray-200 bg-white px-3 py-2.5 text-gray-800 line-clamp-3 overflow-hidden"
        :style="typographyPreviewStyle('{{ $prefix }}')"
        x-text="typographyPreview('{{ $prefix }}')"
    ></div>
</div>
