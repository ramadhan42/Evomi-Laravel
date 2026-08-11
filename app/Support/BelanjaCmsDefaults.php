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
            ['hero', 'headline_2', 'string', 'Aroma'],
            ['hero', 'headline_2_color', 'string', '#DD74A5'],
            ['hero', 'headline_3', 'string', 'Evomi'],
            ['hero', 'headline_3_color', 'string', '#1172BA'],
            ['hero', 'star_icon', 'image', '/src/images/belanja/deco/title-star.svg'],

            // List empty states
            ['list', 'empty_title', 'string', 'Belum ada produk'],
            ['list', 'empty_hint', 'string', 'Produk akan muncul di sini setelah tersedia.'],
            ['list', 'see_detail', 'string', 'Lihat Detail'],
            ['list', 'no_image', 'string', 'Tidak ada gambar'],

            // Badges
            ['badges', 'purpose', 'string', 'Optimis'],
            ['badges', 'peaceful', 'string', 'Damai'],
            ['badges', 'rebel', 'string', 'Berani'],
            ['badges', 'sweet', 'string', 'Manis'],

            // Mascot images + spacing (current layout defaults)
            ['images', 'deco_purpose', 'image', '/src/images/belanja/deco/char-purpose.png'],
            ['images', 'deco_peaceful', 'image', '/src/images/belanja/deco/char-peaceful.png'],
            ['images', 'deco_rebel', 'image', '/src/images/belanja/deco/char-rebel.png'],
            ['images', 'deco_sweet', 'image', '/src/images/belanja/deco/char-sweet.png'],
            ['images', 'deco_gap_vertical_desktop', 'string', '80px'],
            ['images', 'deco_gap_horizontal_desktop', 'string', '12px'],
            ['images', 'deco_gap_vertical_mobile', 'string', '48px'],
            ['images', 'deco_gap_horizontal_mobile', 'string', '8px'],

            // Product cards
            ['cards', 'card_purpose_image', 'image', '/src/images/belanja/cards/purpose.png'],
            ['cards', 'card_purpose_title', 'string', 'Purpose Prestige'],
            ['cards', 'card_purpose_desc', 'text', 'Aroma yang merefleksikan ketenangan dan kejelasan tujuan.'],
            ['cards', 'card_purpose_price', 'string', 'Rp189.000'],
            ['cards', 'card_peaceful_image', 'image', '/src/images/belanja/cards/peaceful.png'],
            ['cards', 'card_peaceful_title', 'string', 'Peaceful Calm'],
            ['cards', 'card_peaceful_desc', 'text', 'Keberanian dan semangat untuk mengekspresikan diri.'],
            ['cards', 'card_peaceful_price', 'string', 'Rp199.000'],
            ['cards', 'card_rebel_image', 'image', '/src/images/belanja/cards/rebel.png'],
            ['cards', 'card_rebel_title', 'string', 'Rebel Brave'],
            ['cards', 'card_rebel_desc', 'text', 'Aroma menenangkan yang menyatu dengan diri.'],
            ['cards', 'card_rebel_price', 'string', 'Rp179.000'],
            ['cards', 'card_sweet_image', 'image', '/src/images/belanja/cards/sweet.png'],
            ['cards', 'card_sweet_title', 'string', 'Sweet Shy'],
            ['cards', 'card_sweet_desc', 'text', 'Aroma menenangkan yang menyatu dengan diri.'],
            ['cards', 'card_sweet_price', 'string', 'Rp179.000'],
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
            ['hero', 'headline_2', 'string', 'Scent'],
            ['hero', 'headline_2_color', 'string', '#DD74A5'],
            ['hero', 'headline_3', 'string', 'Collection'],
            ['hero', 'headline_3_color', 'string', '#1172BA'],
            ['hero', 'star_icon', 'image', '/src/images/belanja/deco/title-star.svg'],
            ['list', 'empty_title', 'string', 'No products yet'],
            ['list', 'empty_hint', 'string', 'Products will appear here when available.'],
            ['list', 'see_detail', 'string', 'View Details'],
            ['list', 'no_image', 'string', 'No image'],
            ['badges', 'purpose', 'string', 'Optimistic'],
            ['badges', 'peaceful', 'string', 'Peaceful'],
            ['badges', 'rebel', 'string', 'Brave'],
            ['badges', 'sweet', 'string', 'Sweet'],
            ['images', 'deco_purpose', 'image', '/src/images/belanja/deco/char-purpose.png'],
            ['images', 'deco_peaceful', 'image', '/src/images/belanja/deco/char-peaceful.png'],
            ['images', 'deco_rebel', 'image', '/src/images/belanja/deco/char-rebel.png'],
            ['images', 'deco_sweet', 'image', '/src/images/belanja/deco/char-sweet.png'],
            ['images', 'deco_gap_vertical_desktop', 'string', '80px'],
            ['images', 'deco_gap_horizontal_desktop', 'string', '12px'],
            ['images', 'deco_gap_vertical_mobile', 'string', '48px'],
            ['images', 'deco_gap_horizontal_mobile', 'string', '8px'],
            ['cards', 'card_purpose_image', 'image', '/src/images/belanja/cards/purpose.png'],
            ['cards', 'card_purpose_title', 'string', 'Purpose Prestige'],
            ['cards', 'card_purpose_desc', 'text', 'A scent that reflects calm and clarity of purpose.'],
            ['cards', 'card_purpose_price', 'string', 'Rp189.000'],
            ['cards', 'card_peaceful_image', 'image', '/src/images/belanja/cards/peaceful.png'],
            ['cards', 'card_peaceful_title', 'string', 'Peaceful Calm'],
            ['cards', 'card_peaceful_desc', 'text', 'Courage and spirit to express yourself.'],
            ['cards', 'card_peaceful_price', 'string', 'Rp199.000'],
            ['cards', 'card_rebel_image', 'image', '/src/images/belanja/cards/rebel.png'],
            ['cards', 'card_rebel_title', 'string', 'Rebel Brave'],
            ['cards', 'card_rebel_desc', 'text', 'A calming scent that blends with who you are.'],
            ['cards', 'card_rebel_price', 'string', 'Rp179.000'],
            ['cards', 'card_sweet_image', 'image', '/src/images/belanja/cards/sweet.png'],
            ['cards', 'card_sweet_title', 'string', 'Sweet Shy'],
            ['cards', 'card_sweet_desc', 'text', 'A calming scent that blends with who you are.'],
            ['cards', 'card_sweet_price', 'string', 'Rp179.000'],
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
                $out[] = $indexed[$mapKey];
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
        if (preg_match('/^-?\d+(\.\d+)?(px|rem|em|%|vh|vw)?$/i', $raw)) {
            if (preg_match('/^-?\d+(\.\d+)?$/', $raw)) {
                return $raw.'px';
            }

            return $raw;
        }

        return $fallback;
    }
}
