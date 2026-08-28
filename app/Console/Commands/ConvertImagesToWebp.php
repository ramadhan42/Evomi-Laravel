<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use FilesystemIterator;

/**
 * Konversi gambar unggahan ke WebP di tempatnya.
 *
 * Berkas di storage/app/public tidak ikut repo (di-gitignore), jadi tiap
 * environment menyimpan unggahannya sendiri. Perintah ini dijalankan saat
 * deploy supaya server mengonversi berkasnya sendiri, sebelum migrasi
 * menyamakan path di database ke .webp.
 *
 * Berkas asli TIDAK dihapus - biar rollback tetap mungkin.
 */
class ConvertImagesToWebp extends Command
{
    protected $signature = 'evomi:images-to-webp
                            {--path=* : Folder relatif ke base_path (default: storage/app/public)}
                            {--quality=82 : Kualitas WebP untuk gambar besar}
                            {--dry : Hitung saja, tanpa menulis berkas}';

    protected $description = 'Konversi gambar PNG/JPG ke WebP di tempatnya, mempertahankan dimensi asli';

    public function handle(): int
    {
        if (! function_exists('imagewebp')) {
            $this->error('GD tanpa dukungan WebP - konversi dilewati.');

            return self::FAILURE;
        }

        $paths = $this->option('path') ?: ['storage/app/public'];
        $quality = max(1, min(100, (int) $this->option('quality')));
        $dry = (bool) $this->option('dry');

        $done = $skipped = $failed = 0;
        $before = $after = 0;

        foreach ($paths as $rel) {
            $dir = base_path($rel);

            if (! is_dir($dir)) {
                $this->line("lewati (tidak ada): $rel");
                continue;
            }

            $items = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
            );

            foreach ($items as $file) {
                if (! $file->isFile()) {
                    continue;
                }

                if (! in_array(strtolower($file->getExtension()), ['png', 'jpg', 'jpeg'], true)) {
                    continue;
                }

                $src = str_replace('\\', '/', $file->getPathname());
                $dst = preg_replace('/\.(png|jpe?g)$/i', '.webp', $src);

                // Sudah pernah dikonversi -> jangan kerjakan ulang.
                if (is_file($dst)) {
                    $skipped++;
                    continue;
                }

                $result = $this->convert($src, $dst, $quality, $dry);

                if ($result === null) {
                    $failed++;
                    $this->line('  gagal: ' . basename($src));
                    continue;
                }

                // 0 = sengaja dilewati (WebP lebih besar / dimensi di luar batas)
                if ($result === 0) {
                    $skipped++;
                    continue;
                }

                $done++;
                $before += $file->getSize();
                $after += $result;
            }
        }

        $this->info(sprintf(
            '%sdikonversi=%d dilewati=%d gagal=%d | %.1f MB -> %.1f MB',
            $dry ? '[DRY] ' : '',
            $done,
            $skipped,
            $failed,
            $before / 1048576,
            $after / 1048576
        ));

        return self::SUCCESS;
    }

    /**
     * @return int|null Byte hasil, 0 bila sengaja dilewati, null bila gagal.
     */
    private function convert(string $src, string $dst, int $quality, bool $dry): ?int
    {
        $info = @getimagesize($src);

        if (! $info) {
            return null;
        }

        [$w, $h, $type] = $info;

        // Batas keras format WebP - bukan kegagalan, memang tidak bisa.
        if ($w > 16383 || $h > 16383) {
            return 0;
        }

        $image = match ($type) {
            IMAGETYPE_PNG => @imagecreatefrompng($src),
            IMAGETYPE_JPEG => @imagecreatefromjpeg($src),
            default => null,
        };

        if (! $image) {
            return null;
        }

        imagepalettetotruecolor($image);
        imagealphablending($image, false);
        imagesavealpha($image, true);

        // Gambar kecil dinaikkan kualitasnya - selisih ukurannya tidak berarti,
        // tapi artefak pada ikon dan teks jadi jauh lebih kentara.
        $q = max($w, $h) <= 1024 ? min(100, $quality + 8) : $quality;

        $tmp = $dst . '.tmp';
        $ok = @imagewebp($image, $tmp, $q);
        imagedestroy($image);

        if (! $ok) {
            @unlink($tmp);

            return null;
        }

        $check = @getimagesize($tmp);

        if (! $check || $check[2] !== IMAGETYPE_WEBP || $check[0] !== $w || $check[1] !== $h) {
            @unlink($tmp);

            return null;
        }

        $size = filesize($tmp);

        // WebP yang lebih besar tidak ada gunanya - biarkan berkas asli dipakai.
        if ($size >= filesize($src)) {
            @unlink($tmp);

            return 0;
        }

        if ($dry) {
            @unlink($tmp);

            return $size;
        }

        rename($tmp, $dst);

        return $size;
    }
}
