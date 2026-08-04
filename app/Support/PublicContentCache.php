<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * Short-lived cache for public read-heavy endpoints (CMS / products / FAQs).
 * Keeps Hostinger MySQL + PHP workers from re-querying on every page view.
 */
final class PublicContentCache
{
    public const TTL_SECONDS = 300;

    public static function cmsPageKey(string $page, string $locale): string
    {
        return "cms.page.{$page}.{$locale}";
    }

    public static function faqsKey(string $locale): string
    {
        return "cms.faqs.{$locale}";
    }

    public static function productsKey(string $locale): string
    {
        return "products.index.{$locale}";
    }

    public static function forgetCms(?string $page = null): void
    {
        $locales = ['id', 'en'];

        if ($page !== null) {
            foreach ($locales as $locale) {
                Cache::forget(self::cmsPageKey($page, $locale));
            }

            return;
        }

        foreach ($locales as $locale) {
            Cache::forget(self::faqsKey($locale));
        }
    }

    public static function forgetFaqs(): void
    {
        foreach (['id', 'en'] as $locale) {
            Cache::forget(self::faqsKey($locale));
        }
    }

    public static function forgetProducts(): void
    {
        foreach (['id', 'en'] as $locale) {
            Cache::forget(self::productsKey($locale));
        }
    }
}
