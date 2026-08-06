<?php

use App\Support\StorefrontText;

if (! function_exists('evomi_l')) {
    /**
     * Storefront bilingual string (ID / EN) based on evomi_locale cookie.
     */
    function evomi_l(string $id, string $en): string
    {
        return StorefrontText::L($id, $en);
    }
}

if (! function_exists('evomi_locale')) {
    function evomi_locale(): string
    {
        return StorefrontText::locale();
    }
}
