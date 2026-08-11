<?php

namespace App\Support;

use App\Models\SiteContent;

/**
 * Storefront reader for SiteContent (grouped by section).
 * Mirrors Next.js CmsContext tBeranda / resolveCmsImage / cmsFonts helpers.
 */
final class CmsStorefront
{
    /** @var array<string, array<string, string>> */
    private array $grouped;

    /** @param array<string, array<string, string>> $grouped */
    public function __construct(array $grouped)
    {
        $this->grouped = $grouped;
    }

    public static function forPage(string $page, ?string $locale = null): self
    {
        $locale = $locale ?? self::resolveLocale();
        $grouped = [];
        $idRows = SiteContent::query()
            ->where('page', $page)
            ->where('locale', 'id')
            ->get(['section', 'key', 'value']);

        foreach ($idRows as $row) {
            $grouped[$row->section][$row->key] = (string) ($row->value ?? '');
        }

        if ($locale !== 'id') {
            $localeRows = SiteContent::query()
                ->where('page', $page)
                ->where('locale', $locale)
                ->get(['section', 'key', 'value']);
            foreach ($localeRows as $row) {
                if ($row->value !== null && $row->value !== '') {
                    $grouped[$row->section][$row->key] = (string) $row->value;
                }
            }
        }

        return new self($grouped);
    }

    public static function resolveLocale(): string
    {
        $fromCookie = request()?->cookie('evomi_locale');
        if ($fromCookie === 'en' || $fromCookie === 'id') {
            return $fromCookie;
        }

        $fromQuery = request()?->query('locale');
        if ($fromQuery === 'en' || $fromQuery === 'id') {
            return $fromQuery;
        }

        return 'id';
    }

    public function get(string $section, string $key, string $fallback = ''): string
    {
        $val = $this->grouped[$section][$key] ?? null;
        if ($val === null || $val === '') {
            return $fallback;
        }

        return $val;
    }

    public function image(string $section, string $key, string $fallback = ''): string
    {
        return self::resolveImage($this->get($section, $key, ''), $fallback);
    }

    public static function resolveImage(?string $path, string $fallback = ''): string
    {
        $p = trim((string) $path);
        if ($p === '') {
            $p = trim($fallback);
        }
        if ($p === '') {
            return '';
        }
        if (preg_match('#^(https?:|blob:|data:)#i', $p)) {
            return $p;
        }
        if (
            str_starts_with($p, '/src/')
            || str_starts_with($p, '/images/')
            || str_starts_with($p, '/favicon')
            || str_starts_with($p, '/storage/')
            || str_starts_with($p, '/')
        ) {
            return $p;
        }

        return '/storage/'.ltrim($p, '/');
    }

    public static function fontFamilyCss(?string $raw, string $fallback = 'nohemi'): string
    {
        $map = [
            'nohemi' => "var(--font-nohemi), 'Nohemi', sans-serif",
            'parkinsans' => "var(--font-parkinsans), 'Parkinsans', sans-serif",
            'syne' => "var(--font-syne), 'Syne', sans-serif",
            'heavy' => "var(--font-heavy), '8-Heavy', sans-serif",
            'arial' => 'Arial, Helvetica, sans-serif',
            'helvetica' => 'Helvetica, Arial, sans-serif',
            'georgia' => "Georgia, 'Times New Roman', serif",
            'times' => "'Times New Roman', Times, serif",
            'verdana' => 'Verdana, Geneva, sans-serif',
            'tahoma' => 'Tahoma, Geneva, sans-serif',
            'courier' => "'Courier New', Courier, monospace",
            'system' => "system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif",
        ];
        $key = strtolower(trim((string) $raw));
        if (isset($map[$key])) {
            return $map[$key];
        }
        if ($key !== '' && (str_contains($key, ',') || str_starts_with($key, 'var('))) {
            return trim((string) $raw);
        }

        return $map[$fallback] ?? $map['nohemi'];
    }

    public static function fontWeight(?string $raw, string $fallback = '400'): string
    {
        $v = trim((string) $raw);
        if (preg_match('/^(300|400|500|600|700|800|900)$/', $v)) {
            return $v;
        }

        return $fallback;
    }

    public static function fontStyle(?string $raw, string $fallback = 'normal'): string
    {
        $v = strtolower(trim((string) $raw));

        return $v === 'italic' ? 'italic' : $fallback;
    }

    /**
     * Max Enter-lines for a text key (1–3).
     */
    public function maxLines(string $section, string $key, int $fallback = 2): int
    {
        $raw = trim($this->get($section, "{$key}_max_lines", (string) $fallback));
        $n = (int) $raw;
        if ($n < 1) {
            $n = $fallback;
        }

        return max(1, min(3, $n));
    }

    /**
     * Split CMS text into lines, clamped by {key}_max_lines (max 3).
     *
     * @return list<string>
     */
    public function lines(string $section, string $key, string $fallback = '', ?int $defaultMax = null): array
    {
        $raw = $this->get($section, $key, $fallback);
        $max = $this->maxLines($section, $key, $defaultMax ?? 2);
        $parts = preg_split("/\r\n|\n|\r/", (string) $raw);
        if (! is_array($parts) || $parts === []) {
            $parts = [$raw];
        }

        return array_slice(array_map(static fn ($l) => (string) $l, $parts), 0, $max);
    }

    /**
     * Text with newlines clamped — use with whitespace-pre-line / cms-lines.
     */
    public function textLines(string $section, string $key, string $fallback = '', ?int $defaultMax = null): string
    {
        return implode("\n", $this->lines($section, $key, $fallback, $defaultMax));
    }

    /**
     * Font family / weight / style only (no size) — for compact UI like product cards.
     */
    public function fontFaceInline(string $section, string $prefix, string $defaultWeight = '400'): string
    {
        $ff = self::fontFamilyCss($this->get($section, "{$prefix}_font_family", 'nohemi'));
        $fw = self::fontWeight($this->get($section, "{$prefix}_font_weight", $defaultWeight), $defaultWeight);
        $fst = self::fontStyle($this->get($section, "{$prefix}_font_style", 'normal'));

        return "font-family: {$ff}; font-weight: {$fw}; font-style: {$fst};";
    }

    /**
     * Inline font-family / weight / style / size CSS vars for a CMS text prefix.
     * Pair with class `cms-fs` so mobile/desktop sizes apply responsively.
     */
    public function fontInline(string $section, string $prefix, string $defaultWeight = '400'): string
    {
        $parts = [rtrim($this->fontFaceInline($section, $prefix, $defaultWeight), ';')];

        $fsM = trim($this->get($section, "{$prefix}_fs_mobile", ''));
        $fsD = trim($this->get($section, "{$prefix}_fs_desktop", ''));
        if ($fsM !== '') {
            $parts[] = '--cms-fs-m: '.$this->cssSize($fsM, $fsM);
        }
        if ($fsD !== '') {
            $parts[] = '--cms-fs-d: '.$this->cssSize($fsD, $fsD);
        }

        return implode('; ', $parts).';';
    }

    /**
     * Build CSS custom properties for .hero-section (matches Next HeroSection).
     *
     * @return array<string, string>
     */
    public function heroCssVars(): array
    {
        $s = fn (string $key, string $default): string => $this->styleVal('hero', $key, $default);
        $scale = static function (string $value): string {
            $n = (float) $value;

            return is_finite($n) ? (string) ($n / 100) : '1';
        };
        $deg = static function (string $value): string {
            $n = (float) $value;

            return is_finite($n) ? "{$n}deg" : '0deg';
        };

        return [
            '--hero-hl1-ff' => self::fontFamilyCss($s('headline_1_font_family', 'nohemi')),
            '--hero-hl1-fw' => self::fontWeight($s('headline_1_font_weight', '600'), '600'),
            '--hero-hl1-fst' => self::fontStyle($s('headline_1_font_style', 'normal')),
            '--hero-hl1-fs-m' => $s('headline_1_fs_mobile', '28px'),
            '--hero-hl1-fs-d' => $s('headline_1_fs_desktop', '42px'),
            '--hero-hl1-gap-x-m' => $this->cssSize($s('headline_1_gap_horizontal_mobile', '0'), '0'),
            '--hero-hl1-gap-x-d' => $this->cssSize($s('headline_1_gap_horizontal_desktop', '0'), '0'),
            '--hero-hl2-ff' => self::fontFamilyCss($s('headline_2_font_family', 'nohemi')),
            '--hero-hl2-fw' => self::fontWeight($s('headline_2_font_weight', '600'), '600'),
            '--hero-hl2-fst' => self::fontStyle($s('headline_2_font_style', 'normal')),
            '--hero-hl2-fs-m' => $s('headline_2_fs_mobile', '28px'),
            '--hero-hl2-fs-d' => $s('headline_2_fs_desktop', '42px'),
            '--hero-hl2-gap-x-m' => $this->cssSize($s('headline_2_gap_horizontal_mobile', '0.28em'), '0.28em'),
            '--hero-hl2-gap-x-d' => $this->cssSize($s('headline_2_gap_horizontal_desktop', '0.28em'), '0.28em'),
            '--hero-hl3-ff' => self::fontFamilyCss($s('headline_3_font_family', 'nohemi')),
            '--hero-hl3-fw' => self::fontWeight($s('headline_3_font_weight', '600'), '600'),
            '--hero-hl3-fst' => self::fontStyle($s('headline_3_font_style', 'normal')),
            '--hero-hl3-fs-m' => $s('headline_3_fs_mobile', '28px'),
            '--hero-hl3-fs-d' => $s('headline_3_fs_desktop', '42px'),
            '--hero-hl3-gap-x-m' => $this->cssSize($s('headline_3_gap_horizontal_mobile', '0'), '0'),
            '--hero-hl3-gap-x-d' => $this->cssSize($s('headline_3_gap_horizontal_desktop', '0'), '0'),
            '--hero-hl4-ff' => self::fontFamilyCss($s('headline_4_font_family', 'nohemi')),
            '--hero-hl4-fw' => self::fontWeight($s('headline_4_font_weight', '600'), '600'),
            '--hero-hl4-fst' => self::fontStyle($s('headline_4_font_style', 'normal')),
            '--hero-hl4-fs-m' => $s('headline_4_fs_mobile', '28px'),
            '--hero-hl4-fs-d' => $s('headline_4_fs_desktop', '42px'),
            '--hero-hl4-gap-x-m' => $this->cssSize($s('headline_4_gap_horizontal_mobile', '0.28em'), '0.28em'),
            '--hero-hl4-gap-x-d' => $this->cssSize($s('headline_4_gap_horizontal_desktop', '0.28em'), '0.28em'),
            '--hero-hl-top-m' => $s('headline_pos_top_mobile', '0px'),
            '--hero-hl-top-d' => $s('headline_pos_top_desktop', '0px'),
            '--hero-hl-left-m' => $s('headline_pos_left_mobile', '0px'),
            '--hero-hl-left-d' => $s('headline_pos_left_desktop', '0px'),

            '--hero-badge-l-ff' => self::fontFamilyCss($s('badge_left_font_family', 'nohemi')),
            '--hero-badge-l-fw' => self::fontWeight($s('badge_left_font_weight', '700'), '700'),
            '--hero-badge-l-fst' => self::fontStyle($s('badge_left_font_style', 'normal')),
            '--hero-badge-l-fs-m' => $s('badge_left_fs_mobile', '7px'),
            '--hero-badge-l-fs-d' => $s('badge_left_fs_desktop', '14px'),
            '--hero-badge-l-icon-m' => $s('badge_left_icon_size_mobile', '8px'),
            '--hero-badge-l-icon-d' => $s('badge_left_icon_size_desktop', '20px'),
            '--hero-badge-l-left-m' => $s('badge_left_left_mobile', '4%'),
            '--hero-badge-l-left-d' => $s('badge_left_left_desktop', '9%'),
            '--hero-badge-l-top-m' => $s('badge_left_top_mobile', '8%'),
            '--hero-badge-l-top-d' => $s('badge_left_top_desktop', '5%'),

            '--hero-badge-r-ff' => self::fontFamilyCss($s('badge_right_font_family', 'nohemi')),
            '--hero-badge-r-fw' => self::fontWeight($s('badge_right_font_weight', '700'), '700'),
            '--hero-badge-r-fst' => self::fontStyle($s('badge_right_font_style', 'normal')),
            '--hero-badge-r-fs-m' => $s('badge_right_fs_mobile', '7px'),
            '--hero-badge-r-fs-d' => $s('badge_right_fs_desktop', '14px'),
            '--hero-badge-r-icon-m' => $s('badge_right_icon_size_mobile', '8px'),
            '--hero-badge-r-icon-d' => $s('badge_right_icon_size_desktop', '16px'),
            '--hero-badge-r-right-m' => $s('badge_right_right_mobile', '-1%'),
            '--hero-badge-r-right-d' => $s('badge_right_right_desktop', '4.7%'),
            '--hero-badge-r-bottom-m' => $s('badge_right_bottom_mobile', '72%'),
            '--hero-badge-r-bottom-d' => $s('badge_right_bottom_desktop', '80.4%'),

            '--hero-wave-l-left-m' => $s('wave_left_left_mobile', '-24%'),
            '--hero-wave-l-left-d' => $s('wave_left_left_desktop', '-11%'),
            '--hero-wave-l-top-m' => $s('wave_left_top_mobile', '-44%'),
            '--hero-wave-l-top-d' => $s('wave_left_top_desktop', '-35%'),
            '--hero-wave-r-right-m' => $s('wave_right_right_mobile', '-17%'),
            '--hero-wave-r-right-d' => $s('wave_right_right_desktop', '-11%'),
            '--hero-wave-r-top-m' => $s('wave_right_top_mobile', '-74%'),
            '--hero-wave-r-top-d' => $s('wave_right_top_desktop', '-50%'),

            '--hero-p1-size-m' => $scale($s('product1_size_mobile', '100')),
            '--hero-p1-size-d' => $scale($s('product1_size_desktop', '100')),
            '--hero-p1-left-m' => $s('product1_left_mobile', '7.7%') ?: 'auto',
            '--hero-p1-left-d' => $s('product1_left_desktop', '7.7%') ?: 'auto',
            '--hero-p1-top-m' => $s('product1_top_mobile', '-24.5%'),
            '--hero-p1-top-d' => $s('product1_top_desktop', '-24.5%'),
            '--hero-p1-right-m' => $s('product1_right_mobile', '') ?: 'auto',
            '--hero-p1-right-d' => $s('product1_right_desktop', '') ?: 'auto',
            '--hero-p1-rot-m' => $deg($s('product1_rotate_mobile', '3')),
            '--hero-p1-rot-d' => $deg($s('product1_rotate_desktop', '3')),

            '--hero-p2-size-m' => $scale($s('product2_size_mobile', '100')),
            '--hero-p2-size-d' => $scale($s('product2_size_desktop', '100')),
            '--hero-p2-left-m' => $s('product2_left_mobile', '-2.3%') ?: 'auto',
            '--hero-p2-left-d' => $s('product2_left_desktop', '-2.3%') ?: 'auto',
            '--hero-p2-top-m' => $s('product2_top_mobile', '-15%'),
            '--hero-p2-top-d' => $s('product2_top_desktop', '-15%'),
            '--hero-p2-right-m' => $s('product2_right_mobile', '') ?: 'auto',
            '--hero-p2-right-d' => $s('product2_right_desktop', '') ?: 'auto',
            '--hero-p2-rot-m' => $deg($s('product2_rotate_mobile', '-3')),
            '--hero-p2-rot-d' => $deg($s('product2_rotate_desktop', '-3')),

            '--hero-p3-size-m' => $scale($s('product3_size_mobile', '100')),
            '--hero-p3-size-d' => $scale($s('product3_size_desktop', '100')),
            '--hero-p3-left-m' => $s('product3_left_mobile', '') ?: 'auto',
            '--hero-p3-left-d' => $s('product3_left_desktop', '') ?: 'auto',
            '--hero-p3-top-m' => $s('product3_top_mobile', '-24%'),
            '--hero-p3-top-d' => $s('product3_top_desktop', '-24%'),
            '--hero-p3-right-m' => $s('product3_right_mobile', '13%') ?: 'auto',
            '--hero-p3-right-d' => $s('product3_right_desktop', '13%') ?: 'auto',
            '--hero-p3-rot-m' => $deg($s('product3_rotate_mobile', '4')),
            '--hero-p3-rot-d' => $deg($s('product3_rotate_desktop', '4')),

            '--hero-p4-size-m' => $scale($s('product4_size_mobile', '100')),
            '--hero-p4-size-d' => $scale($s('product4_size_desktop', '100')),
            '--hero-p4-left-m' => $s('product4_left_mobile', '-23.5%') ?: 'auto',
            '--hero-p4-left-d' => $s('product4_left_desktop', '-23.5%') ?: 'auto',
            '--hero-p4-top-m' => $s('product4_top_mobile', '-20%'),
            '--hero-p4-top-d' => $s('product4_top_desktop', '-20%'),
            '--hero-p4-right-m' => $s('product4_right_mobile', '') ?: 'auto',
            '--hero-p4-right-d' => $s('product4_right_desktop', '') ?: 'auto',
            '--hero-p4-rot-m' => $deg($s('product4_rotate_mobile', '-4')),
            '--hero-p4-rot-d' => $deg($s('product4_rotate_desktop', '-4')),

            '--hero-marquee-ff' => self::fontFamilyCss($s('marquee_font_family', 'nohemi')),
            '--hero-marquee-fw' => self::fontWeight($s('marquee_font_weight', '500'), '500'),
            '--hero-marquee-fst' => self::fontStyle($s('marquee_font_style', 'normal')),
            '--hero-marquee-fs-m' => $s('marquee_fs_mobile', '8px'),
            '--hero-marquee-fs-d' => $s('marquee_fs_desktop', '14px'),
            '--hero-div-icon1-m' => $s('divider_icon_1_size_mobile', '14px'),
            '--hero-div-icon1-d' => $s('divider_icon_1_size_desktop', '25px'),
            '--hero-div-icon2-m' => $s('divider_icon_2_size_mobile', '14px'),
            '--hero-div-icon2-d' => $s('divider_icon_2_size_desktop', '25px'),
            '--hero-div-icon3-m' => $s('divider_icon_3_size_mobile', '14px'),
            '--hero-div-icon3-d' => $s('divider_icon_3_size_desktop', '25px'),
            '--hero-div-icon4-m' => $s('divider_icon_4_size_mobile', '14px'),
            '--hero-div-icon4-d' => $s('divider_icon_4_size_desktop', '25px'),
            '--hero-div-bottom-m' => $s('divider_bottom_mobile', '8px'),
            '--hero-div-bottom-d' => $s('divider_bottom_desktop', '0px'),
        ];
    }

    public function heroCssStyleAttr(): string
    {
        $parts = [];
        foreach ($this->heroCssVars() as $prop => $value) {
            $parts[] = $prop.': '.$value;
        }
        $gap = $this->sectionGapVars('hero');
        foreach ($gap as $prop => $value) {
            $parts[] = $prop.': '.$value;
        }

        return implode('; ', $parts);
    }

    /**
     * CSS vars for horizontal/vertical gaps between divs inside a section.
     *
     * @param  array{hx_m?:string,hx_d?:string,vy_m?:string,vy_d?:string}  $defaults
     * @return array<string, string>
     */
    public function sectionGapVars(string $section, array $defaults = []): array
    {
        $defs = array_merge([
            'hx_m' => '16px',
            'hx_d' => '24px',
            'vy_m' => '16px',
            'vy_d' => '24px',
        ], $defaults);

        // Legacy second-section keys
        $hxM = $this->get($section, 'gap_horizontal_mobile', '');
        $hxD = $this->get($section, 'gap_horizontal_desktop', '');
        if ($section === 'second') {
            if ($hxM === '') {
                $hxM = $this->get($section, 'card_gap_horizontal_mobile', $defs['hx_m']);
            }
            if ($hxD === '') {
                $hxD = $this->get($section, 'card_gap_horizontal_desktop', $defs['hx_d']);
            }
        }

        return [
            '--sec-gap-x-m' => $this->cssSize($hxM !== '' ? $hxM : $defs['hx_m'], $defs['hx_m']),
            '--sec-gap-x-d' => $this->cssSize($hxD !== '' ? $hxD : $defs['hx_d'], $defs['hx_d']),
            '--sec-gap-y-m' => $this->cssSize(
                $this->get($section, 'gap_vertical_mobile', $defs['vy_m']),
                $defs['vy_m'],
            ),
            '--sec-gap-y-d' => $this->cssSize(
                $this->get($section, 'gap_vertical_desktop', $defs['vy_d']),
                $defs['vy_d'],
            ),
        ];
    }

    public function sectionGapStyleAttr(string $section, array $defaults = []): string
    {
        $parts = [];
        foreach ($this->sectionGapVars($section, $defaults) as $prop => $value) {
            $parts[] = $prop.': '.$value;
        }

        return implode('; ', $parts);
    }

    private function cssSize(string $raw, string $fallback): string
    {
        $v = trim($raw);
        if ($v === '') {
            return $fallback;
        }
        if (preg_match('/^-?\d+(\.\d+)?$/', $v)) {
            return $v.'px';
        }

        return $v;
    }

    private function styleVal(string $section, string $key, string $default): string
    {
        $raw = trim($this->get($section, $key, $default));

        return $raw !== '' ? $raw : $default;
    }
}
