<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Product;
use App\Models\SeoSetting;
use App\Models\SiteContent;
use App\Support\SiteSeo;
use Illuminate\Database\QueryException;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * GET /sitemap.xml
 *
 * The list of pages we want Google to crawl, each with the date it last
 * changed. `lastmod` is the point of the whole file: it is how Google learns
 * that a description or share image edited in the dashboard is worth
 * re-fetching, instead of waiting weeks for a routine recrawl.
 *
 * Pages an editor marked noindex are left out, as is anything private
 * (checkout, payment, auth, the dashboard) and every per-visitor URL such as
 * a tracking result.
 */
class SitemapController extends Controller
{
    /** Crawlers hit this repeatedly; the DB work is worth caching. */
    private const CACHE_KEY = 'sitemap.xml';

    private const CACHE_TTL = 900;

    public function __invoke(): Response
    {
        $xml = Cache::remember(self::CACHE_KEY, self::CACHE_TTL, fn () => $this->build());

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'X-Robots-Tag' => 'noindex',
        ]);
    }

    /** Drop the cached document - call after anything a URL entry reflects. */
    public static function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    private function build(): string
    {
        $entries = array_merge($this->staticPages(), $this->articles(), $this->products());

        $lines = ['<?xml version="1.0" encoding="UTF-8"?>'];
        $lines[] = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        foreach ($entries as $entry) {
            $lines[] = '  <url>';
            $lines[] = '    <loc>'.htmlspecialchars($entry['loc'], ENT_XML1 | ENT_QUOTES, 'UTF-8').'</loc>';
            if ($entry['lastmod'] !== '') {
                $lines[] = '    <lastmod>'.$entry['lastmod'].'</lastmod>';
            }
            $lines[] = '  </url>';
        }

        $lines[] = '</urlset>';

        return implode("\n", $lines)."\n";
    }

    /**
     * The fixed pages, straight from the list the dashboard SEO menu edits.
     *
     * A page counts as changed when either its SEO row or the CMS copy behind
     * it was touched, so editing a description really does move `lastmod`.
     *
     * @return list<array{loc: string, lastmod: string}>
     */
    private function staticPages(): array
    {
        // The deploy copies code before `migrate` runs, so for a few seconds
        // this table may not exist yet. A sitemap without `lastmod` is still a
        // valid sitemap; a 500 logged in Search Console is not worth it.
        try {
            $seoDates = SeoSetting::query()->pluck('updated_at', 'page');
        } catch (QueryException $e) {
            report($e);
            $seoDates = collect();
        }

        $cmsDates = SiteContent::query()
            ->selectRaw('page, MAX(updated_at) as updated_at')
            ->groupBy('page')
            ->pluck('updated_at', 'page');

        $newestArticle = Article::query()->published()->max('updated_at');
        $newestProduct = Product::query()->max('updated_at');

        $out = [];

        foreach (SiteSeo::all() as $page => $row) {
            // "default" is only a fallback holder, not a page anyone can visit.
            if ($page === SiteSeo::DEFAULT_PAGE || ! empty($row['noindex'])) {
                continue;
            }

            $candidates = [$seoDates[$page] ?? null, $cmsDates[$page] ?? null];

            // The two index pages also age with what they list.
            if ($page === 'artikel') {
                $candidates[] = $newestArticle;
            }
            if ($page === 'belanja') {
                $candidates[] = $newestProduct;
            }

            $out[] = [
                'loc' => $row['url'],
                'lastmod' => $this->newest($candidates),
            ];
        }

        return $out;
    }

    /** @return list<array{loc: string, lastmod: string}> */
    private function articles(): array
    {
        return Article::query()
            ->published()
            ->where(fn ($q) => $q->where('noindex', false)->orWhereNull('noindex'))
            ->orderByDesc('published_at')
            ->get(['slug', 'updated_at', 'published_at'])
            ->map(fn (Article $a) => [
                'loc' => route('artikel.show', $a->slug),
                'lastmod' => $this->newest([$a->updated_at, $a->published_at]),
            ])
            ->all();
    }

    /** @return list<array{loc: string, lastmod: string}> */
    private function products(): array
    {
        return Product::query()
            ->orderBy('id')
            ->get(['id', 'updated_at'])
            ->map(fn (Product $p) => [
                'loc' => route('belanja.show', $p->id),
                'lastmod' => $this->newest([$p->updated_at]),
            ])
            ->all();
    }

    /**
     * The most recent of a set of dates, as W3C datetime. Empty when nothing
     * usable was given - a missing `lastmod` is better than an invented one.
     *
     * @param  array<int, mixed>  $values
     */
    private function newest(array $values): string
    {
        $newest = null;

        foreach ($values as $value) {
            if (empty($value)) {
                continue;
            }

            try {
                $date = Carbon::parse($value);
            } catch (\Throwable) {
                continue;
            }

            if ($newest === null || $date->greaterThan($newest)) {
                $newest = $date;
            }
        }

        return $newest?->toAtomString() ?? '';
    }
}
