<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\User;
use App\Support\ArticleSeo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ArticleSeoTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): User
    {
        $admin = User::factory()->create([
            'email' => 'seo-admin@evomi.test',
            'password' => Hash::make('password123'),
            'is_admin' => true,
        ]);
        Sanctum::actingAs($admin);

        return $admin;
    }

    public function test_meta_falls_back_to_the_article_when_left_empty(): void
    {
        $seo = ArticleSeo::forArticle([
            'title' => 'Aroma Musim Hujan',
            'excerpt_text' => 'Catatan singkat tentang wangi yang cocok saat udara lembap.',
        ], 'https://evomi.id/artikel/aroma-musim-hujan');

        $this->assertSame('Aroma Musim Hujan', $seo['title']);
        $this->assertSame('Aroma Musim Hujan | Evomi', $seo['title_tag']);
        $this->assertSame('Catatan singkat tentang wangi yang cocok saat udara lembap.', $seo['description']);
        $this->assertSame('https://evomi.id/artikel/aroma-musim-hujan', $seo['canonical']);
    }

    public function test_long_descriptions_are_trimmed_on_a_word_boundary(): void
    {
        $seo = ArticleSeo::forArticle([
            'title' => 'Panjang',
            'meta_description' => str_repeat('parfum ', 60),
        ], 'https://evomi.id/artikel/panjang');

        $this->assertLessThanOrEqual(ArticleSeo::DESCRIPTION_MAX + 1, mb_strlen($seo['description']));
        $this->assertStringEndsWith('…', $seo['description']);
    }

    public function test_faq_pairs_become_a_faq_page_node(): void
    {
        $seo = ArticleSeo::forArticle([
            'title' => 'FAQ',
            'faqs' => [
                ['question' => 'Berapa lama?', 'answer' => 'Sekitar 8 jam.'],
                ['question' => 'Kosong', 'answer' => ''],
            ],
        ], 'https://evomi.id/artikel/faq');

        $this->assertCount(1, $seo['faqs']);

        $faqNode = collect($seo['schema']['@graph'])->firstWhere('@type', 'FAQPage');
        $this->assertNotNull($faqNode);
        $this->assertSame('Berapa lama?', $faqNode['mainEntity'][0]['name']);
        $this->assertSame('Sekitar 8 jam.', $faqNode['mainEntity'][0]['acceptedAnswer']['text']);
    }

    public function test_english_locale_prefers_the_english_faq_copy(): void
    {
        $faqs = ArticleSeo::localizedFaqs([
            ['question' => 'Berapa lama?', 'answer' => 'Sekitar 8 jam.', 'question_en' => 'How long?', 'answer_en' => 'About 8 hours.'],
            ['question' => 'Unisex?', 'answer' => 'Sebagian.', 'question_en' => '', 'answer_en' => ''],
        ], 'en');

        $this->assertSame('How long?', $faqs[0]['question']);
        $this->assertSame('About 8 hours.', $faqs[0]['answer']);
        // No translation yet, so the Indonesian copy still shows.
        $this->assertSame('Unisex?', $faqs[1]['question']);
    }

    public function test_noindex_flips_the_robots_directive(): void
    {
        $seo = ArticleSeo::forArticle(['title' => 'Draft', 'noindex' => true], 'https://evomi.id/artikel/draft');

        $this->assertSame('noindex, nofollow', $seo['robots']);
    }

    public function test_a_custom_schema_blob_replaces_the_generated_article_node(): void
    {
        $seo = ArticleSeo::forArticle([
            'title' => 'Custom',
            'schema_json' => '{"@type":"Recipe","name":"Bukan artikel"}',
        ], 'https://evomi.id/artikel/custom');

        $this->assertSame('Recipe', $seo['schema']['@graph'][0]['@type']);
        // Breadcrumbs still ship alongside the override.
        $this->assertSame('BreadcrumbList', $seo['schema']['@graph'][1]['@type']);
    }

    public function test_a_broken_custom_schema_falls_back_to_the_generated_node(): void
    {
        $seo = ArticleSeo::forArticle([
            'title' => 'Rusak',
            'schema_type' => 'NewsArticle',
            'schema_json' => '{not json',
        ], 'https://evomi.id/artikel/rusak');

        $this->assertSame('NewsArticle', $seo['schema']['@graph'][0]['@type']);
    }

    public function test_keywords_are_split_and_deduplicated(): void
    {
        $this->assertSame(
            ['parfum', 'evomi'],
            ArticleSeo::keywordList('parfum, evomi , parfum,  ')
        );
    }

    public function test_the_article_page_renders_meta_and_json_ld(): void
    {
        $article = Article::query()->create([
            'title' => 'Uji SEO Artikel',
            'slug' => 'uji-seo-artikel',
            'excerpt' => 'Ringkasan uji.',
            'content' => '<p>Isi artikel uji coba SEO.</p>',
            'meta_title' => 'Judul Meta Uji',
            'meta_description' => 'Deskripsi meta untuk pengujian.',
            'meta_keywords' => 'uji, seo',
            'schema_type' => 'Article',
            'faqs' => [['question' => 'Apa ini?', 'answer' => 'Sebuah pengujian.']],
            'is_published' => true,
            'published_at' => now(),
        ]);

        $response = $this->get(route('artikel.show', $article->slug));

        $response->assertOk();
        $response->assertSee('<title>Judul Meta Uji | Evomi</title>', false);
        $response->assertSee('name="description" content="Deskripsi meta untuk pengujian."', false);
        $response->assertSee('property="og:title" content="Judul Meta Uji"', false);
        $response->assertSee('rel="canonical"', false);
        $response->assertSee('application/ld+json', false);
        $response->assertSee('FAQPage', false);
        $response->assertSee('Apa ini?', false);
    }

    public function test_admin_can_store_seo_fields_and_faqs(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/api/admin/articles', [
            'title' => 'Artikel Baru',
            'content' => '<p>Isi artikel.</p>',
            'meta_title' => 'Meta Judul Baru',
            'meta_description' => 'Meta deskripsi baru.',
            'meta_keywords' => 'parfum, evomi',
            'canonical_url' => 'https://evomi.id/artikel/artikel-baru',
            'noindex' => true,
            'schema_type' => 'NewsArticle',
            'faqs' => [
                ['question' => 'Pertanyaan?', 'answer' => 'Jawaban.', 'question_en' => 'Question?', 'answer_en' => 'Answer.'],
                ['question' => '', 'answer' => 'Tanpa pertanyaan'],
            ],
        ])->assertCreated();

        $article = Article::query()->latest('id')->firstOrFail();

        $this->assertSame('Meta Judul Baru', $article->meta_title);
        $this->assertSame('Meta deskripsi baru.', $article->meta_description);
        $this->assertSame('NewsArticle', $article->schema_type);
        $this->assertTrue($article->noindex);
        // The half-filled row is dropped.
        $this->assertCount(1, $article->faqs);
        $this->assertSame('Question?', $article->faqs[0]['question_en']);
    }

    public function test_admin_update_clears_seo_fields_when_blanked(): void
    {
        $this->actingAsAdmin();

        $article = Article::query()->create([
            'title' => 'Ada Meta',
            'slug' => 'ada-meta',
            'content' => '<p>Isi.</p>',
            'meta_title' => 'Lama',
            'meta_keywords' => 'lama',
            'faqs' => [['question' => 'Lama?', 'answer' => 'Ya.']],
            'is_published' => true,
            'published_at' => now(),
        ]);

        $this->putJson("/api/admin/articles/{$article->id}", [
            'meta_title' => '',
            'meta_keywords' => '',
            'faqs' => [],
        ])->assertOk();

        $article->refresh();
        $this->assertNull($article->meta_title);
        $this->assertNull($article->meta_keywords);
        $this->assertSame([], $article->faqs);
    }

    public function test_the_dashboard_form_shape_is_accepted(): void
    {
        $this->actingAsAdmin();

        $article = Article::query()->create([
            'title' => 'Form Shape',
            'slug' => 'form-shape',
            'content' => '<p>Isi.</p>',
            'faqs' => [['question' => 'Lama?', 'answer' => 'Ya.']],
            'is_published' => true,
            'published_at' => now(),
        ]);

        // The editor posts multipart FormData: every value is a string, and an
        // empty FAQ list arrives as a bare empty field.
        $this->post("/api/admin/articles/{$article->id}", [
            'title' => 'Form Shape',
            'content' => '<p>Isi.</p>',
            'meta_title' => '',
            'meta_description' => '',
            'meta_keywords' => '',
            'canonical_url' => '',
            'noindex' => '0',
            'schema_type' => 'BlogPosting',
            'schema_json' => '',
            'faqs' => '',
            'is_published' => '1',
        ])->assertOk();

        $article->refresh();
        $this->assertNull($article->meta_title);
        $this->assertFalse($article->noindex);
        $this->assertSame([], $article->faqs);
    }

    public function test_admin_rejects_a_malformed_custom_schema_blob(): void
    {
        $this->actingAsAdmin();

        $article = Article::query()->create([
            'title' => 'Schema',
            'slug' => 'schema',
            'content' => '<p>Isi.</p>',
            'schema_json' => '{"@type":"Article"}',
            'is_published' => true,
            'published_at' => now(),
        ]);

        $this->putJson("/api/admin/articles/{$article->id}", [
            'schema_json' => '{broken',
        ])->assertOk();

        // Rather than shipping invalid JSON-LD, the blob is dropped.
        $this->assertNull($article->refresh()->schema_json);
    }
}
