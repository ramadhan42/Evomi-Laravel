<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class PersonalityTheme extends Model
{
    protected $fillable = [
        'personality_key',
        'badge',
        'badge_key',
        'accent',
        'soft_accent',
        'character',
        'fallback_img',
    ];

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function themeMap(): array
    {
        return Cache::remember('personality_themes_map', 60, function () {
            $rows = static::query()->orderBy('id')->get();
            $map = [];
            foreach ($rows as $row) {
                $map[$row->personality_key] = [
                    'badge' => $row->badge,
                    'badge_key' => $row->badge_key,
                    'accent' => $row->accent,
                    'soft_accent' => $row->soft_accent,
                    'character' => $row->character,
                    'fallback_img' => $row->fallback_img,
                ];
            }

            return $map;
        });
    }

    /**
     * @return array<string, mixed>
     */
    public static function forKey(string $personality): array
    {
        $map = self::themeMap();
        $key = $personality === 'purpose_prestige' ? 'prestige' : $personality;

        return $map[$key] ?? $map['prestige'] ?? [
            'badge' => 'Evomi',
            'badge_key' => 'purpose',
            'accent' => '#1172BA',
            'soft_accent' => '#E6F3FB',
            'character' => 'belanja/detail/purpose-character.svg',
            'fallback_img' => 'section 5/purpose-prestige.png',
        ];
    }

    public static function clearCache(): void
    {
        Cache::forget('personality_themes_map');
    }

    protected static function booted(): void
    {
        static::saved(fn () => self::clearCache());
        static::deleted(fn () => self::clearCache());
    }
}
