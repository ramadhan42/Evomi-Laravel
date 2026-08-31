/*
 * Shared font catalog for the admin CMS (article typography, doc editor).
 * Mirrors App\Support\CmsStorefront::fontFamilyCss() on the PHP side.
 */

export const FONT_FAMILY_OPTIONS = [
    { value: 'nohemi', label: 'Nohemi (project)', group: 'project' },
    { value: 'parkinsans', label: 'Parkinsans (project)', group: 'project' },
    { value: 'syne', label: 'Syne (project)', group: 'project' },
    { value: 'heavy', label: '8-Heavy (project)', group: 'project' },
    { value: 'arial', label: 'Arial', group: 'system' },
    { value: 'helvetica', label: 'Helvetica', group: 'system' },
    { value: 'georgia', label: 'Georgia', group: 'system' },
    { value: 'times', label: 'Times New Roman', group: 'system' },
    { value: 'verdana', label: 'Verdana', group: 'system' },
    { value: 'tahoma', label: 'Tahoma', group: 'system' },
    { value: 'courier', label: 'Courier New', group: 'system' },
    { value: 'system', label: 'System UI', group: 'system' },
];

export const FONT_FAMILY_CSS = {
    nohemi: "var(--font-nohemi), 'Nohemi', sans-serif",
    parkinsans: "var(--font-parkinsans), 'Parkinsans', sans-serif",
    syne: "var(--font-syne), 'Syne', sans-serif",
    heavy: "var(--font-heavy), '8-Heavy', sans-serif",
    arial: 'Arial, Helvetica, sans-serif',
    helvetica: 'Helvetica, Arial, sans-serif',
    georgia: "Georgia, 'Times New Roman', serif",
    times: "'Times New Roman', Times, serif",
    verdana: 'Verdana, Geneva, sans-serif',
    tahoma: 'Tahoma, Geneva, sans-serif',
    courier: "'Courier New', Courier, monospace",
    system: "system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif",
};

/* Per-level article heading defaults — mirrors App\Support\ArticleContent::defaults(). */
export const HEADING_FONT_DEFAULTS = {
    h1: { font_family: 'nohemi', font_weight: '700', font_style: 'normal', font_size: '32' },
    h2: { font_family: 'nohemi', font_weight: '700', font_style: 'normal', font_size: '28' },
    h3: { font_family: 'nohemi', font_weight: '600', font_style: 'normal', font_size: '22' },
    h4: { font_family: 'parkinsans', font_weight: '600', font_style: 'normal', font_size: '20' },
    h5: { font_family: 'parkinsans', font_weight: '600', font_style: 'normal', font_size: '18' },
    h6: { font_family: 'parkinsans', font_weight: '600', font_style: 'normal', font_size: '16' },
};

export const HEADING_LEVELS = Object.keys(HEADING_FONT_DEFAULTS);

export function resolveFontFamilyCss(raw) {
    const key = String(raw || '')
        .trim()
        .toLowerCase();
    if (FONT_FAMILY_CSS[key]) return FONT_FAMILY_CSS[key];
    if (key.includes(',') || key.startsWith('var(')) return String(raw).trim();
    return FONT_FAMILY_CSS.nohemi;
}

/* Generic families appear in every stack, so they identify nothing. */
const GENERIC_FAMILIES = ['sans-serif', 'serif', 'monospace', 'cursive', 'fantasy', 'system-ui'];

/**
 * Best-effort reverse lookup: a browser's computed font-family back to a
 * catalog key, so the editor toolbar can show what the caret sits on.
 */
export function fontFamilyKeyFromCss(computed) {
    const haystack = String(computed || '').toLowerCase();
    if (haystack === '') return '';

    for (const [key, css] of Object.entries(FONT_FAMILY_CSS)) {
        const names = css
            .replace(/var\([^)]*\)/g, '')
            .split(',')
            .map((n) => n.trim().replace(/^['"]|['"]$/g, '').toLowerCase())
            .filter((n) => n && !GENERIC_FAMILIES.includes(n));
        if (names.some((n) => haystack.includes(n))) return key;
    }

    return '';
}
