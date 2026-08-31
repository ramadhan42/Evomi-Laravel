/*
 * Google Docs-style editor for the admin article form.
 *
 * One document surface holds the whole article: title, excerpt (ringkasan)
 * and the rich text content, each pre-filled from the record being edited and
 * each styled with its own typography. Title and excerpt stay plain text —
 * they end up in cards, meta tags and the <title> — while the content is HTML,
 * sanitized by App\Support\ArticleContent before the storefront prints it.
 */

import {
    FONT_FAMILY_OPTIONS,
    HEADING_FONT_DEFAULTS,
    HEADING_LEVELS,
    fontFamilyKeyFromCss,
    resolveFontFamilyCss,
} from './font-catalog';

export const DOC_FONT_SIZES = [8, 9, 10, 11, 12, 14, 16, 18, 20, 24, 28, 32, 36, 40, 48, 60, 72, 96];
export const DOC_ZOOM_LEVELS = [50, 75, 90, 100, 125, 150, 200];
export const DOC_LINE_SPACINGS = [
    { value: '1', label: 'Rapat (1)' },
    { value: '1.15', label: 'Normal (1,15)' },
    { value: '1.5', label: 'Longgar (1,5)' },
    { value: '2', label: 'Ganda (2)' },
];

/** Font catalog split into the groups shown in the family dropdown. */
export const DOC_FONT_GROUPS = [
    {
        key: 'project',
        label: 'Font project',
        options: FONT_FAMILY_OPTIONS.filter((o) => o.group === 'project'),
    },
    {
        key: 'system',
        label: 'Font sistem',
        options: FONT_FAMILY_OPTIONS.filter((o) => o.group === 'system'),
    },
];

export const DOC_COLORS = [
    '#000000', '#434343', '#666666', '#999999', '#b7b7b7', '#ffffff',
    '#980000', '#ff0000', '#ff9900', '#ffc700', '#00a352', '#00bcd4',
    '#4a86e8', '#1155cc', '#9900ff', '#e91e63', '#1172ba', '#e8f4fc',
];

/** Surfaces of the document, in tab order. */
export const DOC_SURFACES = ['title', 'excerpt', 'content'];

/** Surfaces stored as HTML, where bold/italic apply to the selection. */
export const DOC_RICH_SURFACES = ['excerpt', 'content'];

const BLOCK_TAGS = ['p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'blockquote', 'li', 'div', 'pre'];

const PASTE_TAGS = new Set([
    'P', 'BR', 'HR', 'DIV', 'H1', 'H2', 'H3', 'H4', 'H5', 'H6',
    'STRONG', 'B', 'EM', 'I', 'U', 'S', 'STRIKE', 'DEL', 'INS', 'MARK', 'SUB', 'SUP',
    'BLOCKQUOTE', 'UL', 'OL', 'LI', 'A', 'SPAN', 'CODE', 'PRE',
]);

const PASTE_STYLE_PROPS = [
    'font-family', 'font-size', 'font-weight', 'font-style',
    'text-decoration', 'text-align', 'color', 'background-color', 'line-height',
];

const HTML_HINT = /<(p|div|br|hr|h[1-6]|ul|ol|li|blockquote|span|strong|b|em|i|u|s|a|pre|code)\b[^>]*>/i;

function escapeHtml(text) {
    return String(text ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
}

export function looksLikeHtml(value) {
    return HTML_HINT.test(String(value ?? ''));
}

/** Legacy plain text ("## Judul" + blank-line paragraphs) into editor HTML. */
export function plainTextToHtml(value) {
    const text = String(value ?? '').trim();
    if (text === '') return '';

    const out = [];
    let buffer = [];

    const flush = () => {
        const chunk = buffer.join('\n').trim();
        buffer = [];
        if (chunk !== '') out.push('<p>' + escapeHtml(chunk).replace(/\n/g, '<br>') + '</p>');
    };

    for (const line of text.split(/\r\n|\n|\r/)) {
        const heading = /^\s*(#{1,6})\s+(\S.*)$/.exec(line);
        if (heading) {
            flush();
            const level = heading[1].length;
            out.push(`<h${level}>${escapeHtml(heading[2].trim())}</h${level}>`);
            continue;
        }
        if (line.trim() === '') {
            flush();
            continue;
        }
        buffer.push(line);
    }
    flush();

    return out.join('');
}

/** Keep pasted markup down to the tags and inline styles we store. */
export function cleanPastedHtml(html) {
    const doc = new DOMParser().parseFromString(String(html ?? ''), 'text/html');

    const walk = (node) => {
        for (const child of Array.from(node.childNodes)) {
            if (child.nodeType === Node.TEXT_NODE) continue;
            if (child.nodeType !== Node.ELEMENT_NODE) {
                child.remove();
                continue;
            }

            walk(child);

            if (!PASTE_TAGS.has(child.tagName)) {
                child.replaceWith(...Array.from(child.childNodes));
                continue;
            }

            const style = child.getAttribute('style') || '';
            const href = child.tagName === 'A' ? child.getAttribute('href') || '' : '';
            for (const attr of Array.from(child.attributes)) child.removeAttribute(attr.name);

            const keep = style
                .split(';')
                .map((rule) => rule.trim())
                .filter((rule) => {
                    const prop = rule.split(':')[0]?.trim().toLowerCase();
                    return PASTE_STYLE_PROPS.includes(prop) && !/url\s*\(|expression/i.test(rule);
                });
            if (keep.length) child.setAttribute('style', keep.join('; '));
            if (href && /^(https?:\/\/|mailto:|tel:|\/|#)/i.test(href)) child.setAttribute('href', href);
        }
    };

    walk(doc.body);

    return doc.body.innerHTML;
}

export function registerDocEditor(Alpine) {
    Alpine.data('docWorkspace', () => ({
        articleForm: null,
        locale: 'id',
        active: 'content',
        zoom: 100,
        menu: null,
        linkUrl: '',
        savedRange: null,
        last: { title: null, excerpt: null, content: null },
        empty: { title: true, excerpt: true, content: true },
        words: 0,
        chars: 0,
        fontFamilyOptions: FONT_FAMILY_OPTIONS,
        fontFamilyGroups: DOC_FONT_GROUPS,
        customColor: '#1172ba',
        fontSizes: DOC_FONT_SIZES,
        zoomLevels: DOC_ZOOM_LEVELS,
        lineSpacings: DOC_LINE_SPACINGS,
        colors: DOC_COLORS,
        state: {
            block: 'p',
            family: '',
            size: 17,
            bold: false,
            italic: false,
            underline: false,
            strike: false,
            align: 'left',
            ul: false,
            ol: false,
        },

        /* ---------- lifecycle ---------- */

        init() {
            try {
                document.execCommand('defaultParagraphSeparator', false, 'p');
                document.execCommand('styleWithCSS', false, true);
            } catch (e) {
                /* older browsers keep their defaults */
            }

            this.onSelectionChange = () => {
                if (this.isInside(this.surface(this.active))) this.refreshState();
            };
            document.addEventListener('selectionchange', this.onSelectionChange);
        },

        destroy() {
            if (this.onSelectionChange) {
                document.removeEventListener('selectionchange', this.onSelectionChange);
            }
        },

        /* ---------- fields ---------- */

        /** Form key backing a surface for the language being edited. */
        fieldFor(name) {
            return this.locale === 'en' ? name + '_en' : name;
        },

        surface(name) {
            return this.$refs[name] || null;
        },

        /** Inline formatting (bold, colour, font on a selection). */
        richEnabled() {
            return DOC_RICH_SURFACES.includes(this.active);
        },

        /** Block formatting (lists, alignment, indent) — content only. */
        blockEnabled() {
            return this.active === 'content';
        },

        setLocale(locale) {
            this.menu = null;
            this.locale = locale === 'en' ? 'en' : 'id';
        },

        /* ---------- value <-> DOM ---------- */

        /**
         * Driven by x-effect on each surface: it follows the admin form even
         * when opening another article replaces the whole form object, so an
         * edit always starts from the text that is already stored.
         */
        bindSurface(name, form, value) {
            this.articleForm = form;
            const incoming = String(value ?? '');
            if (incoming !== this.last[name]) this.renderSurface(name, incoming);
            this.refreshState();
        },

        renderSurface(name, raw) {
            const el = this.surface(name);
            const value = String(raw ?? '');
            this.last[name] = value;
            if (!el) return;

            if (name === 'content') {
                const html = value.trim() === ''
                    ? ''
                    : looksLikeHtml(value)
                        ? value
                        : plainTextToHtml(value);
                el.innerHTML = html || '<p><br></p>';
            } else if (name === 'excerpt') {
                // Inline HTML: one line that can carry bold/italic/links.
                el.innerHTML = looksLikeHtml(value)
                    ? value
                    : escapeHtml(value).replace(/\r?\n/g, ' ');
            } else {
                el.textContent = value;
            }

            this.updateMeta();
        },

        syncSurface(name) {
            const el = this.surface(name);
            if (!el || !this.articleForm) return;

            let value;
            if (name === 'content') {
                value = el.innerHTML.trim();
                if (value === '<br>' || value === '<p><br></p>' || value === '<p></p>') value = '';
            } else if (name === 'excerpt') {
                value = el.innerHTML.replace(/<br\s*\/?>\s*$/i, '').trim();
                if ((el.innerText || '').trim() === '') value = '';
            } else {
                value = (el.innerText || '').replace(/\s+/g, ' ').trim();
            }

            this.last[name] = value;
            this.articleForm[this.fieldFor(name)] = value;
            this.updateMeta();
        },

        updateMeta() {
            for (const name of DOC_SURFACES) {
                const el = this.surface(name);
                this.empty[name] = ((el?.innerText || '').trim()) === '';
            }

            const text = (this.surface('content')?.innerText || '').trim();
            this.words = text === '' ? 0 : text.split(/\s+/).length;
            this.chars = text.length;
        },

        focusSurface(name) {
            const el = this.surface(name || this.active);
            if (el && !this.isInside(el)) el.focus();
        },

        onFocus(name) {
            this.active = name;
            this.menu = null;
            this.refreshState();
        },

        isInside(el) {
            const sel = window.getSelection();

            return !!(el && sel && sel.anchorNode && el.contains(sel.anchorNode));
        },

        /* ---------- commands ---------- */

        exec(command, value = null) {
            if (!this.richEnabled()) return;
            const target = this.active;
            this.focusSurface(target);
            try {
                document.execCommand(command, false, value);
            } catch (e) {
                /* the surface simply keeps its content */
            }
            this.syncSurface(target);
            this.refreshState();
        },

        /**
         * Style picker: a real heading tag inside the content, or the heading
         * level the title / excerpt is rendered with on the storefront.
         */
        setBlock(tag) {
            this.menu = null;

            if (this.active === 'content') {
                this.exec('formatBlock', '<' + tag + '>');

                return;
            }
            if (!this.articleForm) return;

            if (this.active === 'title') {
                this.articleForm.title_heading_level = tag === 'p' || tag === 'blockquote' ? 'h1' : tag;
            } else {
                this.articleForm.excerpt_heading_level = tag === 'p' || tag === 'blockquote' ? 'normal' : tag;
            }
            this.refreshState();
        },

        blockOptions() {
            if (this.active === 'title') return ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'];
            if (this.active === 'excerpt') return ['p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'];

            return ['p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'blockquote'];
        },

        /**
         * execCommand has no "set this CSS on the selection", so tag it with
         * the unused font size 7 and swap those nodes out. styleWithCSS has to
         * be off for that, or the browser writes font-size: xxx-large instead.
         */
        applyInline(prop, value) {
            const target = this.active;
            const el = this.surface(target);
            if (!el) return;

            this.focusSurface(target);
            try {
                document.execCommand('styleWithCSS', false, false);
                document.execCommand('fontSize', false, '7');
                document.execCommand('styleWithCSS', false, true);
            } catch (e) {
                return;
            }

            const applied = [];
            el.querySelectorAll('font[size="7"], span[style*="xxx-large"]').forEach((node) => {
                const span = document.createElement('span');
                span.setAttribute('style', node.getAttribute('style') || '');
                if (span.style.fontSize === 'xxx-large') span.style.fontSize = '';
                span.style[prop] = value;
                while (node.firstChild) span.appendChild(node.firstChild);
                node.replaceWith(span);
                applied.push(span);
            });

            // Swapping the nodes drops the selection; put it back over them so
            // picking a font and then a size both apply to the same words.
            if (applied.length) {
                const last = applied[applied.length - 1];
                const range = document.createRange();
                range.setStart(applied[0], 0);
                range.setEnd(last, last.childNodes.length);
                const sel = window.getSelection();
                sel.removeAllRanges();
                sel.addRange(range);
            }

            this.syncSurface(target);
            this.refreshState();
        },

        /** Typography of the focused surface, as a whole field. */
        setTypography(key, value) {
            if (!this.articleForm) return;
            this.articleForm[this.active + '_font_' + key] = String(value);
            this.refreshState();
        },

        /**
         * Selected words get an inline style, a caret inside the content
         * restyles its block, and anything else changes the typography of the
         * focused field as a whole.
         */
        applyTextStyle(prop, cssValue, typographyKey, typographyValue) {
            if (!this.richEnabled()) {
                this.setTypography(typographyKey, typographyValue);

                return;
            }

            if (this.hasSelection()) {
                this.applyInline(prop, cssValue);

                return;
            }

            const block = this.blockEnabled() ? this.blockOf(this.nodeAtCaret()) : null;
            if (block) {
                block.style[prop] = cssValue;
                this.syncSurface(this.active);
                this.refreshState();

                return;
            }

            this.setTypography(typographyKey, typographyValue);
        },

        setFamily(key) {
            this.menu = null;
            this.applyTextStyle('fontFamily', resolveFontFamilyCss(key), 'family', key);
        },

        setSize(px) {
            this.menu = null;
            const size = Math.min(200, Math.max(6, Number(px) || 0));
            if (!size) return;
            this.applyTextStyle('fontSize', size + 'px', 'size', String(size));
        },

        stepSize(delta) {
            this.setSize((Number(this.state.size) || 17) + delta);
        },

        toggleBold() {
            if (this.richEnabled()) {
                this.exec('bold');

                return;
            }
            const heavy = Number(this.articleForm?.[this.active + '_font_weight']) >= 600;
            this.setTypography('weight', heavy ? '400' : '700');
        },

        toggleItalic() {
            if (this.richEnabled()) {
                this.exec('italic');

                return;
            }
            const italic = this.articleForm?.[this.active + '_font_style'] === 'italic';
            this.setTypography('style', italic ? 'normal' : 'italic');
        },

        setColor(command, value) {
            this.menu = null;
            this.restoreRange();
            this.exec(command, value);
        },

        setAlign(dir) {
            const map = {
                left: 'justifyLeft',
                center: 'justifyCenter',
                right: 'justifyRight',
                justify: 'justifyFull',
            };
            this.exec(map[dir] || 'justifyLeft');
        },

        setSpacing(value) {
            this.menu = null;
            if (!this.blockEnabled()) return;
            this.eachSelectedBlock((block) => {
                block.style.lineHeight = value;
            });
            this.syncSurface('content');
        },

        toggleList(ordered) {
            this.exec(ordered ? 'insertOrderedList' : 'insertUnorderedList');
        },

        insertRule() {
            this.exec('insertHorizontalRule');
        },

        clearFormatting() {
            if (!this.richEnabled()) return;
            this.exec('removeFormat');
            if (!this.blockEnabled()) return;
            this.eachSelectedBlock((block) => block.removeAttribute('style'));
            this.setBlock('p');
        },

        /* ---------- links ---------- */

        openLink() {
            if (!this.richEnabled()) return;
            this.rememberRange();
            this.linkUrl = this.closest('a')?.getAttribute('href') || '';
            this.menu = this.menu === 'link' ? null : 'link';
            if (this.menu === 'link') {
                this.$nextTick(() => this.$refs.linkInput?.focus());
            }
        },

        applyLink() {
            const url = this.linkUrl.trim();
            this.menu = null;
            this.restoreRange();

            if (url === '') {
                this.exec('unlink');

                return;
            }

            const safe = /^(https?:\/\/|mailto:|tel:|\/|#)/i.test(url) ? url : 'https://' + url;
            this.exec('createLink', safe);

            const anchor = this.closest('a');
            if (anchor) {
                anchor.setAttribute('target', '_blank');
                anchor.setAttribute('rel', 'noopener noreferrer');
                this.syncSurface('content');
            }
        },

        removeLink() {
            this.menu = null;
            this.restoreRange();
            this.exec('unlink');
        },

        rememberRange() {
            const sel = window.getSelection();
            this.savedRange = this.isInside(this.surface(this.active)) && sel.rangeCount
                ? sel.getRangeAt(0).cloneRange()
                : null;
        },

        restoreRange() {
            if (!this.savedRange) return;
            const sel = window.getSelection();
            sel.removeAllRanges();
            sel.addRange(this.savedRange);
        },

        /* ---------- selection helpers ---------- */

        hasSelection() {
            const sel = window.getSelection();

            return this.isInside(this.surface(this.active)) && sel && !sel.isCollapsed;
        },

        nodeAtCaret() {
            const sel = window.getSelection();
            if (!sel || !sel.anchorNode) return null;

            let node = sel.anchorNode;
            if (node.nodeType === Node.ELEMENT_NODE) {
                node = node.childNodes[sel.anchorOffset] || node.lastChild || node;
            }

            return node.nodeType === Node.TEXT_NODE ? node.parentElement : node;
        },

        closest(selector) {
            const el = this.surface(this.active);
            const node = this.nodeAtCaret();
            if (!el || !node || !el.contains(node)) return null;
            const hit = node.closest(selector);

            return hit && el.contains(hit) ? hit : null;
        },

        blockOf(node) {
            const el = this.surface('content');
            let current = node;
            while (current && current !== el) {
                if (BLOCK_TAGS.includes(current.tagName?.toLowerCase())) return current;
                current = current.parentElement;
            }

            return null;
        },

        eachSelectedBlock(callback) {
            const el = this.surface('content');
            const sel = window.getSelection();
            if (!el) return;

            if (!sel || !sel.rangeCount || !this.isInside(el)) {
                Array.from(el.children).forEach(callback);

                return;
            }

            const range = sel.getRangeAt(0);
            const blocks = Array.from(el.querySelectorAll(BLOCK_TAGS.join(',')))
                .filter((block) => range.intersectsNode(block));

            if (blocks.length === 0) {
                const single = this.blockOf(this.nodeAtCaret());
                if (single) blocks.push(single);
            }

            blocks.forEach(callback);
        },

        /** Toolbar mirrors whatever the caret (or focused surface) sits on. */
        refreshState() {
            const form = this.articleForm || {};

            if (this.active !== 'content') {
                this.state.block = this.active === 'title'
                    ? (form.title_heading_level || 'h1')
                    : (form.excerpt_heading_level || 'p');
                if (this.state.block === 'normal') this.state.block = 'p';
                this.state.align = 'left';
                this.state.ul = false;
                this.state.ol = false;

                const caret = this.active === 'excerpt' ? this.nodeAtCaret() : null;
                const inside = caret && this.surface('excerpt')?.contains(caret);

                if (inside) {
                    // Excerpt carries inline formatting, so read it off the caret.
                    const style = window.getComputedStyle(caret);
                    this.state.family = fontFamilyKeyFromCss(style.fontFamily);
                    this.state.size = Math.round(parseFloat(style.fontSize) || 18);
                    try {
                        this.state.bold = document.queryCommandState('bold');
                        this.state.italic = document.queryCommandState('italic');
                        this.state.underline = document.queryCommandState('underline');
                        this.state.strike = document.queryCommandState('strikeThrough');
                    } catch (e) {
                        /* best effort */
                    }

                    return;
                }

                this.state.family = form[this.active + '_font_family'] || '';
                this.state.size = Number(form[this.active + '_font_size']) || 17;
                this.state.bold = Number(form[this.active + '_font_weight'] || 400) >= 600;
                this.state.italic = form[this.active + '_font_style'] === 'italic';
                this.state.underline = false;
                this.state.strike = false;

                return;
            }

            const el = this.surface('content');
            const node = this.nodeAtCaret();
            if (!el || !node || !el.contains(node)) {
                this.state.family = form.content_font_family || '';
                this.state.size = Number(form.content_font_size) || 17;

                return;
            }

            const style = window.getComputedStyle(node);
            const block = this.blockOf(node);

            this.state.block = block ? block.tagName.toLowerCase() : 'p';
            if (this.state.block === 'li' || this.state.block === 'div') this.state.block = 'p';
            this.state.size = Math.round(parseFloat(style.fontSize) || 17);
            this.state.family = fontFamilyKeyFromCss(style.fontFamily);
            this.state.align = ['center', 'right', 'justify'].includes(style.textAlign)
                ? style.textAlign
                : 'left';

            try {
                this.state.bold = document.queryCommandState('bold');
                this.state.italic = document.queryCommandState('italic');
                this.state.underline = document.queryCommandState('underline');
                this.state.strike = document.queryCommandState('strikeThrough');
                this.state.ul = document.queryCommandState('insertUnorderedList');
                this.state.ol = document.queryCommandState('insertOrderedList');
            } catch (e) {
                /* queryCommandState is best effort */
            }
        },

        /* ---------- input handling ---------- */

        onPaste(name, event) {
            const data = event.clipboardData;
            if (!data) return;

            const text = data.getData('text/plain') || '';

            if (name === 'title') {
                document.execCommand('insertText', false, text.replace(/\s+/g, ' ').trim());
                this.syncSurface(name);

                return;
            }

            const html = data.getData('text/html');

            if (name === 'excerpt') {
                // Keep the inline styling, drop the block structure.
                const inline = html
                    ? cleanPastedHtml(html).replace(/<\/?(p|div|h[1-6]|ul|ol|li|blockquote|pre)[^>]*>/gi, ' ')
                    : escapeHtml(text);
                this.focusSurface('excerpt');
                try {
                    document.execCommand('insertHTML', false, inline.replace(/\s+/g, ' ').trim());
                } catch (e) {
                    document.execCommand('insertText', false, text);
                }
                this.syncSurface('excerpt');

                return;
            }
            const payload = html
                ? cleanPastedHtml(html)
                : escapeHtml(text)
                      .split(/\n{2,}/)
                      .map((chunk) => '<p>' + chunk.replace(/\n/g, '<br>') + '</p>')
                      .join('');

            this.focusSurface('content');
            try {
                document.execCommand('insertHTML', false, payload);
            } catch (e) {
                document.execCommand('insertText', false, text);
            }
            this.syncSurface('content');
        },

        /** Docs shortcuts: Ctrl+B/I/U, Ctrl+K, Ctrl+Alt+0-6, Ctrl+\ */
        onKeydown(name, event) {
            if (event.key === 'Enter' && name !== 'content') {
                // Title and excerpt are single blocks; Enter moves on instead.
                event.preventDefault();
                this.focusSurface(name === 'title' ? 'excerpt' : 'content');

                return;
            }

            if (!event.ctrlKey && !event.metaKey) return;
            const key = event.key.toLowerCase();

            if (key === 'k' && this.richEnabled()) {
                event.preventDefault();
                this.openLink();

                return;
            }

            if (event.altKey && /^[0-6]$/.test(key) && this.blockEnabled()) {
                event.preventDefault();
                this.setBlock(key === '0' ? 'p' : 'h' + key);

                return;
            }

            if (key === '\\' && this.richEnabled()) {
                event.preventDefault();
                this.clearFormatting();

                return;
            }

            if (['b', 'i', 'u'].includes(key)) {
                if (!this.richEnabled()) {
                    event.preventDefault();
                    if (key === 'b') this.toggleBold();
                    if (key === 'i') this.toggleItalic();

                    return;
                }
                window.requestAnimationFrame(() => {
                    this.syncSurface('content');
                    this.refreshState();
                });
            }
        },

        /* ---------- chrome ---------- */

        blockLabelFor(tag) {
            if (tag === 'p') return this.active === 'excerpt' ? 'Normal' : 'Teks normal';
            if (tag === 'blockquote') return 'Kutipan';

            return 'Heading ' + tag.slice(1);
        },

        familyLabel() {
            const hit = FONT_FAMILY_OPTIONS.find((o) => o.value === this.state.family);

            return hit ? hit.label.replace(' (project)', '') : '—';
        },

        familyStyle(key) {
            return { fontFamily: resolveFontFamilyCss(key) };
        },

        setZoom(value) {
            this.menu = null;
            this.zoom = Number(value) || 100;
        },

        pageStyle() {
            return '--doc-zoom: ' + this.zoom / 100;
        },

        /** Article typography drives how the document looks while editing. */
        editorCss() {
            const form = this.articleForm || {};
            const rules = [];

            const surface = (name, fallbackFamily, fallbackWeight, fallbackSize) => {
                const family = resolveFontFamilyCss(form[name + '_font_family'] || fallbackFamily);
                const weight = form[name + '_font_weight'] || fallbackWeight;
                const style = form[name + '_font_style'] || 'normal';
                const size = form[name + '_font_size'] || fallbackSize;
                rules.push(
                    `#doc-${name} { font-family: ${family}; font-weight: ${weight}; font-style: ${style}; font-size: ${size}px; }`,
                );
            };

            surface('title', 'nohemi', '700', '40');
            surface('excerpt', 'parkinsans', '400', '18');
            surface('content', 'parkinsans', '400', '17');

            for (const level of HEADING_LEVELS) {
                const defaults = HEADING_FONT_DEFAULTS[level];
                const family = resolveFontFamilyCss(form[level + '_font_family'] || defaults.font_family);
                const weight = form[level + '_font_weight'] || defaults.font_weight;
                const style = form[level + '_font_style'] || defaults.font_style;
                const size = form[level + '_font_size'] || defaults.font_size;
                rules.push(
                    `#doc-content ${level} { font-family: ${family}; font-weight: ${weight}; font-style: ${style}; font-size: ${size}px; }`,
                );
            }

            return rules.join('\n').replace(/[<>]/g, '');
        },
    }));
}
