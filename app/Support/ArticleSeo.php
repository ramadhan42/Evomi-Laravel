<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * SEO payload for an article page: the meta/social copy, the FAQ pairs and
 * the JSON-LD graph that Google reads.
 *
 * Everything here degrades gracefully - an editor who fills in nothing still
 * gets a valid title, description and BlogPosting schema built from the
 * article itself.
 */
final class ArticleSeo
{
    /** Schema.org types the editor may pick for the main entity. */
    public const SCHEMA_TYPES = ['BlogPosting', 'Article', 'NewsArticle'];

    /** Google truncates around here, so this is what the counters aim at. */
    public const TITLE_MAX = 60;

    public const DESCRIPTION_MAX = 160;

    public static function schemaType(?string $raw, string $fallback = 'BlogPosting'): string
    {
        $value = trim((string) $raw);

        return in_array($value, self::SCHEMA_TYPES, true) ? $value : $fallback;
    }

    /**
     * FAQ rows come from the dashboard as a loose array; keep only pairs that
     * have both a question and an answer so FAQPage never ships empty entries.
     *
     * @return list<array{question: string, answer: string, question_en: string, answer_en: string}>
     */
    public static function normalizeFaqs($raw): array
    {
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : [];
        }

        if (! is_array($raw)) {
            return [];
        }

        $out = [];
        foreach ($raw as $row) {
            if (! is_array($row)) {
                continue;
            }

            $question = self::clean($row['question'] ?? '');
            $answer = self::clean($row['answer'] ?? '');

            if ($question === '' || $answer === '') {
                continue;
            }

            $out[] = [
                'question' => Str::limit($question, 300, ''),
                'answer' => Str::limit($answer, 1200, ''),
                'question_en' => Str::limit(self::clean($row['question_en'] ?? ''), 300, ''),
                'answer_en' => Str::limit(self::clean($row['answer_en'] ?? ''), 1200, ''),
            ];

            if (count($out) >= 20) {
                break;
            }
        }

        return $out;
    }

    /**
     * FAQ rows resolved to one locale, ready to render and to feed FAQPage.
     *
     * @return list<array{question: string, answer: string}>
     */
    public static function localizedFaqs($raw, string $locale = 'id'): array
    {
        $out = [];
        foreach (self::normalizeFaqs($raw) as $row) {
            $question = $locale === 'en' && $row['question_en'] !== '' ? $row['question_en'] : $row['question'];
            $answer = $locale === 'en' && $row['answer_en'] !== '' ? $row['answer_en'] : $row['answer'];

            $out[] = ['question' => $question, 'answer' => $answer];
        }

        return $out;
    }

    /**
     * Keywords stay a comma separated string in the DB; split it for the
     * meta tag and for schema `keywords`.
     *
     * @return list<string>
     */
    public static function keywordList(?string $raw): array
    {
        $parts = array_map(
            fn ($k) => self::clean($k),
            preg_split('/[,\n]+/u', (string) $raw) ?: []
        );

        return array_values(array_filter(array_unique($parts), fn ($k) => $k !== ''));
    }

    /**
     * Trim a description to whole words so the snippet never ends mid-word.
     */
    public static function truncate(string $text, int $max): string
    {
        $text = self::clean($text);

        if ($text === '' || mb_strlen($text) <= $max) {
            return $text;
        }

        $cut = mb_substr($text, 0, $max);
        $lastSpace = mb_strrpos($cut, ' ');

        if ($lastSpace !== false && $lastSpace > (int) ($max * 0.6)) {
            $cut = mb_substr($cut, 0, $lastSpace);
        }

        return rtrim($cut, ' .,;:-').'…';
    }

    /**
     * Build the whole SEO bundle for one already-localized article array
     * (the shape PageController::mapArticle returns).
     *
     * @param  array<string, mixed>  $article
     * @return array<string, mixed>
     */
    public static function forArticle(array $article, string $url, string $locale = 'id'): array
    {
        $siteName = 'Evomi';
        $title = self::clean($article['title'] ?? '');
        $metaTitle = self::clean($article['meta_title'] ?? '');

        if ($metaTitle === '') {
            $metaTitle = $title;
        }

        $metaDescription = self::clean($article['meta_description'] ?? '');
        if ($metaDescription === '') {
            $metaDescription = self::clean($article['excerpt_text'] ?? $article['excerpt'] ?? '');
        }
        if ($metaDescription === '') {
            $metaDescription = self::clean($article['content_text'] ?? '');
        }
        $metaDescription = self::truncate($metaDescription, self::DESCRIPTION_MAX);

        $canonical = self::clean($article['canonical_url'] ?? '');
        if ($canonical === '' || ! preg_match('#^https?://#i', $canonical)) {
            $canonical = $url;
        }

        $image = trim((string) ($article['image'] ?? ''));
        $keywords = self::keywordList($article['meta_keywords'] ?? '');
        $faqs = self::localizedFaqs($article['faqs'] ?? [], $locale);
        $noindex = (bool) ($article['noindex'] ?? false);

        return [
            'title' => $metaTitle,
            'title_tag' => $metaTitle !== '' ? $metaTitle.' | '.$siteName : $siteName,
            'description' => $metaDescription,
            'canonical' => $canonical,
            'image' => $image,
            'keywords' => $keywords,
            'keywords_string' => implode(', ', $keywords),
            'noindex' => $noindex,
            'robots' => $noindex
                ? 'noindex, nofollow'
                : 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1',
            'locale' => $locale === 'en' ? 'en_US' : 'id_ID',
            'og_type' => 'article',
            'faqs' => $faqs,
            'schema' => self::schemaGraph($article, [
                'url' => $canonical,
                'title' => $metaTitle !== '' ? $metaTitle : $title,
                'description' => $metaDescription,
                'image' => $image,
                'keywords' => $keywords,
                'faqs' => $faqs,
                'locale' => $locale,
            ]),
        ];
    }

    /**
     * The JSON-LD graph: the article itself, its breadcrumb trail and - when
     * the editor added any - an FAQPage. A hand-written `schema_json`
     * replaces the generated article node entirely.
     *
     * @param  array<string, mixed>  $article
     * @param  array<string, mixed>  $ctx
     * @return array<string, mixed>
     */
    public static function schemaGraph(array $article, array $ctx): array
    {
        $url = (string) $ctx['url'];
        $siteUrl = rtrim((string) config('app.url'), '/');
        $published = self::isoDate($article['published_at_iso'] ?? $article['published_at'] ?? null);
        $modified = self::isoDate($article['updated_at'] ?? null) ?: $published;

        $node = [
            '@type' => self::schemaType($article['schema_type'] ?? null),
            '@id' => $url.'#article',
            'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $url],
            'headline' => Str::limit((string) $ctx['title'], 110, ''),
            'name' => (string) $ctx['title'],
            'description' => (string) $ctx['description'],
            'url' => $url,
            'inLanguage' => $ctx['locale'] === 'en' ? 'en-US' : 'id-ID',
            'author' => [
                '@type' => 'Person',
                'name' => self::clean($article['author'] ?? '') ?: 'Evomi',
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => 'Evomi',
                'url' => $siteUrl !== '' ? $siteUrl : $url,
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => $siteUrl.'/favicon.png',
                ],
            ],
        ];

        if ($ctx['image'] !== '') {
            $node['image'] = [
                '@type' => 'ImageObject',
                'url' => $ctx['image'],
            ];
        }

        if ($published !== '') {
            $node['datePublished'] = $published;
        }
        if ($modified !== '') {
            $node['dateModified'] = $modified;
        }
        if (! empty($ctx['keywords'])) {
            $node['keywords'] = implode(', ', $ctx['keywords']);
        }
        if (! empty($article['category'])) {
            $node['articleSection'] = (string) $article['category'];
        }

        $body = self::clean($article['content_text'] ?? '');
        if ($body !== '') {
            $node['wordCount'] = count(preg_split('/\s+/u', $body, -1, PREG_SPLIT_NO_EMPTY) ?: []);
        }

        // An editor-supplied blob wins over everything we inferred.
        $custom = self::decodeCustom($article['schema_json'] ?? null);
        if ($custom !== null) {
            $node = $custom;
        }

        $graph = [$node];

        $graph[] = [
            '@type' => 'BreadcrumbList',
            '@id' => $url.'#breadcrumb',
            'itemListElement' => [
                [
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => 'Evomi',
                    'item' => $siteUrl !== '' ? $siteUrl.'/' : $url,
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 2,
                    'name' => $ctx['locale'] === 'en' ? 'Articles' : 'Artikel',
                    'item' => $siteUrl.'/artikel',
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 3,
                    'name' => (string) $ctx['title'],
                    'item' => $url,
                ],
            ],
        ];

        if (! empty($ctx['faqs'])) {
            $graph[] = [
                '@type' => 'FAQPage',
                '@id' => $url.'#faq',
                'mainEntity' => array_map(fn (array $faq) => [
                    '@type' => 'Question',
                    'name' => $faq['question'],
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => $faq['answer'],
                    ],
                ], $ctx['faqs']),
            ];
        }

        return ['@context' => 'https://schema.org', '@graph' => $graph];
    }

    /**
     * A custom schema blob is only honoured when it parses as a JSON object.
     * Anything else silently falls back to the generated node.
     *
     * @return array<string, mixed>|null
     */
    private static function decodeCustom(?string $raw): ?array
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return null;
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) && $decoded !== [] ? $decoded : null;
    }

    private static function isoDate($value): string
    {
        if (empty($value)) {
            return '';
        }

        try {
            return Carbon::parse($value)->toIso8601String();
        } catch (\Throwable) {
            return '';
        }
    }

    private static function clean($value): string
    {
        $text = ArticleContent::plainText(is_scalar($value) ? (string) $value : '');

        return trim($text);
    }
}
