<?php

namespace App\Support;

use App\Models\Disclaimer;
use App\Models\PersonalityTheme;
use App\Models\Product;
use App\Models\Promo;

class BelanjaCatalog
{
    /**
     * Tema visual per personality — dari tabel personality_themes.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function themes(): array
    {
        $map = PersonalityTheme::themeMap();
        if ($map !== []) {
            return $map;
        }

        return [
            'prestige' => PersonalityTheme::forKey('prestige'),
            'peaceful_calm' => PersonalityTheme::forKey('peaceful_calm'),
            'rebel_brave' => PersonalityTheme::forKey('rebel_brave'),
            'sweet_shy' => PersonalityTheme::forKey('sweet_shy'),
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

    /**
     * Resolve article/CMS image paths.
     */
    public static function articleImageUrl(?string $path): string
    {
        if (! is_string($path) || trim($path) === '') {
            return '';
        }

        $path = trim($path);
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }

        if (str_starts_with($path, '/')) {
            return asset(ltrim($path, '/'));
        }

        return self::storageUrl($path);
    }

    public static function fallbackAssetUrl(string $personality): string
    {
        $theme = PersonalityTheme::forKey($personality);
        $img = $theme['fallback_img'] ?? 'section 5/purpose-prestige.png';

        return asset('src/images/'.$img);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function all(): array
    {
        $out = [];
        foreach (Product::query()->orderBy('id')->get() as $product) {
            $mapped = self::mapProduct($product);
            $out[$mapped['id']] = $mapped;
        }

        return $out;
    }

    public static function find(int $id, ?string $locale = null): ?array
    {
        $product = Product::query()->find($id);

        return $product ? self::mapProduct($product, $locale) : null;
    }

    /**
     * @return array<string, mixed>
     */
    public static function mapProduct(Product $product, ?string $locale = null): array
    {
        $locale = LocaleResolver::normalize($locale ?? CmsStorefront::resolveLocale());
        $localized = ProductLocalizer::localize($product, $locale) ?? $product->toArray();

        $personality = (string) ($product->personality_type ?: 'prestige');
        $theme = PersonalityTheme::forKey($personality);
        $accent = $product->color ?: ($theme['accent'] ?? '#1172BA');
        $soft = $theme['soft_accent'] ?? '#9CD6FF';

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
        $gallery = array_slice($gallery, 0, 3);
        if ($gallery === []) {
            $gallery = [$primary];
        }

        $price = (float) $product->price;

        return [
            'id' => (int) $product->id,
            'title' => $localized['title'] ?? $product->title,
            'badge' => $theme['badge'] ?? 'Evomi',
            'description' => $localized['description'] ?? $product->description,
            'price' => $price,
            'price_label' => 'Rp'.number_format($price, 0, ',', '.'),
            'stock' => (int) ($product->quantity ?? 0),
            'bottle_size' => (int) ($product->bottle_size ?? 50),
            'perfume_type' => $localized['perfume_type'] ?? ($product->perfume_type ?: 'Eau de Parfum'),
            'personality_type' => $personality,
            'accent' => $accent,
            'soft_accent' => $soft,
            'badge_key' => $theme['badge_key'] ?? 'purpose',
            'character' => $theme['character'] ?? 'belanja/detail/purpose-character.svg',
            'img' => $primary,
            'img_url' => true,
            'gallery' => $gallery,
            'imgBg' => 'bg-['.$accent.']',
            'cardBg' => 'bg-['.$soft.']',
            'text' => 'text-['.$accent.']',
            'descColor' => 'text-['.$accent.']',
            'border' => 'border-['.$accent.']',
            'btn' => 'bg-['.$accent.']',
            'top_note' => $localized['top_note'] ?? $product->top_note,
            'middle_note' => $localized['middle_note'] ?? $product->middle_note,
            'base_note' => $localized['base_note'] ?? $product->base_note,
            'kondisi' => $localized['kondisi'] ?? ($product->kondisi ?: 'Baru'),
            'berat_satuan' => (int) ($product->berat_satuan ?? 250),
            'kategori' => $localized['kategori'] ?? ($product->kategori ?: 'Parfum'),
            'brand' => $localized['brand'] ?? ($product->brand ?: 'Evomi'),
            'etalase' => $localized['etalase'] ?? ($product->etalase ?: 'Semua Etalase'),
            'alamat_awal_pengiriman' => $product->alamat_awal_pengiriman ?: 'Jakarta Selatan',
        ];
    }

    /**
     * @return list<string>
     */
    public static function disclaimers(): array
    {
        return Disclaimer::query()
            ->orderBy('id')
            ->pluck('deskripsi')
            ->filter()
            ->map(fn ($v) => (string) $v)
            ->values()
            ->all();
    }

    public static function activePromoAmount(): float
    {
        $amount = Promo::query()->active()->orderByDesc('harga_promo')->value('harga_promo');

        return $amount !== null ? (float) $amount : 0.0;
    }

    /**
     * Emergency fallback if kurir table empty.
     *
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
                'jenis' => 'Same Day',
                'harga' => 25000,
                'estimasi_hari' => 1,
                'destinasi' => 'Jakarta → Jabodetabek',
            ],
        ];
    }
}
