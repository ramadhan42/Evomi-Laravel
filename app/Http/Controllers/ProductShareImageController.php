<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Support\BelanjaCatalog;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;

class ProductShareImageController extends Controller
{
    /**
     * Generate a Twitter/OG-friendly 1200x630 JPEG for a product.
     * Source packshots are often >4096px (rejected by X) and ~3MB.
     */
    public function show(int $id): Response
    {
        @ini_set('memory_limit', '256M');

        $product = Product::query()->find($id);
        if (! $product) {
            abort(404);
        }

        $mapped = BelanjaCatalog::mapProduct($product);
        $accent = $this->hexToRgb((string) ($mapped['accent'] ?? '#1172BA'));

        $cacheDir = storage_path('app/public/share');
        if (! File::isDirectory($cacheDir)) {
            File::makeDirectory($cacheDir, 0755, true);
        }

        $cachePath = $cacheDir.'/product-'.$id.'.jpg';
        $sourcePath = $this->resolveSourcePath($product);
        $sourceMtime = is_file($sourcePath) ? (int) filemtime($sourcePath) : 0;
        $cacheMtime = is_file($cachePath) ? (int) filemtime($cachePath) : 0;

        if (! is_file($cachePath) || ($sourceMtime > 0 && $sourceMtime > $cacheMtime)) {
            $this->renderCard($cachePath, $sourcePath, $accent);
        }

        if (! is_file($cachePath)) {
            abort(404);
        }

        return response((string) file_get_contents($cachePath), 200, [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'public, max-age=604800',
            'Content-Length' => (string) filesize($cachePath),
        ]);
    }

    private function resolveSourcePath(Product $product): string
    {
        foreach ([
            $product->image_produk_belanja,
            $product->image_2,
            $product->image_1,
            $product->gambar_2,
            $product->gambar_1,
        ] as $raw) {
            if (! is_string($raw) || trim($raw) === '') {
                continue;
            }
            $clean = ltrim(preg_replace('#^storage/#i', '', trim($raw)) ?? trim($raw), '/');
            $path = storage_path('app/public/'.$clean);
            if (is_file($path)) {
                return $path;
            }
            $public = public_path('storage/'.$clean);
            if (is_file($public)) {
                return $public;
            }
        }

        return '';
    }

    /**
     * @param  array{r:int,g:int,b:int}  $accent
     */
    private function renderCard(string $dest, string $sourcePath, array $accent): void
    {
        $width = 1200;
        $height = 630;
        $canvas = imagecreatetruecolor($width, $height);
        if ($canvas === false) {
            return;
        }

        imagealphablending($canvas, true);
        $bg = imagecolorallocate($canvas, $accent['r'], $accent['g'], $accent['b']);
        imagefilledrectangle($canvas, 0, 0, $width, $height, $bg);

        $src = $this->loadImage($sourcePath);
        if ($src !== null) {
            $sw = imagesx($src);
            $sh = imagesy($src);
            if ($sw > 0 && $sh > 0) {
                // Downscale huge packshots first (X max is 4096; sources are often ~4800px)
                $maxSide = 1200;
                if (max($sw, $sh) > $maxSide) {
                    $preScale = $maxSide / max($sw, $sh);
                    $pw = (int) max(1, round($sw * $preScale));
                    $ph = (int) max(1, round($sh * $preScale));
                    $pre = imagecreatetruecolor($pw, $ph);
                    if ($pre !== false) {
                        imagealphablending($pre, false);
                        imagesavealpha($pre, true);
                        $transparent = imagecolorallocatealpha($pre, 0, 0, 0, 127);
                        imagefilledrectangle($pre, 0, 0, $pw, $ph, $transparent);
                        imagealphablending($pre, true);
                        imagecopyresampled($pre, $src, 0, 0, 0, 0, $pw, $ph, $sw, $sh);
                        imagedestroy($src);
                        $src = $pre;
                        $sw = $pw;
                        $sh = $ph;
                    }
                }

                $pad = 72;
                $maxW = $width - ($pad * 2);
                $maxH = $height - ($pad * 2);
                $scale = min($maxW / $sw, $maxH / $sh);
                $dw = (int) max(1, round($sw * $scale));
                $dh = (int) max(1, round($sh * $scale));
                $dx = (int) (($width - $dw) / 2);
                $dy = (int) (($height - $dh) / 2);
                imagecopyresampled($canvas, $src, $dx, $dy, 0, 0, $dw, $dh, $sw, $sh);
            }
            imagedestroy($src);
        }

        imagejpeg($canvas, $dest, 85);
        imagedestroy($canvas);
    }

    /**
     * @return \GdImage|resource|null
     */
    private function loadImage(string $path)
    {
        if ($path === '' || ! is_file($path)) {
            return null;
        }

        $info = @getimagesize($path);
        if (! is_array($info)) {
            return null;
        }

        return match ($info[2] ?? null) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path) ?: null,
            IMAGETYPE_PNG => @imagecreatefrompng($path) ?: null,
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? (@imagecreatefromwebp($path) ?: null) : null,
            IMAGETYPE_GIF => @imagecreatefromgif($path) ?: null,
            default => null,
        };
    }

    /**
     * @return array{r:int,g:int,b:int}
     */
    private function hexToRgb(string $hex): array
    {
        $hex = ltrim(trim($hex), '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        if (! preg_match('/^[0-9a-fA-F]{6}$/', $hex)) {
            return ['r' => 17, 'g' => 114, 'b' => 186];
        }

        return [
            'r' => hexdec(substr($hex, 0, 2)),
            'g' => hexdec(substr($hex, 2, 2)),
            'b' => hexdec(substr($hex, 4, 2)),
        ];
    }
}
