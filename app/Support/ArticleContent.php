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
     * Content on its way into the database: sanitized HTML from the editor,
     * or plain text left as typed for articles that predate it.
     */
    public static function normalizeContent(?string $content): string
    {
        $content = (string) $content;

        return self::looksLikeHtml($content) ? self::sanitizeHtml($content) : trim($content);
    }

    /**
     * Rich-text content is stored as HTML once it has been through the editor.
     * Older articles are plain text, so both shapes have to keep rendering.
     */
    public static function looksLikeHtml(?string $content): bool
    {
        return (bool) preg_match(
            '/<(p|div|br|hr|h[1-6]|ul|ol|li|blockquote|span|strong|b|em|i|u|s|a|pre|code)\b[^>]*>/i',
            (string) $content,
        );
    }

    /** Tags the editor may produce; anything else is unwrapped or dropped. */
    private const ALLOWED_TAGS = [
        'p', 'br', 'hr', 'div',
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        'strong', 'b', 'em', 'i', 'u', 's', 'strike', 'del', 'ins', 'mark', 'sub', 'sup',
        'blockquote', 'ul', 'ol', 'li', 'a', 'span', 'code', 'pre',
    ];

    /** Tags removed together with everything inside them. */
    private const DROPPED_TAGS = [
        'script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'select',
        'textarea', 'button', 'link', 'meta', 'base', 'svg', 'math', 'template',
    ];

    /** Inline CSS the editor is allowed to leave behind. */
    private const ALLOWED_STYLE_PROPS = [
        'font-family', 'font-size', 'font-weight', 'font-style',
        'text-decoration', 'text-decoration-line', 'text-align',
        'color', 'background-color', 'line-height', 'margin-left', 'padding-left',
    ];

    /**
     * Strip everything the editor is not allowed to store, so admin-authored
     * HTML can be printed on the storefront without escaping it.
     */
    public static function sanitizeHtml(?string $html): string
    {
        $html = trim((string) $html);
        if ($html === '') {
            return '';
        }

        // Without ext-dom there is no way to filter attributes safely, so the
        // markup is dropped rather than trusted.
        if (! class_exists(\DOMDocument::class)) {
            return trim(strip_tags($html));
        }

        $doc = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $doc->loadHTML(
            '<?xml encoding="utf-8" ?><body>'.$html.'</body>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD,
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $body = $doc->getElementsByTagName('body')->item(0);
        if (! $body) {
            return '';
        }

        self::cleanChildren($body);

        $out = '';
        foreach ($body->childNodes as $child) {
            $out .= $doc->saveHTML($child);
        }

        return trim($out);
    }

    private static function cleanChildren(\DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof \DOMText) {
                continue;
            }

            if (! $child instanceof \DOMElement) {
                $child->parentNode?->removeChild($child);

                continue;
            }

            $tag = strtolower($child->tagName);

            if (in_array($tag, self::DROPPED_TAGS, true)) {
                $child->parentNode?->removeChild($child);

                continue;
            }

            self::cleanChildren($child);

            if (! in_array($tag, self::ALLOWED_TAGS, true)) {
                // Unknown wrapper: keep the text, drop the tag.
                while ($child->firstChild) {
                    $child->parentNode?->insertBefore($child->firstChild, $child);
                }
                $child->parentNode?->removeChild($child);

                continue;
            }

            self::cleanAttributes($child, $tag);
        }
    }

    private static function cleanAttributes(\DOMElement $el, string $tag): void
    {
        foreach (iterator_to_array($el->attributes) as $attr) {
            $name = strtolower($attr->nodeName);
            $value = (string) $attr->nodeValue;

            if ($name === 'style') {
                $style = self::cleanStyle($value);
                if ($style === '') {
                    $el->removeAttribute('style');
                } else {
                    $el->setAttribute('style', $style);
                }

                continue;
            }

            if ($tag === 'a' && in_array($name, ['href', 'target', 'rel'], true)) {
                continue;
            }

            $el->removeAttribute($attr->nodeName);
        }

        if ($tag !== 'a') {
            return;
        }

        $href = trim($el->getAttribute('href'));
        if ($href === '' || ! preg_match('#^(https?://|mailto:|tel:|/|\#)#i', $href)) {
            $el->removeAttribute('href');
            $el->removeAttribute('target');
            $el->removeAttribute('rel');

            return;
        }

        if ($el->getAttribute('target') === '_blank') {
            $el->setAttribute('rel', 'noopener noreferrer');
        } else {
            $el->removeAttribute('target');
            $el->removeAttribute('rel');
        }
    }

    private static function cleanStyle(string $style): string
    {
        $kept = [];

        foreach (explode(';', $style) as $rule) {
            if (! str_contains($rule, ':')) {
                continue;
            }

            [$prop, $value] = explode(':', $rule, 2);
            $prop = strtolower(trim($prop));
            $value = trim($value);

            if (! in_array($prop, self::ALLOWED_STYLE_PROPS, true) || $value === '') {
                continue;
            }
            if (preg_match('/url\s*\(|expression|javascript:|@import|[<>{}\\\\]/i', $value)) {
                continue;
            }
            if (! preg_match("/^[\w\s,.'\"()%#\/+-]+$/u", $value)) {
                continue;
            }

            $kept[] = $prop.': '.$value;
        }

        return implode('; ', $kept);
    }

    /** Tags the excerpt may keep: styling only, never structure. */
    private const INLINE_TAGS = [
        'strong', 'b', 'em', 'i', 'u', 's', 'strike', 'del', 'ins',
        'mark', 'sub', 'sup', 'span', 'a', 'br', 'code',
    ];

    /**
     * The excerpt is edited as rich text but printed inside cards, meta tags
     * and one paragraph, so only inline styling survives.
     */
    public static function sanitizeInlineHtml(?string $html): string
    {
        $clean = self::sanitizeHtml($html);
        if ($clean === '' || ! class_exists(\DOMDocument::class)) {
            return $clean;
        }

        $doc = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $doc->loadHTML(
            '<?xml encoding="utf-8" ?><body>'.$clean.'</body>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD,
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $body = $doc->getElementsByTagName('body')->item(0);
        if (! $body) {
            return '';
        }

        self::flattenToInline($body);

        $out = '';
        foreach ($body->childNodes as $child) {
            $out .= $doc->saveHTML($child);
        }

        return trim(preg_replace('/\s+/u', ' ', $out) ?? '');
    }

    private static function flattenToInline(\DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if (! $child instanceof \DOMElement) {
                continue;
            }

            self::flattenToInline($child);

            if (in_array(strtolower($child->tagName), self::INLINE_TAGS, true)) {
                continue;
            }

            // Blocks become a space so words do not run together.
            $child->parentNode?->insertBefore($child->ownerDocument->createTextNode(' '), $child);
            while ($child->firstChild) {
                $child->parentNode?->insertBefore($child->firstChild, $child);
            }
            $child->parentNode?->insertBefore($child->ownerDocument->createTextNode(' '), $child);
            $child->parentNode?->removeChild($child);
        }
    }

    /** Excerpt on its way into the database. */
    public static function normalizeExcerpt(?string $excerpt): string
    {
        $excerpt = (string) $excerpt;

        return self::looksLikeHtml($excerpt) ? self::sanitizeInlineHtml($excerpt) : trim($excerpt);
    }

    /** Readable text behind the markup, for word counts and previews. */
    public static function plainText(?string $content): string
    {
        $text = preg_replace('/<(br|\/p|\/h[1-6]|\/li|\/div|\/blockquote)\s*\/?>/i', ' ', (string) $content);
        $text = strip_tags((string) $text);

        return trim(html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    /**
     * Per-level heading CSS scoped to one selector, so headings inside a rich
     * text blob pick up the article's own typography.
     */
    public static function headingCss($fonts, string $scope): string
    {
        $lines = [];
        foreach (self::LEVELS as $level) {
            $lines[] = $scope.' '.$level.' { '.self::headingFontInline($fonts, $level).' }';
        }

        return str_replace(['<', '>'], '', implode("\n", $lines));
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
