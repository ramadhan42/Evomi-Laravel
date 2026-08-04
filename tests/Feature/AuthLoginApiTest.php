<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthLoginApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_returns_token_and_admin_can_hit_revenue(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin-test@evomi.test',
            'password' => Hash::make('password123'),
            'is_admin' => true,
        ]);

        $login = $this->postJson('/api/login', [
            'email' => $admin->email,
            'password' => 'password123',
        ]);

        $login->assertOk()
            ->assertJsonStructure(['token', 'user' => ['id', 'email', 'is_admin']]);

        $this->assertTrue((bool) $login->json('user.is_admin'));

        $token = $login->json('token');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/admin/revenue')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_register_creates_user_and_returns_token(): void
    {
        $res = $this->postJson('/api/register', [
            'name' => 'Tester Evomi',
            'email' => 'tester@evomi.test',
            'password' => 'password123',
        ]);

        $res->assertCreated()
            ->assertJsonStructure(['token', 'user' => ['id', 'email']]);

        $this->assertFalse((bool) $res->json('user.is_admin'));
        $this->assertDatabaseHas('users', ['email' => 'tester@evomi.test']);
    }

    public function test_dashboard_routes_render(): void
    {
        $this->get('/dashboard')->assertOk();
        $this->get('/dashboard/products')->assertOk();
        $this->get('/login')->assertOk();
        $this->get('/register')->assertOk();
    }

    public function test_profile_routes_render(): void
    {
        $this->get('/profile')->assertOk();
        $this->get('/profile/cart')->assertOk();
        $this->get('/profile/wishlist')->assertOk();
        $this->get('/profile/history')->assertOk();
        $this->get('/profile/chat')->assertOk();
    }
}
