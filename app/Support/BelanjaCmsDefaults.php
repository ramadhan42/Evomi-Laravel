<?php

namespace App\Support;

/**
 * Canonical Belanja CMS defaults matching the current storefront UI.
 * Used by Dashboard CMS merge and ShopCmsSeeder. Existing DB rows always win.
 */
final class BelanjaCmsDefaults
{
    /**
     * Flat list of default fields: [section, key, type, value].
     *
     * @return list<array{0:string,1:string,2:string,3:string}>
     */
    public static function rows(): array
    {
        return [
            // Hero
            ['hero', 'headline_1', 'string', 'Koleksi'],
            ['hero', 'headline_1_color', 'string', '#5EA14A'],
            ...self::font('hero', 'headline_1', '600', '26px', '32px', '1'),
            ['hero', 'headline_2', 'string', 'Aroma'],
            ['hero', 'headline_2_color', 'string', '#DD74A5'],
            ...self::font('hero', 'headline_2', '600', '26px', '32px', '1'),
            ['hero', 'headline_3', 'string', 'Evomi'],
            ['hero', 'headline_3_color', 'string', '#1172BA'],
            ...self::font('hero', 'headline_3', '600', '26px', '32px', '1'),
            ['hero', 'star_icon', 'image', '/src/images/belanja/deco/title-star.svg'],

            // List empty states
            ['list', 'empty_title', 'string', 'Belum ada produk'],
            ...self::font('list', 'empty_title', '600', '18px', '18px', '2'),
            ['list', 'empty_hint', 'string', 'Produk akan muncul di sini setelah tersedia.'],
            ...self::font('list', 'empty_hint', '400', '14px', '14px', '3', 'parkinsans'),
            ['list', 'see_detail', 'string', 'Lihat Detail'],
            ['list', 'no_image', 'string', 'Tidak ada gambar'],

            // Badges
            ['badges', 'purpose', 'string', 'Optimis'],
            ...self::font('badges', 'purpose', '600', '7.93px', '7.93px', '1'),
            ['badges', 'peaceful', 'string', 'Damai'],
            ...self::font('badges', 'peaceful', '600', '7.93px', '7.93px', '1'),
            ['badges', 'rebel', 'string', 'Berani'],
            ...self::font('badges', 'rebel', '600', '7.93px', '7.93px', '1'),
            ['badges', 'sweet', 'string', 'Manis'],
            ...self::font('badges', 'sweet', '600', '7.93px', '7.93px', '1'),

            // Mascot images + spacing (current layout defaults)
            ['images', 'deco_purpose', 'image', '/src/images/belanja/deco/char-purpose.png'],
            ['images', 'deco_peaceful', 'image', '/src/images/belanja/deco/char-peaceful.webp'],
            ['images', 'deco_rebel', 'image', '/src/images/belanja/deco/char-rebel.png'],
            ['images', 'deco_sweet', 'image', '/src/images/belanja/deco/char-sweet.webp'],
            ['images', 'deco_gap_vertical_desktop', 'string', '80px'],
            ['images', 'deco_gap_horizontal_desktop', 'string', '12px'],
            ['images', 'deco_gap_vertical_mobile', 'string', '48px'],
            ['images', 'deco_gap_horizontal_mobile', 'string', '8px'],

            // Product cards
            ['cards', 'card_purpose_image', 'image', '/src/images/belanja/cards/purpose.webp'],
            ['cards', 'card_purpose_title', 'string', 'Purpose Prestige'],
            ...self::font('cards', 'card_purpose_title', '600', '13px', '14.532px', '1'),
            ['cards', 'card_purpose_desc', 'text', 'Aroma yang merefleksikan ketenangan dan kejelasan tujuan.'],
            ...self::font('cards', 'card_purpose_desc', '400', '9px', '8.587px', '2', 'parkinsans'),
            ['cards', 'card_purpose_price', 'string', 'Rp189.000'],
            ...self::font('cards', 'card_purpose_price', '600', '11px', '10.569px', '1'),
            ['cards', 'card_peaceful_image', 'image', '/src/images/belanja/cards/peaceful.webp'],
            ['cards', 'card_peaceful_title', 'string', 'Peaceful Calm'],
            ...self::font('cards', 'card_peaceful_title', '600', '13px', '14.532px', '1'),
            ['cards', 'card_peaceful_desc', 'text', 'Keberanian dan semangat untuk mengekspresikan diri.'],
            ...self::font('cards', 'card_peaceful_desc', '400', '9px', '8.587px', '2', 'parkinsans'),
            ['cards', 'card_peaceful_price', 'string', 'Rp199.000'],
            ...self::font('cards', 'card_peaceful_price', '600', '11px', '10.569px', '1'),
            ['cards', 'card_rebel_image', 'image', '/src/images/belanja/cards/rebel.webp'],
            ['cards', 'card_rebel_title', 'string', 'Rebel Brave'],
            ...self::font('cards', 'card_rebel_title', '600', '13px', '14.532px', '1'),
            ['cards', 'card_rebel_desc', 'text', 'Aroma menenangkan yang menyatu dengan diri.'],
            ...self::font('cards', 'card_rebel_desc', '400', '9px', '8.587px', '2', 'parkinsans'),
            ['cards', 'card_rebel_price', 'string', 'Rp179.000'],
            ...self::font('cards', 'card_rebel_price', '600', '11px', '10.569px', '1'),
            ['cards', 'card_sweet_image', 'image', '/src/images/belanja/cards/sweet.webp'],
            ['cards', 'card_sweet_title', 'string', 'Sweet Shy'],
            ...self::font('cards', 'card_sweet_title', '600', '13px', '14.532px', '1'),
            ['cards', 'card_sweet_desc', 'text', 'Aroma menenangkan yang menyatu dengan diri.'],
            ...self::font('cards', 'card_sweet_desc', '400', '9px', '8.587px', '2', 'parkinsans'),
            ['cards', 'card_sweet_price', 'string', 'Rp179.000'],
            ...self::font('cards', 'card_sweet_price', '600', '11px', '10.569px', '1'),
            ['cards', 'card_image_width_mobile', 'string', '170%'],
            ['cards', 'card_image_width_desktop', 'string', '185%'],
            ['cards', 'card_image_rotate_mobile', 'string', '30deg'],
            ['cards', 'card_image_rotate_desktop', 'string', '30deg'],
            ['cards', 'card_image_top_mobile', 'string', '46%'],
            ['cards', 'card_image_top_desktop', 'string', '42%'],
        ];
    }

    /**
     * EN locale copy (layout/image keys reuse ID values via merge).
     *
     * @return list<array{0:string,1:string,2:string,3:string}>
     */
    public static function rowsEn(): array
    {
        return [
            ['hero', 'headline_1', 'string', 'Evomi'],
            ['hero', 'headline_1_color', 'string', '#5EA14A'],
            ...self::font('hero', 'headline_1', '600', '26px', '32px', '1'),
            ['hero', 'headline_2', 'string', 'Scent'],
            ['hero', 'headline_2_color', 'string', '#DD74A5'],
            ...self::font('hero', 'headline_2', '600', '26px', '32px', '1'),
            ['hero', 'headline_3', 'string', 'Collection'],
            ['hero', 'headline_3_color', 'string', '#1172BA'],
            ...self::font('hero', 'headline_3', '600', '26px', '32px', '1'),
            ['hero', 'star_icon', 'image', '/src/images/belanja/deco/title-star.svg'],
            ['list', 'empty_title', 'string', 'No products yet'],
            ...self::font('list', 'empty_title', '600', '18px', '18px', '2'),
            ['list', 'empty_hint', 'string', 'Products will appear here when available.'],
            ...self::font('list', 'empty_hint', '400', '14px', '14px', '3', 'parkinsans'),
            ['list', 'see_detail', 'string', 'View Details'],
            ['list', 'no_image', 'string', 'No image'],
            ['badges', 'purpose', 'string', 'Optimistic'],
            ...self::font('badges', 'purpose', '600', '7.93px', '7.93px', '1'),
            ['badges', 'peaceful', 'string', 'Peaceful'],
            ...self::font('badges', 'peaceful', '600', '7.93px', '7.93px', '1'),
            ['badges', 'rebel', 'string', 'Brave'],
            ...self::font('badges', 'rebel', '600', '7.93px', '7.93px', '1'),
            ['badges', 'sweet', 'string', 'Sweet'],
            ...self::font('badges', 'sweet', '600', '7.93px', '7.93px', '1'),
            ['images', 'deco_purpose', 'image', '/src/images/belanja/deco/char-purpose.png'],
            ['images', 'deco_peaceful', 'image', '/src/images/belanja/deco/char-peaceful.webp'],
            ['images', 'deco_rebel', 'image', '/src/images/belanja/deco/char-rebel.png'],
            ['images', 'deco_sweet', 'image', '/src/images/belanja/deco/char-sweet.webp'],
            ['images', 'deco_gap_vertical_desktop', 'string', '80px'],
            ['images', 'deco_gap_horizontal_desktop', 'string', '12px'],
            ['images', 'deco_gap_vertical_mobile', 'string', '48px'],
            ['images', 'deco_gap_horizontal_mobile', 'string', '8px'],
            ['cards', 'card_purpose_image', 'image', '/src/images/belanja/cards/purpose.webp'],
            ['cards', 'card_purpose_title', 'string', 'Purpose Prestige'],
            ...self::font('cards', 'card_purpose_title', '600', '13px', '14.532px', '1'),
            ['cards', 'card_purpose_desc', 'text', 'A scent that reflects calm and clarity of purpose.'],
            ...self::font('cards', 'card_purpose_desc', '400', '9px', '8.587px', '2', 'parkinsans'),
            ['cards', 'card_purpose_price', 'string', 'Rp189.000'],
            ...self::font('cards', 'card_purpose_price', '600', '11px', '10.569px', '1'),
            ['cards', 'card_peaceful_image', 'image', '/src/images/belanja/cards/peaceful.webp'],
            ['cards', 'card_peaceful_title', 'string', 'Peaceful Calm'],
            ...self::font('cards', 'card_peaceful_title', '600', '13px', '14.532px', '1'),
            ['cards', 'card_peaceful_desc', 'text', 'Courage and spirit to express yourself.'],
            ...self::font('cards', 'card_peaceful_desc', '400', '9px', '8.587px', '2', 'parkinsans'),
            ['cards', 'card_peaceful_price', 'string', 'Rp199.000'],
            ...self::font('cards', 'card_peaceful_price', '600', '11px', '10.569px', '1'),
            ['cards', 'card_rebel_image', 'image', '/src/images/belanja/cards/rebel.webp'],
            ['cards', 'card_rebel_title', 'string', 'Rebel Brave'],
            ...self::font('cards', 'card_rebel_title', '600', '13px', '14.532px', '1'),
            ['cards', 'card_rebel_desc', 'text', 'A calming scent that blends with who you are.'],
            ...self::font('cards', 'card_rebel_desc', '400', '9px', '8.587px', '2', 'parkinsans'),
            ['cards', 'card_rebel_price', 'string', 'Rp179.000'],
            ...self::font('cards', 'card_rebel_price', '600', '11px', '10.569px', '1'),
            ['cards', 'card_sweet_image', 'image', '/src/images/belanja/cards/sweet.webp'],
            ['cards', 'card_sweet_title', 'string', 'Sweet Shy'],
            ...self::font('cards', 'card_sweet_title', '600', '13px', '14.532px', '1'),
            ['cards', 'card_sweet_desc', 'text', 'A calming scent that blends with who you are.'],
            ...self::font('cards', 'card_sweet_desc', '400', '9px', '8.587px', '2', 'parkinsans'),
            ['cards', 'card_sweet_price', 'string', 'Rp179.000'],
            ...self::font('cards', 'card_sweet_price', '600', '11px', '10.569px', '1'),
            ['cards', 'card_image_width_mobile', 'string', '170%'],
            ['cards', 'card_image_width_desktop', 'string', '185%'],
            ['cards', 'card_image_rotate_mobile', 'string', '30deg'],
            ['cards', 'card_image_rotate_desktop', 'string', '30deg'],
            ['cards', 'card_image_top_mobile', 'string', '46%'],
            ['cards', 'card_image_top_desktop', 'string', '42%'],
        ];
    }

    /**
     * @param  iterable<int, object|array<string, mixed>>  $dbRows
     * @return list<array{id:mixed,page:string,section:string,key:string,locale:string,type:string,value:mixed}>
     */
    public static function mergeAdminRows(iterable $dbRows, string $locale = 'id'): array
    {
        $indexed = [];
        foreach ($dbRows as $row) {
            $arr = is_array($row) ? $row : $row->toArray();
            $section = (string) ($arr['section'] ?? 'general');
            $key = (string) ($arr['key'] ?? '');
            if ($key === '') {
                continue;
            }
            $indexed[$section.'|'.$key] = [
                'id' => $arr['id'] ?? null,
                'page' => (string) ($arr['page'] ?? 'belanja'),
                'section' => $section,
                'key' => $key,
                'locale' => (string) ($arr['locale'] ?? $locale),
                'type' => (string) ($arr['type'] ?? 'string'),
                'value' => $arr['value'] ?? null,
            ];
        }

        $catalog = $locale === 'en' ? self::rowsEn() : self::rows();
        $out = [];
        $seen = [];
        foreach ($catalog as [$section, $key, $type, $value]) {
            $mapKey = $section.'|'.$key;
            $seen[$mapKey] = true;
            if (isset($indexed[$mapKey])) {
                $row = $indexed[$mapKey];
                $current = (string) ($row['value'] ?? '');
                if (self::isStaleTypographyValue($key, $current)) {
                    $row['value'] = $value;
                }
                $out[] = $row;
                continue;
            }

            $isStyle = BerandaCmsDefaults::isLayoutStyleKey($key) || $type === 'image';
            $out[] = [
                'id' => null,
                'page' => 'belanja',
                'section' => $section,
                'key' => $key,
                'locale' => $locale,
                'type' => $type,
                'value' => ($locale === 'en' && ! $isStyle && $type !== 'image') ? null : $value,
            ];
        }

        foreach ($indexed as $mapKey => $row) {
            if (! isset($seen[$mapKey])) {
                $out[] = $row;
            }
        }

        return $out;
    }

    /**
     * @return list<array{0:string,1:string,2:string,3:string}>
     */
    private static function font(
        string $section,
        string $prefix,
        string $weight,
        string $fsMobile,
        string $fsDesktop,
        string $maxLines = '2',
        string $family = 'nohemi',
        string $style = 'normal',
    ): array {
        return [
            [$section, "{$prefix}_font_family", 'string', $family],
            [$section, "{$prefix}_font_weight", 'string', $weight],
            [$section, "{$prefix}_font_style", 'string', $style],
            [$section, "{$prefix}_fs_mobile", 'string', $fsMobile],
            [$section, "{$prefix}_fs_desktop", 'string', $fsDesktop],
            [$section, "{$prefix}_max_lines", 'string', $maxLines],
        ];
    }

    /**
     * Old admin companion defaults that were never used on storefront.
     * Treat them as unset so Figma Belanja sizes remain the real default.
     */
    public static function isStaleTypographyValue(string $key, string $value): bool
    {
        $v = strtolower(trim($value));
        if ($v === '') {
            return true;
        }
        if (str_ends_with($key, '_font_weight') && $v === '700') {
            return true;
        }
        if (preg_match('/_fs_(mobile|desktop)$/', $key)) {
            return in_array($v, ['24px', '36px', '16px', '18px', '14px', '12px', '10px'], true);
        }

        return false;
    }

    /**
     * Inline CSS vars for product-card bottle image controls.
     */
    public static function cardStyleAttr(CmsStorefront $cms): string
    {
        $wM = self::cssLen($cms->get('cards', 'card_image_width_mobile', '170%'), '170%');
        $wD = self::cssLen($cms->get('cards', 'card_image_width_desktop', '185%'), '185%');
        $rotM = self::cssLen($cms->get('cards', 'card_image_rotate_mobile', '30deg'), '30deg');
        $rotD = self::cssLen($cms->get('cards', 'card_image_rotate_desktop', '30deg'), '30deg');
        $topM = self::cssLen($cms->get('cards', 'card_image_top_mobile', '46%'), '46%');
        $topD = self::cssLen($cms->get('cards', 'card_image_top_desktop', '42%'), '42%');

        return implode('; ', [
            '--belanja-card-img-w-m: '.$wM,
            '--belanja-card-img-w-d: '.$wD,
            '--belanja-card-img-rotate-m: '.$rotM,
            '--belanja-card-img-rotate-d: '.$rotD,
            '--belanja-card-img-top-m: '.$topM,
            '--belanja-card-img-top-d: '.$topD,
        ]).';';
    }

    /**
     * Inline CSS vars for mascot spacing on .belanja-page.
     */
    public static function decoStyleAttr(CmsStorefront $cms): string
    {
        $gapY = self::cssLen($cms->get('images', 'deco_gap_vertical_desktop', '80px'), '80px');
        $gapX = self::cssLen($cms->get('images', 'deco_gap_horizontal_desktop', '12px'), '12px');
        $gapYm = self::cssLen($cms->get('images', 'deco_gap_vertical_mobile', '48px'), '48px');
        $gapXm = self::cssLen($cms->get('images', 'deco_gap_horizontal_mobile', '8px'), '8px');

        return implode('; ', [
            '--belanja-deco-gap-y: '.$gapY,
            '--belanja-deco-gap-x: '.$gapX,
            '--belanja-deco-gap-y-m: '.$gapYm,
            '--belanja-deco-gap-x-m: '.$gapXm,
        ]).';';
    }

    private static function cssLen(string $raw, string $fallback): string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return $fallback;
        }
        if (preg_match('/^-?\d+(\.\d+)?(px|rem|em|%|vh|vw|deg)?$/i', $raw)) {
            if (preg_match('/^-?\d+(\.\d+)?$/', $raw)) {
                return $raw.'px';
            }

            return $raw;
        }

        return $fallback;
    }
}
