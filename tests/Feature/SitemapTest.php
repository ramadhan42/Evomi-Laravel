<?php

namespace Tests\Feature;

use App\Http\Controllers\SitemapController;
use App\Models\Article;
use App\Models\SeoSetting;
use App\Support\SiteSeo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SitemapTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    /** @param array<string, mixed> $overrides */
    private function makeArticle(string $slug, array $overrides = []): Article
    {
        return Article::query()->create($overrides + [
            'title' => 'Artikel '.$slug,
            'slug' => $slug,
            'excerpt' => 'Ringkasan singkat.',
            'content' => '<p>Isi artikel.</p>',
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);
    }

    public function test_it_serves_xml_with_the_public_pages(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/xml; charset=UTF-8');

        $body = $response->getContent();

        $this->assertStringContainsString('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">', $body);
        $this->assertStringContainsString('<loc>'.route('beranda').'</loc>', $body);
        $this->assertStringContainsString('<loc>'.route('artikel').'</loc>', $body);
        $this->assertStringContainsString('<loc>'.route('faq').'</loc>', $body);
    }

    public function test_it_is_well_formed_xml(): void
    {
        $body = $this->get('/sitemap.xml')->getContent();

        $previous = libxml_use_internal_errors(true);
        $document = simplexml_load_string($body);
        libxml_use_internal_errors($previous);

        $this->assertNotFalse($document, 'The sitemap is not well-formed XML.');
    }

    public function test_it_lists_published_articles_and_skips_the_rest(): void
    {
        $published = $this->makeArticle('aroma-pagi', ['noindex' => false]);
        $this->makeArticle('masih-draft', ['is_published' => false]);
        $this->makeArticle('disembunyikan', ['noindex' => true]);

        $body = $this->get('/sitemap.xml')->getContent();

        $this->assertStringContainsString(route('artikel.show', $published->slug), $body);
        $this->assertStringNotContainsString('masih-draft', $body);
        $this->assertStringNotContainsString('disembunyikan', $body);
    }

    public function test_a_page_marked_noindex_is_left_out(): void
    {
        SeoSetting::query()->updateOrCreate(['page' => 'faq'], ['noindex' => true]);

        $body = $this->get('/sitemap.xml')->getContent();

        $this->assertStringNotContainsString('<loc>'.route('faq').'</loc>', $body);
        $this->assertStringContainsString('<loc>'.route('beranda').'</loc>', $body);
    }

    public function test_the_fallback_row_is_not_a_url(): void
    {
        $body = $this->get('/sitemap.xml')->getContent();

        $this->assertStringNotContainsString('/default', $body);
    }

    public function test_private_pages_stay_out(): void
    {
        $body = $this->get('/sitemap.xml')->getContent();

        foreach (['/checkout', '/login', '/register', '/dashboard', '/profile'] as $path) {
            $this->assertStringNotContainsString('<loc>'.url($path).'</loc>', $body);
        }
    }

    /** The few seconds during a deploy when the code is new and the table is not. */
    public function test_it_still_renders_when_the_seo_table_is_missing(): void
    {
        SiteSeo::forgetCache();
        SitemapController::forgetCache();
        Schema::drop('seo_settings');

        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $this->assertStringContainsString('<loc>'.route('beranda').'</loc>', $response->getContent());
    }

    /**
     * The default row feeds every page that left a field blank, so editing it
     * has to move their lastmod - otherwise a site-wide description or share
     * image change ships with a sitemap still claiming nothing happened.
     */
    public function test_editing_the_default_row_moves_lastmod_on_inheriting_pages(): void
    {
        $before = $this->lastmodFor(route('faq'));
        $this->assertNotSame('', $before);

        $this->travel(2)->days();
        SeoSetting::query()->updateOrCreate(
            ['page' => SiteSeo::DEFAULT_PAGE],
            ['meta_description' => 'Deskripsi baru untuk seluruh situs.']
        );
        SiteSeo::forgetCache();
        SitemapController::forgetCache();

        $after = $this->lastmodFor(route('faq'));

        $this->assertNotSame($before, $after, 'lastmod halaman FAQ tidak ikut bergerak.');
        $this->assertTrue(Carbon::parse($after)->greaterThan(Carbon::parse($before)));
    }

    /** A page that fills in everything itself does not inherit, so it must not move. */
    public function test_a_fully_filled_page_ignores_the_default_row(): void
    {
        SeoSetting::query()->updateOrCreate(['page' => 'faq'], [
            'meta_title' => 'FAQ Evomi',
            'meta_description' => 'Pertanyaan yang sering diajukan.',
            'meta_keywords' => 'faq, evomi',
            'og_image' => 'cms/seo/faq.png',
        ]);
        SiteSeo::forgetCache();
        SitemapController::forgetCache();

        $before = $this->lastmodFor(route('faq'));

        $this->travel(2)->days();
        SeoSetting::query()->updateOrCreate(
            ['page' => SiteSeo::DEFAULT_PAGE],
            ['meta_description' => 'Deskripsi situs yang lain lagi.']
        );
        SiteSeo::forgetCache();
        SitemapController::forgetCache();

        $this->assertSame($before, $this->lastmodFor(route('faq')));
    }

    /** Pull the lastmod that belongs to one loc out of the document. */
    private function lastmodFor(string $loc): string
    {
        $body = $this->get('/sitemap.xml')->getContent();
        $document = simplexml_load_string($body);

        foreach ($document->url as $url) {
            if ((string) $url->loc === $loc) {
                return (string) $url->lastmod;
            }
        }

        return '';
    }

    public function test_editing_seo_settings_refreshes_the_cached_document(): void
    {
        $before = $this->get('/sitemap.xml')->getContent();
        $this->assertStringContainsString('<loc>'.route('faq').'</loc>', $before);

        // Exactly what the dashboard does when an editor saves a page.
        SeoSetting::query()->updateOrCreate(['page' => 'faq'], ['noindex' => true]);
        SiteSeo::forgetCache();
        SitemapController::forgetCache();

        $after = $this->get('/sitemap.xml')->getContent();
        $this->assertStringNotContainsString('<loc>'.route('faq').'</loc>', $after);
    }
}
