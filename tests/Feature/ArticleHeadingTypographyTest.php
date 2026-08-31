<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\User;
use App\Support\ArticleContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ArticleHeadingTypographyTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): User
    {
        $admin = User::factory()->create([
            'email' => 'article-admin@evomi.test',
            'password' => Hash::make('password123'),
            'is_admin' => true,
        ]);
        Sanctum::actingAs($admin);

        return $admin;
    }

    private function makeArticle(array $attributes = []): Article
    {
        return Article::create(array_merge([
            'title' => 'Cara memilih parfum',
            'slug' => 'cara-memilih-parfum',
            'excerpt' => 'Ringkasan singkat.',
            'content' => "Paragraf pembuka.\n\n## Aroma tahan lama\nBertahan 8 jam.",
            'category' => 'parfum',
            'author' => 'Evomi',
            'is_published' => true,
            'published_at' => now()->subDay(),
        ], $attributes));
    }

    public function test_blocks_splits_hash_prefixed_lines_into_headings(): void
    {
        $blocks = ArticleContent::blocks("Pembuka.\nLanjutan.\n\n### Catatan\nIsi catatan.\n####### bukan heading");

        $this->assertSame(
            [
                ['tag' => 'p', 'text' => "Pembuka.\nLanjutan."],
                ['tag' => 'h3', 'text' => 'Catatan'],
                ['tag' => 'p', 'text' => "Isi catatan.\n####### bukan heading"],
            ],
            $blocks,
        );
    }

    public function test_heading_fonts_fall_back_to_defaults_when_missing(): void
    {
        $normalized = ArticleContent::normalizeFonts(['h2' => ['font_family' => 'syne']]);

        $this->assertSame('syne', $normalized['h2']['font_family']);
        $this->assertSame('28', $normalized['h2']['font_size']);
        $this->assertSame(ArticleContent::defaults()['h4'], $normalized['h4']);
        $this->assertSame(ArticleContent::LEVELS, array_keys($normalized));
    }

    public function test_admin_update_persists_title_level_and_heading_fonts(): void
    {
        $this->actingAsAdmin();
        $article = $this->makeArticle();

        $res = $this->putJson("/api/admin/articles/{$article->id}", [
            'title' => $article->title,
            'content' => $article->content,
            'title_heading_level' => 'h2',
            'heading_fonts' => [
                'h2' => [
                    'font_family' => 'syne',
                    'font_weight' => '900',
                    'font_style' => 'italic',
                    'font_size' => '30',
                ],
                // Unknown levels are dropped, missing keys fall back to defaults.
                'h9' => ['font_family' => 'arial'],
            ],
        ]);

        $res->assertOk()->assertJsonPath('success', true);

        $fresh = $article->fresh();
        $this->assertSame('h2', $fresh->title_heading_level);
        $this->assertSame('syne', $fresh->heading_fonts['h2']['font_family']);
        $this->assertSame('900', $fresh->heading_fonts['h2']['font_weight']);
        $this->assertSame('italic', $fresh->heading_fonts['h2']['font_style']);
        $this->assertSame('30', $fresh->heading_fonts['h2']['font_size']);
        $this->assertArrayNotHasKey('h9', $fresh->heading_fonts);
        $this->assertSame('nohemi', $fresh->heading_fonts['h1']['font_family']);
    }

    public function test_admin_update_rejects_an_invalid_title_level(): void
    {
        $this->actingAsAdmin();
        $article = $this->makeArticle();

        $this->putJson("/api/admin/articles/{$article->id}", [
            'title_heading_level' => 'h7',
        ])->assertStatus(422);
    }

    public function test_article_page_renders_the_chosen_tags_and_fonts(): void
    {
        $article = $this->makeArticle([
            'title_heading_level' => 'h2',
            'heading_fonts' => ArticleContent::normalizeFonts([
                'h2' => ['font_family' => 'syne', 'font_weight' => '900', 'font_size' => '30'],
            ]),
        ]);

        $res = $this->get("/artikel/{$article->slug}");
        $res->assertOk();

        $html = $res->getContent();
        $this->assertStringContainsString('<h2', $html);
        $this->assertStringContainsString('Aroma tahan lama', $html);
        // Blade escapes the quotes inside the style attribute.
        $this->assertStringContainsString(e("var(--font-syne), 'Syne', sans-serif; font-weight: 900"), $html);
        $this->assertStringNotContainsString('## Aroma tahan lama', $html);
    }

    public function test_block_level_keeps_known_levels_and_falls_back_to_normal(): void
    {
        $this->assertSame('h3', ArticleContent::blockLevel('H3'));
        $this->assertSame('normal', ArticleContent::blockLevel('normal'));
        $this->assertSame('normal', ArticleContent::blockLevel(null));
        $this->assertSame('normal', ArticleContent::blockLevel('h7'));
        $this->assertSame('h2', ArticleContent::blockLevel('nonsense', 'h2'));
    }

    public function test_admin_update_persists_excerpt_and_content_levels(): void
    {
        $this->actingAsAdmin();
        $article = $this->makeArticle();

        $res = $this->putJson("/api/admin/articles/{$article->id}", [
            'excerpt_heading_level' => 'h3',
            'content_heading_level' => 'normal',
        ]);

        $res->assertOk()->assertJsonPath('success', true);

        $fresh = $article->fresh();
        $this->assertSame('h3', $fresh->excerpt_heading_level);
        $this->assertSame('normal', $fresh->content_heading_level);
    }

    public function test_admin_update_rejects_an_invalid_body_level(): void
    {
        $this->actingAsAdmin();
        $article = $this->makeArticle();

        $this->putJson("/api/admin/articles/{$article->id}", [
            'content_heading_level' => 'div',
        ])->assertStatus(422);
    }

    public function test_article_page_renders_excerpt_and_content_with_the_chosen_tags(): void
    {
        $article = $this->makeArticle([
            'slug' => 'level-ringkasan-dan-konten',
            'excerpt_heading_level' => 'h3',
            'content_heading_level' => 'h4',
        ]);

        $html = $this->get("/artikel/{$article->slug}")->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/<h3[^>]*>\s*Ringkasan singkat\./', $html);
        $this->assertMatchesRegularExpression('/<h4[^>]*>Paragraf pembuka\.<\/h4>/', $html);
        // A "##" line keeps its own level, it is not overridden by the content level.
        $this->assertMatchesRegularExpression('/<h2[^>]*>Aroma tahan lama<\/h2>/', $html);
    }

    public function test_article_page_renders_plain_paragraphs_by_default(): void
    {
        $article = $this->makeArticle(['slug' => 'ringkasan-normal']);

        $html = $this->get("/artikel/{$article->slug}")->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/<p[^>]*>Paragraf pembuka\.<\/p>/', $html);
    }

    public function test_article_page_falls_back_to_defaults_without_heading_fonts(): void
    {
        $article = $this->makeArticle(['slug' => 'tanpa-heading-fonts']);

        $res = $this->get("/artikel/{$article->slug}");
        $res->assertOk();

        $html = $res->getContent();
        $this->assertStringContainsString('artikel-detail-heading', $html);
        $this->assertStringContainsString('font-size: 28px', $html);
    }
}
