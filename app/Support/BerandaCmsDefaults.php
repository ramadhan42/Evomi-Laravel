<?php

namespace App\Support;

/**
 * Canonical Beranda CMS defaults matching the current storefront UI
 * (blade fallbacks + CmsStorefront hero vars + section typography).
 *
 * Used by Dashboard CMS merge and CmsSeeder. Existing DB rows always win.
 */
final class BerandaCmsDefaults
{
    /**
     * Flat list of default fields: [section, key, type, value].
     *
     * @return list<array{0:string,1:string,2:string,3:string}>
     */
    public static function rows(): array
    {
        return array_merge(
            self::gaps('hero', '16px', '24px', '8px', '12px'),
            self::hero(),
            self::gaps('second', '16px', '32px', '40px', '56px'),
            self::second(),
            self::gaps('third', '40px', '56px', '40px', '56px'),
            self::third(),
            self::gaps('fourth', '16px', '24px', '16px', '24px'),
            [
                ['fourth', 'image', 'image', '/src/images/section 4/thanks-card.png'],
            ],
            self::gaps('fifth', '12px', '24px', '12px', '24px'),
            self::fifth(),
            self::gaps('sixth', '16px', '24px', '12px', '20px'),
            self::sixth(),
            self::gaps('seventh', '16px', '24px', '24px', '32px'),
            self::seventh(),
        );
    }

    /**
     * Merge catalog defaults with DB rows for the admin CMS editor.
     * DB values win. Missing keys are filled from the catalog.
     * For locale=en, copy keys are left blank; layout/style keys use defaults.
     *
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
                'page' => (string) ($arr['page'] ?? 'beranda'),
                'section' => $section,
                'key' => $key,
                'locale' => (string) ($arr['locale'] ?? $locale),
                'type' => (string) ($arr['type'] ?? 'string'),
                'value' => $arr['value'] ?? null,
            ];
        }

        $out = [];
        $seen = [];
        foreach (self::rows() as [$section, $key, $type, $value]) {
            $mapKey = $section.'|'.$key;
            $seen[$mapKey] = true;
            if (isset($indexed[$mapKey])) {
                $out[] = $indexed[$mapKey];
                continue;
            }

            $isStyle = self::isLayoutStyleKey($key);
            $out[] = [
                'id' => null,
                'page' => 'beranda',
                'section' => $section,
                'key' => $key,
                'locale' => $locale,
                'type' => $type,
                'value' => ($locale === 'en' && ! $isStyle) ? null : $value,
            ];
        }

        // Preserve any extra DB keys not in the catalog
        foreach ($indexed as $mapKey => $row) {
            if (! isset($seen[$mapKey])) {
                $out[] = $row;
            }
        }

        return $out;
    }

    /**
     * Seeder-friendly tuples including page name.
     *
     * @return list<array{0:string,1:string,2:string,3:string,4:string}>
     */
    public static function seederRows(): array
    {
        $out = [];
        foreach (self::rows() as [$section, $key, $type, $value]) {
            $out[] = ['beranda', $section, $key, $type, $value];
        }

        return $out;
    }

    public static function isLayoutStyleKey(string $key): bool
    {
        if (str_starts_with($key, 'wave_')) {
            return true;
        }

        return (bool) preg_match(
            '/(_mobile|_desktop|_color|_fs_|_size_|_gap_|_rotate_|_pos_|_left_|_right_|_top_|_bottom_|_font_family|_font_weight|_font_style|_max_lines)/',
            $key
        );
    }

    /**
     * @return list<array{0:string,1:string,2:string,3:string}>
     */
    private static function gaps(string $section, string $hxM, string $hxD, string $vyM, string $vyD): array
    {
        return [
            [$section, 'gap_horizontal_mobile', 'string', $hxM],
            [$section, 'gap_horizontal_desktop', 'string', $hxD],
            [$section, 'gap_vertical_mobile', 'string', $vyM],
            [$section, 'gap_vertical_desktop', 'string', $vyD],
        ];
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
     * @return list<array{0:string,1:string,2:string,3:string}>
     */
    private static function hero(): array
    {
        $rows = [
            ['hero', 'headline_1', 'string', 'Temukan'],
            ['hero', 'headline_1_color', 'string', '#FFFFFF'],
            ['hero', 'headline_1_gap_horizontal_mobile', 'string', '0'],
            ['hero', 'headline_1_gap_horizontal_desktop', 'string', '0'],
            ['hero', 'headline_2', 'string', 'karakter'],
            ['hero', 'headline_2_color', 'string', '#5CB2ED'],
            ['hero', 'headline_2_gap_horizontal_mobile', 'string', '0.28em'],
            ['hero', 'headline_2_gap_horizontal_desktop', 'string', '0.28em'],
            ['hero', 'headline_3', 'string', 'aromamu'],
            ['hero', 'headline_3_color', 'string', '#FFA3CB'],
            ['hero', 'headline_3_gap_horizontal_mobile', 'string', '0'],
            ['hero', 'headline_3_gap_horizontal_desktop', 'string', '0'],
            ['hero', 'headline_4', 'string', 'di Evomi'],
            ['hero', 'headline_4_color', 'string', '#FFFFFF'],
            ['hero', 'headline_4_gap_horizontal_mobile', 'string', '0.28em'],
            ['hero', 'headline_4_gap_horizontal_desktop', 'string', '0.28em'],
            ['hero', 'badge_left', 'string', 'Eau de Parfum'],
            ['hero', 'badge_left_icon', 'image', '/src/images/section 1/badge-left-star.svg'],
            ['hero', 'badge_right', 'string', 'Recycle Bottle Cap'],
            ['hero', 'badge_right_icon', 'image', '/src/images/section 1/recycle.webp'],
            ['hero', 'product1_image', 'image', '/src/images/beranda/botol-purpose-prestige.webp'],
            ['hero', 'product1_badge_label', 'string', 'Purpose Prestige'],
            ['hero', 'product1_badge_icon', 'image', '/src/images/section 1/purpose-prestige.webp'],
            ['hero', 'product2_image', 'image', '/src/images/beranda/botol-rabel-brave.webp'],
            ['hero', 'product2_badge_label', 'string', 'Rebel Brave'],
            ['hero', 'product2_badge_icon', 'image', '/src/images/section 1/rabel-brave.webp'],
            ['hero', 'product3_image', 'image', '/src/images/beranda/botol-peaceful-calm.webp'],
            ['hero', 'product3_badge_label', 'string', 'Peaceful Calm'],
            ['hero', 'product3_badge_icon', 'image', '/src/images/section 1/peaceful-calm.webp'],
            ['hero', 'product4_image', 'image', '/src/images/beranda/botol-sweet-shy.webp'],
            ['hero', 'product4_badge_label', 'string', 'Sweet Shy'],
            ['hero', 'product4_badge_icon', 'image', '/src/images/section 1/sweet-shy.webp'],
            ['hero', 'marquee_text', 'string', 'Every Version of Me'],
            ['hero', 'divider_icon_1', 'image', '/src/images/section 1/purpose.webp'],
            ['hero', 'divider_icon_2', 'image', '/src/images/section 1/peaceful.webp'],
            ['hero', 'divider_icon_3', 'image', '/src/images/section 1/rab.webp'],
            ['hero', 'divider_icon_4', 'image', '/src/images/section 1/sweetshy.webp'],
            ['hero', 'wave_left_icon', 'image', '/src/images/section 1/sayap-kiri.svg'],
            ['hero', 'wave_right_icon', 'image', '/src/images/section 1/sayap-kanan.svg'],

            // Layout positions / sizes (CmsStorefront::heroCssVars)
            ['hero', 'headline_pos_top_mobile', 'string', '0px'],
            ['hero', 'headline_pos_top_desktop', 'string', '0px'],
            ['hero', 'headline_pos_left_mobile', 'string', '0px'],
            ['hero', 'headline_pos_left_desktop', 'string', '0px'],
            ['hero', 'badge_left_icon_size_mobile', 'string', '8px'],
            ['hero', 'badge_left_icon_size_desktop', 'string', '20px'],
            ['hero', 'badge_left_left_mobile', 'string', '4%'],
            ['hero', 'badge_left_left_desktop', 'string', '9%'],
            ['hero', 'badge_left_top_mobile', 'string', '8%'],
            ['hero', 'badge_left_top_desktop', 'string', '5%'],
            ['hero', 'badge_right_icon_size_mobile', 'string', '8px'],
            ['hero', 'badge_right_icon_size_desktop', 'string', '16px'],
            ['hero', 'badge_right_right_mobile', 'string', '-1%'],
            ['hero', 'badge_right_right_desktop', 'string', '4.7%'],
            ['hero', 'badge_right_bottom_mobile', 'string', '72%'],
            ['hero', 'badge_right_bottom_desktop', 'string', '80.4%'],
            ['hero', 'wave_left_left_mobile', 'string', '-24%'],
            ['hero', 'wave_left_left_desktop', 'string', '-11%'],
            ['hero', 'wave_left_top_mobile', 'string', '-38%'],
            ['hero', 'wave_left_top_desktop', 'string', '-29%'],
            ['hero', 'wave_right_right_mobile', 'string', '-17%'],
            ['hero', 'wave_right_right_desktop', 'string', '-11%'],
            ['hero', 'wave_right_top_mobile', 'string', '-77%'],
            ['hero', 'wave_right_top_desktop', 'string', '-53%'],
            ['hero', 'product1_size_mobile', 'string', '100'],
            ['hero', 'product1_size_desktop', 'string', '100'],
            ['hero', 'product1_left_mobile', 'string', '7.7%'],
            ['hero', 'product1_left_desktop', 'string', '7.7%'],
            ['hero', 'product1_top_mobile', 'string', '-24.5%'],
            ['hero', 'product1_top_desktop', 'string', '-24.5%'],
            ['hero', 'product1_right_mobile', 'string', ''],
            ['hero', 'product1_right_desktop', 'string', ''],
            ['hero', 'product1_rotate_mobile', 'string', '3'],
            ['hero', 'product1_rotate_desktop', 'string', '3'],
            ['hero', 'product2_size_mobile', 'string', '100'],
            ['hero', 'product2_size_desktop', 'string', '100'],
            ['hero', 'product2_left_mobile', 'string', '-2.3%'],
            ['hero', 'product2_left_desktop', 'string', '-2.3%'],
            ['hero', 'product2_top_mobile', 'string', '-15%'],
            ['hero', 'product2_top_desktop', 'string', '-15%'],
            ['hero', 'product2_right_mobile', 'string', ''],
            ['hero', 'product2_right_desktop', 'string', ''],
            ['hero', 'product2_rotate_mobile', 'string', '-3'],
            ['hero', 'product2_rotate_desktop', 'string', '-3'],
            ['hero', 'product3_size_mobile', 'string', '100'],
            ['hero', 'product3_size_desktop', 'string', '100'],
            ['hero', 'product3_left_mobile', 'string', ''],
            ['hero', 'product3_left_desktop', 'string', ''],
            ['hero', 'product3_top_mobile', 'string', '-24%'],
            ['hero', 'product3_top_desktop', 'string', '-24%'],
            ['hero', 'product3_right_mobile', 'string', '13%'],
            ['hero', 'product3_right_desktop', 'string', '13%'],
            ['hero', 'product3_rotate_mobile', 'string', '4'],
            ['hero', 'product3_rotate_desktop', 'string', '4'],
            ['hero', 'product4_size_mobile', 'string', '100'],
            ['hero', 'product4_size_desktop', 'string', '100'],
            ['hero', 'product4_left_mobile', 'string', '-23.5%'],
            ['hero', 'product4_left_desktop', 'string', '-23.5%'],
            ['hero', 'product4_top_mobile', 'string', '-20%'],
            ['hero', 'product4_top_desktop', 'string', '-20%'],
            ['hero', 'product4_right_mobile', 'string', ''],
            ['hero', 'product4_right_desktop', 'string', ''],
            ['hero', 'product4_rotate_mobile', 'string', '-4'],
            ['hero', 'product4_rotate_desktop', 'string', '-4'],
            ['hero', 'divider_icon_1_size_mobile', 'string', '14px'],
            ['hero', 'divider_icon_1_size_desktop', 'string', '25px'],
            ['hero', 'divider_icon_2_size_mobile', 'string', '14px'],
            ['hero', 'divider_icon_2_size_desktop', 'string', '25px'],
            ['hero', 'divider_icon_3_size_mobile', 'string', '14px'],
            ['hero', 'divider_icon_3_size_desktop', 'string', '25px'],
            ['hero', 'divider_icon_4_size_mobile', 'string', '14px'],
            ['hero', 'divider_icon_4_size_desktop', 'string', '25px'],
            ['hero', 'divider_bottom_mobile', 'string', '8px'],
            ['hero', 'divider_bottom_desktop', 'string', '0px'],
        ];

        foreach ([1, 2, 3, 4] as $n) {
            $rows = array_merge($rows, self::font('hero', "headline_{$n}", '600', '28px', '42px', '1'));
        }
        $rows = array_merge(
            $rows,
            self::font('hero', 'badge_left', '700', '7px', '14px', '2'),
            self::font('hero', 'badge_right', '700', '7px', '14px', '2'),
            // Hero marquee uses marquee_* (not marquee_text_*) in CSS vars
            [
                ['hero', 'marquee_font_family', 'string', 'nohemi'],
                ['hero', 'marquee_font_weight', 'string', '500'],
                ['hero', 'marquee_font_style', 'string', 'normal'],
                ['hero', 'marquee_fs_mobile', 'string', '8px'],
                ['hero', 'marquee_fs_desktop', 'string', '14px'],
                ['hero', 'marquee_max_lines', 'string', '1'],
            ],
            self::font('hero', 'product1_badge_label', '700', '10px', '12px', '1'),
            self::font('hero', 'product2_badge_label', '700', '10px', '12px', '1'),
            self::font('hero', 'product3_badge_label', '700', '10px', '12px', '1'),
            self::font('hero', 'product4_badge_label', '700', '10px', '12px', '1'),
        );

        return $rows;
    }

    /**
     * @return list<array{0:string,1:string,2:string,3:string}>
     */
    private static function second(): array
    {
        $rows = [
            ['second', 'headline_1', 'string', 'Kenalan sama'],
            ['second', 'headline_2', 'string', 'karakter '],
            ['second', 'headline_3', 'string', 'kita yuk!'],
            ['second', 'cta_label', 'string', 'Lihat Semua Karakter'],
            ['second', 'card_icon_size_mobile', 'string', '100px'],
            ['second', 'card_icon_size_desktop', 'string', '140px'],
            ['second', 'card_label_gap_mobile', 'string', '0px'],
            ['second', 'card_label_gap_desktop', 'string', '12px'],
            ['second', 'card_gap_horizontal_mobile', 'string', '16px'],
            ['second', 'card_gap_horizontal_desktop', 'string', '32px'],
            ['second', 'card1_name', 'string', "Purpose\nPrestige"],
            ['second', 'card1_title', 'string', 'Purpose Prestige'],
            ['second', 'card1_image', 'image', '/src/images/section 2/purpose-prestige.webp'],
            ['second', 'card2_name', 'string', "Peaceful\nCalm"],
            ['second', 'card2_title', 'string', 'Peaceful Calm'],
            ['second', 'card2_image', 'image', '/src/images/section 2/peaceful-calm.webp'],
            ['second', 'card3_name', 'string', "Rebel\nBrave"],
            ['second', 'card3_title', 'string', 'Rebel Brave'],
            ['second', 'card3_image', 'image', '/src/images/section 2/rabel-brave.webp'],
            ['second', 'card4_name', 'string', "Sweet\nShy"],
            ['second', 'card4_title', 'string', 'Sweet Shy'],
            ['second', 'card4_image', 'image', '/src/images/section 2/sweet-shy.webp'],
        ];

        $rows = array_merge(
            $rows,
            self::font('second', 'headline_1', '600', '20px', '42px', '1'),
            self::font('second', 'headline_2', '600', '20px', '42px', '1'),
            self::font('second', 'headline_3', '600', '20px', '42px', '1'),
            self::font('second', 'cta_label', '700', '11px', '15px', '1'),
        );
        foreach ([1, 2, 3, 4] as $n) {
            $rows = array_merge($rows, self::font('second', "card{$n}_name", '700', '16px', '24px', '2'));
        }

        return $rows;
    }

    /**
     * @return list<array{0:string,1:string,2:string,3:string}>
     */
    private static function third(): array
    {
        $rows = [
            ['third', 'title_1', 'string', 'Brand'],
            ['third', 'title_2', 'string', 'Value'],
            ['third', 'tagline', 'text', 'Every Version of Me'],
            ['third', 'card1_title', 'string', "Self\nAwareness"],
            ['third', 'card1_desc', 'text', 'Setiap aroma dirancang untuk merepresentasikan versi diri, emosi, dan karakter manusia yang berbeda, sehingga parfum menjadi medium ekspresi personal, bukan sekadar wewangian.'],
            ['third', 'card1_icon', 'image', '/src/images/section 3/star-medium.webp'],
            ['third', 'card2_title', 'string', "Environment\nFriendly"],
            ['third', 'card2_desc', 'text', 'Mengusung kepedulian terhadap lingkungan melalui pemanfaatan daur ulang tutup botol plastik menjadi bagian dari identitas produk, sebagai bentuk kontribusi kecil dalam mengurangi limbah plastik sekaligus menghadirkan nilai sustainability.'],
            ['third', 'card2_icon', 'image', '/src/images/section 3/peaceful-calm.webp'],
            ['third', 'card3_title', 'string', "Playful Design\nConcept"],
            ['third', 'card3_desc', 'text', 'Dikemas dengan pendekatan visual yang playful, ekspresif, dan dekat dengan generasi muda agar pengalaman menggunakan parfum terasa lebih personal dan menyenangkan.'],
            ['third', 'card3_icon', 'image', '/src/images/section 3/triangle.webp'],
        ];

        return array_merge(
            $rows,
            self::font('third', 'title_1', '700', '28px', '42px', '2'),
            self::font('third', 'title_2', '700', '28px', '42px', '2'),
            self::font('third', 'tagline', '700', '20px', '28px', '2'),
            self::font('third', 'card1_title', '600', '18px', '22px', '2'),
            self::font('third', 'card1_desc', '500', '13px', '15px', '3', 'parkinsans'),
            self::font('third', 'card2_title', '600', '18px', '22px', '2'),
            self::font('third', 'card2_desc', '500', '13px', '15px', '3', 'parkinsans'),
            self::font('third', 'card3_title', '600', '18px', '22px', '2'),
            self::font('third', 'card3_desc', '500', '13px', '15px', '3', 'parkinsans'),
        );
    }

    /**
     * @return list<array{0:string,1:string,2:string,3:string}>
     */
    private static function fifth(): array
    {
        $cards = [
            1 => ['Optimis', 'Purpose Prestige', 'Aroma yang merefleksikan ketenangan dan kejelasan tujuan.', 'Rp189.000', '/src/images/beranda/botol-purpose-prestige.webp'],
            2 => ['Damai', 'Peaceful Calm', 'Aroma menenangkan yang menyatu dengan diri.', 'Rp199.000', '/src/images/beranda/botol-peaceful-calm.webp'],
            3 => ['Berani', 'Rebel Brave', 'Keberanian dan semangat untuk mengekspresikan diri.', 'Rp179.000', '/src/images/beranda/botol-rabel-brave.webp'],
            4 => ['Manis', 'Sweet Shy', 'Aroma menenangkan yang menyatu dengan diri.', 'Rp189.000', '/src/images/beranda/botol-sweet-shy.webp'],
        ];

        $rows = [
            ['fifth', 'title_1', 'string', 'Khas'],
            ['fifth', 'title_2', 'string', 'Evomi'],
            ['fifth', 'subtitle', 'text', 'Empat karakter aroma yang mewakili sisi berbeda dari dirimu.'],
            ['fifth', 'cta_label', 'string', 'Lihat Koleksi'],
        ];
        foreach ($cards as $n => [$badge, $title, $desc, $price, $image]) {
            $rows[] = ['fifth', "card{$n}_badge", 'string', $badge];
            $rows[] = ['fifth', "card{$n}_title", 'string', $title];
            $rows[] = ['fifth', "card{$n}_desc", 'text', $desc];
            $rows[] = ['fifth', "card{$n}_price", 'string', $price];
            $rows[] = ['fifth', "card{$n}_image", 'image', $image];
        }

        $rows = array_merge(
            $rows,
            self::font('fifth', 'title_1', '700', '26px', '38px', '2'),
            self::font('fifth', 'title_2', '700', '26px', '38px', '2'),
            self::font('fifth', 'subtitle', '400', '12px', '16px', '3'),
            self::font('fifth', 'cta_label', '700', '13px', '14px', '1'),
        );
        foreach ([1, 2, 3, 4] as $n) {
            $rows = array_merge(
                $rows,
                self::font('fifth', "card{$n}_badge", '700', '10px', '12px', '1'),
                self::font('fifth', "card{$n}_title", '700', '13px', '16px', '2'),
                self::font('fifth', "card{$n}_desc", '500', '10px', '11px', '3'),
                self::font('fifth', "card{$n}_price", '700', '11px', '12px', '1'),
            );
        }

        return $rows;
    }

    /**
     * @return list<array{0:string,1:string,2:string,3:string}>
     */
    private static function sixth(): array
    {
        return array_merge(
            [
                ['sixth', 'title_1', 'string', 'Packaging'],
                ['sixth', 'title_2', 'string', 'Reveal'],
                ['sixth', 'image', 'image', '/src/images/section 6/packaging.webp'],
                ['sixth', 'marquee_text', 'string', 'Every Version of Me'],
                ['sixth', 'label1', 'string', "Purpose\nPrestige"],
                ['sixth', 'label2', 'string', "Rebel\nBrave"],
                ['sixth', 'label3', 'string', "Peaceful\nCalm"],
                ['sixth', 'label4', 'string', "Sweet\nShy"],
            ],
            self::font('sixth', 'title_1', '700', '24px', '42px', '2'),
            self::font('sixth', 'title_2', '700', '24px', '42px', '2'),
            self::font('sixth', 'marquee_text', '500', '11px', '14px', '1'),
            self::font('sixth', 'label1', '500', '9px', '16px', '2'),
            self::font('sixth', 'label2', '500', '9px', '16px', '2'),
            self::font('sixth', 'label3', '500', '9px', '16px', '2'),
            self::font('sixth', 'label4', '500', '9px', '16px', '2'),
        );
    }

    /**
     * @return list<array{0:string,1:string,2:string,3:string}>
     */
    private static function seventh(): array
    {
        $labels = [
            1 => ['Prestige', 'Purpose Prestige', '#5CB2ED', '61%', '33%'],
            2 => ['Calm', 'Peaceful Calm', '#5EA14A', '82%', '15%'],
            3 => ['Rebel', 'Rebel Brave', '#E33D35', '26%', '15%'],
            4 => ['Sweet', 'Sweet Shy', '#DD74A5', '47.5%', '-3%'],
        ];

        $rows = [
            ['seventh', 'headline_1', 'string', 'Temukan'],
            ['seventh', 'headline_2', 'string', 'aromamu'],
            ['seventh', 'headline_3', 'string', 'dengan'],
            ['seventh', 'headline_4', 'string', 'bermain'],
            ['seventh', 'headline_5', 'string', 'kuis'],
            ['seventh', 'cta_label', 'string', 'Temukan Aromamu'],
            ['seventh', 'product_image', 'image', '/src/images/beranda/produk-kuis.webp'],
        ];

        foreach ($labels as $n => [$text, $title, $color, $left, $top]) {
            $rows[] = ['seventh', "label{$n}_text", 'string', $text];
            $rows[] = ['seventh', "label{$n}_title", 'string', $title];
            $rows[] = ['seventh', "label{$n}_color", 'string', $color];
            $rows[] = ['seventh', "label{$n}_fs_mobile", 'string', '9px'];
            $rows[] = ['seventh', "label{$n}_fs_desktop", 'string', '16px'];
            $rows[] = ['seventh', "label{$n}_left_mobile", 'string', $left];
            $rows[] = ['seventh', "label{$n}_left_desktop", 'string', $left];
            $rows[] = ['seventh', "label{$n}_right_mobile", 'string', ''];
            $rows[] = ['seventh', "label{$n}_right_desktop", 'string', ''];
            $rows[] = ['seventh', "label{$n}_top_mobile", 'string', $top];
            $rows[] = ['seventh', "label{$n}_top_desktop", 'string', $top];
            $rows[] = ['seventh', "label{$n}_bottom_mobile", 'string', ''];
            $rows[] = ['seventh', "label{$n}_bottom_desktop", 'string', ''];
        }

        foreach ([1, 2, 3, 4, 5] as $n) {
            $rows = array_merge($rows, self::font('seventh', "headline_{$n}", '600', '30px', '55px', '2'));
        }
        $rows = array_merge($rows, self::font('seventh', 'cta_label', '600', '14px', '19px', '1'));
        foreach ([1, 2, 3, 4] as $n) {
            $rows = array_merge(
                $rows,
                self::font('seventh', "label{$n}_text", '700', '9px', '16px', '2'),
                self::font('seventh', "label{$n}_title", '700', '12px', '16px', '2'),
            );
        }

        return $rows;
    }
}
