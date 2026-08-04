<?php

namespace App\Support;

use App\Models\Product;
use Illuminate\Support\Collection;

class ProductLocalizer
{
    private const FIELDS = [
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

    public static function localize(?Product $product, string $locale): ?array
    {
        if (!$product) {
            return null;
        }

        return LocaleResolver::resolveFields(
            $product->toArray(),
            self::FIELDS,
            $locale
        );
    }

    /**
     * Map a collection of models that have a nested product relation.
     *
     * @param  Collection<int, mixed>  $items
     * @return Collection<int, array<string, mixed>>
     */
    public static function mapWithProduct(Collection $items, string $locale): Collection
    {
        return $items->map(function ($item) use ($locale) {
            $data = $item->toArray();
            $data['product'] = self::localize($item->product, $locale);
            return $data;
        });
    }
}
