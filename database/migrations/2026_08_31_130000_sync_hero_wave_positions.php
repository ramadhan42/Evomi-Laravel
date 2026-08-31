<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The hero wings were retuned after they were re-anchored to the screen edges.
 * Stored CMS rows win over the catalogue defaults, so every environment gets
 * the new coordinates here instead of needing someone to edit four fields by
 * hand in each dashboard.
 */
return new class extends Migration
{
    private const CURRENT = [
        'wave_left_top_mobile' => '-38%',
        'wave_left_top_desktop' => '-29%',
        'wave_right_top_mobile' => '-77%',
        'wave_right_top_desktop' => '-53%',
    ];

    private const PREVIOUS = [
        'wave_left_top_mobile' => '-44%',
        'wave_left_top_desktop' => '-35%',
        'wave_right_top_mobile' => '-74%',
        'wave_right_top_desktop' => '-50%',
    ];

    public function up(): void
    {
        $this->apply(self::CURRENT);
    }

    public function down(): void
    {
        $this->apply(self::PREVIOUS);
    }

    /**
     * @param  array<string, string>  $values
     */
    private function apply(array $values): void
    {
        foreach ($values as $key => $value) {
            DB::table('site_contents')
                ->where('page', 'beranda')
                ->where('section', 'hero')
                ->where('key', $key)
                ->update(['value' => $value]);
        }
    }
};
