<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Samakan path gambar di database dengan hasil konversi WebP.
 *
 * Berkas gambar sudah dikonversi di repo (public/src/images, storage/app/public,
 * database/seeders/product-images). Tanpa migrasi ini database produksi masih
 * menunjuk .png/.jpg yang sudah tidak ada, sehingga semua gambar jadi 404.
 */
return new class extends Migration
{
    /** Kolom yang menyimpan path gambar. */
    private const COLUMNS = [
        ['articles', 'image'],
        ['personality_themes', 'fallback_img'],
        ['products', 'image_produk_belanja'],
        ['products', 'image_1'],
        ['products', 'image_2'],
        ['products', 'image_3'],
        ['products', 'image_4'],
        ['quiz_personality_results', 'bg_image'],
        ['quiz_personality_results', 'product_image'],
        ['site_contents', 'value'],
        ['users', 'avatar_profile'],
    ];

    /**
     * Berkas yang sengaja TETAP png dan tidak boleh ikut diubah:
     * thanks-card melewati batas dimensi WebP (16537px > 16383px), tiga sisanya
     * justru membengkak bila dikonversi.
     */
    private const KEEP_AS_IS = [
        '/src/images/section 4/thanks-card.png',
        '/src/images/section 4/tutup-botol.png',
        '/src/images/belanja/deco/char-purpose.png',
        '/src/images/belanja/deco/char-rebel.png',
    ];

    /**
     * Path yang aslinya .jpg (bukan .png), dipakai supaya down() bisa memulihkan
     * ekstensi yang benar. Di luar daftar ini down() mengasumsikan .png.
     */
    private const WAS_JPG = [
        '/src/images/articles/article-01.webp',
        '/src/images/articles/article-02.webp',
        '/src/images/articles/article-03.webp',
        '/src/images/articles/article-04.webp',
        '/src/images/articles/article-05.webp',
        '/src/images/articles/article-06.webp',
        '/src/images/articles/article-07.webp',
        '/src/images/articles/article-08.webp',
        '/src/images/articles/article-09.webp',
        '/src/images/articles/article-10.webp',
        '/src/images/articles/article-11.webp',
        '/src/images/articles/article-12.webp',
        'avatars/4o7Z41sKFaJraxCUtGsUMwZu9ob0WmZaW7VPCx3k.webp',
    ];

    public function up(): void
    {
        $this->rewrite(
            fn (string $value): ?string => in_array($value, self::KEEP_AS_IS, true)
                ? null
                : preg_replace('/\.(png|jpe?g)$/i', '.webp', $value),
            '/\.(png|jpe?g)$/i'
        );
    }

    public function down(): void
    {
        $this->rewrite(
            fn (string $value): ?string => in_array($value, self::WAS_JPG, true)
                ? preg_replace('/\.webp$/i', '.jpg', $value)
                : preg_replace('/\.webp$/i', '.png', $value),
            '/\.webp$/i'
        );
    }

    /**
     * Terapkan $transform ke setiap nilai kolom yang cocok $match.
     * Nilai dilewati bila $transform mengembalikan null atau tidak berubah.
     */
    private function rewrite(callable $transform, string $match): void
    {
        foreach (self::COLUMNS as [$table, $column]) {
            // Lewati tabel/kolom yang belum ada supaya migrasi tetap aman
            // dijalankan pada database yang skemanya tertinggal.
            if (! DB::getSchemaBuilder()->hasTable($table)
                || ! DB::getSchemaBuilder()->hasColumn($table, $column)) {
                continue;
            }

            DB::table($table)
                ->select('id', $column)
                ->whereNotNull($column)
                ->orderBy('id')
                ->chunkById(200, function ($rows) use ($table, $column, $transform, $match): void {
                    foreach ($rows as $row) {
                        $value = $row->$column;

                        if (! is_string($value) || ! preg_match($match, $value)) {
                            continue;
                        }

                        $next = $transform($value);

                        if ($next === null || $next === $value) {
                            continue;
                        }

                        DB::table($table)->where('id', $row->id)->update([$column => $next]);
                    }
                });
        }
    }
};
