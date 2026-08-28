<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Samakan path gambar di database dengan berkas WebP yang ada di disk.
 *
 * Berkas di public/src/images ikut repo, tapi unggahan di storage/app/public
 * di-gitignore sehingga tiap environment punya isinya sendiri. Karena itu
 * migrasi ini TIDAK memakai daftar tetap: sebuah path hanya diubah ke .webp
 * kalau berkas .webp-nya benar-benar ada di server yang sedang dimigrasi.
 *
 * Jalankan `php artisan evomi:images-to-webp` lebih dulu supaya unggahan
 * dikonversi sebelum path di database ikut berubah.
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

    public function up(): void
    {
        $this->rewrite(
            '/\.(png|jpe?g)$/i',
            function (string $value): ?string {
                $next = preg_replace('/\.(png|jpe?g)$/i', '.webp', $value);

                // Hanya ubah kalau hasil konversinya memang ada di server ini.
                return $this->fileExists($next) ? $next : null;
            }
        );
    }

    public function down(): void
    {
        $this->rewrite(
            '/\.webp$/i',
            function (string $value): ?string {
                foreach (['png', 'jpg', 'jpeg'] as $ext) {
                    $candidate = preg_replace('/\.webp$/i', '.' . $ext, $value);

                    if ($this->fileExists($candidate)) {
                        return $candidate;
                    }
                }

                // Tidak ada berkas asli yang tersisa - biarkan apa adanya
                // daripada menunjuk ke berkas yang tidak ada.
                return null;
            }
        );
    }

    /**
     * Terjemahkan nilai kolom menjadi path di disk lalu cek keberadaannya.
     *
     * Tiga bentuk yang dipakai aplikasi:
     *   "/src/images/..."              -> public/
     *   "products/..."                 -> storage/app/public/
     *   "section 5/purpose-...webp"    -> public/src/images/   (fallback_img,
     *                                     dirakit BelanjaCatalog::fallbackAssetUrl)
     */
    private function fileExists(string $value): bool
    {
        $value = ltrim(urldecode(trim($value)), '/');

        if ($value === '') {
            return false;
        }

        $candidates = [
            public_path($value),
            storage_path('app/public/' . $value),
            public_path('src/images/' . $value),
        ];

        if (str_starts_with($value, 'storage/')) {
            $candidates[] = storage_path('app/public/' . substr($value, 8));
        }

        foreach ($candidates as $path) {
            if (is_file($path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Terapkan $transform ke tiap nilai kolom yang cocok $match.
     * Nilai dilewati bila $transform mengembalikan null.
     */
    private function rewrite(string $match, callable $transform): void
    {
        $schema = DB::getSchemaBuilder();
        $changed = 0;

        foreach (self::COLUMNS as [$table, $column]) {
            // Lewati tabel/kolom yang belum ada supaya migrasi tetap aman
            // dijalankan pada database yang skemanya tertinggal.
            if (! $schema->hasTable($table) || ! $schema->hasColumn($table, $column)) {
                continue;
            }

            DB::table($table)
                ->select('id', $column)
                ->whereNotNull($column)
                ->orderBy('id')
                ->chunkById(200, function ($rows) use ($table, $column, $match, $transform, &$changed): void {
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
                        $changed++;
                    }
                });
        }

        if (function_exists('app') && app()->runningInConsole()) {
            echo "  path gambar diperbarui: $changed\n";
        }
    }
};
