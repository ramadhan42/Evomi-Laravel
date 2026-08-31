<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class ProductSeeder extends Seeder
{
    /**
     * Data awal 4 produk Evomi + gambar dari database/seeders/product-images.
     * Dipakai frontend (belanja & detail) dan admin dashboard.
     *
     * Urutan:
     * 1 Purpose Prestige, 2 Peaceful Calm, 3 Rebel Brave, 4 Sweet Shy
     *
     * File gambar per produk (wajib):
     * - belanja.webp         → image_produk_belanja (kartu belanja + slide 1)
     * - image_1.webp … image_5.webp → slide 2-6 pada slider detail
     *   (image_5.webp = lembar notes varian)
     */
    public function run(): void
    {
        // Hapus produk lama + folder storage products agar path gambar bersih
        Product::query()->delete();
        Storage::disk('public')->deleteDirectory('products');
        Storage::disk('public')->makeDirectory('products');

        $catalog = [
            [
                'slug' => 'purpose',
                'title' => 'Evomi Purpose Prestige',
                'description' => 'Aroma yang merefleksikan ketenangan dan kejelasan tujuan. Berkelas dan karismatik untuk ambisi serta kesan profesional yang eksklusif.',
                'color' => '#1172BA',
                'price' => 189000,
                'personality_type' => 'prestige',
                'top_note' => 'Citrus',
                'middle_note' => 'Woody',
                'base_note' => 'Amber',
                'gender' => 'unisex',
            ],
            [
                'slug' => 'peaceful',
                'title' => 'Evomi Peaceful Calm',
                'description' => 'Aroma menenangkan yang menyatu dengan diri. Segar dan damai untuk jiwa yang mencari kedamaian serta keseimbangan.',
                'color' => '#5EA14A',
                'price' => 199000,
                'personality_type' => 'peaceful_calm',
                'top_note' => 'Bergamot',
                'middle_note' => 'Green Tea',
                'base_note' => 'Musk',
                'gender' => 'unisex',
            ],
            [
                'slug' => 'rebel',
                'title' => 'Evomi Rebel Brave',
                'description' => 'Keberanian dan semangat untuk mengekspresikan diri. Aroma berani dan dinamis untuk jiwa petualang.',
                'color' => '#E33D35',
                'price' => 179000,
                'personality_type' => 'rebel_brave',
                'top_note' => 'Pepper',
                'middle_note' => 'Leather',
                'base_note' => 'Cedar',
                'gender' => 'unisex',
            ],
            [
                'slug' => 'sweet',
                'title' => 'Evomi Sweet Shy',
                'description' => 'Aroma manis yang lembut dan berhati-hati, memberikan kesan hangat, ramah, dan memikat secara perlahan.',
                'color' => '#DD74A5',
                'price' => 189000,
                'personality_type' => 'sweet_shy',
                'top_note' => 'Peach',
                'middle_note' => 'Rose',
                'base_note' => 'Vanilla',
                'gender' => 'female',
            ],
        ];

        foreach ($catalog as $sortOrder => $item) {
            $images = $this->publishImages($item['slug']);

            Product::query()->updateOrCreate(
                ['personality_type' => $item['personality_type']],
                [
                    'title' => $item['title'],
                    'description' => $item['description'],
                    'color' => $item['color'],
                    'price' => $item['price'],
                    'top_note' => $item['top_note'],
                    'middle_note' => $item['middle_note'],
                    'base_note' => $item['base_note'],
                    'image_produk_belanja' => $images['belanja'],
                    'image_1' => $images['image_1'],
                    'image_2' => $images['image_2'],
                    'image_3' => $images['image_3'],
                    'image_4' => $images['image_4'],
                    'image_5' => $images['image_5'],
                    'bottle_size' => 50,
                    'perfume_type' => 'Eau de Parfum',
                    'gender' => $item['gender'],
                    'quantity' => 50,
                    'stock_status' => 'tersedia',
                    'alamat_awal_pengiriman' => 'Cisauk',
                    'kondisi' => 'Baru',
                    'kategori' => 'Parfum',
                    'berat_satuan' => 200,
                    'brand' => 'Evomi',
                    'etalase' => 'Koleksi Karakter',
                ],
            );

            $this->command?->info(sprintf(
                '[%d] %s → belanja=%s',
                $sortOrder + 1,
                $item['title'],
                $images['belanja'],
            ));
        }
    }

    /**
     * Salin ulang gambar seed ke storage/app/public/products/{slug}
     * (terhubung ke public/storage/products/{slug}).
     *
     * @return array{belanja: string, image_1: string, image_2: string, image_3: string, image_4: ?string}
     */
    private function publishImages(string $slug): array
    {
        $sourceDir = database_path("seeders/product-images/{$slug}");
        $targetDir = "products/{$slug}";

        if (!File::isDirectory($sourceDir)) {
            throw new \RuntimeException("Folder seed gambar tidak ditemukan: {$sourceDir}");
        }

        Storage::disk('public')->makeDirectory($targetDir);

        $required = [
            'belanja' => 'belanja.webp',
            'image_1' => 'image_1.webp',
            'image_2' => 'image_2.webp',
            'image_3' => 'image_3.webp',
        ];

        $paths = [];

        foreach ($required as $field => $filename) {
            $source = $sourceDir . DIRECTORY_SEPARATOR . $filename;
            if (!File::exists($source)) {
                throw new \RuntimeException("File wajib tidak ada: {$source}");
            }

            $relative = "{$targetDir}/{$filename}";
            Storage::disk('public')->put($relative, File::get($source));
            $paths[$field] = $relative;
        }

        // Slide 5 dan 6 (termasuk lembar notes) opsional per varian.
        foreach (['image_4', 'image_5'] as $extra) {
            $source = $sourceDir . DIRECTORY_SEPARATOR . $extra . '.webp';

            if (File::exists($source)) {
                $relative = "{$targetDir}/{$extra}.webp";
                Storage::disk('public')->put($relative, File::get($source));
                $paths[$extra] = $relative;
            } else {
                $paths[$extra] = null;
            }
        }

        return $paths;
    }
}
