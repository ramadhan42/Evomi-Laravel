{{-- Editor teks CMS: perkakas yang sama dengan editor artikel, tapi ringkas
     dan hanya menyimpan format inline. Dipakai untuk field bertipe teks di
     setiap section dan setiap menu CMS. --}}
<div class="cms-editor" x-data="cmsRichText()" @click.outside="menu = null">
    <div class="cms-editor__bar">
        <button type="button" class="cms-editor__btn" :class="state.bold && 'is-on'" title="Tebal (Ctrl+B)" @mousedown.prevent @click="exec('bold')">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5h5.5a3.5 3.5 0 0 1 2.4 6 3.75 3.75 0 0 1-1.6 7.1H8V5Zm2.4 2.2v3.6h3a1.8 1.8 0 0 0 0-3.6h-3Zm0 5.7v4h3.6a2 2 0 0 0 0-4h-3.6Z"/></svg>
        </button>
        <button type="button" class="cms-editor__btn" :class="state.italic && 'is-on'" title="Miring (Ctrl+I)" @mousedown.prevent @click="exec('italic')">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 5h8v2h-3l-3.2 10H15v2H7v-2h3l3.2-10H10z"/></svg>
        </button>
        <button type="button" class="cms-editor__btn" :class="state.underline && 'is-on'" title="Garis bawah (Ctrl+U)" @mousedown.prevent @click="exec('underline')">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 4v6a5 5 0 0 0 10 0V4h-2v6a3 3 0 0 1-6 0V4H7ZM5 19h14v2H5z"/></svg>
        </button>

        <span class="cms-editor__sep"></span>

        {{-- Font --}}
        <div class="cms-editor__wrap">
            <button type="button" class="cms-editor__select" title="Font" @mousedown.prevent @click="menu = menu === 'family' ? null : 'family'">
                <span class="truncate" :style="familyStyle(state.family || 'parkinsans')" x-text="familyLabel()"></span>
                <svg class="cms-editor__caret" viewBox="0 0 24 24" aria-hidden="true"><path d="m7 10 5 5 5-5z"/></svg>
            </button>
            <div class="cms-editor__menu" x-show="menu === 'family'" x-cloak x-transition.opacity.duration.100ms>
                <template x-for="opt in fontFamilyOptions" :key="opt.value">
                    <button type="button" class="cms-editor__item" :class="state.family === opt.value && 'is-on'" @mousedown.prevent @click="setFamily(opt.value)">
                        <span :style="familyStyle(opt.value)" x-text="opt.label.replace(' (project)', '')"></span>
                    </button>
                </template>
            </div>
        </div>

        {{-- Ukuran --}}
        <div class="cms-editor__wrap">
            <button type="button" class="cms-editor__size" title="Ukuran teks" @mousedown.prevent @click="menu = menu === 'size' ? null : 'size'" x-text="state.size"></button>
            <div class="cms-editor__menu cms-editor__menu--size" x-show="menu === 'size'" x-cloak x-transition.opacity.duration.100ms>
                <template x-for="size in fontSizes" :key="size">
                    <button type="button" class="cms-editor__item" :class="state.size === size && 'is-on'" @mousedown.prevent @click="setSize(size)" x-text="size"></button>
                </template>
            </div>
        </div>

        {{-- Warna --}}
        <div class="cms-editor__wrap">
            <button type="button" class="cms-editor__btn" title="Warna teks" @mousedown.prevent="rememberRange()" @click="menu = menu === 'color' ? null : 'color'">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m12 4 5 12h-2.2l-1-2.6H10.2l-1 2.6H7l5-12Zm-1.1 7.6h2.2L12 8.5l-1.1 3.1Z"/></svg>
            </button>
            <div class="cms-editor__menu cms-editor__menu--color" x-show="menu === 'color'" x-cloak x-transition.opacity.duration.100ms>
                <template x-for="color in colors" :key="color">
                    <button type="button" class="cms-editor__swatch" :style="{ background: color }" :title="color" @mousedown.prevent @click="setColor(color)"></button>
                </template>
            </div>
        </div>

        {{-- Tautan --}}
        <div class="cms-editor__wrap">
            <button type="button" class="cms-editor__btn" title="Tautan" @mousedown.prevent @click="openLink()">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10.6 13.4a1 1 0 0 1 0-1.4l1.4-1.4a1 1 0 0 1 1.4 1.4l-1.4 1.4a1 1 0 0 1-1.4 0Zm-3.3 4.7a4.5 4.5 0 0 1 0-6.4l2.5-2.5 1.4 1.4-2.5 2.5a2.5 2.5 0 0 0 3.6 3.6l2.5-2.5 1.4 1.4-2.5 2.5a4.5 4.5 0 0 1-6.4 0Zm9.4-9.4-2.5 2.5-1.4-1.4 2.5-2.5a2.5 2.5 0 0 0-3.6-3.6L9.2 6.2 7.8 4.8l2.5-2.5a4.5 4.5 0 0 1 6.4 6.4Z"/></svg>
            </button>
            <div class="cms-editor__menu cms-editor__menu--link" x-show="menu === 'link'" x-cloak x-transition.opacity.duration.100ms>
                <input type="url" class="cms-editor__input" x-model="linkUrl" placeholder="Tempel tautan" @keydown.enter.prevent="applyLink()" @keydown.escape.prevent="menu = null">
                <div class="cms-editor__actions">
                    <button type="button" class="cms-editor__chip" @mousedown.prevent @click="exec('unlink')">Hapus</button>
                    <button type="button" class="cms-editor__chip is-primary" @mousedown.prevent @click="applyLink()">Terapkan</button>
                </div>
            </div>
        </div>

        <span class="cms-editor__sep"></span>

        <button type="button" class="cms-editor__btn" title="Bersihkan format" @mousedown.prevent @click="clearFormatting()">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 5h12v2h-4.6l-1.2 4.1-1.9-1.9L10.6 7H6V5Zm12.6 14.6-4.2-4.2-.7 2.6h-2.1l1.2-4.2-6.4-6.4 1.4-1.4 12.2 12.2-1.4 1.4Z"/></svg>
        </button>

        <span class="cms-editor__hint">maks <span x-text="maxLines"></span> baris</span>
    </div>

    <div
        class="cms-editor__surface"
        x-ref="surface"
        contenteditable="true"
        role="textbox"
        aria-multiline="true"
        spellcheck="true"
        x-effect="bind(field, field.value, maxLinesFor(field))"
        @input="sync()"
        @blur="sync()"
        @paste.prevent="onPaste($event)"
        @keydown="onKeydown($event)"
        @keyup="refreshState()"
        @mouseup="refreshState()"
    ></div>
</div>
