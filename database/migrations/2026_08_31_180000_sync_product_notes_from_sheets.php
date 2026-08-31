<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Notes tiap varian disalin dari lembar notes yang dikirim tim, sekaligus
 * memasang lembar itu sebagai slide keenam. Dikerjakan lewat migrasi supaya
 * setiap server ikut, tanpa menjalankan ProductSeeder yang menghapus dan
 * membuat ulang produk (dan memutus relasi pesanan lama).
 */
return new class extends Migration
{
    private const SHEETS = [
        'prestige' => [
            'slug' => 'purpose',
            'top_note' => 'Plum • Grapefruit • Bergamot',
            'middle_note' => 'Hazelnut • Honey • Milk • Amberwood',
            'base_note' => 'Cedarwood • Cashmere Wood • Vetiver • Marine',
            'olfactory_family' => 'Woody',
        ],
        'peaceful_calm' => [
            'slug' => 'peaceful',
            'top_note' => 'Bergamot • Peach • Pomelo • Bamboo',
            'middle_note' => 'Peony • Rose • Narccissus • Jasmine',
            'base_note' => 'Ylang-Ylang • Vanilla',
            'olfactory_family' => 'Musk, Woody, Floral',
        ],
        'rebel_brave' => [
            'slug' => 'rebel',
            'top_note' => 'Bergamot • Lavender • Cinnamon',
            'middle_note' => 'Orange Blossom • Lily of the Valley • Vanilla',
            'base_note' => 'Vanilla • Tonka Bean • Amber',
            'olfactory_family' => 'Oriental Vanilla',
        ],
        'sweet_shy' => [
            'slug' => 'sweet',
            'top_note' => 'Pineapple • Mandarin • Apricot',
            'middle_note' => 'Melon • Honey • Jasmine',
            'base_note' => 'Musk • Ambergris • Jasmine',
            'olfactory_family' => 'Floral Fruity',
        ],
    ];

    public function up(): void
    {
        foreach (self::SHEETS as $personality => $sheet) {
            $slug = $sheet['slug'];
            $source = database_path("seeders/product-images/{$slug}/image_5.webp");
            $relative = "products/{$slug}/image_5.webp";

            if (is_file($source)) {
                Storage::disk('public')->put($relative, (string) file_get_contents($source));
            }

            DB::table('products')->where('personality_type', $personality)->update([
                'image_5' => is_file($source) ? $relative : null,
                'top_note' => $sheet['top_note'],
                'middle_note' => $sheet['middle_note'],
                'base_note' => $sheet['base_note'],
                'olfactory_family' => $sheet['olfactory_family'],
                'olfactory_family_en' => $sheet['olfactory_family'],
                'sillage' => 'Moderate',
                'sillage_en' => 'Moderate',
                'projection' => '1-2 Meters',
                'projection_en' => '1-2 Meters',
                'longevity' => '8+- hrs',
                'longevity_en' => '8+- hrs',
            ]);
        }
    }

    public function down(): void
    {
        // Catatan lama tidak disimpan; kolomnya sendiri dibuang oleh migrasi
        // sebelumnya bila di-rollback lebih jauh.
    }
};
