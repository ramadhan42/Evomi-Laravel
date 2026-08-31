<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Five product photos per variant now feed the detail slider. The files ship
 * with the repo (database/seeders/product-images), so every environment can
 * publish them without re-running ProductSeeder — which would delete and
 * recreate the products and orphan the orders that reference them.
 */
return new class extends Migration
{
    private const VARIANTS = [
        'prestige' => 'purpose',
        'peaceful_calm' => 'peaceful',
        'rebel_brave' => 'rebel',
        'sweet_shy' => 'sweet',
    ];

    private const SLOTS = [
        'image_produk_belanja' => 'belanja',
        'image_1' => 'image_1',
        'image_2' => 'image_2',
        'image_3' => 'image_3',
        'image_4' => 'image_4',
    ];

    public function up(): void
    {
        foreach (self::VARIANTS as $personality => $slug) {
            $paths = [];

            foreach (self::SLOTS as $column => $name) {
                $source = database_path("seeders/product-images/{$slug}/{$name}.webp");

                if (! is_file($source)) {
                    continue;
                }

                $relative = "products/{$slug}/{$name}.webp";
                Storage::disk('public')->put($relative, (string) file_get_contents($source));
                $paths[$column] = $relative;
            }

            if ($paths !== []) {
                DB::table('products')->where('personality_type', $personality)->update($paths);
            }
        }
    }

    public function down(): void
    {
        // The previous paths pointed at per-install uploads; there is nothing
        // meaningful to restore, and the files themselves are left in place.
    }
};
