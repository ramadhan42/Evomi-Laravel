<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\SitemapController;
use App\Models\SeoSetting;
use App\Support\ArticleSeo;
use App\Support\SiteSeo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 * Dashboard "SEO" menu: the title, description and share image Google and the
 * social networks show for each public page.
 */
class SeoSettingController extends Controller
{
    private const RULES = [
        'meta_title' => 'nullable|string|max:255',
        'meta_title_en' => 'nullable|string|max:255',
        'meta_description' => 'nullable|string|max:500',
        'meta_description_en' => 'nullable|string|max:500',
        'meta_keywords' => 'nullable|string|max:255',
        'og_image' => 'nullable|string|max:255',
        'noindex' => 'nullable|boolean',
    ];

    private const TEXT_FIELDS = [
        'meta_title',
        'meta_title_en',
        'meta_description',
        'meta_description_en',
        'meta_keywords',
        'og_image',
    ];

    /**
     * Every editable page with its stored values, the resolved preview (what a
     * visitor would actually get today) and the character limits the UI shows.
     */
    public function index()
    {
        $rows = [];

        foreach (SiteSeo::all() as $page => $row) {
            $resolved = SiteSeo::forPage($page, $row['url']);

            $rows[] = $row + [
                'og_image_url' => SiteSeo::imageUrl($row['og_image']),
                'resolved' => [
                    'title' => $resolved['title'],
                    'title_tag' => $resolved['title_tag'],
                    'description' => $resolved['description'],
                    'image' => $resolved['image'],
                ],
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $rows,
            'meta' => [
                'title_max' => ArticleSeo::TITLE_MAX,
                'description_max' => ArticleSeo::DESCRIPTION_MAX,
            ],
        ]);
    }

    public function update(Request $request, string $page)
    {
        if (! SiteSeo::isPage($page)) {
            return response()->json([
                'success' => false,
                'message' => 'Halaman tidak dikenal.',
            ], 404);
        }

        $validator = Validator::make($request->all(), self::RULES);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $request->only(self::TEXT_FIELDS);

        // Blank means "fall back to the default row", so store NULL not ''.
        foreach (self::TEXT_FIELDS as $field) {
            if (array_key_exists($field, $data)) {
                $value = trim((string) $data[$field]);
                $data[$field] = $value === '' ? null : $value;
            }
        }

        if ($request->has('noindex')) {
            $data['noindex'] = $request->boolean('noindex');
        }

        $setting = SeoSetting::query()->firstOrNew(['page' => $page]);
        $previousImage = $setting->og_image;
        $setting->fill($data + ['page' => $page]);
        $setting->save();

        // Drop an uploaded image that nothing points at any more.
        if (
            $previousImage
            && $previousImage !== $setting->og_image
            && str_starts_with($previousImage, 'cms/')
            && ! SeoSetting::query()->where('og_image', $previousImage)->exists()
        ) {
            Storage::disk('public')->delete($previousImage);
        }

        SiteSeo::forgetCache();
        // The page's `lastmod` just moved - let the next crawl see it.
        SitemapController::forgetCache();

        $resolved = SiteSeo::forPage($page, SiteSeo::all()[$page]['url']);

        return response()->json([
            'success' => true,
            'message' => 'Pengaturan SEO disimpan.',
            'data' => [
                'page' => $page,
                'og_image_url' => SiteSeo::imageUrl($setting->og_image),
                'resolved' => [
                    'title' => $resolved['title'],
                    'title_tag' => $resolved['title_tag'],
                    'description' => $resolved['description'],
                    'image' => $resolved['image'],
                ],
            ],
        ]);
    }

    /** Share image upload. Kept apart from CMS art so it is easy to spot. */
    public function uploadImage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $path = $request->file('image')->store('cms/seo', 'public');

        return response()->json([
            'success' => true,
            'message' => 'Gambar berhasil diunggah.',
            'data' => [
                'path' => $path,
                'url' => Storage::disk('public')->url($path),
            ],
        ]);
    }
}
