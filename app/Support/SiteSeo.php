<?php

namespace App\Support;

use App\Models\SeoSetting;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;

/**
 * Site-wide SEO: the title, description and share image Google and the social
 * networks show for each public page.
 *
 * Rows live in `seo_settings`, one per page plus a "default" row the others
 * fall back to. The dashboard SEO menu is the only thing that writes them.
 *
 * The array returned by forPage() is deliberately the same shape ArticleSeo
 * produces, so partials/seo-head can render either without branching.
 */
final class SiteSeo
{
    /** Cache key for the whole table - small enough to hold in one entry. */
    private const CACHE_KEY = 'seo_settings.all';

    private const CACHE_TTL = 3600;

    /** The row every other page falls back to. */
    public const DEFAULT_PAGE = 'default';

    /**
     * Pages the dashboard can edit, in the order they are listed there.
     *
     * @var array<string, array{label: string, label_en: string, route: ?string}>
     */
    public const PAGES = [
        'default' => ['label' => 'Default situs', 'label_en' => 'Site default', 'route' => null],
        'beranda' => ['label' => 'Beranda', 'label_en' => 'Home', 'route' => 'beranda'],
        'belanja' => ['label' => 'Belanja', 'label_en' => 'Shop', 'route' => 'belanja'],
        'artikel' => ['label' => 'Artikel', 'label_en' => 'Articles', 'route' => 'artikel'],
        'kuis' => ['label' => 'Kuis', 'label_en' => 'Quiz', 'route' => 'kuis'],
        'faq' => ['label' => 'FAQ', 'label_en' => 'FAQ', 'route' => 'faq'],
        'kontak' => ['label' => 'Kontak', 'label_en' => 'Contact', 'route' => 'kontak'],
        'pengiriman' => ['label' => 'Pengiriman', 'label_en' => 'Shipping', 'route' => 'pengiriman'],
    ];

    /** Route names that map onto a page key beyond the obvious one-to-one. */
    private const ROUTE_ALIASES = [
        'pengiriman.show' => 'pengiriman',
    ];

    public static function isPage(?string $page): bool
    {
        return array_key_exists((string) $page, self::PAGES);
    }

    /** The page key for a route name, or null when that route has no row. */
    public static function pageForRoute(?string $routeName): ?string
    {
        $name = (string) $routeName;

        if (isset(self::ROUTE_ALIASES[$name])) {
            return self::ROUTE_ALIASES[$name];
        }

        return self::isPage($name) && $name !== self::DEFAULT_PAGE ? $name : null;
    }

    /**
     * Every row keyed by page, with the missing ones filled in as blanks so the
     * dashboard always shows the full list.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function all(): array
    {
        $stored = Cache::get(self::CACHE_KEY);

        if ($stored === null) {
            $stored = self::stored();

            // A failed read is never cached, so the pages recover on the very
            // next request once the table is back.
            if ($stored === null) {
                $stored = [];
            } else {
                Cache::put(self::CACHE_KEY, $stored, self::CACHE_TTL);
            }
        }

        $out = [];
        foreach (self::PAGES as $page => $meta) {
            $out[$page] = $stored[$page] ?? [
                'page' => $page,
                'meta_title' => '',
                'meta_title_en' => '',
                'meta_description' => '',
                'meta_description_en' => '',
                'meta_keywords' => '',
                'og_image' => '',
                'noindex' => false,
            ];
            $out[$page]['label'] = $meta['label'];
            $out[$page]['label_en'] = $meta['label_en'];
            $out[$page]['url'] = $meta['route'] ? route($meta['route']) : rtrim((string) config('app.url'), '/').'/';
        }

        return $out;
    }

    public static function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * The rows as stored, or none at all when the table cannot be read.
     *
     * This runs on every storefront page, so it must never be what takes the
     * site down. During a deploy the new code is in place for a few seconds
     * before `migrate` creates the table, and a hosting panel can revoke a
     * grant at any time; in both cases the pages should quietly fall back to
     * the built-in copy rather than return a 500.
     *
     * @return array<string, array<string, mixed>>|null
     */
    private static function stored(): ?array
    {
        try {
            return SeoSetting::query()->get()->keyBy('page')->map(fn (SeoSetting $r) => [
                'page' => $r->page,
                'meta_title' => (string) $r->meta_title,
                'meta_title_en' => (string) $r->meta_title_en,
                'meta_description' => (string) $r->meta_description,
                'meta_description_en' => (string) $r->meta_description_en,
                'meta_keywords' => (string) $r->meta_keywords,
                'og_image' => (string) $r->og_image,
                'noindex' => (bool) $r->noindex,
            ])->all();
        } catch (QueryException $e) {
            report($e);

            return null;
        }
    }

    /**
     * Resolve one page for the storefront. Anything the page leaves empty comes
     * from the "default" row; anything still empty falls back to the built-in
     * copy so the tags are never blank.
     *
     * @return array<string, mixed>
     */
    public static function forPage(string $page, ?string $url = null, ?string $locale = null): array
    {
        $locale = $locale === 'en' ? 'en' : 'id';
        $rows = self::all();
        $row = $rows[$page] ?? $rows[self::DEFAULT_PAGE];
        $default = $rows[self::DEFAULT_PAGE];
        $url = $url ?: (request() ? url()->current() : rtrim((string) config('app.url'), '/'));

        $title = self::pick($row, $default, 'meta_title', $locale)
            ?: ($locale === 'en' ? 'Evomi Perfume' : 'Evomi Perfume');

        $description = self::pick($row, $default, 'meta_description', $locale)
            ?: ($locale === 'en'
                ? 'Discover the exclusive Evomi fragrances that mirror your personality.'
                : 'Temukan keharuman eksklusif Evomi yang mencerminkan kepribadian Anda.');
        $description = ArticleSeo::truncate($description, ArticleSeo::DESCRIPTION_MAX);

        $keywords = ArticleSeo::keywordList(
            ($row['meta_keywords'] ?? '') !== '' ? $row['meta_keywords'] : ($default['meta_keywords'] ?? '')
        );

        $image = self::imageUrl(($row['og_image'] ?? '') !== '' ? $row['og_image'] : ($default['og_image'] ?? ''));
        $noindex = (bool) ($row['noindex'] ?? false);

        return [
            'page' => $page,
            'title' => $title,
            // The home page already reads as the brand, so it is not suffixed.
            'title_tag' => self::titleTag($title, $page),
            'description' => $description,
            'canonical' => $url,
            'image' => $image,
            'keywords' => $keywords,
            'keywords_string' => implode(', ', $keywords),
            'noindex' => $noindex,
            'robots' => $noindex
                ? 'noindex, nofollow'
                : 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1',
            'locale' => $locale === 'en' ? 'en_US' : 'id_ID',
            'og_type' => 'website',
            'faqs' => [],
            'schema' => self::schemaGraph($page, $title, $description, $url, $image, $locale),
        ];
    }

    /**
     * WebSite + Organization on the home page (this is what feeds Google's
     * brand panel and the site name shown above a result); a plain WebPage
     * with breadcrumbs everywhere else.
     *
     * @return array<string, mixed>
     */
    private static function schemaGraph(
        string $page,
        string $title,
        string $description,
        string $url,
        string $image,
        string $locale,
    ): array {
        $siteUrl = rtrim((string) config('app.url'), '/');
        $inLanguage = $locale === 'en' ? 'en-US' : 'id-ID';

        $organization = [
            '@type' => 'Organization',
            '@id' => $siteUrl.'/#organization',
            'name' => 'Evomi',
            'url' => $siteUrl.'/',
            'logo' => [
                '@type' => 'ImageObject',
                'url' => $siteUrl.'/favicon.png',
            ],
        ];

        if ($image !== '') {
            $organization['image'] = $image;
        }

        $website = [
            '@type' => 'WebSite',
            '@id' => $siteUrl.'/#website',
            'name' => 'Evomi',
            'url' => $siteUrl.'/',
            'inLanguage' => $inLanguage,
            'publisher' => ['@id' => $siteUrl.'/#organization'],
        ];

        $graph = [$organization, $website];

        if ($page !== 'beranda' && $page !== self::DEFAULT_PAGE) {
            $graph[] = [
                '@type' => 'WebPage',
                '@id' => $url.'#webpage',
                'url' => $url,
                'name' => $title,
                'description' => $description,
                'inLanguage' => $inLanguage,
                'isPartOf' => ['@id' => $siteUrl.'/#website'],
            ];

            $graph[] = [
                '@type' => 'BreadcrumbList',
                '@id' => $url.'#breadcrumb',
                'itemListElement' => [
                    [
                        '@type' => 'ListItem',
                        'position' => 1,
                        'name' => 'Evomi',
                        'item' => $siteUrl.'/',
                    ],
                    [
                        '@type' => 'ListItem',
                        'position' => 2,
                        'name' => $title,
                        'item' => $url,
                    ],
                ],
            ];
        }

        return ['@context' => 'https://schema.org', '@graph' => $graph];
    }

    private static function titleTag(string $title, string $page): string
    {
        if ($page === 'beranda' || $page === self::DEFAULT_PAGE) {
            return $title;
        }

        return str_contains(strtolower($title), 'evomi') ? $title : $title.' | Evomi';
    }

    /**
     * Locale-aware read with a fall-through to the default row.
     *
     * @param  array<string, mixed>  $row
     * @param  array<string, mixed>  $default
     */
    private static function pick(array $row, array $default, string $field, string $locale): string
    {
        $candidates = $locale === 'en'
            ? [$row[$field.'_en'] ?? '', $row[$field] ?? '', $default[$field.'_en'] ?? '', $default[$field] ?? '']
            : [$row[$field] ?? '', $default[$field] ?? ''];

        foreach ($candidates as $candidate) {
            $value = trim((string) $candidate);
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    /** Share images must be absolute for Google and the social crawlers. */
    public static function imageUrl(?string $path): string
    {
        $resolved = CmsStorefront::resolveImage($path);

        if ($resolved === '') {
            return '';
        }

        if (preg_match('#^https?://#i', $resolved)) {
            return $resolved;
        }

        return rtrim((string) config('app.url'), '/').'/'.ltrim($resolved, '/');
    }
}
