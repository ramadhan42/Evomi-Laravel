<?php

namespace App\Support;

use App\Models\Product;

class BelanjaCatalog
{
    /**
     * Tema visual per personality (UI Belanja).
     *
     * @return array<string, array<string, mixed>>
     */
    public static function themes(): array
    {
        return [
            'prestige' => [
                'badge' => 'Optimis',
                'badge_key' => 'purpose',
                'character' => 'belanja/detail/purpose-character.svg',
                'fallback_img' => 'section 5/purpose-prestige.png',
                'imgBg' => 'bg-[#1172BA]',
                'cardBg' => 'bg-[#9CD6FF]',
                'text' => 'text-[#1172BA]',
                'descColor' => 'text-[#1172BAB2]',
                'border' => 'border-[#1172BA]',
                'btn' => 'bg-[#1172BA]',
                'accent' => '#1172BA',
            ],
            'peaceful_calm' => [
                'badge' => 'Damai',
                'badge_key' => 'peaceful',
                'character' => 'belanja/detail/peaceful-character.svg',
                'fallback_img' => 'section 5/peaceful-calm.png',
                'imgBg' => 'bg-[#5EA14A]',
                'cardBg' => 'bg-[#C6F5B8]',
                'text' => 'text-[#5EA14A]',
                'descColor' => 'text-[#5EA14A]',
                'border' => 'border-[#5EA14A]',
                'btn' => 'bg-[#5EA14A]',
                'accent' => '#5EA14A',
            ],
            'rebel_brave' => [
                'badge' => 'Berani',
                'badge_key' => 'rebel',
                'character' => 'belanja/detail/rebel-character.svg',
                'fallback_img' => 'section 5/rabel-brave.png',
                'imgBg' => 'bg-[#E33D35]',
                'cardBg' => 'bg-[#FFBBB5]',
                'text' => 'text-[#E33D35]',
                'descColor' => 'text-[#E33D35]',
                'border' => 'border-[#E33D35]',
                'btn' => 'bg-[#E33D35]',
                'accent' => '#E33D35',
            ],
            'sweet_shy' => [
                'badge' => 'Manis',
                'badge_key' => 'sweet',
                'character' => 'belanja/detail/sweet-character.svg',
                'fallback_img' => 'section 5/sweet-shy.png',
                'imgBg' => 'bg-[#DD74A5]',
                'cardBg' => 'bg-[#F5D7E7]',
                'text' => 'text-[#DD74A5]',
                'descColor' => 'text-[#DD74A5]',
                'border' => 'border-[#DD74A5]',
                'btn' => 'bg-[#DD74A5]',
                'accent' => '#DD74A5',
            ],
        ];
    }

    public static function storageUrl(?string $path): string
    {
        if (! is_string($path) || trim($path) === '') {
            return '';
        }

        $path = trim($path);
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }

        $clean = ltrim(preg_replace('#^storage/#i', '', $path) ?? $path, '/');

        return asset('storage/'.$clean);
    }

    public static function fallbackAssetUrl(string $personality): string
    {
        $theme = self::themes()[$personality] ?? null;
        $img = $theme['fallback_img'] ?? 'section 5/purpose-prestige.png';

        return asset('src/images/'.$img);
    }

    /**
     * Katalog dari database (+ tema UI). Fallback ke dummy jika DB kosong.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function all(): array
    {
        $products = Product::query()->orderBy('id')->get();

        if ($products->isEmpty()) {
            return self::dummyAll();
        }

        $out = [];
        foreach ($products as $product) {
            $mapped = self::mapProduct($product);
            $out[$mapped['id']] = $mapped;
        }

        return $out;
    }

    public static function find(int $id): ?array
    {
        $product = Product::query()->find($id);

        if ($product) {
            return self::mapProduct($product);
        }

        return self::dummyAll()[$id] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    public static function mapProduct(Product $product): array
    {
        $personality = (string) ($product->personality_type ?: 'prestige');
        $theme = self::themes()[$personality] ?? self::themes()['prestige'];

        $primary = self::storageUrl($product->image_produk_belanja ?: $product->image_1);
        if ($primary === '') {
            $primary = self::fallbackAssetUrl($personality);
        }

        $gallery = [];
        foreach ([$product->image_1, $product->image_2, $product->image_3] as $img) {
            $url = self::storageUrl($img);
            if ($url !== '') {
                $gallery[] = $url;
            }
        }
        // Belanja details: max 3 slides (image_1 … image_3), sama seperti Next.js
        $gallery = array_slice($gallery, 0, 3);
        if ($gallery === []) {
            $gallery = [$primary];
        }

        $price = (float) $product->price;

        return [
            'id' => (int) $product->id,
            'title' => $product->title,
            'badge' => $theme['badge'],
            'description' => $product->description,
            'price' => $price,
            'price_label' => 'Rp'.number_format($price, 0, ',', '.'),
            'stock' => (int) ($product->quantity ?? 0),
            'bottle_size' => (int) ($product->bottle_size ?? 50),
            'perfume_type' => $product->perfume_type ?: 'Eau de Parfum',
            'personality_type' => $personality,
            'accent' => $product->color ?: $theme['accent'],
            'badge_key' => $theme['badge_key'],
            'character' => $theme['character'],
            'img' => $primary,
            'img_url' => true,
            'gallery' => $gallery,
            'imgBg' => $theme['imgBg'],
            'cardBg' => $theme['cardBg'],
            'text' => $theme['text'],
            'descColor' => $theme['descColor'],
            'border' => $theme['border'],
            'btn' => $theme['btn'],
            'top_note' => $product->top_note,
            'middle_note' => $product->middle_note,
            'base_note' => $product->base_note,
            'kondisi' => $product->kondisi ?: 'Baru',
            'berat_satuan' => (int) ($product->berat_satuan ?? 250),
            'kategori' => $product->kategori ?: 'Parfum',
            'brand' => $product->brand ?: 'Evomi',
            'etalase' => $product->etalase ?: 'Semua Etalase',
            'alamat_awal_pengiriman' => $product->alamat_awal_pengiriman ?: 'Jakarta Selatan',
        ];
    }

    /**
     * Dummy katalog Belanja (fallback jika DB kosong).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function dummyAll(): array
    {
        return [
            1 => [
                'id' => 1,
                'title' => 'Evomi Purpose Prestige',
                'badge' => 'Optimis',
                'description' => 'Aroma yang merefleksikan ketenangan dan kejelasan tujuan. Berkelas dan karismatik untuk ambisi serta kesan profesional yang eksklusif.',
                'price' => 189000,
                'price_label' => 'Rp189.000',
                'stock' => 25,
                'bottle_size' => 50,
                'perfume_type' => 'Eau de Parfum',
                'personality_type' => 'prestige',
                'accent' => '#1172BA',
                'badge_key' => 'purpose',
                'character' => 'belanja/detail/purpose-character.svg',
                'img' => 'section 5/purpose-prestige.png',
                'img_url' => false,
                'imgBg' => 'bg-[#1172BA]',
                'cardBg' => 'bg-[#9CD6FF]',
                'text' => 'text-[#1172BA]',
                'descColor' => 'text-[#1172BAB2]',
                'border' => 'border-[#1172BA]',
                'btn' => 'bg-[#1172BA]',
                'top_note' => 'Citrus • Plum • Grapefruit',
                'middle_note' => 'Woody • Hazelnut • Amberwood',
                'base_note' => 'Amber • Cedarwood • Vetiver',
                'kondisi' => 'Baru',
                'berat_satuan' => 250,
                'kategori' => 'Parfum',
                'brand' => 'Evomi',
                'etalase' => 'Semua Etalase',
                'alamat_awal_pengiriman' => 'Jakarta Selatan',
            ],
            2 => [
                'id' => 2,
                'title' => 'Evomi Peaceful Calm',
                'badge' => 'Damai',
                'description' => 'Aroma menenangkan yang menyatu dengan diri. Segar dan damai untuk jiwa yang mencari kedamaian serta keseimbangan.',
                'price' => 199000,
                'price_label' => 'Rp199.000',
                'stock' => 18,
                'bottle_size' => 50,
                'perfume_type' => 'Eau de Parfum',
                'personality_type' => 'peaceful_calm',
                'accent' => '#5EA14A',
                'badge_key' => 'peaceful',
                'character' => 'belanja/detail/peaceful-character.svg',
                'img' => 'section 5/peaceful-calm.png',
                'img_url' => false,
                'imgBg' => 'bg-[#5EA14A]',
                'cardBg' => 'bg-[#C6F5B8]',
                'text' => 'text-[#5EA14A]',
                'descColor' => 'text-[#5EA14A]',
                'border' => 'border-[#5EA14A]',
                'btn' => 'bg-[#5EA14A]',
                'top_note' => 'Bergamot • Green Leaf',
                'middle_note' => 'Green Tea • Soft Floral',
                'base_note' => 'Musk • Soft Woods',
                'kondisi' => 'Baru',
                'berat_satuan' => 250,
                'kategori' => 'Parfum',
                'brand' => 'Evomi',
                'etalase' => 'Semua Etalase',
                'alamat_awal_pengiriman' => 'Jakarta Selatan',
            ],
            3 => [
                'id' => 3,
                'title' => 'Evomi Rebel Brave',
                'badge' => 'Berani',
                'description' => 'Keberanian dan semangat untuk mengekspresikan diri. Aroma berani dan dinamis untuk jiwa petualang.',
                'price' => 179000,
                'price_label' => 'Rp179.000',
                'stock' => 12,
                'bottle_size' => 50,
                'perfume_type' => 'Eau de Parfum',
                'personality_type' => 'rebel_brave',
                'accent' => '#E33D35',
                'badge_key' => 'rebel',
                'character' => 'belanja/detail/rebel-character.svg',
                'img' => 'section 5/rabel-brave.png',
                'img_url' => false,
                'imgBg' => 'bg-[#E33D35]',
                'cardBg' => 'bg-[#FFBBB5]',
                'text' => 'text-[#E33D35]',
                'descColor' => 'text-[#E33D35]',
                'border' => 'border-[#E33D35]',
                'btn' => 'bg-[#E33D35]',
                'top_note' => 'Pepper • Spicy Citrus',
                'middle_note' => 'Leather • Smoky Woods',
                'base_note' => 'Cedar • Amber',
                'kondisi' => 'Baru',
                'berat_satuan' => 250,
                'kategori' => 'Parfum',
                'brand' => 'Evomi',
                'etalase' => 'Semua Etalase',
                'alamat_awal_pengiriman' => 'Jakarta Selatan',
            ],
            4 => [
                'id' => 4,
                'title' => 'Evomi Sweet Shy',
                'badge' => 'Manis',
                'description' => 'Aroma manis yang lembut dan berhati-hati, memberikan kesan hangat, ramah, dan memikat secara perlahan.',
                'price' => 189000,
                'price_label' => 'Rp189.000',
                'stock' => 30,
                'bottle_size' => 50,
                'perfume_type' => 'Eau de Parfum',
                'personality_type' => 'sweet_shy',
                'accent' => '#DD74A5',
                'badge_key' => 'sweet',
                'character' => 'belanja/detail/sweet-character.svg',
                'img' => 'section 5/sweet-shy.png',
                'img_url' => false,
                'imgBg' => 'bg-[#DD74A5]',
                'cardBg' => 'bg-[#F5D7E7]',
                'text' => 'text-[#DD74A5]',
                'descColor' => 'text-[#DD74A5]',
                'border' => 'border-[#DD74A5]',
                'btn' => 'bg-[#DD74A5]',
                'top_note' => 'Peach • Soft Berries',
                'middle_note' => 'Rose • Creamy Florals',
                'base_note' => 'Vanilla • Soft Musk',
                'kondisi' => 'Baru',
                'berat_satuan' => 250,
                'kategori' => 'Parfum',
                'brand' => 'Evomi',
                'etalase' => 'Semua Etalase',
                'alamat_awal_pengiriman' => 'Jakarta Selatan',
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function kurirs(): array
    {
        return [
            [
                'id' => 1,
                'nama' => 'JNE',
                'jenis' => 'Reguler',
                'harga' => 15000,
                'estimasi_hari' => 3,
                'destinasi' => 'Jakarta → seluruh Indonesia',
            ],
            [
                'id' => 2,
                'nama' => 'SiCepat',
                'jenis' => 'HALU',
                'harga' => 22000,
                'estimasi_hari' => 1,
                'destinasi' => 'Jakarta → Jabodetabek',
            ],
            [
                'id' => 3,
                'nama' => 'AnterAja',
                'jenis' => 'Reguler',
                'harga' => 12000,
                'estimasi_hari' => 4,
                'destinasi' => 'Jakarta → Jawa & Bali',
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function disclaimers(): array
    {
        return [
            'Warna dan aroma aktual dapat sedikit berbeda tergantung batch produksi.',
            'Simpan di tempat sejuk, jauh dari sinar matahari langsung.',
            'Komplain hanya diterima maksimal 2x24 jam setelah barang diterima, disertai video unboxing.',
            'Produk yang sudah dibuka seal tidak dapat dikembalikan kecuali cacat produksi.',
        ];
    }
}
