<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * JSON-LD untuk halaman detail produk.
 *
 * Google menolak node Product yang tidak menyertakan salah satu dari `offers`,
 * `review`, atau `aggregateRating`; laporan Merchant listings menandainya
 * sebagai galat kritis. Yang benar untuk kami adalah `offers` - harganya nyata
 * dan sudah tampil di halaman. Rating tidak dikarang: belum ada ulasan asli,
 * dan memasang bintang palsu melanggar kebijakan Google serta bisa berujung
 * tindakan manual.
 */
class ProductSeo
{
    /**
     * Harga yang benar-benar dibayar pembeli.
     *
     * Sumbernya `config('evomi.pricing.display')`, sama persis dengan yang
     * dipakai belanja/detail.blade.php. Memakai kolom `price` mentah akan
     * menuliskan harga coret ke dalam schema, dan Google menandai selisih
     * antara harga di markup dan harga di halaman sebagai pelanggaran.
     *
     * @param  array<string, mixed>  $product
     */
    public static function price(array $product): float
    {
        return (float) (config('evomi.pricing.display') ?? ($product['price'] ?? 0));
    }

    /**
     * Node Product + Offer, ditemani jejak remah halaman.
     *
     * @param  array<string, mixed>  $product
     * @param  array<string, mixed>  $ctx
     * @return array<string, mixed>
     */
    public static function schemaGraph(array $product, array $ctx): array
    {
        $url = (string) $ctx['url'];
        $siteUrl = rtrim((string) config('app.url'), '/');
        $price = self::price($product);
        $stock = (int) ($product['stock'] ?? 0);

        $node = [
            '@type' => 'Product',
            '@id' => $url.'#product',
            'name' => (string) $ctx['title'],
            'description' => (string) $ctx['description'],
            'url' => $url,
            'sku' => (string) ($product['id'] ?? ''),
            'brand' => [
                '@type' => 'Brand',
                'name' => (string) ($product['brand'] ?? '') ?: 'Evomi',
            ],
            'offers' => [
                '@type' => 'Offer',
                'url' => $url,
                // Google menuntut angka polos: tanpa pemisah ribuan, tanpa "Rp".
                'price' => number_format($price, 0, '.', ''),
                'priceCurrency' => 'IDR',
                'availability' => $stock > 0
                    ? 'https://schema.org/InStock'
                    : 'https://schema.org/OutOfStock',
                'itemCondition' => 'https://schema.org/NewCondition',
                'seller' => [
                    '@type' => 'Organization',
                    'name' => 'Evomi',
                    'url' => $siteUrl !== '' ? $siteUrl.'/' : $url,
                ],
            ],
        ];

        $images = self::images($ctx);
        if ($images !== []) {
            $node['image'] = $images;
        }

        if (! empty($product['kategori'])) {
            $node['category'] = (string) $product['kategori'];
        }

        // Ukuran botol membedakan varian yang namanya sama, jadi layak ikut.
        $size = (int) ($product['bottle_size'] ?? 0);
        if ($size > 0) {
            $node['size'] = $size.' ml';
        }

        return [
            '@context' => 'https://schema.org',
            '@graph' => [
                $node,
                [
                    '@type' => 'BreadcrumbList',
                    '@id' => $url.'#breadcrumb',
                    'itemListElement' => [
                        [
                            '@type' => 'ListItem',
                            'position' => 1,
                            'name' => 'Evomi',
                            'item' => $siteUrl !== '' ? $siteUrl.'/' : $url,
                        ],
                        [
                            '@type' => 'ListItem',
                            'position' => 2,
                            'name' => 'Belanja',
                            'item' => $siteUrl !== '' ? $siteUrl.'/belanja' : $url,
                        ],
                        [
                            '@type' => 'ListItem',
                            'position' => 3,
                            'name' => (string) $ctx['title'],
                            'item' => $url,
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Foto produk untuk markup, hanya yang benar-benar bisa diambil crawler.
     *
     * Entri galeri yang berupa jalur relatif dilewati: Google mengambil
     * gambar dari URL absolut, dan jalur setengah jadi hanya menghasilkan
     * peringatan gambar tidak terbaca. Gambar share dipakai sebagai cadangan
     * supaya node Product tidak pernah kehilangan `image`.
     *
     * @param  array<string, mixed>  $ctx
     * @return list<string>
     */
    private static function images(array $ctx): array
    {
        $out = [];

        foreach ((array) ($ctx['gallery'] ?? []) as $src) {
            $src = trim((string) $src);

            if ($src !== '' && Str::startsWith($src, ['http://', 'https://'])) {
                $out[] = $src;
            }
        }

        $out = array_values(array_unique($out));

        if ($out === []) {
            $fallback = trim((string) ($ctx['image'] ?? ''));

            if ($fallback !== '') {
                $out[] = $fallback;
            }
        }

        return $out;
    }
}
