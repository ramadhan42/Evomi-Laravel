<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Support\LocaleResolver;
use App\Support\PublicContentCache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    /** Max upload size in KB (40MB per product image). */
    private const MAX_IMAGE_KB = 40960;

    private const LOCALIZED_FIELDS = [
        'title',
        'description',
        'personality_type',
        'top_note',
        'middle_note',
        'base_note',
        'perfume_type',
        'gender',
        'stock_status',
        'kondisi',
        'kategori',
        'brand',
        'etalase',
    ];

    private function localizeProduct(Product $product, string $locale): array
    {
        return LocaleResolver::resolveFields(
            $product->toArray(),
            self::LOCALIZED_FIELDS,
            $locale
        );
    }

    public function index(Request $request)
    {
        $locale = LocaleResolver::normalize($request->query('locale', 'id'));

        $products = Cache::remember(
            PublicContentCache::productsKey($locale),
            PublicContentCache::TTL_SECONDS,
            function () use ($locale) {
                return Product::query()
                    ->orderBy('id', 'asc')
                    ->get()
                    ->map(fn (Product $p) => $this->localizeProduct($p, $locale))
                    ->all();
            }
        );

        return response()->json([
            'success' => true,
            'data' => $products,
            'locale' => $locale,
        ], 200);
    }

    public function show(Request $request, $id)
    {
        $locale = LocaleResolver::normalize($request->query('locale', 'id'));
        $product = Product::find($id);

        if (! $product) {
            return response()->json(['success' => false, 'message' => 'Produk tidak ditemukan'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->localizeProduct($product, $locale),
            'locale' => $locale,
        ], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'title_en' => 'nullable|string|max:255',
            'description' => 'required|string',
            'description_en' => 'nullable|string',
            'color' => 'nullable|string|max:50',
            'price' => 'required|numeric',
            'personality_type' => 'nullable|in:prestige,purpose_prestige,peaceful_calm,rebel_brave,sweet_shy',
            'personality_type_en' => 'nullable|string|max:255',
            'top_note' => 'nullable|string|max:255',
            'top_note_en' => 'nullable|string|max:255',
            'middle_note' => 'nullable|string|max:255',
            'middle_note_en' => 'nullable|string|max:255',
            'base_note' => 'nullable|string|max:255',
            'base_note_en' => 'nullable|string|max:255',
            'image_1' => 'required|image|mimes:jpeg,png,jpg,webp|max:'.self::MAX_IMAGE_KB,
            'image_2' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:'.self::MAX_IMAGE_KB,
            'image_3' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:'.self::MAX_IMAGE_KB,
            'image_4' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:'.self::MAX_IMAGE_KB,
            'image_produk_belanja' => 'required|image|mimes:jpeg,png,jpg,webp|max:'.self::MAX_IMAGE_KB,
            'bottle_size' => 'required|integer',
            'perfume_type' => 'required|string|max:255',
            'perfume_type_en' => 'nullable|string|max:255',
            'gender' => 'required|in:unisex,male,female',
            'gender_en' => 'nullable|string|max:50',
            'quantity' => 'integer',
            'stock_status' => 'in:tersedia,minim,habis',
            'stock_status_en' => 'nullable|string|max:50',
            'alamat_awal_pengiriman' => 'nullable|string|max:255',
            'kondisi' => 'nullable|string|max:100',
            'kondisi_en' => 'nullable|string|max:100',
            'kategori' => 'nullable|string|max:100',
            'kategori_en' => 'nullable|string|max:100',
            'berat_satuan' => 'nullable|numeric|min:0',
            'brand' => 'nullable|string|max:100',
            'brand_en' => 'nullable|string|max:100',
            'etalase' => 'nullable|string|max:100',
            'etalase_en' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $data = $request->except(['image_1', 'image_2', 'image_3', 'image_4', 'image_produk_belanja']);

        if (($data['personality_type'] ?? null) === 'purpose_prestige') {
            $data['personality_type'] = 'prestige';
        }

        $imageFields = ['image_1', 'image_2', 'image_3', 'image_4', 'image_produk_belanja'];
        foreach ($imageFields as $field) {
            if ($request->hasFile($field)) {
                $data[$field] = $request->file($field)->store('products', 'public');
            }
        }

        $product = Product::create($data);
        if (array_key_exists('quantity', $data) && ! array_key_exists('stock_status', $data)) {
            $product->applyStockStatusFromQuantity();
            $product->save();
        }

        PublicContentCache::forgetProducts();

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil ditambahkan',
            'data' => $product->fresh(),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $product = Product::find($id);

        if (! $product) {
            return response()->json(['success' => false, 'message' => 'Produk tidak ditemukan'], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'title_en' => 'nullable|string|max:255',
            'description' => 'sometimes|required|string',
            'description_en' => 'nullable|string',
            'color' => 'nullable|string|max:50',
            'price' => 'sometimes|required|numeric',
            'personality_type' => 'nullable|in:prestige,purpose_prestige,peaceful_calm,rebel_brave,sweet_shy',
            'personality_type_en' => 'nullable|string|max:255',
            'top_note' => 'nullable|string|max:255',
            'top_note_en' => 'nullable|string|max:255',
            'middle_note' => 'nullable|string|max:255',
            'middle_note_en' => 'nullable|string|max:255',
            'base_note' => 'nullable|string|max:255',
            'base_note_en' => 'nullable|string|max:255',
            'image_1' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:'.self::MAX_IMAGE_KB,
            'image_2' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:'.self::MAX_IMAGE_KB,
            'image_3' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:'.self::MAX_IMAGE_KB,
            'image_4' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:'.self::MAX_IMAGE_KB,
            'image_produk_belanja' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:'.self::MAX_IMAGE_KB,
            'bottle_size' => 'sometimes|required|integer',
            'perfume_type' => 'sometimes|required|string|max:255',
            'perfume_type_en' => 'nullable|string|max:255',
            'gender' => 'sometimes|required|in:unisex,male,female',
            'gender_en' => 'nullable|string|max:50',
            'quantity' => 'integer',
            'stock_status' => 'in:tersedia,minim,habis',
            'stock_status_en' => 'nullable|string|max:50',
            'alamat_awal_pengiriman' => 'nullable|string|max:255',
            'kondisi' => 'nullable|string|max:100',
            'kondisi_en' => 'nullable|string|max:100',
            'kategori' => 'nullable|string|max:100',
            'kategori_en' => 'nullable|string|max:100',
            'berat_satuan' => 'nullable|numeric|min:0',
            'brand' => 'nullable|string|max:100',
            'brand_en' => 'nullable|string|max:100',
            'etalase' => 'nullable|string|max:100',
            'etalase_en' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $data = $request->except(['image_1', 'image_2', 'image_3', 'image_4', 'image_produk_belanja']);

        if (($data['personality_type'] ?? null) === 'purpose_prestige') {
            $data['personality_type'] = 'prestige';
        }

        $imageFields = ['image_1', 'image_2', 'image_3', 'image_4', 'image_produk_belanja'];
        foreach ($imageFields as $field) {
            if ($request->hasFile($field)) {
                if ($product->$field) {
                    Storage::disk('public')->delete($product->$field);
                }
                $data[$field] = $request->file($field)->store('products', 'public');
            }
        }

        $product->update($data);

        // Jika admin hanya mengubah angka stok tanpa status, sync label otomatis
        if (array_key_exists('quantity', $data) && ! array_key_exists('stock_status', $data)) {
            $product->applyStockStatusFromQuantity();
            $product->save();
        }

        PublicContentCache::forgetProducts();

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil diupdate',
            'data' => $product->fresh(),
        ], 200);
    }

    public function destroy($id)
    {
        $product = Product::find($id);

        if (! $product) {
            return response()->json(['success' => false, 'message' => 'Produk tidak ditemukan'], 404);
        }

        $imageFields = ['image_1', 'image_2', 'image_3', 'image_4', 'image_produk_belanja'];
        foreach ($imageFields as $field) {
            if ($product->$field) {
                Storage::disk('public')->delete($product->$field);
            }
        }

        $product->delete();

        PublicContentCache::forgetProducts();

        return response()->json(['success' => true, 'message' => 'Produk berhasil dihapus'], 200);
    }
}
