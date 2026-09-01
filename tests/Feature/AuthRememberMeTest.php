<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

/**
 * "Biarkan saya tetap masuk" memilih umur token, dan umur itulah yang membuat
 * sesi bertahan atau berhenti. Keduanya dikunci di sini.
 */
class AuthRememberMeTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(): User
    {
        return User::factory()->create([
            'email' => 'ingat-saya@evomi.test',
            'password' => Hash::make('password123'),
        ]);
    }

    private function login(array $extra = []): TestResponse
    {
        $this->makeUser();

        return $this->postJson('/api/login', $extra + [
            'email' => 'ingat-saya@evomi.test',
            'password' => 'password123',
        ]);
    }

    public function test_centang_memberi_sesi_tiga_puluh_hari(): void
    {
        Carbon::setTestNow('2026-09-01 08:00:00');

        $response = $this->login(['remember' => true]);

        $response->assertOk()->assertJsonPath('remember', true);

        $expiresAt = Carbon::parse($response->json('expires_at'));
        $this->assertSame(30, (int) now()->diffInDays($expiresAt));

        $token = PersonalAccessToken::findToken($response->json('token'));
        $this->assertNotNull($token->expires_at);
        $this->assertSame(30, (int) now()->diffInDays($token->expires_at));
    }

    public function test_tanpa_centang_sesi_hanya_dua_belas_jam(): void
    {
        Carbon::setTestNow('2026-09-01 08:00:00');

        $response = $this->login(['remember' => false]);

        $response->assertOk()->assertJsonPath('remember', false);

        $expiresAt = Carbon::parse($response->json('expires_at'));
        $this->assertSame(12, (int) now()->diffInHours($expiresAt));
    }

    /** Field yang tidak dikirim sama sekali diperlakukan seperti tidak dicentang. */
    public function test_tanpa_field_remember_dianggap_tidak_dicentang(): void
    {
        Carbon::setTestNow('2026-09-01 08:00:00');

        $response = $this->login();

        $response->assertOk()->assertJsonPath('remember', false);
        $this->assertSame(12, (int) now()->diffInHours(Carbon::parse($response->json('expires_at'))));
    }

    public function test_token_yang_diingat_masih_berlaku_di_hari_kedua_puluh_sembilan(): void
    {
        Carbon::setTestNow('2026-09-01 08:00:00');
        $token = $this->login(['remember' => true])->json('token');

        Carbon::setTestNow('2026-09-30 08:00:00');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/user/profile')
            ->assertOk();
    }

    public function test_token_ditolak_setelah_lewat_tiga_puluh_hari(): void
    {
        Carbon::setTestNow('2026-09-01 08:00:00');
        $token = $this->login(['remember' => true])->json('token');

        Carbon::setTestNow('2026-10-02 08:00:00');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/user/profile')
            ->assertUnauthorized();
    }

    public function test_token_tanpa_centang_mati_setelah_dua_belas_jam(): void
    {
        Carbon::setTestNow('2026-09-01 08:00:00');
        $token = $this->login(['remember' => false])->json('token');

        Carbon::setTestNow('2026-09-01 21:00:00');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/user/profile')
            ->assertUnauthorized();
    }

    public function test_durasi_mengikuti_konfigurasi(): void
    {
        config(['evomi.auth.remember_days' => 7]);
        Carbon::setTestNow('2026-09-01 08:00:00');

        $response = $this->login(['remember' => true]);

        $this->assertSame(7, (int) now()->diffInDays(Carbon::parse($response->json('expires_at'))));
    }

    public function test_pendaftaran_baru_ikut_menghormati_centang(): void
    {
        Carbon::setTestNow('2026-09-01 08:00:00');

        $response = $this->postJson('/api/register', [
            'name' => 'Pendaftar Baru',
            'email' => 'pendaftar@evomi.test',
            'password' => 'password123',
            'remember' => true,
        ]);

        $response->assertCreated()->assertJsonPath('remember', true);
        $this->assertSame(30, (int) now()->diffInDays(Carbon::parse($response->json('expires_at'))));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }
}
