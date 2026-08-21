<?php

namespace Database\Seeders;

use App\Models\Kurir;
use App\Models\KurirTarif;
use App\Support\ShippingConfig;
use Illuminate\Database\Seeder;

class KurirTarifSeeder extends Seeder
{
    public function run(): void
    {
        $originCity = ShippingConfig::DEFAULT_ORIGIN_CITY;

        $cities = [
            'Jakarta' => 1.00,
            'Bogor' => 0.95,
            'Depok' => 0.95,
            'Tangerang' => 0.85,
            'Bekasi' => 1.00,
            'Bandung' => 1.10,
            'Surabaya' => 1.30,
            'Yogyakarta' => 1.20,
            'Semarang' => 1.25,
            'Medan' => 1.45,
            'Makassar' => 1.50,
        ];

        $weightRanges = [
            ['min' => 0, 'max' => 250, 'mult' => 1.00],
            ['min' => 251, 'max' => 500, 'mult' => 1.25],
            ['min' => 501, 'max' => 1000, 'mult' => 1.75],
            ['min' => 1001, 'max' => 999999, 'mult' => 2.40],
        ];

        // Kurir yang beroperasi di Kec. Cisauk
        $couriers = [
            // JNE — Sales Counter di Ruko Serpong Garden & The Icon Business Park, Cisauk
            ['nama' => 'JNE', 'jenis' => 'REG', 'base_harga' => 9000, 'estimasi_hari' => 3, 'destinasi' => 'Cisauk → seluruh Indonesia'],
            ['nama' => 'JNE', 'jenis' => 'YES', 'base_harga' => 18000, 'estimasi_hari' => 1, 'destinasi' => 'Cisauk → kota besar'],
            ['nama' => 'JNE', 'jenis' => 'OKE', 'base_harga' => 7000, 'estimasi_hari' => 4, 'destinasi' => 'Cisauk → seluruh Indonesia'],

            // J&T Express — J&T Shop di Ruko Serpong Garden, Cisauk
            ['nama' => 'J&T Express', 'jenis' => 'EZ', 'base_harga' => 9000, 'estimasi_hari' => 3, 'destinasi' => 'Cisauk → seluruh Indonesia'],
            ['nama' => 'J&T Express', 'jenis' => 'J&T Super', 'base_harga' => 15000, 'estimasi_hari' => 1, 'destinasi' => 'Cisauk → Jabodetabek'],

            // SiCepat — Agen di Jl. Raya Sampora, Cisauk
            ['nama' => 'SiCepat', 'jenis' => 'REG', 'base_harga' => 8500, 'estimasi_hari' => 3, 'destinasi' => 'Cisauk → seluruh Indonesia'],
            ['nama' => 'SiCepat', 'jenis' => 'BEST', 'base_harga' => 12000, 'estimasi_hari' => 1, 'destinasi' => 'Cisauk → kota besar'],
            ['nama' => 'SiCepat', 'jenis' => 'GOKIL', 'base_harga' => 6500, 'estimasi_hari' => 4, 'destinasi' => 'Cisauk → seluruh Indonesia (min 10kg)'],

            // AnterAja — Drop Point di Jl. Raya Cisauk, Sampora
            ['nama' => 'AnterAja', 'jenis' => 'Reguler', 'base_harga' => 8000, 'estimasi_hari' => 3, 'destinasi' => 'Cisauk → seluruh Indonesia'],
            ['nama' => 'AnterAja', 'jenis' => 'Next Day', 'base_harga' => 14000, 'estimasi_hari' => 1, 'destinasi' => 'Cisauk → Jabodetabek'],
            ['nama' => 'AnterAja', 'jenis' => 'Same Day', 'base_harga' => 22000, 'estimasi_hari' => 1, 'destinasi' => 'Cisauk → Tangerang & sekitar'],
        ];

        // Nonaktifkan semua kurir lama yang tidak ada di list baru
        $validPairs = array_map(fn ($c) => $c['nama'].'|'.$c['jenis'], $couriers);

        Kurir::query()->get()->each(function (Kurir $k) use ($validPairs) {
            $pair = $k->nama.'|'.$k->jenis;
            if (! in_array($pair, $validPairs, true)) {
                $k->update(['is_active' => false]);
            }
        });

        foreach ($couriers as $c) {
            $kurir = Kurir::query()->firstOrCreate(
                ['nama' => $c['nama'], 'jenis' => $c['jenis']],
                [
                    'harga' => $c['base_harga'],
                    'destinasi' => $c['destinasi'],
                    'estimasi_hari' => $c['estimasi_hari'],
                    'is_active' => true,
                ],
            );

            $kurir->update([
                'harga' => $c['base_harga'],
                'destinasi' => $c['destinasi'],
                'estimasi_hari' => $c['estimasi_hari'],
                'is_active' => true,
            ]);

            foreach ($cities as $city => $cityMult) {
                foreach ($weightRanges as $range) {
                    $harga = round($c['base_harga'] * $cityMult * $range['mult'], 2);

                    KurirTarif::query()->updateOrCreate(
                        [
                            'kurir_id' => $kurir->id,
                            'kota_asal' => $originCity,
                            'kota_tujuan' => $city,
                            'berat_min_gram' => $range['min'],
                            'berat_max_gram' => $range['max'],
                        ],
                        [
                            'harga' => $harga,
                            'estimasi_hari' => $c['estimasi_hari'],
                            'is_active' => true,
                        ],
                    );
                }
            }
        }
    }
}
