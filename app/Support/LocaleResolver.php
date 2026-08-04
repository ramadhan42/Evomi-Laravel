<?php

namespace App\Support;

class LocaleResolver
{
    public static function normalize(?string $locale): string
    {
        $locale = strtolower(trim((string) $locale));
        return in_array($locale, ['id', 'en'], true) ? $locale : 'id';
    }

    /**
     * Resolve product/FAQ text fields for a locale.
     * For locale=en, prefer *_en columns; fall back to base field.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, string>  $fields
     * @return array<string, mixed>
     */
    public static function resolveFields(array $data, array $fields, string $locale): array
    {
        if ($locale !== 'en') {
            return $data;
        }

        foreach ($fields as $field) {
            $enKey = $field . '_en';
            if (!empty($data[$enKey])) {
                $data[$field] = $data[$enKey];
            }
        }

        return $data;
    }
}
