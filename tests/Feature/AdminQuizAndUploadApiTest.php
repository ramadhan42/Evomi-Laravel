<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminQuizAndUploadApiTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'email' => 'admin-quiz@evomi.test',
            'password' => Hash::make('password123'),
            'is_admin' => true,
        ]);
    }

    private function customer(): User
    {
        return User::factory()->create([
            'email' => 'customer-quiz@evomi.test',
            'password' => Hash::make('password123'),
            'is_admin' => false,
        ]);
    }

    private function questionPayload(array $overrides = []): array
    {
        return array_merge([
            'question_text' => 'Apa aroma favoritmu?',
            'question_text_en' => 'What is your favorite scent?',
            'options' => [
                [
                    'option_text' => 'Manis',
                    'option_text_en' => 'Sweet',
                    'prestige_score' => 1,
                    'peaceful_calm_score' => 0,
                    'rebel_brave_score' => 0,
                    'sweet_shy_score' => 3,
                ],
                [
                    'option_text' => 'Segar',
                    'option_text_en' => 'Fresh',
                    'prestige_score' => 0,
                    'peaceful_calm_score' => 3,
                    'rebel_brave_score' => 1,
                    'sweet_shy_score' => 0,
                ],
            ],
        ], $overrides);
    }

    public function test_guest_cannot_access_admin_quiz_endpoints(): void
    {
        $this->getJson('/api/admin/quiz/questions')->assertUnauthorized();
        $this->postJson('/api/admin/quiz/questions', $this->questionPayload())->assertUnauthorized();
    }

    public function test_non_admin_cannot_manage_quiz(): void
    {
        Sanctum::actingAs($this->customer());

        $this->getJson('/api/admin/quiz/questions')->assertForbidden();
        $this->postJson('/api/admin/quiz/questions', $this->questionPayload())->assertForbidden();
    }

    public function test_admin_can_create_update_and_delete_quiz_question(): void
    {
        Sanctum::actingAs($this->admin());

        $create = $this->postJson('/api/admin/quiz/questions', $this->questionPayload())
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.question_text', 'Apa aroma favoritmu?')
            ->assertJsonPath('data.question_text_en', 'What is your favorite scent?')
            ->assertJsonPath('data.options.0.option_text_en', 'Sweet');

        $id = $create->json('data.id');
        $this->assertNotNull($id);
        $this->assertCount(2, $create->json('data.options'));

        $this->postJson('/api/admin/quiz/questions', $this->questionPayload([
            'options' => [
                ['option_text' => 'Only one'],
            ],
        ]))->assertStatus(422);

        $this->putJson("/api/admin/quiz/questions/{$id}", $this->questionPayload([
            'question_text' => 'Pertanyaan diubah',
            'question_text_en' => 'Updated question',
            'options' => [
                [
                    'id' => $create->json('data.options.0.id'),
                    'option_text' => 'Manis lembut',
                    'option_text_en' => 'Soft sweet',
                    'prestige_score' => 2,
                    'peaceful_calm_score' => 0,
                    'rebel_brave_score' => 0,
                    'sweet_shy_score' => 4,
                ],
                [
                    'option_text' => 'Berani',
                    'option_text_en' => 'Bold',
                    'prestige_score' => 0,
                    'peaceful_calm_score' => 0,
                    'rebel_brave_score' => 5,
                    'sweet_shy_score' => 0,
                ],
                [
                    'option_text' => 'Tenang',
                    'option_text_en' => 'Calm',
                    'prestige_score' => 0,
                    'peaceful_calm_score' => 4,
                    'rebel_brave_score' => 0,
                    'sweet_shy_score' => 1,
                ],
            ],
        ]))->assertOk()
            ->assertJsonPath('data.question_text', 'Pertanyaan diubah')
            ->assertJsonPath('data.options.0.option_text_en', 'Soft sweet');

        $this->assertCount(3, $this->getJson("/api/admin/quiz/questions/{$id}")->json('data.options'));

        $this->deleteJson("/api/admin/quiz/questions/{$id}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('quiz_questions', ['id' => $id]);
    }

    public function test_admin_can_update_and_delete_quiz_score(): void
    {
        Sanctum::actingAs($this->admin());
        $user = $this->customer();
        $product = Product::create([
            'title' => 'Parfum ID',
            'title_en' => 'Perfume EN',
            'description' => 'Deskripsi',
            'description_en' => 'Description',
            'price' => 100000,
            'bottle_size' => 50,
            'perfume_type' => 'EDP',
            'gender' => 'unisex',
            'quantity' => 10,
            'stock_status' => 'tersedia',
            'image_1' => 'products/a.jpg',
            'image_produk_belanja' => 'products/b.jpg',
        ]);

        $attempt = QuizAttempt::create([
            'user_id' => $user->id,
            'total_prestige' => 2,
            'total_peaceful_calm' => 5,
            'total_rebel_brave' => 1,
            'total_sweet_shy' => 0,
            'dominant_personality' => 'peaceful_calm',
            'product_id' => $product->id,
        ]);

        $this->getJson('/api/admin/quiz/scores')
            ->assertOk()
            ->assertJsonPath('data.0.id', $attempt->id)
            ->assertJsonPath('data.0.dominant_personality', 'peaceful_calm');

        $this->putJson("/api/admin/quiz/scores/{$attempt->id}", [
            'total_prestige' => 8,
            'total_peaceful_calm' => 1,
            'total_rebel_brave' => 0,
            'total_sweet_shy' => 0,
            'dominant_personality' => 'prestige',
            'product_id' => $product->id,
        ])->assertOk()
            ->assertJsonPath('data.dominant_personality', 'prestige')
            ->assertJsonPath('data.total_prestige', 8);

        $this->deleteJson("/api/admin/quiz/scores/{$attempt->id}")
            ->assertOk();

        $this->assertDatabaseMissing('quiz_attempts', ['id' => $attempt->id]);
    }

    public function test_admin_product_multipart_create_keeps_bilingual_fields(): void
    {
        Storage::fake('public');
        Sanctum::actingAs($this->admin());

        $response = $this->post('/api/products', [
            'title' => 'Aroma ID',
            'title_en' => 'Aroma EN',
            'description' => 'Deskripsi ID',
            'description_en' => 'Description EN',
            'price' => 150000,
            'bottle_size' => 50,
            'perfume_type' => 'EDP',
            'gender' => 'unisex',
            'quantity' => 5,
            'stock_status' => 'tersedia',
            'image_1' => UploadedFile::fake()->image('cover.jpg'),
            'image_produk_belanja' => UploadedFile::fake()->image('shop.jpg'),
        ], [
            'Accept' => 'application/json',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true);

        $productId = $response->json('data.id');
        $this->assertNotNull($productId);

        $list = $this->getJson('/api/admin/products')->assertOk();
        $row = collect($list->json('data'))->firstWhere('id', (int) $productId);

        $this->assertNotNull($row);
        $this->assertSame('Aroma ID', $row['title']);
        $this->assertSame('Aroma EN', $row['title_en']);
        $this->assertSame('Deskripsi ID', $row['description']);
        $this->assertSame('Description EN', $row['description_en']);
    }
}
