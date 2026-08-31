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
        $img = $theme['fallback_img'] ?? 'section 5/purpose-prestige.webp';

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
        $soft = $theme['soft_accent'] ?? '#E6F3FB';
        $badgeKey = (string) ($theme['badge_key'] ?? 'purpose');

        $cmsCardImage = self::cmsCardProductImage($badgeKey);
        $legacyLocal = self::legacyBelanjaCardImage($badgeKey);
        $primary = $cmsCardImage !== ''
            ? $cmsCardImage
            : ($legacyLocal !== ''
                ? $legacyLocal
                : self::storageUrl($product->image_produk_belanja ?: $product->image_1));
        if ($primary === '') {
            $primary = self::fallbackAssetUrl($personality);
        }

        // Detail slider: cover first, then the four gallery slots — five slides.
        $gallery = [];
        $slots = [
            $product->image_produk_belanja,
            $product->image_1,
            $product->image_2,
            $product->image_3,
            $product->image_4,
        ];
        foreach ($slots as $img) {
            $url = self::storageUrl($img);
            if ($url !== '' && ! in_array($url, $gallery, true)) {
                $gallery[] = $url;
            }
        }
        $gallery = array_slice($gallery, 0, 5);
        if ($gallery === []) {
            $gallery = [$primary];
        }

        $price = (float) $product->price;
        $cmsBadge = trim(CmsStorefront::forPage('belanja')->get('badges', $badgeKey, ''));
        $cmsCopy = self::cmsCardCopy($badgeKey);

        $title = $cmsCopy['title'] !== ''
            ? $cmsCopy['title']
            : ($localized['title'] ?? $product->title);
        $description = $cmsCopy['desc'] !== ''
            ? $cmsCopy['desc']
            : ($localized['description'] ?? $product->description);
        $priceLabel = $cmsCopy['price'] !== ''
            ? $cmsCopy['price']
            : ('Rp'.number_format($price, 0, ',', '.'));

        return [
            'id' => (int) $product->id,
            'title' => $title,
            'badge' => $cmsBadge !== '' ? $cmsBadge : ($theme['badge'] ?? 'Evomi'),
            'description' => $description,
            'price' => $price,
            'price_label' => $priceLabel,
            'stock' => (int) ($product->quantity ?? 0),
            'bottle_size' => (int) ($product->bottle_size ?? 50),
            'perfume_type' => $localized['perfume_type'] ?? ($product->perfume_type ?: 'Eau de Parfum'),
            'personality_type' => $personality,
            'accent' => $accent,
            'soft_accent' => $soft,
            'badge_key' => $badgeKey,
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
            'alamat_awal_pengiriman' => $product->alamat_awal_pengiriman ?: 'Cisauk',
        ];
    }

    /**
     * CMS override for belanja listing card bottle image (by personality badge key).
     */
    public static function cmsCardProductImage(string $badgeKey): string
    {
        $cms = CmsStorefront::forPage('belanja');
        $field = match ($badgeKey) {
            'peaceful' => 'card_peaceful_image',
            'rebel' => 'card_rebel_image',
            'sweet' => 'card_sweet_image',
            default => 'card_purpose_image',
        };

        $raw = trim($cms->get('cards', $field, ''));
        if ($raw === '') {
            return '';
        }

        return self::articleImageUrl($raw);
    }

    /**
     * Pre-CMS local bottle assets (gambar sebelum perbaikan / Figma cards folder).
     */
    public static function legacyBelanjaCardImage(string $badgeKey): string
    {
        $file = match ($badgeKey) {
            'peaceful' => 'peaceful.webp',
            'rebel' => 'rebel.webp',
            'sweet' => 'sweet.webp',
            default => 'purpose.webp',
        };

        foreach (["belanja/cards/{$file}", "belanja/{$file}"] as $rel) {
            $abs = public_path('src/images/'.$rel);
            if (is_file($abs)) {
                return asset('src/images/'.$rel);
            }
        }

        return '';
    }

    /**
     * @return array{title: string, desc: string, price: string}
     */
    public static function cmsCardCopy(string $badgeKey): array
    {
        $cms = CmsStorefront::forPage('belanja');
        $prefix = match ($badgeKey) {
            'peaceful' => 'card_peaceful',
            'rebel' => 'card_rebel',
            'sweet' => 'card_sweet',
            default => 'card_purpose',
        };

        return [
            'title' => trim($cms->get('cards', $prefix.'_title', '')),
            'desc' => trim($cms->get('cards', $prefix.'_desc', '')),
            'price' => trim($cms->get('cards', $prefix.'_price', '')),
        ];
    }

    /**
     * Shared bottle transform settings for belanja product cards (from CMS).
     *
     * @return array{width_mobile: string, width_desktop: string, rotate_mobile: string, rotate_desktop: string, top_mobile: string, top_desktop: string}
     */
    public static function cardImageLayout(): array
    {
        $cms = CmsStorefront::forPage('belanja');

        return [
            'width_mobile' => $cms->get('cards', 'card_image_width_mobile', '170%') ?: '170%',
            'width_desktop' => $cms->get('cards', 'card_image_width_desktop', '185%') ?: '185%',
            'rotate_mobile' => $cms->get('cards', 'card_image_rotate_mobile', '30deg') ?: '30deg',
            'rotate_desktop' => $cms->get('cards', 'card_image_rotate_desktop', '30deg') ?: '30deg',
            'top_mobile' => $cms->get('cards', 'card_image_top_mobile', '46%') ?: '46%',
            'top_desktop' => $cms->get('cards', 'card_image_top_desktop', '42%') ?: '42%',
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
        $promo = Promo::current();
        if (! $promo) {
            return 0.0;
        }

        return (float) ($promo->harga_promo ?? 0);
    }

    /**
     * @return array{harga_promo: float, persentase_promo: float}|null
     */
    public static function activeCheckoutPromo(): ?array
    {
        $promo = Promo::current();
        if (! $promo) {
            return null;
        }

        return [
            'harga_promo' => (float) ($promo->harga_promo ?? 0),
            'persentase_promo' => (float) ($promo->persentase_promo ?? 0),
        ];
    }

    /**
     * Emergency fallback if kurir table empty.
     *
     * @return list<array<string, mixed>>
     */
    public static function kurirs(): array
    {
        return [
            ['id' => 1, 'nama' => 'JNE', 'jenis' => 'REG', 'harga' => 9000, 'estimasi_hari' => 3, 'destinasi' => 'Cisauk → seluruh Indonesia'],
            ['id' => 2, 'nama' => 'JNE', 'jenis' => 'YES', 'harga' => 18000, 'estimasi_hari' => 1, 'destinasi' => 'Cisauk → kota besar'],
            ['id' => 3, 'nama' => 'JNE', 'jenis' => 'OKE', 'harga' => 7000, 'estimasi_hari' => 4, 'destinasi' => 'Cisauk → seluruh Indonesia'],
            ['id' => 4, 'nama' => 'J&T Express', 'jenis' => 'EZ', 'harga' => 9000, 'estimasi_hari' => 3, 'destinasi' => 'Cisauk → seluruh Indonesia'],
            ['id' => 5, 'nama' => 'J&T Express', 'jenis' => 'J&T Super', 'harga' => 15000, 'estimasi_hari' => 1, 'destinasi' => 'Cisauk → Jabodetabek'],
            ['id' => 6, 'nama' => 'SiCepat', 'jenis' => 'REG', 'harga' => 8500, 'estimasi_hari' => 3, 'destinasi' => 'Cisauk → seluruh Indonesia'],
            ['id' => 7, 'nama' => 'SiCepat', 'jenis' => 'BEST', 'harga' => 12000, 'estimasi_hari' => 1, 'destinasi' => 'Cisauk → kota besar'],
            ['id' => 8, 'nama' => 'AnterAja', 'jenis' => 'Reguler', 'harga' => 8000, 'estimasi_hari' => 3, 'destinasi' => 'Cisauk → seluruh Indonesia'],
            ['id' => 9, 'nama' => 'AnterAja', 'jenis' => 'Next Day', 'harga' => 14000, 'estimasi_hari' => 1, 'destinasi' => 'Cisauk → Jabodetabek'],
            ['id' => 10, 'nama' => 'AnterAja', 'jenis' => 'Same Day', 'harga' => 22000, 'estimasi_hari' => 1, 'destinasi' => 'Cisauk → Tangerang & sekitar'],
        ];
    }
}
