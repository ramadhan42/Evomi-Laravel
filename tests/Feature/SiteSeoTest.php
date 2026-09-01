<?php

namespace Tests\Feature;

use App\Models\SeoSetting;
use App\Models\User;
use App\Support\SiteSeo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SiteSeoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        SiteSeo::forgetCache();
    }

    private function actingAsAdmin(): User
    {
        $admin = User::factory()->create([
            'email' => 'site-seo-admin@evomi.test',
            'password' => Hash::make('password123'),
            'is_admin' => true,
        ]);
        Sanctum::actingAs($admin);

        return $admin;
    }

    private function setPage(string $page, array $attributes): void
    {
        SeoSetting::query()->updateOrCreate(['page' => $page], $attributes);
        SiteSeo::forgetCache();
    }

    public function test_the_migration_seeds_a_row_for_every_editable_page(): void
    {
        foreach (array_keys(SiteSeo::PAGES) as $page) {
            $this->assertDatabaseHas('seo_settings', ['page' => $page]);
        }
    }

    public function test_a_page_inherits_the_site_default_when_left_empty(): void
    {
        $this->setPage('default', [
            'meta_description' => 'Deskripsi default situs.',
            'og_image' => 'cms/seo/default.jpg',
        ]);
        $this->setPage('kuis', ['meta_description' => null, 'og_image' => null]);

        $seo = SiteSeo::forPage('kuis', 'https://evomi.id/kuis');

        $this->assertSame('Deskripsi default situs.', $seo['description']);
        $this->assertStringEndsWith('/storage/cms/seo/default.jpg', $seo['image']);
    }

    public function test_a_page_value_overrides_the_default(): void
    {
        $this->setPage('default', ['meta_description' => 'Default.']);
        $this->setPage('faq', ['meta_description' => 'Khusus FAQ.']);

        $this->assertSame('Khusus FAQ.', SiteSeo::forPage('faq', 'https://evomi.id/faq')['description']);
    }

    public function test_english_prefers_the_english_column_then_falls_back(): void
    {
        $this->setPage('belanja', [
            'meta_title' => 'Belanja Parfum',
            'meta_title_en' => 'Shop Perfume',
            'meta_description' => 'Koleksi parfum Evomi.',
            'meta_description_en' => null,
        ]);

        $en = SiteSeo::forPage('belanja', 'https://evomi.id/belanja', 'en');

        $this->assertSame('Shop Perfume', $en['title']);
        // No English description yet, so the Indonesian one still ships.
        $this->assertSame('Koleksi parfum Evomi.', $en['description']);
        $this->assertSame('en_US', $en['locale']);
    }

    public function test_the_home_page_title_is_not_suffixed_but_others_are(): void
    {
        $this->setPage('beranda', ['meta_title' => 'Parfum Lokal Terbaik']);
        $this->setPage('faq', ['meta_title' => 'Pertanyaan Umum']);

        $this->assertSame('Parfum Lokal Terbaik', SiteSeo::forPage('beranda', 'https://evomi.id/')['title_tag']);
        $this->assertSame('Pertanyaan Umum | Evomi', SiteSeo::forPage('faq', 'https://evomi.id/faq')['title_tag']);
    }

    public function test_noindex_flips_the_robots_directive(): void
    {
        $this->setPage('kontak', ['noindex' => true]);

        $this->assertSame('noindex, nofollow', SiteSeo::forPage('kontak', 'https://evomi.id/kontak')['robots']);
    }

    public function test_route_names_map_onto_page_keys(): void
    {
        $this->assertSame('beranda', SiteSeo::pageForRoute('beranda'));
        $this->assertSame('pengiriman', SiteSeo::pageForRoute('pengiriman.show'));
        // Routes with no row opt out entirely.
        $this->assertNull(SiteSeo::pageForRoute('checkout'));
        $this->assertNull(SiteSeo::pageForRoute('belanja.show'));
        $this->assertNull(SiteSeo::pageForRoute('default'));
    }

    public function test_the_home_page_renders_the_dashboard_copy(): void
    {
        $this->setPage('beranda', [
            'meta_title' => 'Parfum Evomi Asli Indonesia',
            'meta_description' => 'Koleksi parfum lokal dengan aroma tahan lama.',
        ]);

        $response = $this->get(route('beranda'));

        $response->assertOk();
        $response->assertSee('<title>Parfum Evomi Asli Indonesia</title>', false);
        $response->assertSee('name="description" content="Koleksi parfum lokal dengan aroma tahan lama."', false);
        $response->assertSee('property="og:title" content="Parfum Evomi Asli Indonesia"', false);
        $response->assertSee('"@type":"WebSite"', false);
    }

    public function test_pages_emit_exactly_one_set_of_social_tags(): void
    {
        foreach (['beranda', 'belanja', 'artikel', 'kuis', 'faq', 'kontak', 'pengiriman'] as $route) {
            $html = $this->get(route($route))->assertOk()->getContent();

            $this->assertSame(1, substr_count($html, 'property="og:title"'), "og:title on {$route}");
            $this->assertSame(1, substr_count($html, 'rel="canonical"'), "canonical on {$route}");
        }
    }

    public function test_routes_without_a_row_keep_the_built_in_defaults(): void
    {
        $html = $this->get(route('login'))->assertOk()->getContent();

        $this->assertStringContainsString('<title>Login | Evomi</title>', $html);
        $this->assertStringNotContainsString('property="og:title"', $html);
    }

    public function test_admin_can_list_every_page_with_its_resolved_preview(): void
    {
        $this->actingAsAdmin();

        $response = $this->getJson('/api/admin/seo')->assertOk();

        $pages = collect($response->json('data'))->pluck('page')->all();
        $this->assertSame(array_keys(SiteSeo::PAGES), $pages);
        $this->assertNotEmpty($response->json('data.0.resolved.description'));
        $this->assertSame(160, $response->json('meta.description_max'));
    }

    public function test_admin_can_update_a_page_and_the_change_shows_immediately(): void
    {
        $this->actingAsAdmin();

        $this->putJson('/api/admin/seo/faq', [
            'meta_title' => 'Pertanyaan Umum Evomi',
            'meta_description' => 'Jawaban untuk pertanyaan tersering soal parfum Evomi.',
            'meta_keywords' => 'faq, evomi',
            'noindex' => false,
        ])->assertOk();

        // The storefront cache must not serve the old copy.
        $this->get(route('faq'))
            ->assertSee('property="og:title" content="Pertanyaan Umum Evomi"', false);
    }

    public function test_admin_update_blanks_a_field_back_to_inheriting(): void
    {
        $this->actingAsAdmin();
        $this->setPage('default', ['meta_description' => 'Default situs.']);

        $this->putJson('/api/admin/seo/kuis', ['meta_description' => ''])->assertOk();

        $this->assertNull(SeoSetting::query()->where('page', 'kuis')->value('meta_description'));
        SiteSeo::forgetCache();
        $this->assertSame('Default situs.', SiteSeo::forPage('kuis', 'https://evomi.id/kuis')['description']);
    }

    public function test_admin_cannot_update_an_unknown_page(): void
    {
        $this->actingAsAdmin();

        $this->putJson('/api/admin/seo/tidak-ada', ['meta_title' => 'X'])->assertNotFound();
    }

    public function test_admin_can_upload_a_share_image(): void
    {
        Storage::fake('public');
        $this->actingAsAdmin();

        $response = $this->postJson('/api/admin/seo/image', [
            'image' => UploadedFile::fake()->image('share.jpg', 1200, 630),
        ])->assertOk();

        $path = $response->json('data.path');
        $this->assertStringStartsWith('cms/seo/', $path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_the_seo_endpoints_are_admin_only(): void
    {
        $this->getJson('/api/admin/seo')->assertUnauthorized();
        $this->putJson('/api/admin/seo/faq', ['meta_title' => 'X'])->assertUnauthorized();
    }

    /**
     * The composer runs on every storefront page, so an unreadable table must
     * degrade to the built-in copy instead of taking the site down. This is the
     * few-second window during a deploy, before `migrate` has run.
     */
    public function test_pages_still_render_when_the_seo_table_is_missing(): void
    {
        SiteSeo::forgetCache();
        Schema::drop('seo_settings');

        $response = $this->get(route('faq'));

        $response->assertOk();
        $response->assertSee('Temukan keharuman eksklusif Evomi', false);
    }
}
