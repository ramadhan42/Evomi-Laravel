<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Support\ProductSeo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductSeoTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function product(array $overrides = []): array
    {
        return array_merge([
            'id' => 1,
            'title' => 'Purpose Prestige',
            'price' => 250000,
            'stock' => 12,
            'brand' => 'Evomi',
            'kategori' => 'Parfum',
            'bottle_size' => 50,
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function ctx(array $overrides = []): array
    {
        return array_merge([
            'url' => 'https://evomi.id/belanja/1',
            'title' => 'Purpose Prestige',
            'description' => 'Parfum dengan karakter tenang dan hangat.',
            'image' => 'https://evomi.id/share/product/1.jpg',
            'gallery' => ['https://evomi.id/storage/products/purpose/image_1.webp'],
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $graph
     * @return array<string, mixed>
     */
    private function productNode(array $graph): array
    {
        foreach ($graph['@graph'] as $node) {
            if (($node['@type'] ?? '') === 'Product') {
                return $node;
            }
        }

        $this->fail('Graf tidak memuat node Product.');
    }

    /**
     * Ini galat yang dilaporkan Search Console sebagai kritis: node Product
     * tanpa salah satu dari offers/review/aggregateRating.
     */
    public function test_product_node_always_carries_an_offer(): void
    {
        $node = $this->productNode(ProductSeo::schemaGraph($this->product(), $this->ctx()));

        $this->assertArrayHasKey('offers', $node);
        $this->assertSame('Offer', $node['offers']['@type']);
        $this->assertSame('IDR', $node['offers']['priceCurrency']);
    }

    /**
     * Harga di markup harus sama dengan yang dibaca pembeli di halaman, yaitu
     * harga jual - bukan harga coret di kolom `price`.
     */
    public function test_price_follows_the_displayed_selling_price(): void
    {
        config()->set('evomi.pricing.display', 190000);

        $node = $this->productNode(ProductSeo::schemaGraph($this->product(), $this->ctx()));

        $this->assertSame('190000', $node['offers']['price']);
    }

    /** Angka polos: pemisah ribuan atau "Rp" membuat Google menolak offer. */
    public function test_price_is_written_without_separators(): void
    {
        config()->set('evomi.pricing.display', 1250000);

        $node = $this->productNode(ProductSeo::schemaGraph($this->product(), $this->ctx()));

        $this->assertSame('1250000', $node['offers']['price']);
        $this->assertMatchesRegularExpression('/^\d+$/', $node['offers']['price']);
    }

    public function test_availability_follows_stock(): void
    {
        $inStock = $this->productNode(
            ProductSeo::schemaGraph($this->product(['stock' => 3]), $this->ctx())
        );
        $soldOut = $this->productNode(
            ProductSeo::schemaGraph($this->product(['stock' => 0]), $this->ctx())
        );

        $this->assertSame('https://schema.org/InStock', $inStock['offers']['availability']);
        $this->assertSame('https://schema.org/OutOfStock', $soldOut['offers']['availability']);
    }

    /** Crawler hanya bisa mengambil URL absolut. */
    public function test_relative_gallery_entries_are_left_out(): void
    {
        $node = $this->productNode(ProductSeo::schemaGraph($this->product(), $this->ctx([
            'gallery' => [
                'https://evomi.id/storage/a.webp',
                'src/images/b.webp',
                '',
                'https://evomi.id/storage/a.webp',
            ],
        ])));

        $this->assertSame(['https://evomi.id/storage/a.webp'], $node['image']);
    }

    /** Tanpa galeri yang terpakai, node Product tetap tidak boleh tanpa gambar. */
    public function test_share_image_is_used_when_the_gallery_gives_nothing(): void
    {
        $node = $this->productNode(ProductSeo::schemaGraph($this->product(), $this->ctx([
            'gallery' => ['src/images/relatif.webp'],
        ])));

        $this->assertSame(['https://evomi.id/share/product/1.jpg'], $node['image']);
    }

    public function test_breadcrumb_ends_at_the_product(): void
    {
        $graph = ProductSeo::schemaGraph($this->product(), $this->ctx());

        $crumbs = null;
        foreach ($graph['@graph'] as $node) {
            if (($node['@type'] ?? '') === 'BreadcrumbList') {
                $crumbs = $node['itemListElement'];
            }
        }

        $this->assertNotNull($crumbs);
        $this->assertCount(3, $crumbs);
        $this->assertSame('Purpose Prestige', $crumbs[2]['name']);
        $this->assertSame('https://evomi.id/belanja/1', $crumbs[2]['item']);
    }

    /** Halaman produk yang benar-benar dirender harus memuat markup itu. */
    public function test_product_page_renders_the_json_ld_block(): void
    {
        $product = Product::create([
            'title' => 'Purpose Prestige',
            'description' => 'Parfum dengan karakter tenang dan hangat.',
            'price' => 250000,
            'bottle_size' => 50,
            'perfume_type' => 'EDP',
            'gender' => 'unisex',
            'quantity' => 10,
            'stock_status' => 'tersedia',
            'image_1' => 'products/a.jpg',
            'image_produk_belanja' => 'products/b.jpg',
        ]);

        $response = $this->get('/belanja/'.$product->id);

        $response->assertOk();
        $response->assertSee('application/ld+json', false);
        $response->assertSee('"@type":"Product"', false);
        $response->assertSee('"@type":"Offer"', false);
    }
}
