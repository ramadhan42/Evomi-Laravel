<?php

namespace App\Support;

/**
 * Storefront ID/EN chrome — mirrors Next.js L(locale, id, en).
 */
final class StorefrontText
{
    public static function locale(): string
    {
        return CmsStorefront::resolveLocale();
    }

    public static function isEn(): bool
    {
        return self::locale() === 'en';
    }

    public static function L(string $id, string $en): string
    {
        return self::isEn() ? $en : $id;
    }

    /** @param array{0:string,1:string} $pair */
    public static function pair(array $pair): string
    {
        return self::L($pair[0], $pair[1]);
    }
}
