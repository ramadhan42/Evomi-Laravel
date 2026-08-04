<?php

namespace Database\Seeders;

use App\Models\Disclaimer;
use App\Models\Kurir;
use App\Models\Promo;
use App\Models\User;
use Illuminate\Database\Seeder;

class EvomiCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedAdminFromEnv();

        if (!User::where('email', 'demo@evomi.com')->exists()) {
            User::create([
                'name' => 'Demo User',
                'email' => 'demo@evomi.com',
                'password' => 'password123',
                'is_admin' => false,
                'nama_lengkap' => 'Pengguna Demo',
            ]);
        }

        // Produk dikelola ProductSeeder (gambar + urutan lengkap)

        $kurirSeeds = [
            [
                'nama' => 'JNE Reguler',
                'jenis' => 'REG',
                'harga' => 20000,
                'destinasi' => 'Seluruh Indonesia',
                'estimasi_hari' => 3,
                'is_active' => true,
            ],
            [
                'nama' => 'JNE YES',
                'jenis' => 'YES',
                'harga' => 35000,
                'destinasi' => 'Kota besar Indonesia',
                'estimasi_hari' => 1,
                'is_active' => true,
            ],
            [
                'nama' => 'JNE OKE',
                'jenis' => 'OKE',
                'harga' => 16000,
                'destinasi' => 'Seluruh Indonesia',
                'estimasi_hari' => 4,
                'is_active' => true,
            ],
            [
                'nama' => 'J&T Express',
                'jenis' => 'EZ',
                'harga' => 18000,
                'destinasi' => 'Seluruh Indonesia',
                'estimasi_hari' => 3,
                'is_active' => true,
            ],
            [
                'nama' => 'J&T Cargo',
                'jenis' => 'Economy',
                'harga' => 14000,
                'destinasi' => 'Seluruh Indonesia',
                'estimasi_hari' => 5,
                'is_active' => true,
            ],
            [
                'nama' => 'SiCepat REG',
                'jenis' => 'REG',
                'harga' => 17000,
                'destinasi' => 'Jabodetabek & Jawa',
                'estimasi_hari' => 3,
                'is_active' => true,
            ],
            [
                'nama' => 'SiCepat Halu',
                'jenis' => 'HALU',
                'harga' => 22000,
                'destinasi' => 'Jabodetabek & kota besar',
                'estimasi_hari' => 1,
                'is_active' => true,
            ],
            [
                'nama' => 'SiCepat GOKIL',
                'jenis' => 'GOKIL',
                'harga' => 28000,
                'destinasi' => 'Jabodetabek',
                'estimasi_hari' => 1,
                'is_active' => true,
            ],
            [
                'nama' => 'TIKI Reguler',
                'jenis' => 'REG',
                'harga' => 19000,
                'destinasi' => 'Seluruh Indonesia',
                'estimasi_hari' => 3,
                'is_active' => true,
            ],
            [
                'nama' => 'TIKI Overnight',
                'jenis' => 'ONS',
                'harga' => 40000,
                'destinasi' => 'Kota besar Indonesia',
                'estimasi_hari' => 1,
                'is_active' => true,
            ],
            [
                'nama' => 'Anteraja Reguler',
                'jenis' => 'REG',
                'harga' => 15000,
                'destinasi' => 'Jabodetabek & Jawa',
                'estimasi_hari' => 3,
                'is_active' => true,
            ],
            [
                'nama' => 'Anteraja Same Day',
                'jenis' => 'Same Day',
                'harga' => 25000,
                'destinasi' => 'Jabodetabek',
                'estimasi_hari' => 1,
                'is_active' => true,
            ],
            [
                'nama' => 'Ninja Xpress',
                'jenis' => 'Standard',
                'harga' => 16500,
                'destinasi' => 'Seluruh Indonesia',
                'estimasi_hari' => 3,
                'is_active' => true,
            ],
            [
                'nama' => 'Pos Indonesia',
                'jenis' => 'Kilat Khusus',
                'harga' => 18000,
                'destinasi' => 'Seluruh Indonesia',
                'estimasi_hari' => 4,
                'is_active' => true,
            ],
            [
                'nama' => 'ID Express',
                'jenis' => 'REG',
                'harga' => 15500,
                'destinasi' => 'Jawa & Sumatera',
                'estimasi_hari' => 3,
                'is_active' => true,
            ],
            [
                'nama' => 'Shopee Express',
                'jenis' => 'Standard',
                'harga' => 12000,
                'destinasi' => 'Jabodetabek & kota besar',
                'estimasi_hari' => 2,
                'is_active' => true,
            ],
        ];

        foreach ($kurirSeeds as $seed) {
            Kurir::updateOrCreate(
                [
                    'nama' => $seed['nama'],
                    'jenis' => $seed['jenis'],
                ],
                $seed,
            );
        }

        if (!Promo::exists()) {
            Promo::create([
                'harga_promo' => 15000,
                'persentase_promo' => null,
                'tanggal_berlaku_promo' => now()->toDateString(),
                'tanggal_berakhir_promo' => now()->addMonths(3)->toDateString(),
            ]);
        }

        if (!Disclaimer::exists()) {
            Disclaimer::create([
                'deskripsi' => 'Warna dan aroma aktual dapat sedikit berbeda tergantung batch produksi.',
            ]);
            Disclaimer::create([
                'deskripsi' => 'Simpan di tempat sejuk, jauh dari sinar matahari langsung.',
            ]);
        }
    }

    /**
     * Buat/update admin dari EVOMI_ADMIN_* di .env (pola Arcanisia).
     */
    private function seedAdminFromEnv(): void
    {
        if (app()->isProduction()) {
            return;
        }

        $email = config('evomi.development_admin.email');
        $password = config('evomi.development_admin.password');

        if (!is_string($email) || $email === '' || !is_string($password) || $password === '') {
            $this->command?->warn(
                'EVOMI_ADMIN_EMAIL / EVOMI_ADMIN_PASSWORD belum di-set di .env — admin tidak di-seed.',
            );

            return;
        }

        User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => config('evomi.development_admin.name') ?: 'Evomi Admin',
                'password' => $password,
                'is_admin' => true,
                'nama_lengkap' => config('evomi.development_admin.name') ?: 'Evomi Admin',
                'email_verified_at' => now(),
            ],
        );
    }
}
