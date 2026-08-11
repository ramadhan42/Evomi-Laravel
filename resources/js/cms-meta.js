/**
 * CMS field order + labels (aligned with Next.js Evomi admin CMS).
 */

export function withFontFieldOrder(order, contentKeys) {
    const set = new Set(contentKeys);
    const out = [];
    for (const key of order) {
        out.push(key);
        if (set.has(key)) {
            out.push(
                `${key}_font_family`,
                `${key}_font_weight`,
                `${key}_font_style`,
                `${key}_fs_mobile`,
                `${key}_fs_desktop`,
                `${key}_max_lines`,
            );
        }
    }
    return out;
}

export const HERO_FIELD_ORDER = [
    'gap_horizontal_mobile', 'gap_horizontal_desktop', 'gap_vertical_mobile', 'gap_vertical_desktop',
    'headline_1', 'headline_1_color', 'headline_1_font_family', 'headline_1_font_weight', 'headline_1_font_style', 'headline_1_fs_mobile', 'headline_1_fs_desktop', 'headline_1_max_lines',
    'headline_2', 'headline_2_color', 'headline_2_font_family', 'headline_2_font_weight', 'headline_2_font_style', 'headline_2_fs_mobile', 'headline_2_fs_desktop', 'headline_2_max_lines',
    'headline_3', 'headline_3_color', 'headline_3_font_family', 'headline_3_font_weight', 'headline_3_font_style', 'headline_3_fs_mobile', 'headline_3_fs_desktop', 'headline_3_max_lines',
    'headline_4', 'headline_4_color', 'headline_4_font_family', 'headline_4_font_weight', 'headline_4_font_style', 'headline_4_fs_mobile', 'headline_4_fs_desktop', 'headline_4_max_lines',
    'headline_pos_top_mobile', 'headline_pos_top_desktop', 'headline_pos_left_mobile', 'headline_pos_left_desktop',
    'badge_left', 'badge_left_icon', 'badge_left_font_family', 'badge_left_font_weight', 'badge_left_font_style', 'badge_left_fs_mobile', 'badge_left_fs_desktop', 'badge_left_max_lines',
    'badge_left_icon_size_mobile', 'badge_left_icon_size_desktop', 'badge_left_left_mobile', 'badge_left_left_desktop', 'badge_left_top_mobile', 'badge_left_top_desktop',
    'badge_right', 'badge_right_icon', 'badge_right_font_family', 'badge_right_font_weight', 'badge_right_font_style', 'badge_right_fs_mobile', 'badge_right_fs_desktop', 'badge_right_max_lines',
    'badge_right_icon_size_mobile', 'badge_right_icon_size_desktop', 'badge_right_right_mobile', 'badge_right_right_desktop', 'badge_right_bottom_mobile', 'badge_right_bottom_desktop',
    'wave_left_icon', 'wave_right_icon',
    'wave_left_left_mobile', 'wave_left_left_desktop', 'wave_left_top_mobile', 'wave_left_top_desktop',
    'wave_right_right_mobile', 'wave_right_right_desktop', 'wave_right_top_mobile', 'wave_right_top_desktop',
    'product1_badge_label', 'product1_badge_icon', 'product1_image', 'product1_size_mobile', 'product1_size_desktop',
    'product1_left_mobile', 'product1_left_desktop', 'product1_top_mobile', 'product1_top_desktop', 'product1_right_mobile', 'product1_right_desktop', 'product1_rotate_mobile', 'product1_rotate_desktop',
    'product2_badge_label', 'product2_badge_icon', 'product2_image', 'product2_size_mobile', 'product2_size_desktop',
    'product2_left_mobile', 'product2_left_desktop', 'product2_top_mobile', 'product2_top_desktop', 'product2_right_mobile', 'product2_right_desktop', 'product2_rotate_mobile', 'product2_rotate_desktop',
    'product3_badge_label', 'product3_badge_icon', 'product3_image', 'product3_size_mobile', 'product3_size_desktop',
    'product3_left_mobile', 'product3_left_desktop', 'product3_top_mobile', 'product3_top_desktop', 'product3_right_mobile', 'product3_right_desktop', 'product3_rotate_mobile', 'product3_rotate_desktop',
    'product4_badge_label', 'product4_badge_icon', 'product4_image', 'product4_size_mobile', 'product4_size_desktop',
    'product4_left_mobile', 'product4_left_desktop', 'product4_top_mobile', 'product4_top_desktop', 'product4_right_mobile', 'product4_right_desktop', 'product4_rotate_mobile', 'product4_rotate_desktop',
    'marquee_text', 'marquee_font_family', 'marquee_font_weight', 'marquee_font_style', 'marquee_fs_mobile', 'marquee_fs_desktop', 'marquee_max_lines',
    'divider_icon_1', 'divider_icon_1_size_mobile', 'divider_icon_1_size_desktop',
    'divider_icon_2', 'divider_icon_2_size_mobile', 'divider_icon_2_size_desktop',
    'divider_icon_3', 'divider_icon_3_size_mobile', 'divider_icon_3_size_desktop',
    'divider_icon_4', 'divider_icon_4_size_mobile', 'divider_icon_4_size_desktop',
    'divider_bottom_mobile', 'divider_bottom_desktop',
];

export const SITE_FIELD_ORDER = withFontFieldOrder(
    ['browser_title', 'dashboard_browser_title', 'favicon'],
    ['browser_title', 'dashboard_browser_title'],
);

/** Keys that should never get font companions */
const NUMERIC_STYLE_KEY_RE =
    /(_fs_|_pos_|_left_|_top_|_right_|_bottom_|_size_|_gap_|_rotate_|_icon_size_|_max_lines$|^wave_|^gap_(horizontal|vertical)_)/;

function isFontMetaKey(key) {
    return /_font_(family|weight|style)$|_max_lines$/.test(key || '');
}

/**
 * Text-like CMS fields that need font family / weight / style pickers
 * (same pattern as Hero section).
 */
export function isTypographyBaseField(field) {
    const key = field?.key || '';
    if (!key) return false;
    if (field.type === 'image') return false;
    if (isFontMetaKey(key)) return false;
    if (key.endsWith('_color')) return false;
    if (NUMERIC_STYLE_KEY_RE.test(key)) return false;
    if (/^gap_(horizontal|vertical)_/.test(key)) return false;
    if (/^card_gap_|^card_label_gap_|^card_icon_size_/.test(key)) return false;
    if (
        key.endsWith('_icon') ||
        key.endsWith('_image') ||
        key === 'image' ||
        key === 'favicon' ||
        key === 'product_image' ||
        key === 'bg_image'
    ) {
        return false;
    }
    // URLs / raw contact values are not typography
    if (/_url$|_href$/i.test(key)) return false;
    if (/^(email|phone|address|whatsapp)_value$/.test(key)) return false;
    if (field.type === 'text') return true;
    // Prefer copy / label keys; still cover generic string UI labels
    if (
        /(headline|title|subtitle|tagline|label|name|desc|text|cta|marquee|badge|bulletin|help|message|question|answer|menu|browser_title|preparing|pay_|buy_|add_|share|discussion|stock|subtotal|shipping|promo|total|empty_|page_title|store_name|success_|failed_|address|phone|email|disclaimer|guarantee|chat|wishlist|qty|note|kurir|courier|header|footer|sidebar|common|auth|profile|kontak|belanja|checkout|nav|copyright|heading)/i.test(
            key,
        )
    ) {
        return true;
    }
    // Remaining plain strings (UI copy) also get font controls
    return field.type === 'string' || field.type == null || field.type === '';
}

/** Default mobile / desktop font-size pair for a typography base key */
export function defaultFsPair(key) {
    const k = key || '';
    // Product card copy — mengikuti Next FifthSection
    if (/^card\d+_title$/i.test(k)) return ['13px', '16px'];
    if (/^card\d+_desc$/i.test(k)) return ['10px', '11px'];
    if (/^card\d+_badge$/i.test(k)) return ['10px', '12px'];
    if (/^card\d+_price$/i.test(k)) return ['11px', '12px'];
    if (/headline|title_[12]|^title$|page_title/i.test(k)) return ['24px', '36px'];
    if (/subtitle|tagline|description|^desc$|_desc$/i.test(k)) return ['14px', '18px'];
    if (/badge|label|marquee|cta|name|price|bulletin|menu|help/i.test(k)) return ['12px', '16px'];
    if (/browser_title|dashboard_browser/i.test(k)) return ['14px', '14px'];
    return ['16px', '18px'];
}

/**
 * Default font weight for a typography base key.
 * Nohemi only ships 400 / 600 / 700, so "medium" copy maps to 600.
 */
export function defaultFontWeight(key) {
    const k = key || '';
    if (/headline|title|badge|price|cta|^name$|_name$/i.test(k)) return '700';
    if (/label|marquee|menu|^tab|question/i.test(k)) return '600';
    return '400';
}

/** Default max Enter-lines (1–3) for a typography base key */
export function defaultMaxLines(key) {
    const k = key || '';
    if (/_desc$|description|subtitle|answer|content|excerpt|body|message/i.test(k)) return '3';
    if (/name|label|title|headline|tagline/i.test(k)) return '2';
    if (/cta|badge|price|marquee|browser_title|dashboard_browser/i.test(k)) return '1';
    return '2';
}

/**
 * Inject missing font family / weight / style / size / max_lines after text fields.
 */
export function ensureFontCompanionFields(fields) {
    const list = Array.isArray(fields) ? fields : [];
    const bySection = new Map();
    for (const f of list) {
        const s = f.section || 'general';
        if (!bySection.has(s)) bySection.set(s, new Set());
        bySection.get(s).add(f.key);
    }
    const extras = [];
    for (const f of list) {
        if (!isTypographyBaseField(f)) continue;
        const keys = bySection.get(f.section || 'general');
        const [fsM, fsD] = defaultFsPair(f.key);
        const companions = [
            ['_font_family', 'nohemi'],
            ['_font_weight', defaultFontWeight(f.key)],
            ['_font_style', 'normal'],
            ['_fs_mobile', fsM],
            ['_fs_desktop', fsD],
            ['_max_lines', defaultMaxLines(f.key)],
        ];
        for (const [suffix, def] of companions) {
            const nk = `${f.key}${suffix}`;
            if (keys.has(nk)) continue;
            keys.add(nk);
            extras.push({
                section: f.section || 'general',
                key: nk,
                type: 'string',
                value: def,
            });
        }
    }
    return extras.length ? [...list, ...extras] : list;
}

/** Spacing controls shared by every beranda section */
export const SECTION_SPACING_KEYS = [
    'gap_horizontal_mobile',
    'gap_horizontal_desktop',
    'gap_vertical_mobile',
    'gap_vertical_desktop',
];

const SECTION_SPACING_DEFAULTS = {
    hero: { gap_horizontal_mobile: '16px', gap_horizontal_desktop: '24px', gap_vertical_mobile: '8px', gap_vertical_desktop: '12px' },
    second: { gap_horizontal_mobile: '16px', gap_horizontal_desktop: '32px', gap_vertical_mobile: '40px', gap_vertical_desktop: '56px' },
    third: { gap_horizontal_mobile: '40px', gap_horizontal_desktop: '56px', gap_vertical_mobile: '40px', gap_vertical_desktop: '56px' },
    fourth: { gap_horizontal_mobile: '16px', gap_horizontal_desktop: '24px', gap_vertical_mobile: '16px', gap_vertical_desktop: '24px' },
    fifth: { gap_horizontal_mobile: '12px', gap_horizontal_desktop: '24px', gap_vertical_mobile: '12px', gap_vertical_desktop: '24px' },
    sixth: { gap_horizontal_mobile: '16px', gap_horizontal_desktop: '24px', gap_vertical_mobile: '12px', gap_vertical_desktop: '20px' },
    seventh: { gap_horizontal_mobile: '16px', gap_horizontal_desktop: '24px', gap_vertical_mobile: '24px', gap_vertical_desktop: '32px' },
};

/**
 * Inject gap_horizontal / gap_vertical (mobile + desktop) for each beranda section present.
 */
export function ensureSectionSpacingFields(fields, page = 'beranda') {
    if (page !== 'beranda') return Array.isArray(fields) ? fields : [];
    const list = Array.isArray(fields) ? [...fields] : [];
    const bySection = new Map();
    for (const f of list) {
        const s = f.section || 'general';
        if (!bySection.has(s)) bySection.set(s, new Set());
        bySection.get(s).add(f.key);
    }
    const sections = new Set([
        ...Object.keys(SECTION_SPACING_DEFAULTS),
        ...[...bySection.keys()].filter((s) => SECTION_SPACING_DEFAULTS[s]),
    ]);
    const extras = [];
    for (const section of sections) {
        const keys = bySection.get(section) || new Set();
        if (!bySection.has(section)) bySection.set(section, keys);
        const defs = SECTION_SPACING_DEFAULTS[section] || SECTION_SPACING_DEFAULTS.second;
        for (const key of SECTION_SPACING_KEYS) {
            if (keys.has(key)) continue;
            // Prefer legacy second card_gap_horizontal as starting value
            let value = defs[key];
            if (section === 'second' && key === 'gap_horizontal_mobile') {
                const legacy = list.find(
                    (f) => f.section === 'second' && f.key === 'card_gap_horizontal_mobile',
                );
                if (legacy?.value) value = legacy.value;
            }
            if (section === 'second' && key === 'gap_horizontal_desktop') {
                const legacy = list.find(
                    (f) => f.section === 'second' && f.key === 'card_gap_horizontal_desktop',
                );
                if (legacy?.value) value = legacy.value;
            }
            keys.add(key);
            extras.push({ section, key, type: 'string', value });
        }
    }
    return extras.length ? [...list, ...extras] : list;
}

/** Default typography fields for FAQ / Kuis Hasil special tabs */
export function defaultFaqTypographyFields() {
    return ensureFontCompanionFields([
        { section: 'faq', key: 'title', type: 'string', value: 'Pertanyaan yang Sering Diajukan' },
        { section: 'faq', key: 'subtitle', type: 'text', value: 'Temukan jawaban seputar pesanan, pengiriman, dan aroma Evomi.' },
    ]);
}

export function defaultKuisHasilTypographyFields(personalityKeys = []) {
    const base = [
        { section: 'common', key: 'title', type: 'string', value: 'Hasil Kuis' },
        { section: 'common', key: 'description', type: 'text', value: 'Deskripsi hasil' },
        { section: 'common', key: 'cta_label', type: 'string', value: 'Lihat Produk' },
    ];
    for (const key of personalityKeys) {
        base.push(
            { section: key, key: 'title', type: 'string', value: '' },
            { section: key, key: 'description', type: 'text', value: '' },
        );
    }
    return ensureFontCompanionFields(base);
}

export const SECOND_FIELD_ORDER = withFontFieldOrder(
    [
        'gap_horizontal_mobile', 'gap_horizontal_desktop', 'gap_vertical_mobile', 'gap_vertical_desktop',
        'headline_1', 'headline_2', 'headline_3',
        'card_icon_size_mobile', 'card_icon_size_desktop',
        'card_label_gap_mobile', 'card_label_gap_desktop',
        'card_gap_horizontal_mobile', 'card_gap_horizontal_desktop',
        'card1_name', 'card1_title', 'card1_image',
        'card2_name', 'card2_title', 'card2_image',
        'card3_name', 'card3_title', 'card3_image',
        'card4_name', 'card4_title', 'card4_image',
        'cta_label',
    ],
    ['headline_1', 'headline_2', 'headline_3', 'card1_name', 'card2_name', 'card3_name', 'card4_name', 'cta_label'],
);

export const THIRD_FIELD_ORDER = withFontFieldOrder(
    [
        'gap_horizontal_mobile', 'gap_horizontal_desktop', 'gap_vertical_mobile', 'gap_vertical_desktop',
        'title_1', 'title_2',
        'card1_title', 'card1_desc', 'card1_icon',
        'card2_title', 'card2_desc', 'card2_icon',
        'card3_title', 'card3_desc', 'card3_icon',
        'tagline',
    ],
    ['title_1', 'title_2', 'card1_title', 'card1_desc', 'card2_title', 'card2_desc', 'card3_title', 'card3_desc', 'tagline'],
);

/** Base content defaults so missing DB keys still appear in CMS editors (non-beranda / fallback).
 * Beranda copy+typography defaults are owned by PHP `BerandaCmsDefaults` via the admin API.
 */
export const BERANDA_CONTENT_DEFAULTS = {
    hero: {
        headline_1: 'Temukan',
        headline_2: 'karakter',
        headline_3: 'aromamu',
        headline_4: 'di Evomi',
        badge_left: 'Eau de Parfum',
        badge_right: 'Recycle Bottle Cap',
        marquee_text: 'Every Version of Me',
    },
    second: {
        headline_1: 'Kenalan sama',
        headline_2: 'karakter ',
        headline_3: 'kita yuk!',
        cta_label: 'Lihat Semua Karakter',
        card1_name: "Purpose\nPrestige",
        card2_name: "Peaceful\nCalm",
        card3_name: "Rebel\nBrave",
        card4_name: "Sweet\nShy",
    },
    third: {
        title_1: 'Brand',
        title_2: 'Value',
        tagline: 'Every Version of Me',
        card1_title: "Self\nAwareness",
        card1_desc:
            'Setiap aroma dirancang untuk merepresentasikan versi diri, emosi, dan karakter manusia yang berbeda, sehingga parfum menjadi medium ekspresi personal, bukan sekadar wewangian.',
        card2_title: "Environment\nFriendly",
        card2_desc:
            'Mengusung kepedulian terhadap lingkungan melalui pemanfaatan daur ulang tutup botol plastik menjadi bagian dari identitas produk, sebagai bentuk kontribusi kecil dalam mengurangi limbah plastik sekaligus menghadirkan nilai sustainability.',
        card3_title: "Playful Design\nConcept",
        card3_desc:
            'Dikemas dengan pendekatan visual yang playful, ekspresif, dan dekat dengan generasi muda agar pengalaman menggunakan parfum terasa lebih personal dan menyenangkan.',
    },
    fifth: {
        title_1: 'Khas',
        title_2: 'Evomi',
        subtitle: 'Empat karakter aroma yang mewakili sisi berbeda dari dirimu.',
        cta_label: 'Lihat Koleksi',
        card1_badge: 'Optimis',
        card1_title: 'Purpose Prestige',
        card1_desc: 'Aroma yang merefleksikan ketenangan dan kejelasan tujuan.',
        card1_price: 'Rp189.000',
        card2_badge: 'Damai',
        card2_title: 'Peaceful Calm',
        card2_desc: 'Aroma menenangkan yang menyatu dengan diri.',
        card2_price: 'Rp199.000',
        card3_badge: 'Berani',
        card3_title: 'Rebel Brave',
        card3_desc: 'Keberanian dan semangat untuk mengekspresikan diri.',
        card3_price: 'Rp179.000',
        card4_badge: 'Manis',
        card4_title: 'Sweet Shy',
        card4_desc: 'Aroma menenangkan yang menyatu dengan diri.',
        card4_price: 'Rp189.000',
    },
    sixth: {
        title_1: 'Packaging',
        title_2: 'Reveal',
        marquee_text: 'Every Version of Me',
        label1: "Purpose\nPrestige",
        label2: "Rebel\nBrave",
        label3: "Peaceful\nCalm",
        label4: "Sweet\nShy",
    },
    seventh: {
        headline_1: 'Temukan',
        headline_2: 'aromamu',
        headline_3: 'dengan',
        headline_4: 'bermain',
        headline_5: 'kuis',
        cta_label: 'Mulai Kuis',
    },
};

/**
 * Inject missing beranda content keys (e.g. third.tagline) so they show in CMS.
 */
export function ensureBerandaContentFields(fields, page = 'beranda') {
    if (page !== 'beranda') return Array.isArray(fields) ? fields : [];
    const list = Array.isArray(fields) ? [...fields] : [];
    const bySection = new Map();
    for (const f of list) {
        const s = f.section || 'general';
        if (!bySection.has(s)) bySection.set(s, new Set());
        bySection.get(s).add(f.key);
    }
    const extras = [];
    for (const [section, defaults] of Object.entries(BERANDA_CONTENT_DEFAULTS)) {
        const keys = bySection.get(section) || new Set();
        if (!bySection.has(section)) bySection.set(section, keys);
        for (const [key, value] of Object.entries(defaults)) {
            if (keys.has(key)) continue;
            keys.add(key);
            extras.push({
                section,
                key,
                type: /desc|subtitle|tagline/i.test(key) ? 'text' : 'string',
                value,
            });
        }
    }
    return extras.length ? [...list, ...extras] : list;
}

export const FIFTH_FIELD_ORDER = withFontFieldOrder(
    [
        'gap_horizontal_mobile', 'gap_horizontal_desktop', 'gap_vertical_mobile', 'gap_vertical_desktop',
        'title_1', 'title_2', 'subtitle',
        'card1_badge', 'card1_title', 'card1_desc', 'card1_price', 'card1_image',
        'card2_badge', 'card2_title', 'card2_desc', 'card2_price', 'card2_image',
        'card3_badge', 'card3_title', 'card3_desc', 'card3_price', 'card3_image',
        'card4_badge', 'card4_title', 'card4_desc', 'card4_price', 'card4_image',
        'cta_label',
    ],
    [
        'title_1', 'title_2', 'subtitle',
        'card1_badge', 'card1_title', 'card1_desc', 'card1_price',
        'card2_badge', 'card2_title', 'card2_desc', 'card2_price',
        'card3_badge', 'card3_title', 'card3_desc', 'card3_price',
        'card4_badge', 'card4_title', 'card4_desc', 'card4_price',
        'cta_label',
    ],
);

export const SIXTH_FIELD_ORDER = withFontFieldOrder(
    [
        'gap_horizontal_mobile', 'gap_horizontal_desktop', 'gap_vertical_mobile', 'gap_vertical_desktop',
        'title_1', 'title_2', 'label1', 'label2', 'label3', 'label4', 'marquee_text', 'image',
    ],
    ['title_1', 'title_2', 'label1', 'label2', 'label3', 'label4', 'marquee_text'],
);

const SEVENTH_BASE = [
    'gap_horizontal_mobile', 'gap_horizontal_desktop', 'gap_vertical_mobile', 'gap_vertical_desktop',
    'headline_1', 'headline_2', 'headline_3', 'headline_4', 'headline_5',
    'en_l1', 'en_l2', 'en_l3', 'en_l4', 'cta_label', 'product_image',
    'label1_text', 'label1', 'label1_title', 'label1_color', 'label1_fs_mobile', 'label1_fs_desktop',
    'label1_left_mobile', 'label1_left_desktop', 'label1_right_mobile', 'label1_right_desktop',
    'label1_top_mobile', 'label1_top_desktop', 'label1_bottom_mobile', 'label1_bottom_desktop',
    'label2_text', 'label2', 'label2_title', 'label2_color', 'label2_fs_mobile', 'label2_fs_desktop',
    'label2_left_mobile', 'label2_left_desktop', 'label2_right_mobile', 'label2_right_desktop',
    'label2_top_mobile', 'label2_top_desktop', 'label2_bottom_mobile', 'label2_bottom_desktop',
    'label3_text', 'label3', 'label3_title', 'label3_color', 'label3_fs_mobile', 'label3_fs_desktop',
    'label3_left_mobile', 'label3_left_desktop', 'label3_right_mobile', 'label3_right_desktop',
    'label3_top_mobile', 'label3_top_desktop', 'label3_bottom_mobile', 'label3_bottom_desktop',
    'label4_text', 'label4', 'label4_title', 'label4_color', 'label4_fs_mobile', 'label4_fs_desktop',
    'label4_left_mobile', 'label4_left_desktop', 'label4_right_mobile', 'label4_right_desktop',
    'label4_top_mobile', 'label4_top_desktop', 'label4_bottom_mobile', 'label4_bottom_desktop',
];

export const SEVENTH_FIELD_ORDER = withFontFieldOrder(SEVENTH_BASE, [
    'headline_1', 'headline_2', 'headline_3', 'headline_4', 'headline_5',
    'en_l1', 'en_l2', 'en_l3', 'en_l4', 'cta_label',
    'label1_text', 'label1', 'label1_title',
    'label2_text', 'label2', 'label2_title',
    'label3_text', 'label3', 'label3_title',
    'label4_text', 'label4', 'label4_title',
]);

/** Section display order: Hero → 1 → 2 → 3 → … */
export const SECTION_ORDER = {
    beranda: ['hero', 'second', 'third', 'fourth', 'fifth', 'sixth', 'seventh'],
    belanja: ['hero', 'list', 'cards', 'badges', 'images'],
    belanja_details: ['labels', 'disclaimer', 'guarantee', 'chat', 'content', 'images', 'badges'],
    checkout: ['header', 'sections', 'labels', 'messages', 'images'],
    kontak: ['header', 'info'],
    navfooter: ['site', 'menu', 'bulletin', 'help', 'social', 'legal'],
    ui: ['common', 'nav', 'auth', 'belanja', 'checkout', 'kontak', 'faq', 'kuis', 'profile'],
    admin: ['common', 'sidebar', 'products', 'cms', 'home'],
    faq: ['faq', 'header', 'list'],
    kuis_hasil: [],
};

export const SECTION_LABELS = {
    hero: 'Hero',
    second: '1 — Karakter',
    third: '2 — Brand Values',
    fourth: '3 — Thanks Card',
    fifth: '4 — Produk',
    sixth: '5 — Packaging',
    seventh: '6 — CTA Kuis',
    header: 'Header',
    info: 'Info Kontak',
    menu: 'Menu',
    site: 'Judul Tab Browser',
    bulletin: 'Buletin',
    help: 'Bantuan',
    social: 'Sosial',
    legal: 'Legal',
    common: 'Umum',
    nav: 'Navbar Extra',
    auth: 'Auth',
    list: 'Daftar Produk',
    cards: 'Gambar Card Produk',
    badges: 'Badge Karakter',
    labels: 'Label UI',
    guarantee: 'Jaminan Produk',
    chat: 'Chat',
    content: 'Konten',
    disclaimer: 'Disclaimer',
    images: 'Gambar & Dekorasi',
    sections: 'Section',
    messages: 'Pesan',
    profile: 'Profile',
    kuis: 'Kuis',
    faq: 'FAQ',
    kontak: 'Kontak Form',
    belanja: 'Belanja',
    checkout: 'Checkout',
    sidebar: 'Sidebar',
    products: 'Products',
    cms: 'CMS',
    home: 'Home',
};

const FIELD_LABELS = {
    browser_title: 'Judul Tab Frontend',
    dashboard_browser_title: 'Judul Tab Dashboard',
    favicon: 'Favicon (Icon Tab)',
    headline_1: 'Headline 1',
    headline_1_color: 'Warna Headline 1',
    headline_1_font_family: 'Headline 1 — Font Family',
    headline_1_font_weight: 'Headline 1 — Font Weight',
    headline_1_font_style: 'Headline 1 — Font Style',
    headline_1_fs_mobile: 'Headline 1 — Font Size Mobile',
    headline_1_fs_desktop: 'Headline 1 — Font Size Desktop',
    headline_2: 'Headline 2',
    headline_2_color: 'Warna Headline 2',
    headline_2_font_family: 'Headline 2 — Font Family',
    headline_2_font_weight: 'Headline 2 — Font Weight',
    headline_2_font_style: 'Headline 2 — Font Style',
    headline_2_fs_mobile: 'Headline 2 — Font Size Mobile',
    headline_2_fs_desktop: 'Headline 2 — Font Size Desktop',
    headline_3: 'Headline 3',
    headline_3_color: 'Warna Headline 3',
    headline_3_font_family: 'Headline 3 — Font Family',
    headline_3_font_weight: 'Headline 3 — Font Weight',
    headline_3_font_style: 'Headline 3 — Font Style',
    headline_3_fs_mobile: 'Headline 3 — Font Size Mobile',
    headline_3_fs_desktop: 'Headline 3 — Font Size Desktop',
    headline_4: 'Headline 4',
    headline_4_color: 'Warna Headline 4',
    headline_4_font_family: 'Headline 4 — Font Family',
    headline_4_font_weight: 'Headline 4 — Font Weight',
    headline_4_font_style: 'Headline 4 — Font Style',
    headline_4_fs_mobile: 'Headline 4 — Font Size Mobile',
    headline_4_fs_desktop: 'Headline 4 — Font Size Desktop',
    headline_5: 'Headline 5',
    title_1: 'Judul Bagian 1',
    title_2: 'Judul Bagian 2',
    subtitle: 'Subtitle',
    star_icon: 'Ikon Bintang Judul',
    deco_purpose: 'Maskot Purpose (kiri atas)',
    deco_peaceful: 'Maskot Peaceful (kanan atas)',
    deco_rebel: 'Maskot Rebel (kiri bawah)',
    deco_sweet: 'Maskot Sweet (kanan bawah)',
    card_purpose_image: 'Gambar Card — Purpose Prestige',
    card_peaceful_image: 'Gambar Card — Peaceful Calm',
    card_rebel_image: 'Gambar Card — Rebel Brave',
    card_sweet_image: 'Gambar Card — Sweet Shy',
    card_purpose_title: 'Judul Card — Purpose',
    card_peaceful_title: 'Judul Card — Peaceful',
    card_rebel_title: 'Judul Card — Rebel',
    card_sweet_title: 'Judul Card — Sweet',
    card_purpose_desc: 'Deskripsi Card — Purpose',
    card_peaceful_desc: 'Deskripsi Card — Peaceful',
    card_rebel_desc: 'Deskripsi Card — Rebel',
    card_sweet_desc: 'Deskripsi Card — Sweet',
    card_purpose_price: 'Harga Card — Purpose',
    card_peaceful_price: 'Harga Card — Peaceful',
    card_rebel_price: 'Harga Card — Rebel',
    card_sweet_price: 'Harga Card — Sweet',
    card_image_width_mobile: 'Lebar Gambar Card — Mobile',
    card_image_width_desktop: 'Lebar Gambar Card — Desktop',
    card_image_rotate_mobile: 'Rotasi Gambar Card — Mobile',
    card_image_rotate_desktop: 'Rotasi Gambar Card — Desktop',
    card_image_top_mobile: 'Posisi Vertikal Gambar — Mobile',
    card_image_top_desktop: 'Posisi Vertikal Gambar — Desktop',
    empty_title: 'Judul Saat Produk Kosong',
    empty_hint: 'Petunjuk Saat Produk Kosong',
    see_detail: 'Teks Lihat Detail',
    no_image: 'Teks Tanpa Gambar',
    purpose: 'Badge Purpose',
    peaceful: 'Badge Peaceful',
    rebel: 'Badge Rebel',
    sweet: 'Badge Sweet',
    tagline: 'Tagline (Every Version of Me)',
    cta_label: 'Teks Tombol CTA',
    marquee_text: 'Teks Divider Marquee',
    image: 'Gambar',
    product_image: 'Gambar Produk',
    gap_horizontal_mobile: 'Jarak Horizontal Antar Div — Mobile',
    gap_horizontal_desktop: 'Jarak Horizontal Antar Div — Desktop',
    gap_vertical_mobile: 'Jarak Vertikal Antar Div — Mobile',
    gap_vertical_desktop: 'Jarak Vertikal Antar Div — Desktop',
    card_gap_horizontal_mobile: 'Jarak Horizontal Antar Kartu — Mobile (legacy)',
    card_gap_horizontal_desktop: 'Jarak Horizontal Antar Kartu — Desktop (legacy)',
    card_label_gap_mobile: 'Jarak Label Kartu — Mobile',
    card_label_gap_desktop: 'Jarak Label Kartu — Desktop',
};

const SUFFIX_PRIORITY = [
    '',
    '_color',
    '_font_family',
    '_font_weight',
    '_font_style',
    '_fs_mobile',
    '_fs_desktop',
    '_max_lines',
    '_icon',
    '_image',
    '_badge',
    '_badge_label',
    '_badge_icon',
    '_name',
    '_title',
    '_desc',
    '_price',
    '_text',
    '_icon_size_mobile',
    '_icon_size_desktop',
    '_size_mobile',
    '_size_desktop',
    '_left_mobile',
    '_left_desktop',
    '_top_mobile',
    '_top_desktop',
    '_right_mobile',
    '_right_desktop',
    '_bottom_mobile',
    '_bottom_desktop',
    '_rotate_mobile',
    '_rotate_desktop',
];

export const BELANJA_CARDS_FIELD_ORDER = [
    'card_purpose_image',
    'card_purpose_title',
    'card_purpose_desc',
    'card_purpose_price',
    'card_peaceful_image',
    'card_peaceful_title',
    'card_peaceful_desc',
    'card_peaceful_price',
    'card_rebel_image',
    'card_rebel_title',
    'card_rebel_desc',
    'card_rebel_price',
    'card_sweet_image',
    'card_sweet_title',
    'card_sweet_desc',
    'card_sweet_price',
    'card_image_width_mobile',
    'card_image_width_desktop',
    'card_image_rotate_mobile',
    'card_image_rotate_desktop',
    'card_image_top_mobile',
    'card_image_top_desktop',
];

function fieldOrderForSection(section) {
    switch (section) {
        case 'hero':
            return HERO_FIELD_ORDER;
        case 'second':
            return SECOND_FIELD_ORDER;
        case 'third':
            return THIRD_FIELD_ORDER;
        case 'fourth':
            return [
                'gap_horizontal_mobile',
                'gap_horizontal_desktop',
                'gap_vertical_mobile',
                'gap_vertical_desktop',
                'image',
            ];
        case 'fifth':
            return FIFTH_FIELD_ORDER;
        case 'sixth':
            return SIXTH_FIELD_ORDER;
        case 'seventh':
            return SEVENTH_FIELD_ORDER;
        case 'site':
            return SITE_FIELD_ORDER;
        case 'cards':
            return BELANJA_CARDS_FIELD_ORDER;
        default:
            return null;
    }
}

function naturalFieldRank(key) {
    for (let i = SUFFIX_PRIORITY.length - 1; i >= 0; i -= 1) {
        const suf = SUFFIX_PRIORITY[i];
        if (suf && key.endsWith(suf)) {
            return { base: key.slice(0, -suf.length), sufIdx: i };
        }
    }
    return { base: key, sufIdx: 0 };
}

export function sortSectionFields(section, fields) {
    const order = fieldOrderForSection(section);
    if (order) {
        return [...fields].sort((a, b) => {
            const ai = order.indexOf(a.key);
            const bi = order.indexOf(b.key);
            return (ai === -1 ? 9999 : ai) - (bi === -1 ? 9999 : bi) || a.key.localeCompare(b.key);
        });
    }
    return [...fields].sort((a, b) => {
        const ra = naturalFieldRank(a.key);
        const rb = naturalFieldRank(b.key);
        return ra.base.localeCompare(rb.base) || ra.sufIdx - rb.sufIdx || a.key.localeCompare(b.key);
    });
}

export function fieldLabel(key) {
    if (FIELD_LABELS[key]) return FIELD_LABELS[key];
    if (key.endsWith('_font_family')) {
        const base = key.replace(/_font_family$/, '');
        return `${FIELD_LABELS[base] || base.replace(/_/g, ' ')} — Font Family`;
    }
    if (key.endsWith('_font_weight')) {
        const base = key.replace(/_font_weight$/, '');
        return `${FIELD_LABELS[base] || base.replace(/_/g, ' ')} — Font Weight`;
    }
    if (key.endsWith('_font_style')) {
        const base = key.replace(/_font_style$/, '');
        return `${FIELD_LABELS[base] || base.replace(/_/g, ' ')} — Font Style`;
    }
    if (key.endsWith('_fs_mobile')) {
        const base = key.replace(/_fs_mobile$/, '');
        return `${FIELD_LABELS[base] || base.replace(/_/g, ' ')} — Font Size Mobile`;
    }
    if (key.endsWith('_fs_desktop')) {
        const base = key.replace(/_fs_desktop$/, '');
        return `${FIELD_LABELS[base] || base.replace(/_/g, ' ')} — Font Size Desktop`;
    }
    if (key.endsWith('_max_lines')) {
        const base = key.replace(/_max_lines$/, '');
        return `${FIELD_LABELS[base] || base.replace(/_/g, ' ')} — Max Baris (Enter)`;
    }
    if (key.endsWith('_color')) {
        const base = key.replace(/_color$/, '');
        return `Warna ${FIELD_LABELS[base] || base.replace(/_/g, ' ')}`;
    }
    return key.replace(/_/g, ' ');
}
