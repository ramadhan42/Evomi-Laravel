<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderTracking;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminDashboardApiTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'email' => 'admin-dash@evomi.test',
            'password' => Hash::make('password123'),
            'is_admin' => true,
        ]);
    }

    private function customer(): User
    {
        return User::factory()->create([
            'email' => 'customer-dash@evomi.test',
            'password' => Hash::make('password123'),
            'is_admin' => false,
        ]);
    }

    private function makeProduct(array $extra = []): Product
    {
        return Product::create(array_merge([
            'title' => 'Parfum ID',
            'title_en' => 'Perfume EN',
            'description' => 'Deskripsi ID',
            'description_en' => 'Description EN',
            'price' => 100000,
            'bottle_size' => 50,
            'perfume_type' => 'EDP',
            'gender' => 'unisex',
            'quantity' => 10,
            'stock_status' => 'tersedia',
            'image_1' => 'products/a.jpg',
            'image_produk_belanja' => 'products/b.jpg',
        ], $extra));
    }

    public function test_non_admin_cannot_access_admin_products(): void
    {
        Sanctum::actingAs($this->customer());

        $this->getJson('/api/admin/products')->assertForbidden();
    }

    public function test_admin_products_returns_raw_bilingual_fields(): void
    {
        Sanctum::actingAs($this->admin());
        $this->makeProduct();

        $res = $this->getJson('/api/admin/products');

        $res->assertOk()
            ->assertJsonPath('success', true);

        $row = $res->json('data.0');
        $this->assertSame('Parfum ID', $row['title']);
        $this->assertSame('Perfume EN', $row['title_en']);
        $this->assertSame('Deskripsi ID', $row['description']);
        $this->assertSame('Description EN', $row['description_en']);
    }

    public function test_order_destroy_deletes_only_matching_id(): void
    {
        Sanctum::actingAs($this->admin());
        $product = $this->makeProduct();
        $user = $this->customer();
        $now = now();

        Order::create([
            'id' => 'ord-keep',
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'total_price' => 100000,
            'status' => 'menunggu_konfirmasi',
            'payment_status' => Order::PAYMENT_PENDING,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        Order::create([
            'id' => 'ord-delete',
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'total_price' => 200000,
            'status' => 'menunggu_konfirmasi',
            'payment_status' => Order::PAYMENT_PENDING,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->deleteJson('/api/orders/ord-delete')
            ->assertOk()
            ->assertJsonPath('deleted_count', 1);

        $this->assertDatabaseMissing('orders', ['id' => 'ord-delete']);
        $this->assertDatabaseHas('orders', ['id' => 'ord-keep']);
    }

    public function test_admin_orders_include_guest_email(): void
    {
        Sanctum::actingAs($this->admin());
        $product = $this->makeProduct();

        Order::create([
            'id' => 'ord-guest',
            'user_id' => null,
            'guest_email' => 'guest@evomi.test',
            'product_id' => $product->id,
            'quantity' => 1,
            'total_price' => 50000,
            'status' => 'menunggu_konfirmasi',
            'payment_status' => Order::PAYMENT_PENDING,
        ]);

        $this->getJson('/api/admin/orders')
            ->assertOk()
            ->assertJsonPath('data.0.guest_email', 'guest@evomi.test');
    }

    public function test_tracking_timeline_normalizes_date_to_time(): void
    {
        Sanctum::actingAs($this->admin());

        OrderTracking::create([
            'order_id' => 'ord-track-1',
            'tracking_number' => 'RESI123',
            'status' => 'dalam_perjalanan',
            'recipient_name' => 'Budi',
            'recipient_phone' => '081234',
            'recipient_address' => 'Jakarta',
            'timeline' => [
                ['status' => 'Dikirim', 'date' => '2026-08-01 10:00', 'description' => 'Paket berangkat'],
            ],
        ]);

        $list = $this->getJson('/api/admin/trackings')->assertOk();
        $this->assertSame('2026-08-01 10:00', $list->json('data.0.timeline.0.time'));
        $this->assertArrayNotHasKey('date', $list->json('data.0.timeline.0'));

        $this->putJson('/api/admin/trackings/ord-track-1', [
            'status' => 'diterima',
            'timeline' => [
                ['status' => 'Sampai', 'date' => '2026-08-02 12:00', 'description' => 'Selesai'],
            ],
        ])->assertOk();

        $updated = OrderTracking::where('order_id', 'ord-track-1')->first();
        $this->assertSame('2026-08-02 12:00', $updated->timeline[0]['time'] ?? null);
    }

    public function test_guest_cannot_delete_foreign_order(): void
    {
        $owner = $this->customer();
        $other = User::factory()->create([
            'email' => 'other@evomi.test',
            'password' => Hash::make('password123'),
            'is_admin' => false,
        ]);
        $product = $this->makeProduct();

        Order::create([
            'id' => 'ord-owned',
            'user_id' => $owner->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'total_price' => 10000,
            'status' => 'menunggu_konfirmasi',
            'payment_status' => Order::PAYMENT_PENDING,
        ]);

        Sanctum::actingAs($other);

        $this->deleteJson('/api/orders/ord-owned')->assertForbidden();
        $this->assertDatabaseHas('orders', ['id' => 'ord-owned']);
    }
}
