{{-- Google Docs-style article editor: title + ringkasan + konten in one page --}}
<div class="doc-editor" x-data="docWorkspace" @click.outside="menu = null">
    <style x-text="editorCss()"></style>

    {{-- Language tabs + document meta --}}
    <div class="doc-topbar">
        <div class="doc-lang" role="tablist">
            <button type="button" role="tab" class="doc-lang-tab" :class="locale === 'id' && 'is-on'" @click="setLocale('id')">
                <span>Indonesia</span>
            </button>
            <button type="button" role="tab" class="doc-lang-tab" :class="locale === 'en' && 'is-on'" @click="setLocale('en')">
                <span>English</span>
            </button>
        </div>
        <div class="doc-topbar-meta">
            <span class="doc-chip" x-show="active === 'title'" x-cloak x-text="t('articles','doc_field_title')"></span>
            <span class="doc-chip" x-show="active === 'excerpt'" x-cloak x-text="t('articles','doc_field_excerpt')"></span>
            <span class="doc-chip" x-show="active === 'content'" x-cloak x-text="t('articles','doc_field_content')"></span>
            <span x-text="words + ' ' + t('articles','words')"></span>
        </div>
    </div>

    <div class="doc-toolbar">
        {{-- Undo / redo --}}
        <button type="button" class="doc-btn" :disabled="!richEnabled()" :title="t('articles','undo') + ' (Ctrl+Z)'" @mousedown.prevent @click="exec('undo')">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12.5 8H7.8l1.9-1.9-1.4-1.4L3.9 9l4.4 4.3 1.4-1.4L7.8 10h4.7a4 4 0 0 1 0 8H9v2h3.5a6 6 0 0 0 0-12Z"/></svg>
        </button>
        <button type="button" class="doc-btn" :disabled="!richEnabled()" :title="t('articles','redo') + ' (Ctrl+Y)'" @mousedown.prevent @click="exec('redo')">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M11.5 8h4.7l-1.9-1.9 1.4-1.4L20.1 9l-4.4 4.3-1.4-1.4 1.9-1.9h-4.7a4 4 0 0 0 0 8H15v2h-3.5a6 6 0 0 1 0-12Z"/></svg>
        </button>

        <span class="doc-sep"></span>

        {{-- Zoom --}}
        <div class="doc-menu-wrap">
            <button type="button" class="doc-select doc-select-sm" :title="t('articles','zoom')" @mousedown.prevent @click="menu = menu === 'zoom' ? null : 'zoom'">
                <span x-text="zoom + '%'"></span>
                <svg class="doc-caret" viewBox="0 0 24 24" aria-hidden="true"><path d="m7 10 5 5 5-5z"/></svg>
            </button>
            <div class="doc-menu" x-show="menu === 'zoom'" x-cloak x-transition.opacity.duration.100ms>
                <template x-for="level in zoomLevels" :key="level">
                    <button type="button" class="doc-menu-item" :class="zoom === level && 'is-active'" @mousedown.prevent @click="setZoom(level)">
                        <span x-text="level + '%'"></span>
                        <svg class="doc-menu-check" x-show="zoom === level" x-cloak fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                    </button>
                </template>
            </div>
        </div>

        <span class="doc-sep"></span>

        {{-- Paragraph / heading style --}}
        <div class="doc-menu-wrap">
            <button type="button" class="doc-select doc-select-styles" :title="t('articles','heading_level')" @mousedown.prevent @click="menu = menu === 'styles' ? null : 'styles'">
                <span class="truncate" x-text="state.block === 'p' ? t('articles','style_normal') : (state.block === 'blockquote' ? t('articles','style_quote') : blockLabelFor(state.block))"></span>
                <svg class="doc-caret" viewBox="0 0 24 24" aria-hidden="true"><path d="m7 10 5 5 5-5z"/></svg>
            </button>
            <div class="doc-menu doc-menu-styles" x-show="menu === 'styles'" x-cloak x-transition.opacity.duration.100ms>
                <template x-for="tag in blockOptions()" :key="tag">
                    <button type="button" class="doc-menu-item" :class="state.block === tag && 'is-active'" @mousedown.prevent @click="setBlock(tag)">
                        <span :class="'doc-style-' + tag" x-text="tag === 'p' ? t('articles','style_normal') : (tag === 'blockquote' ? t('articles','style_quote') : blockLabelFor(tag))"></span>
                        <span class="flex items-center gap-2 shrink-0">
                            <span class="doc-menu-hint" x-show="blockEnabled() && tag !== 'blockquote'" x-text="tag === 'p' ? 'Ctrl+Alt+0' : 'Ctrl+Alt+' + tag.slice(1)"></span>
                            <svg class="doc-menu-check" x-show="state.block === tag" x-cloak fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                        </span>
                    </button>
                </template>
            </div>
        </div>

        <span class="doc-sep"></span>

        {{-- Font family --}}
        <div class="doc-menu-wrap">
            <button type="button" class="doc-select doc-select-font" :title="t('articles','font_family')" @mousedown.prevent @click="menu = menu === 'family' ? null : 'family'">
                <span class="truncate" :style="familyStyle(state.family || 'parkinsans')" x-text="familyLabel()"></span>
                <svg class="doc-caret" viewBox="0 0 24 24" aria-hidden="true"><path d="m7 10 5 5 5-5z"/></svg>
            </button>
            <div class="doc-menu doc-menu-font" x-show="menu === 'family'" x-cloak x-transition.opacity.duration.100ms>
                <template x-for="group in fontFamilyGroups" :key="group.key">
                    <div>
                        <p class="doc-menu-group" x-text="group.label"></p>
                        <template x-for="opt in group.options" :key="opt.value">
                            <button type="button" class="doc-menu-item" :class="state.family === opt.value && 'is-active'" @mousedown.prevent @click="setFamily(opt.value)">
                                <span :style="familyStyle(opt.value)" x-text="opt.label.replace(' (project)', '')"></span>
                                <svg class="doc-menu-check" x-show="state.family === opt.value" x-cloak fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                            </button>
                        </template>
                    </div>
                </template>
            </div>
        </div>

        <span class="doc-sep"></span>

        {{-- Font size --}}
        <div class="doc-size">
            <button type="button" class="doc-btn doc-btn-xs" :title="t('articles','size_down')" @mousedown.prevent @click="stepSize(-1)">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 11h14v2H5z"/></svg>
            </button>
            <div class="doc-menu-wrap">
                <button type="button" class="doc-size-value" :title="t('articles','font_size')" @mousedown.prevent @click="menu = menu === 'size' ? null : 'size'" x-text="state.size"></button>
                <div class="doc-menu doc-menu-size" x-show="menu === 'size'" x-cloak x-transition.opacity.duration.100ms>
                    <template x-for="size in fontSizes" :key="size">
                        <button type="button" class="doc-menu-item" :class="state.size === size && 'is-active'" @mousedown.prevent @click="setSize(size)">
                            <span x-text="size"></span>
                            <svg class="doc-menu-check" x-show="state.size === size" x-cloak fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                        </button>
                    </template>
                </div>
            </div>
            <button type="button" class="doc-btn doc-btn-xs" :title="t('articles','size_up')" @mousedown.prevent @click="stepSize(1)">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M11 5h2v6h6v2h-6v6h-2v-6H5v-2h6z"/></svg>
            </button>
        </div>

        <span class="doc-sep"></span>

        {{-- Inline formatting --}}
        <button type="button" class="doc-btn" :class="state.bold && 'is-on'" :title="t('articles','bold') + ' (Ctrl+B)'" @mousedown.prevent @click="toggleBold()">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5h5.5a3.5 3.5 0 0 1 2.4 6 3.75 3.75 0 0 1-1.6 7.1H8V5Zm2.4 2.2v3.6h3a1.8 1.8 0 0 0 0-3.6h-3Zm0 5.7v4h3.6a2 2 0 0 0 0-4h-3.6Z"/></svg>
        </button>
        <button type="button" class="doc-btn" :class="state.italic && 'is-on'" :title="t('articles','italic') + ' (Ctrl+I)'" @mousedown.prevent @click="toggleItalic()">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 5h8v2h-3l-3.2 10H15v2H7v-2h3l3.2-10H10z"/></svg>
        </button>
        <button type="button" class="doc-btn" :class="state.underline && 'is-on'" :disabled="!richEnabled()" :title="t('articles','underline') + ' (Ctrl+U)'" @mousedown.prevent @click="exec('underline')">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 4v6a5 5 0 0 0 10 0V4h-2v6a3 3 0 0 1-6 0V4H7ZM5 19h14v2H5z"/></svg>
        </button>
        <button type="button" class="doc-btn" :class="state.strike && 'is-on'" :disabled="!richEnabled()" :title="t('articles','strike')" @mousedown.prevent @click="exec('strikeThrough')">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 11h16v2H4z"/><path d="M12 5c2.4 0 4 1.1 4.5 3h-2.2c-.3-.7-1.1-1.2-2.3-1.2-1.5 0-2.4.6-2.4 1.5 0 .5.2.9.8 1.2H7.6C7.2 9 7 8.4 7 7.7 7 6.1 9 5 12 5Zm-4.4 9.6h2.3c.2 1 1.1 1.6 2.5 1.6 1.6 0 2.5-.6 2.5-1.6 0-.4-.1-.8-.5-1.1h2.4c.2.4.3.9.3 1.4 0 2-2 3.1-4.7 3.1s-4.6-1.2-4.8-3.4Z"/></svg>
        </button>

        {{-- Text colour --}}
        <div class="doc-menu-wrap">
            <button type="button" class="doc-btn doc-btn-color" :disabled="!richEnabled()" :title="t('articles','text_color')" @mousedown.prevent="rememberRange()" @click="menu = menu === 'color' ? null : 'color'">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m12 4 5 12h-2.2l-1-2.6H10.2l-1 2.6H7l5-12Zm-1.1 7.6h2.2L12 8.5l-1.1 3.1Z"/></svg>
                <span class="doc-color-bar" style="background:#111827"></span>
            </button>
            <div class="doc-menu doc-menu-color" x-show="menu === 'color'" x-cloak x-transition.opacity.duration.100ms>
                <p class="doc-menu-head" x-text="t('articles','text_color')"></p>
                <div class="doc-swatch-grid">
                    <template x-for="color in colors" :key="'fg-' + color">
                        <button type="button" class="doc-swatch" :style="{ background: color }" :title="color" @mousedown.prevent @click="setColor('foreColor', color)"></button>
                    </template>
                </div>
                <label class="doc-color-custom">
                    <input type="color" x-model="customColor" @mousedown.stop @input="setColor('foreColor', $event.target.value)">
                    <span x-text="t('articles','custom_color')"></span>
                </label>
            </div>
        </div>

        {{-- Highlight --}}
        <div class="doc-menu-wrap">
            <button type="button" class="doc-btn doc-btn-color" :disabled="!richEnabled()" :title="t('articles','highlight')" @mousedown.prevent="rememberRange()" @click="menu = menu === 'highlight' ? null : 'highlight'">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14.6 3.6 20.4 9.4 12 17.8H8.2l-1.4 1.4H4.6l2.1-2.1V13.9l7.9-10.3Zm-6 12.1h2.6l6-6-2.6-2.6-6 6v2.6ZM4 20h16v2H4z"/></svg>
                <span class="doc-color-bar" style="background:#ffc700"></span>
            </button>
            <div class="doc-menu doc-menu-color" x-show="menu === 'highlight'" x-cloak x-transition.opacity.duration.100ms>
                <p class="doc-menu-head" x-text="t('articles','highlight')"></p>
                <div class="doc-swatch-grid">
                    <template x-for="color in colors" :key="'bg-' + color">
                        <button type="button" class="doc-swatch" :style="{ background: color }" :title="color" @mousedown.prevent @click="setColor('hiliteColor', color)"></button>
                    </template>
                </div>
                <label class="doc-color-custom">
                    <input type="color" x-model="customColor" @mousedown.stop @input="setColor('hiliteColor', $event.target.value)">
                    <span x-text="t('articles','custom_color')"></span>
                </label>
            </div>
        </div>

        <span class="doc-sep"></span>

        {{-- Link --}}
        <div class="doc-menu-wrap">
            <button type="button" class="doc-btn" :disabled="!richEnabled()" :title="t('articles','insert_link') + ' (Ctrl+K)'" @mousedown.prevent @click="openLink()">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10.6 13.4a1 1 0 0 1 0-1.4l1.4-1.4a1 1 0 0 1 1.4 1.4l-1.4 1.4a1 1 0 0 1-1.4 0Zm-3.3 4.7a4.5 4.5 0 0 1 0-6.4l2.5-2.5 1.4 1.4-2.5 2.5a2.5 2.5 0 0 0 3.6 3.6l2.5-2.5 1.4 1.4-2.5 2.5a4.5 4.5 0 0 1-6.4 0Zm9.4-9.4-2.5 2.5-1.4-1.4 2.5-2.5a2.5 2.5 0 0 0-3.6-3.6L9.2 6.2 7.8 4.8l2.5-2.5a4.5 4.5 0 0 1 6.4 6.4Z"/></svg>
            </button>
            <div class="doc-menu doc-menu-link" x-show="menu === 'link'" x-cloak x-transition.opacity.duration.100ms>
                <input
                    type="url"
                    class="doc-link-input"
                    x-ref="linkInput"
                    x-model="linkUrl"
                    :placeholder="t('articles','link_url')"
                    @keydown.enter.prevent="applyLink()"
                    @keydown.escape.prevent="menu = null"
                >
                <div class="doc-link-actions">
                    <button type="button" class="doc-link-btn" @mousedown.prevent @click="removeLink()" x-text="t('articles','remove_link')"></button>
                    <button type="button" class="doc-link-btn is-primary" @mousedown.prevent @click="applyLink()" x-text="t('common','apply')"></button>
                </div>
            </div>
        </div>

        {{-- Sisipkan gambar --}}
        <button
            type="button"
            class="doc-btn"
            :disabled="!canInsertImage() || imageBusy"
            :title="t('articles','insert_image')"
            @mousedown.prevent="rememberRange()"
            @click="pickImage()"
        >
            <svg x-show="!imageBusy" viewBox="0 0 24 24" aria-hidden="true"><path d="M20 5H4a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h16a1 1 0 0 0 1-1V6a1 1 0 0 0-1-1Zm-1 12H5v-1.6l3.4-3.4 3 3 3.6-3.6L19 14.4V17ZM9 11a1.6 1.6 0 1 1 0-3.2A1.6 1.6 0 0 1 9 11Z"/></svg>
            <svg x-show="imageBusy" x-cloak class="doc-spin" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3a9 9 0 1 0 9 9h-2a7 7 0 1 1-7-7V3Z"/></svg>
        </button>
        <input type="file" x-ref="imageFile" class="hidden" accept="image/jpeg,image/png,image/webp,image/gif" @change="onImageFile($event)">

        <span class="doc-sep"></span>

        {{-- Alignment --}}
        <button type="button" class="doc-btn" :class="state.align === 'left' && richEnabled() && 'is-on'" :disabled="!blockEnabled()" :title="t('articles','align_left')" @mousedown.prevent @click="setAlign('left')">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16v2H4zm0 4h10v2H4zm0 4h16v2H4zm0 4h10v2H4z"/></svg>
        </button>
        <button type="button" class="doc-btn" :class="state.align === 'center' && 'is-on'" :disabled="!blockEnabled()" :title="t('articles','align_center')" @mousedown.prevent @click="setAlign('center')">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16v2H4zm3 4h10v2H7zm-3 4h16v2H4zm3 4h10v2H7z"/></svg>
        </button>
        <button type="button" class="doc-btn" :class="state.align === 'right' && 'is-on'" :disabled="!blockEnabled()" :title="t('articles','align_right')" @mousedown.prevent @click="setAlign('right')">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16v2H4zm6 4h10v2H10zm-6 4h16v2H4zm6 4h10v2H10z"/></svg>
        </button>
        <button type="button" class="doc-btn" :class="state.align === 'justify' && 'is-on'" :disabled="!blockEnabled()" :title="t('articles','align_justify')" @mousedown.prevent @click="setAlign('justify')">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16v2H4zm0 4h16v2H4zm0 4h16v2H4zm0 4h16v2H4z"/></svg>
        </button>

        {{-- Line spacing --}}
        <div class="doc-menu-wrap">
            <button type="button" class="doc-btn" :disabled="!blockEnabled()" :title="t('articles','line_spacing')" @mousedown.prevent @click="menu = menu === 'spacing' ? null : 'spacing'">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 4 3 8h2v8H3l3 4 3-4H7V8h2L6 4Zm5 1h10v2H11zm0 6h10v2H11zm0 6h10v2H11z"/></svg>
            </button>
            <div class="doc-menu doc-menu-right" x-show="menu === 'spacing'" x-cloak x-transition.opacity.duration.100ms>
                <p class="doc-menu-group" x-text="t('articles','line_spacing')"></p>
                <template x-for="opt in lineSpacings" :key="opt.value">
                    <button type="button" class="doc-menu-item" @mousedown.prevent @click="setSpacing(opt.value)">
                        <span x-text="opt.label"></span>
                    </button>
                </template>
            </div>
        </div>

        {{-- Lists & indent --}}
        <button type="button" class="doc-btn" :class="state.ul && 'is-on'" :disabled="!blockEnabled()" :title="t('articles','bullet_list')" @mousedown.prevent @click="toggleList(false)">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6.5a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0Zm0 5.5a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0Zm0 5.5a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0ZM9 5.5h11v2H9zm0 5.5h11v2H9zm0 5.5h11v2H9z"/></svg>
        </button>
        <button type="button" class="doc-btn" :class="state.ol && 'is-on'" :disabled="!blockEnabled()" :title="t('articles','number_list')" @mousedown.prevent @click="toggleList(true)">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4h2v4H5V5H4V4Zm0 6h3v1.2L5.4 13H7v1H4v-1.2L5.6 11H4v-1Zm0 6h3v4H4v-1h2v-.5H4.5v-1H6V17H4v-1Zm5-10.5h11v2H9zm0 5.5h11v2H9zm0 5.5h11v2H9z"/></svg>
        </button>
        <button type="button" class="doc-btn" :disabled="!blockEnabled()" :title="t('articles','outdent')" @mousedown.prevent @click="exec('outdent')">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16v2H4zm7 4h9v2h-9zm0 4h9v2h-9zm-7 4h16v2H4zm4-6L4 12l4-3v6Z"/></svg>
        </button>
        <button type="button" class="doc-btn" :disabled="!blockEnabled()" :title="t('articles','indent')" @mousedown.prevent @click="exec('indent')">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16v2H4zm7 4h9v2h-9zm0 4h9v2h-9zm-7 4h16v2H4zM4 9l4 3-4 3V9Z"/></svg>
        </button>

        <span class="doc-sep"></span>

        <button type="button" class="doc-btn" :disabled="!blockEnabled()" :title="t('articles','insert_rule')" @mousedown.prevent @click="insertRule()">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 11h16v2H4z"/><path d="M4 6h16v1.5H4zm0 10.5h16V18H4z" opacity=".35"/></svg>
        </button>
        <button type="button" class="doc-btn" :disabled="!richEnabled()" :title="t('articles','clear_formatting') + ' (Ctrl+\\)'" @mousedown.prevent @click="clearFormatting()">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 5h12v2h-4.6l-1.2 4.1-1.9-1.9L10.6 7H6V5Zm12.6 14.6-4.2-4.2-.7 2.6h-2.1l1.2-4.2-6.4-6.4 1.4-1.4 12.2 12.2-1.4 1.4Z"/></svg>
        </button>
    </div>

    {{-- Baris kontekstual: muncul saat sebuah gambar dipilih di dalam teks --}}
    <div class="doc-imagebar" x-show="activeImage" x-cloak x-transition.opacity.duration.100ms>
        <span class="doc-imagebar-title" x-text="t('articles','image_selected')"></span>

        <label class="doc-imagebar-field">
            <span x-text="t('articles','image_alt')"></span>
            <input
                type="text"
                class="doc-imagebar-input"
                :value="imageAlt"
                :placeholder="t('articles','image_alt_ph')"
                @input="imageAlt = $event.target.value; setImageAlt($event.target.value)"
            >
        </label>

        <button
            type="button"
            class="doc-chip"
            :class="imageHasCaption && 'is-on'"
            @mousedown.prevent
            @click="toggleCaption()"
            x-text="imageHasCaption ? t('articles','image_caption_off') : t('articles','image_caption_on')"
        ></button>

        <div class="doc-imagebar-group">
            <span x-text="t('articles','image_width')"></span>
            <button type="button" class="doc-chip" @mousedown.prevent @click="stepImageWidth(-5)">−</button>
            <template x-for="w in imageWidths" :key="w">
                <button
                    type="button"
                    class="doc-chip"
                    :class="String(imageWidth) === String(w) && 'is-on'"
                    @mousedown.prevent
                    @click="setImageWidth(w)"
                    x-text="w + '%'"
                ></button>
            </template>
            <button type="button" class="doc-chip" @mousedown.prevent @click="stepImageWidth(5)">+</button>
            <button
                type="button"
                class="doc-chip"
                :class="imageWidth === '' && 'is-on'"
                @mousedown.prevent
                @click="setImageWidth(0)"
                x-text="t('articles','image_width_auto')"
            ></button>
        </div>

        <div class="doc-imagebar-group">
            <span x-text="t('articles','image_align')"></span>
            <template x-for="mode in ['inline', 'left', 'center', 'right']" :key="mode">
                <button
                    type="button"
                    class="doc-chip"
                    :class="imageAlign === mode && 'is-on'"
                    @mousedown.prevent
                    @click="setImageAlign(mode)"
                    x-text="t('articles', 'image_align_' + mode)"
                ></button>
            </template>
        </div>

        <button type="button" class="doc-chip is-danger" @mousedown.prevent @click="removeImage()" x-text="t('articles','image_remove')"></button>
    </div>

    <p class="doc-imagebar-error" x-show="imageError" x-cloak x-text="imageError"></p>

    <div class="doc-canvas">
        <div class="doc-page" :style="pageStyle()">
            {{-- Title --}}
            <div class="doc-field" :class="active === 'title' && 'is-active'">
                <span class="doc-field-tag" x-text="t('articles','doc_field_title')"></span>
                <div
                    id="doc-title"
                    class="doc-surface doc-surface-title"
                    x-ref="title"
                    contenteditable="true"
                    role="textbox"
                    spellcheck="true"
                    x-effect="bindSurface('title', form, form[fieldFor('title')])"
                    @focus="onFocus('title')"
                    @input="syncSurface('title')"
                    @blur="syncSurface('title')"
                    @paste.prevent="onPaste('title', $event)"
                    @keydown="onKeydown('title', $event)"
                ></div>
                <p class="doc-ph" x-show="empty.title" x-cloak x-text="t('articles','doc_ph_title')"></p>
            </div>

            {{-- Ringkasan --}}
            <div class="doc-field" :class="active === 'excerpt' && 'is-active'">
                <span class="doc-field-tag" x-text="t('articles','doc_field_excerpt')"></span>
                <div
                    id="doc-excerpt"
                    class="doc-surface doc-surface-excerpt"
                    x-ref="excerpt"
                    contenteditable="true"
                    role="textbox"
                    spellcheck="true"
                    x-effect="bindSurface('excerpt', form, form[fieldFor('excerpt')])"
                    @focus="onFocus('excerpt')"
                    @input="syncSurface('excerpt')"
                    @blur="syncSurface('excerpt')"
                    @paste.prevent="onPaste('excerpt', $event)"
                    @keydown="onKeydown('excerpt', $event)"
                    @click="onSurfaceClick('excerpt', $event)"
                ></div>
                <p class="doc-ph" x-show="empty.excerpt" x-cloak x-text="t('articles','doc_ph_excerpt')"></p>
            </div>

            <div class="doc-divider"></div>

            {{-- Konten --}}
            <div class="doc-field doc-field-content" :class="active === 'content' && 'is-active'">
                <span class="doc-field-tag" x-text="t('articles','doc_field_content')"></span>
                <div
                    id="doc-content"
                    class="doc-surface doc-surface-content"
                    x-ref="content"
                    contenteditable="true"
                    role="textbox"
                    aria-multiline="true"
                    spellcheck="true"
                    x-effect="bindSurface('content', form, form[fieldFor('content')])"
                    @focus="onFocus('content')"
                    @input="syncSurface('content')"
                    @blur="syncSurface('content')"
                    @paste.prevent="onPaste('content', $event)"
                    @keydown="onKeydown('content', $event)"
                    @keyup="refreshState()"
                    @mouseup="refreshState()"
                    @click="onSurfaceClick('content', $event)"
                ></div>
                <p class="doc-ph" x-show="empty.content" x-cloak x-text="t('articles','doc_ph_content')"></p>
            </div>
        </div>
    </div>

    <div class="doc-statusbar">
        <span x-text="words + ' ' + t('articles','words') + ' · ' + chars + ' ' + t('articles','chars')"></span>
        <span x-text="t('articles','doc_saved_hint')"></span>
    </div>
</div>
