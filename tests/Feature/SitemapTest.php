<?php

namespace Tests\Feature;

use App\Http\Controllers\SitemapController;
use App\Models\Article;
use App\Models\SeoSetting;
use App\Support\SiteSeo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
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
