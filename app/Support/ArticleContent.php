<?php

namespace App\Support;

/**
 * Heading typography + content parsing for articles.
 *
 * Article content is plain text: a line starting with 1–6 "#" becomes a
 * heading of that level, everything else stays a paragraph. Each level
 * (h1–h6) carries its own font family / weight / style / size, stored on
 * the article as the `heading_fonts` JSON column.
 */
final class ArticleContent
{
    /** @var list<string> */
    public const LEVELS = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'];

    /** Level value meaning "render as normal text, no heading". */
    public const NONE = 'normal';

    /** Levels a body field (excerpt/content) may be rendered with. */
    public const BLOCK_LEVELS = ['normal', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'];

    /** @var list<string> */
    public const FONT_KEYS = ['font_family', 'font_weight', 'font_style', 'font_size'];

    /**
     * Per-level defaults, mirroring the article title/excerpt/content scale.
     *
     * @return array<string, array<string, string>>
     */
    public static function defaults(): array
    {
        return [
            'h1' => ['font_family' => 'nohemi', 'font_weight' => '700', 'font_style' => 'normal', 'font_size' => '32'],
            'h2' => ['font_family' => 'nohemi', 'font_weight' => '700', 'font_style' => 'normal', 'font_size' => '28'],
            'h3' => ['font_family' => 'nohemi', 'font_weight' => '600', 'font_style' => 'normal', 'font_size' => '22'],
            'h4' => ['font_family' => 'parkinsans', 'font_weight' => '600', 'font_style' => 'normal', 'font_size' => '20'],
            'h5' => ['font_family' => 'parkinsans', 'font_weight' => '600', 'font_style' => 'normal', 'font_size' => '18'],
            'h6' => ['font_family' => 'parkinsans', 'font_weight' => '600', 'font_style' => 'normal', 'font_size' => '16'],
        ];
    }

    public static function isLevel(?string $raw): bool
    {
        return in_array(strtolower(trim((string) $raw)), self::LEVELS, true);
    }

    public static function headingLevel(?string $raw, string $fallback = 'h1'): string
    {
        $v = strtolower(trim((string) $raw));

        return self::isLevel($v) ? $v : $fallback;
    }

    /**
     * Level for a body field: a heading level, or "normal" for plain text.
     */
    public static function blockLevel(?string $raw, string $fallback = self::NONE): string
    {
        $v = strtolower(trim((string) $raw));

        if (in_array($v, self::BLOCK_LEVELS, true)) {
            return $v;
        }

        return in_array($fallback, self::BLOCK_LEVELS, true) ? $fallback : self::NONE;
    }

    /**
     * Keep only known levels and font keys, filling the gaps with defaults.
     *
     * @param  mixed  $raw
     * @return array<string, array<string, string>>
     */
    public static function normalizeFonts($raw): array
    {
        $input = is_array($raw) ? $raw : [];
        $out = [];

        foreach (self::defaults() as $level => $defaults) {
            $given = is_array($input[$level] ?? null) ? $input[$level] : [];
            $row = [];
            foreach (self::FONT_KEYS as $key) {
                $value = trim((string) ($given[$key] ?? ''));
                $row[$key] = $value !== '' ? $value : $defaults[$key];
            }
            $row['font_style'] = $row['font_style'] === 'italic' ? 'italic' : 'normal';
            $out[$level] = $row;
        }

        return $out;
    }

    /**
     * Normalize a raw font-size value into a CSS length (px when unitless).
     */
    public static function cssFontSize(?string $raw, string $fallback): string
    {
        $size = trim((string) $raw);

        if (preg_match('/^\d+(\.\d+)?(px|rem|em)$/i', $size)) {
            return $size;
        }
        if (preg_match('/^\d+(\.\d+)?$/', $size)) {
            return $size.'px';
        }

        return $fallback.'px';
    }

    /**
     * Inline CSS for one heading level.
     *
     * @param  mixed  $fonts  the article's raw `heading_fonts` value
     */
    public static function headingFontInline($fonts, string $level): string
    {
        $defaults = self::defaults();
        $level = self::headingLevel($level);
        $row = self::normalizeFonts($fonts)[$level];

        $family = CmsStorefront::fontFamilyCss($row['font_family'], $defaults[$level]['font_family']);
        $weight = CmsStorefront::fontWeight($row['font_weight'], $defaults[$level]['font_weight']);
        $style = CmsStorefront::fontStyle($row['font_style'], $defaults[$level]['font_style']);
        $size = self::cssFontSize($row['font_size'], $defaults[$level]['font_size']);

        return "font-family: {$family}; font-weight: {$weight}; font-style: {$style}; font-size: {$size};";
    }

    /**
     * Inline CSS for every heading level, keyed by level.
     *
     * @param  mixed  $fonts
     * @return array<string, string>
     */
    public static function headingFontStyles($fonts): array
    {
        $out = [];
        foreach (self::LEVELS as $level) {
            $out[$level] = self::headingFontInline($fonts, $level);
        }

        return $out;
    }

    /**
     * Split article content into renderable blocks.
     *
     * A line like "## Aroma" becomes ['tag' => 'h2', 'text' => 'Aroma'];
     * blank-line separated runs of other lines become paragraphs.
     *
     * @return list<array{tag: string, text: string}>
     */
    public static function blocks(?string $content): array
    {
        $lines = preg_split("/\r\n|\n|\r/", trim((string) $content));
        if (! is_array($lines)) {
            return [];
        }

        $blocks = [];
        $buffer = [];

        $flush = static function () use (&$blocks, &$buffer): void {
            $text = trim(implode("\n", $buffer));
            $buffer = [];
            if ($text !== '') {
                $blocks[] = ['tag' => 'p', 'text' => $text];
            }
        };

        foreach ($lines as $line) {
            if (preg_match('/^\s*(#{1,6})\s+(\S.*)$/', $line, $m)) {
                $flush();
                $blocks[] = [
                    'tag' => 'h'.strlen($m[1]),
                    'text' => trim($m[2]),
                ];

                continue;
            }

            if (trim($line) === '') {
                $flush();

                continue;
            }

            $buffer[] = $line;
        }

        $flush();

        return $blocks;
    }
}
