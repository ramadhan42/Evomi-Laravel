<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use App\Models\SiteContent;
use App\Support\BerandaCmsDefaults;
use App\Support\LocaleResolver;
use App\Support\PublicContentCache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class CmsController extends Controller
{
    /**
     * Public: GET /api/cms/{page}?locale=id|en
     * Returns grouped content with EN falling back to ID.
     */
    public function showPage(Request $request, string $page)
    {
        $locale = LocaleResolver::normalize($request->query('locale', 'id'));

        $grouped = Cache::remember(
            PublicContentCache::cmsPageKey($page, $locale),
            PublicContentCache::TTL_SECONDS,
            function () use ($page, $locale) {
                $idRows = SiteContent::where('page', $page)->where('locale', 'id')->get();
                $grouped = [];
                foreach ($idRows as $row) {
                    $grouped[$row->section][$row->key] = $row->value;
                }

                if ($locale === 'en') {
                    $enRows = SiteContent::where('page', $page)->where('locale', 'en')->get();
                    foreach ($enRows as $row) {
                        if ($row->value !== null && $row->value !== '') {
                            $grouped[$row->section][$row->key] = $row->value;
                        }
                    }
                }

                return $grouped;
            }
        );

        return response()->json([
            'success' => true,
            'data' => $grouped,
            'locale' => $locale,
        ]);
    }

    /**
     * Admin: GET /api/admin/cms/{page}?locale=id|en
     */
    public function adminShowPage(Request $request, string $page)
    {
        $locale = LocaleResolver::normalize($request->query('locale', 'id'));

        $rows = SiteContent::where('page', $page)
            ->where('locale', $locale)
            ->orderBy('section')
            ->orderBy('key')
            ->get();

        // If EN empty, seed editor with ID keys (values blank or ID as starting point)
        if ($locale === 'en' && $rows->isEmpty()) {
            $idRows = SiteContent::where('page', $page)
                ->where('locale', 'id')
                ->orderBy('section')
                ->orderBy('key')
                ->get();
            $rows = $idRows->map(function ($row) {
                return [
                    'id' => null,
                    'page' => $row->page,
                    'section' => $row->section,
                    'key' => $row->key,
                    'locale' => 'en',
                    'type' => $row->type,
                    'value' => null,
                ];
            });
        }

        // Beranda: merge canonical frontend defaults so Dashboard shows editable
        // fields even when DB rows are missing. Existing DB values always win.
        if ($page === 'beranda') {
            $rows = BerandaCmsDefaults::mergeAdminRows($rows, $locale);
        }

        return response()->json([
            'success' => true,
            'data' => $rows,
            'locale' => $locale,
        ]);
    }

    /**
     * Admin: PUT /api/admin/cms/{page}
     * Body: { locale?, fields: [{ section, key, type?, value }] }
     */
    public function adminUpdatePage(Request $request, string $page)
    {
        $validator = Validator::make($request->all(), [
            'locale' => 'nullable|in:id,en',
            'fields' => 'required|array|min:1',
            'fields.*.section' => 'required|string|max:80',
            'fields.*.key' => 'required|string|max:80',
            'fields.*.type' => 'nullable|in:string,text,image',
            'fields.*.value' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $locale = LocaleResolver::normalize($request->input('locale', 'id'));

        foreach ($request->input('fields') as $field) {
            $payload = [
                'type' => $field['type'] ?? 'string',
                'value' => $field['value'] ?? null,
            ];

            SiteContent::updateOrCreate(
                [
                    'page' => $page,
                    'section' => $field['section'],
                    'key' => $field['key'],
                    'locale' => $locale,
                ],
                $payload
            );

            // Layout/style keys are not translated — keep id + en in sync
            if ($this->isLayoutStyleKey($field['key'])) {
                $otherLocale = $locale === 'id' ? 'en' : 'id';
                SiteContent::updateOrCreate(
                    [
                        'page' => $page,
                        'section' => $field['section'],
                        'key' => $field['key'],
                        'locale' => $otherLocale,
                    ],
                    $payload
                );
            }
        }

        PublicContentCache::forgetCms($page);

        $rows = SiteContent::where('page', $page)->where('locale', $locale)->get();
        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row->section][$row->key] = $row->value;
        }

        return response()->json([
            'success' => true,
            'message' => 'Konten berhasil disimpan.',
            'data' => $grouped,
            'locale' => $locale,
        ]);
    }

    /**
     * Keys that control layout/style (not copy) — shared across locales.
     */
    private function isLayoutStyleKey(string $key): bool
    {
        return BerandaCmsDefaults::isLayoutStyleKey($key);
    }

    /**
     * Admin: POST /api/admin/cms/upload
     */
    public function upload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            // `file` (not `image`) so SVG uploads are allowed for CMS wave/icon assets
            'image' => 'required|file|mimes:jpeg,png,jpg,webp,gif,svg|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $path = $request->file('image')->store('cms', 'public');

        return response()->json([
            'success' => true,
            'message' => 'Gambar berhasil diunggah.',
            'data' => [
                'path' => $path,
                'url' => Storage::disk('public')->url($path),
            ],
        ]);
    }

    /**
     * Public: GET /api/cms/faqs?locale=id|en
     */
    public function publicFaqs(Request $request)
    {
        $locale = LocaleResolver::normalize($request->query('locale', 'id'));

        $faqs = Cache::remember(
            PublicContentCache::faqsKey($locale),
            PublicContentCache::TTL_SECONDS,
            function () use ($locale) {
                return Faq::where('is_active', true)
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->get()
                    ->map(function (Faq $faq) use ($locale) {
                        $data = $faq->toArray();

                        return LocaleResolver::resolveFields(
                            $data,
                            ['category', 'question', 'answer'],
                            $locale
                        );
                    })
                    ->all();
            }
        );

        return response()->json([
            'success' => true,
            'data' => $faqs,
            'locale' => $locale,
        ]);
    }

    /**
     * Admin: GET /api/admin/cms/faqs
     */
    public function adminFaqs()
    {
        $faqs = Faq::orderBy('sort_order')->orderBy('id')->get();

        return response()->json([
            'success' => true,
            'data' => $faqs,
        ]);
    }

    /**
     * Admin: POST /api/admin/cms/faqs
     */
    public function storeFaq(Request $request)
    {
        $validated = $request->validate([
            'category' => 'required|string|max:120',
            'category_en' => 'nullable|string|max:120',
            'question' => 'required|string|max:500',
            'question_en' => 'nullable|string|max:500',
            'answer' => 'required|string',
            'answer_en' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $faq = Faq::create([
            'category' => $validated['category'],
            'category_en' => $validated['category_en'] ?? null,
            'question' => $validated['question'],
            'question_en' => $validated['question_en'] ?? null,
            'answer' => $validated['answer'],
            'answer_en' => $validated['answer_en'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        PublicContentCache::forgetFaqs();

        return response()->json([
            'success' => true,
            'message' => 'FAQ ditambahkan.',
            'data' => $faq,
        ], 201);
    }

    /**
     * Admin: PUT /api/admin/cms/faqs/{id}
     */
    public function updateFaq(Request $request, $id)
    {
        $faq = Faq::find($id);
        if (! $faq) {
            return response()->json(['success' => false, 'message' => 'FAQ tidak ditemukan.'], 404);
        }

        $validated = $request->validate([
            'category' => 'sometimes|required|string|max:120',
            'category_en' => 'nullable|string|max:120',
            'question' => 'sometimes|required|string|max:500',
            'question_en' => 'nullable|string|max:500',
            'answer' => 'sometimes|required|string',
            'answer_en' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $faq->update($validated);

        PublicContentCache::forgetFaqs();

        return response()->json([
            'success' => true,
            'message' => 'FAQ diperbarui.',
            'data' => $faq,
        ]);
    }

    /**
     * Admin: DELETE /api/admin/cms/faqs/{id}
     */
    public function destroyFaq($id)
    {
        $faq = Faq::find($id);
        if (! $faq) {
            return response()->json(['success' => false, 'message' => 'FAQ tidak ditemukan.'], 404);
        }

        $faq->delete();

        PublicContentCache::forgetFaqs();

        return response()->json([
            'success' => true,
            'message' => 'FAQ dihapus.',
        ]);
    }
}
