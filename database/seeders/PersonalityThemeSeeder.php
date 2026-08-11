<?php

namespace Database\Seeders;

use App\Models\PersonalityTheme;
use Illuminate\Database\Seeder;

class PersonalityThemeSeeder extends Seeder
{
    public function run(): void
    {
        $themes = [
            [
                'personality_key' => 'prestige',
                'badge' => 'Optimis',
                'badge_key' => 'purpose',
                'accent' => '#1172BA',
                'soft_accent' => '#E6F3FB',
                'character' => 'belanja/detail/purpose-character.svg',
                'fallback_img' => 'section 5/purpose-prestige.png',
            ],
            [
                'personality_key' => 'peaceful_calm',
                'badge' => 'Damai',
                'badge_key' => 'peaceful',
                'accent' => '#5EA14A',
                'soft_accent' => '#D7FFCC',
                'character' => 'belanja/detail/peaceful-character.svg',
                'fallback_img' => 'section 5/peaceful-calm.png',
            ],
            [
                'personality_key' => 'rebel_brave',
                'badge' => 'Berani',
                'badge_key' => 'rebel',
                'accent' => '#E33D35',
                'soft_accent' => '#FFCDCA',
                'character' => 'belanja/detail/rebel-character.svg',
                'fallback_img' => 'section 5/rabel-brave.png',
            ],
            [
                'personality_key' => 'sweet_shy',
                'badge' => 'Manis',
                'badge_key' => 'sweet',
                'accent' => '#DD74A5',
                'soft_accent' => '#FFDDED',
                'character' => 'belanja/detail/sweet-character.svg',
                'fallback_img' => 'section 5/sweet-shy.png',
            ],
        ];

        foreach ($themes as $theme) {
            PersonalityTheme::updateOrCreate(
                ['personality_key' => $theme['personality_key']],
                $theme
            );
        }

        PersonalityTheme::clearCache();
    }
}
