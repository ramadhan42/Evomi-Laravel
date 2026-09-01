<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Support\ArticleContent;
use App\Support\ArticleSeo;
use App\Support\BelanjaCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ArticleController extends Controller
{
    private const TYPOGRAPHY_FIELDS = [
        'title_font_family',
        'title_font_weight',
        'title_font_style',
        'title_font_size',
        'excerpt_font_family',
        'excerpt_font_weight',
        'excerpt_font_style',
        'excerpt_font_size',
        'content_font_family',
        'content_font_weight',
        'content_font_style',
        'content_font_size',
    ];

    private const TYPOGRAPHY_RULES = [
        'title_font_family' => 'nullable|string|max:40',
        'title_font_weight' => 'nullable|string|max:10',
        'title_font_style' => 'nullable|in:normal,italic',
        'title_font_size' => 'nullable|string|max:20',
        'excerpt_font_family' => 'nullable|string|max:40',
        'excerpt_font_weight' => 'nullable|string|max:10',
        'excerpt_font_style' => 'nullable|in:normal,italic',
        'excerpt_font_size' => 'nullable|string|max:20',
        'content_font_family' => 'nullable|string|max:40',
        'content_font_weight' => 'nullable|string|max:10',
        'content_font_style' => 'nullable|in:normal,italic',
        'content_font_size' => 'nullable|string|max:20',
    ];

    private const HEADING_RULES = [
        'title_heading_level' => 'nullable|in:h1,h2,h3,h4,h5,h6',
        'excerpt_heading_level' => 'nullable|in:normal,h1,h2,h3,h4,h5,h6',
        'content_heading_level' => 'nullable|in:normal,h1,h2,h3,h4,h5,h6',
        'heading_fonts' => 'nullable|array',
        'heading_fonts.*' => 'array',
        'heading_fonts.*.font_family' => 'nullable|string|max:40',
        'heading_fonts.*.font_weight' => 'nullable|string|max:10',
        'heading_fonts.*.font_style' => 'nullable|in:normal,italic',
        'heading_fonts.*.font_size' => 'nullable|string|max:20',
    ];

    /** Plain columns the editor fills in for search engines. */
    private const SEO_FIELDS = [
        'meta_title',
        'meta_title_en',
        'meta_description',
        'meta_description_en',
        'meta_keywords',
        'canonical_url',
        'schema_json',
    ];

    private const SEO_RULES = [
        'meta_title' => 'nullable|string|max:255',
        'meta_title_en' => 'nullable|string|max:255',
        'meta_description' => 'nullable|string|max:500',
        'meta_description_en' => 'nullable|string|max:500',
        'meta_keywords' => 'nullable|string|max:255',
        'canonical_url' => 'nullable|string|max:255',
        'noindex' => 'nullable|boolean',
        'schema_type' => 'nullable|string|max:40',
        'schema_json' => 'nullable|string|max:20000',
        'faqs' => 'nullable|array|max:20',
        'faqs.*' => 'array',
        'faqs.*.question' => 'nullable|string|max:300',
        'faqs.*.answer' => 'nullable|string|max:1200',
        'faqs.*.question_en' => 'nullable|string|max:300',
        'faqs.*.answer_en' => 'nullable|string|max:1200',
    ];

    /**
     * Blank SEO inputs are stored as NULL so the renderer can tell "left
     * empty, fall back to the article" from "deliberately set".
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function applySeo(array $data, Request $request): array
    {
        foreach (self::SEO_FIELDS as $field) {
            if (! array_key_exists($field, $data)) {
                continue;
            }

            $value = trim((string) $data[$field]);
            $data[$field] = $value === '' ? null : $value;
        }

        // A malformed custom schema would break the page's JSON-LD, so drop it.
        if (! empty($data['schema_json']) && ! is_array(json_decode($data['schema_json'], true))) {
            $data['schema_json'] = null;
        }

        return $data;
    }

    private function isStoredUpload(?string $path): bool
    {
        if (! $path) {
            return false;
        }
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return false;
        }
        if (str_starts_with($path, '/')) {
            return false;
        }

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    private function presentArticle(Article $article): array
    {
        $data = $article->toArray();
        $resolved = BelanjaCatalog::articleImageUrl($article->image);
        $data['image_url'] = $resolved !== '' ? $resolved : null;

        return $data;
    }

    public function index(Request $request)
    {
        $query = Article::query()->orderByDesc('published_at')->orderByDesc('id');

        if ($request->boolean('published', true) && ! $request->boolean('all')) {
            $query->published();
        }

        if ($category = $request->query('category')) {
            $query->where('category', $category);
        }

        if ($limit = $request->integer('limit')) {
            $query->limit(max(1, min($limit, 50)));
        }

        return response()->json([
            'success' => true,
            'data' => $query->get()->map(fn (Article $a) => $this->presentArticle($a))->values(),
        ]);
    }

    public function show(string $slug)
    {
        $article = Article::query()
            ->published()
            ->where('slug', $slug)
            ->first();

        if (! $article) {
            return response()->json([
                'success' => false,
                'message' => 'Artikel tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->presentArticle($article),
        ]);
    }

    public function adminIndex()
    {
        $articles = Article::query()
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Article $a) => $this->presentArticle($a))
            ->values();

        return response()->json([
            'success' => true,
            'data' => $articles,
        ]);
    }

    public function adminShow($id)
    {
        $article = Article::find($id);

        if (! $article) {
            return response()->json([
                'success' => false,
                'message' => 'Artikel tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->presentArticle($article),
        ]);
    }

    /**
     * Image dropped into the middle of an article by the editor. Kept apart
     * from the cover upload so inline art is easy to spot in storage.
     */
    public function uploadInlineImage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg,webp,gif|max:5120',
            'alt' => 'nullable|string|max:200',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $path = $request->file('image')->store('articles/inline', 'public');

        return response()->json([
            'success' => true,
            'message' => 'Gambar berhasil diunggah.',
            'data' => [
                'path' => $path,
                'url' => Storage::disk('public')->url($path),
                'alt' => trim((string) $request->input('alt', '')),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), array_merge([
            'title' => 'required|string|max:255',
            'title_en' => 'nullable|string|max:255',
            'slug' => 'nullable|string|max:255|unique:articles,slug',
            'excerpt' => 'nullable|string|max:500',
            'excerpt_en' => 'nullable|string|max:500',
            'content' => 'required|string',
            'content_en' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:10240',
            'category' => 'nullable|string|max:100',
            'author' => 'nullable|string|max:120',
            'is_published' => 'nullable|boolean',
            'published_at' => 'nullable|date',
        ], self::TYPOGRAPHY_RULES, self::HEADING_RULES, self::SEO_RULES));

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $request->only(array_merge([
            'title',
            'title_en',
            'excerpt',
            'excerpt_en',
            'content',
            'content_en',
            'category',
            'author',
            'published_at',
        ], self::TYPOGRAPHY_FIELDS, self::SEO_FIELDS));

        $data['slug'] = $request->filled('slug')
            ? Article::makeUniqueSlug($request->input('slug'))
            : Article::makeUniqueSlug($request->input('title'));
        $data['category'] = ($data['category'] ?? '') ?: 'parfum';
        $data['is_published'] = $request->boolean('is_published', true);
        $data['published_at'] = $data['published_at'] ?? now();

        $data['title_font_family'] = $data['title_font_family'] ?? 'nohemi';
        $data['title_font_weight'] = $data['title_font_weight'] ?? '700';
        $data['title_font_style'] = $data['title_font_style'] ?? 'normal';
        $data['title_font_size'] = $data['title_font_size'] ?? '40';
        $data['excerpt_font_family'] = $data['excerpt_font_family'] ?? 'parkinsans';
        $data['excerpt_font_weight'] = $data['excerpt_font_weight'] ?? '400';
        $data['excerpt_font_style'] = $data['excerpt_font_style'] ?? 'normal';
        $data['excerpt_font_size'] = $data['excerpt_font_size'] ?? '18';
        $data['content_font_family'] = $data['content_font_family'] ?? 'parkinsans';
        $data['content_font_weight'] = $data['content_font_weight'] ?? '400';
        $data['content_font_style'] = $data['content_font_style'] ?? 'normal';
        $data['content_font_size'] = $data['content_font_size'] ?? '17';
        foreach (['content', 'content_en'] as $richField) {
            if (isset($data[$richField])) {
                $data[$richField] = ArticleContent::normalizeContent($data[$richField]);
            }
        }

        foreach (['excerpt', 'excerpt_en'] as $inlineField) {
            if (isset($data[$inlineField])) {
                $data[$inlineField] = ArticleContent::normalizeExcerpt($data[$inlineField]);
            }
        }

        $data['title_heading_level'] = ArticleContent::headingLevel($request->input('title_heading_level'));
        $data['excerpt_heading_level'] = ArticleContent::blockLevel($request->input('excerpt_heading_level'));
        $data['content_heading_level'] = ArticleContent::blockLevel($request->input('content_heading_level'));
        $data['heading_fonts'] = ArticleContent::normalizeFonts($request->input('heading_fonts'));

        $data = $this->applySeo($data, $request);
        $data['noindex'] = $request->boolean('noindex');
        $data['schema_type'] = ArticleSeo::schemaType($request->input('schema_type'));
        $data['faqs'] = ArticleSeo::normalizeFaqs($request->input('faqs'));

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('articles', 'public');
        }

        $article = Article::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Artikel berhasil ditambahkan',
            'data' => $this->presentArticle($article),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $article = Article::find($id);

        if (! $article) {
            return response()->json([
                'success' => false,
                'message' => 'Artikel tidak ditemukan',
            ], 404);
        }

        $validator = Validator::make($request->all(), array_merge([
            'title' => 'sometimes|required|string|max:255',
            'title_en' => 'nullable|string|max:255',
            'slug' => 'nullable|string|max:255|unique:articles,slug,'.$article->id,
            'excerpt' => 'nullable|string|max:500',
            'excerpt_en' => 'nullable|string|max:500',
            'content' => 'sometimes|required|string',
            'content_en' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:10240',
            'category' => 'nullable|string|max:100',
            'author' => 'nullable|string|max:120',
            'is_published' => 'nullable|boolean',
            'published_at' => 'nullable|date',
        ], self::TYPOGRAPHY_RULES, self::HEADING_RULES, self::SEO_RULES));

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $request->only(array_merge([
            'title',
            'title_en',
            'excerpt',
            'excerpt_en',
            'content',
            'content_en',
            'category',
            'author',
            'published_at',
        ], self::TYPOGRAPHY_FIELDS, self::SEO_FIELDS));

        if ($request->filled('slug')) {
            $data['slug'] = Article::makeUniqueSlug($request->input('slug'), $article->id);
        } elseif ($request->filled('title') && $request->input('title') !== $article->title) {
            $data['slug'] = Article::makeUniqueSlug($request->input('title'), $article->id);
        }

        if ($request->has('is_published')) {
            $data['is_published'] = $request->boolean('is_published');
        }

        foreach (['content', 'content_en'] as $richField) {
            if (array_key_exists($richField, $data)) {
                $data[$richField] = ArticleContent::normalizeContent($data[$richField]);
            }
        }

        foreach (['excerpt', 'excerpt_en'] as $inlineField) {
            if (array_key_exists($inlineField, $data)) {
                $data[$inlineField] = ArticleContent::normalizeExcerpt($data[$inlineField]);
            }
        }

        if ($request->has('title_heading_level')) {
            $data['title_heading_level'] = ArticleContent::headingLevel(
                $request->input('title_heading_level'),
                $article->title_heading_level ?: 'h1',
            );
        }

        foreach (['excerpt_heading_level', 'content_heading_level'] as $levelField) {
            if ($request->has($levelField)) {
                $data[$levelField] = ArticleContent::blockLevel(
                    $request->input($levelField),
                    $article->{$levelField} ?: ArticleContent::NONE,
                );
            }
        }

        if ($request->has('heading_fonts')) {
            $data['heading_fonts'] = ArticleContent::normalizeFonts($request->input('heading_fonts'));
        }

        $data = $this->applySeo($data, $request);

        if ($request->has('noindex')) {
            $data['noindex'] = $request->boolean('noindex');
        }

        if ($request->has('schema_type')) {
            $data['schema_type'] = ArticleSeo::schemaType(
                $request->input('schema_type'),
                $article->schema_type ?: 'BlogPosting',
            );
        }

        if ($request->has('faqs')) {
            $data['faqs'] = ArticleSeo::normalizeFaqs($request->input('faqs'));
        }

        if ($request->hasFile('image')) {
            if ($this->isStoredUpload($article->image)) {
                Storage::disk('public')->delete($article->image);
            }
            $data['image'] = $request->file('image')->store('articles', 'public');
        }

        $article->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Artikel berhasil diupdate',
            'data' => $this->presentArticle($article->fresh()),
        ]);
    }

    public function destroy($id)
    {
        $article = Article::find($id);

        if (! $article) {
            return response()->json([
                'success' => false,
                'message' => 'Artikel tidak ditemukan',
            ], 404);
        }

        if ($this->isStoredUpload($article->image)) {
            Storage::disk('public')->delete($article->image);
        }

        $article->delete();

        return response()->json([
            'success' => true,
            'message' => 'Artikel berhasil dihapus',
        ]);
    }
}
