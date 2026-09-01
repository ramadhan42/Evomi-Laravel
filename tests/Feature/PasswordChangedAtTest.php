<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Halaman biodata tidak bisa menampilkan kata sandi - yang tersimpan hanya hash
 * bcrypt - jadi kepastiannya diberikan lewat tanggal ini. Tanggal yang salah
 * lebih buruk daripada tidak ada tanggal, jadi setiap jalur yang mengubah kata
 * sandi dikunci di sini.
 */
class PasswordChangedAtTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsUser(array $extra = []): User
    {
        $user = User::factory()->create($extra + [
            'email' => 'biodata@evomi.test',
            'password' => Hash::make('password123'),
        ]);
        Sanctum::actingAs($user);

        return $user;
    }

    public function test_akun_lama_tidak_menebak_tanggal(): void
    {
        $user = $this->actingAsUser();

        $this->assertNull($user->password_changed_at);
    }

    public function test_mengubah_kata_sandi_mencatat_tanggalnya(): void
    {
        Carbon::setTestNow('2026-09-01 10:00:00');
        $user = $this->actingAsUser();

        $this->postJson('/api/user/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'password' => 'kata-sandi-baru',
        ])->assertOk();

        $user->refresh();
        $this->assertNotNull($user->password_changed_at);
        $this->assertSame('2026-09-01 10:00:00', $user->password_changed_at->format('Y-m-d H:i:s'));
        $this->assertTrue(Hash::check('kata-sandi-baru', $user->password));
    }

    public function test_menyimpan_biodata_tanpa_kata_sandi_tidak_menyentuh_tanggalnya(): void
    {
        Carbon::setTestNow('2026-09-01 10:00:00');
        $user = $this->actingAsUser(['password_changed_at' => Carbon::parse('2026-08-01 09:00:00')]);

        $this->postJson('/api/user/profile', [
            'name' => 'Nama Baru',
            'email' => $user->email,
        ])->assertOk();

        $user->refresh();
        $this->assertSame('Nama Baru', $user->name);
        $this->assertSame('2026-08-01 09:00:00', $user->password_changed_at->format('Y-m-d H:i:s'));
        $this->assertTrue(Hash::check('password123', $user->password), 'Kata sandi lama harus tetap berlaku.');
    }

    public function test_pendaftaran_baru_langsung_punya_tanggal(): void
    {
        Carbon::setTestNow('2026-09-01 10:00:00');

        $this->postJson('/api/register', [
            'name' => 'Pendaftar',
            'email' => 'pendaftar-biodata@evomi.test',
            'password' => 'password123',
        ])->assertCreated();

        $user = User::where('email', 'pendaftar-biodata@evomi.test')->firstOrFail();
        $this->assertNotNull($user->password_changed_at);
    }

    public function test_reset_lewat_tautan_email_ikut_mencatat(): void
    {
        Carbon::setTestNow('2026-09-01 10:00:00');
        $user = User::factory()->create([
            'email' => 'reset-biodata@evomi.test',
            'password' => Hash::make('password123'),
        ]);
        $token = Password::createToken($user);

        $this->postJson('/api/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'sandi-hasil-reset',
            'password_confirmation' => 'sandi-hasil-reset',
        ])->assertOk();

        $user->refresh();
        $this->assertNotNull($user->password_changed_at);
    }

    public function test_api_profil_membagikan_tanggalnya_tetapi_tidak_kata_sandinya(): void
    {
        $this->actingAsUser(['password_changed_at' => Carbon::parse('2026-08-01 09:00:00')]);

        $response = $this->getJson('/api/user/profile')->assertOk();

        $badan = json_encode($response->json());
        $this->assertStringContainsString('password_changed_at', $badan);
        $this->assertStringNotContainsString('$2y$', $badan, 'Hash kata sandi tidak boleh ikut terkirim.');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }
}
