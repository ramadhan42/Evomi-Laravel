<?php

namespace App\Support;

use App\Models\SiteContent;

class ShippingConfig
{
    /** Gudang / alamat asal pengiriman default Evomi */
    public const DEFAULT_ORIGIN_CITY = 'Cisauk';

    public const SETTINGS_PAGE = 'shipping';
    public const SETTINGS_SECTION = 'settings';
    public const FREE_SHIPPING_KEY = 'free_shipping';

    /** Berat per item parfum 50ml + kardus (gram) */
    public const UNIT_WEIGHT_GRAMS = 60;

    /**
     * @return list<string>
     */
    public static function destinationCities(): array
    {
        return [
            'Jakarta',
            'Bogor',
            'Depok',
            'Tangerang',
            'Bekasi',
            'Bandung',
            'Surabaya',
            'Yogyakarta',
            'Semarang',
            'Medan',
            'Makassar',
        ];
    }

    public static function isFreeShipping(): bool
    {
        $row = SiteContent::query()
            ->where('page', self::SETTINGS_PAGE)
            ->where('section', self::SETTINGS_SECTION)
            ->where('key', self::FREE_SHIPPING_KEY)
            ->where('locale', 'id')
            ->first();

        $value = strtolower(trim((string) ($row?->value ?? '0')));

        return in_array($value, ['1', 'true', 'yes', 'on'], true);
    }

    public static function setFreeShipping(bool $enabled): void
    {
        SiteContent::updateOrCreate(
            [
                'page' => self::SETTINGS_PAGE,
                'section' => self::SETTINGS_SECTION,
                'key' => self::FREE_SHIPPING_KEY,
                'locale' => 'id',
            ],
            [
                'type' => 'string',
                'value' => $enabled ? '1' : '0',
            ],
        );
    }
}
