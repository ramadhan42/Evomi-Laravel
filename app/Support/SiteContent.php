<?php

namespace App\Support;

class SiteContent
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function articles(): array
    {
        return [
            [
                'id' => 1,
                'slug' => 'cara-memilih-parfum-pertama',
                'title' => 'Cara Memilih Parfum Pertama yang Cocok',
                'excerpt' => 'Panduan singkat memilih aroma yang selaras dengan kepribadian dan rutinitas harianmu.',
                'category' => 'Tips',
                'author' => 'Tim Evomi',
                'published_at' => '2026-03-12',
                'image' => null,
                'content' => "Memilih parfum pertama sering terasa membingungkan. Mulailah dari karakter yang paling dekat dengan dirimu — tenang, berani, manis, atau berorientasi tujuan.\n\nUji aroma di kulit, bukan hanya di kertas. Biarkan 20–30 menit agar middle dan base note muncul.\n\nSimpan 2–3 opsi favorit, lalu pakai masing-masing selama sehari. Aroma yang paling sering kamu rindukan biasanya yang paling cocok.",
            ],
            [
                'id' => 2,
                'slug' => 'merawat-parfum-agar-awet',
                'title' => 'Merawat Parfum Agar Awet dan Stabil',
                'excerpt' => 'Suhu, cahaya, dan cara menyemprot memengaruhi daya tahan wewangian.',
                'category' => 'Perawatan',
                'author' => 'Tim Evomi',
                'published_at' => '2026-04-02',
                'image' => null,
                'content' => "Simpan parfum di tempat sejuk, gelap, dan kering. Hindari kamar mandi yang lembap.\n\nJangan kocok botol terlalu keras. Semprot dari jarak 15–20 cm pada pulse point.\n\nTutup rapat setelah dipakai agar alkohol tidak cepat menguap dan aroma tetap stabil.",
            ],
            [
                'id' => 3,
                'slug' => 'empat-karakter-aroma-evomi',
                'title' => 'Mengenal Empat Karakter Aroma Evomi',
                'excerpt' => 'Purpose, Peaceful, Rebel, dan Sweet — empat sisi kepribadian dalam satu koleksi.',
                'category' => 'Koleksi',
                'author' => 'Tim Evomi',
                'published_at' => '2026-05-18',
                'image' => null,
                'content' => "Purpose Prestige menghadirkan kesan jelas, percaya diri, dan profesional.\n\nPeaceful Calm membawa keseimbangan dan ketenangan.\n\nRebel Brave penuh energi untuk ekspresi diri.\n\nSweet Shy lembut, hangat, dan memikat perlahan.\n\nTidak harus memilih satu selamanya — kamu bisa berganti sesuai mood dan momen.",
            ],
            [
                'id' => 4,
                'slug' => 'layering-parfum-dengan-aman',
                'title' => 'Layering Parfum: Tips Aman untuk Pemula',
                'excerpt' => 'Gabungkan dua aroma tanpa saling menekan. Mulai dari note yang saling mendukung.',
                'category' => 'Tips',
                'author' => 'Tim Evomi',
                'published_at' => '2026-06-01',
                'image' => null,
                'content' => "Layering terbaik biasanya memakai aroma yang berbagi keluarga note — misalnya woody dengan amber, atau floral dengan soft musk.\n\nSemprot aroma yang lebih berat lebih dulu, lalu yang lebih ringan.\n\nMulai dengan satu semprotan masing-masing. Tambah perlahan jika dibutuhkan.",
            ],
            [
                'id' => 5,
                'slug' => 'parfum-untuk-cuaca-tropis',
                'title' => 'Parfum untuk Cuaca Tropis',
                'excerpt' => 'Di iklim panas dan lembap, pilih aroma yang tetap segar tanpa terasa menyesakkan.',
                'category' => 'Tips',
                'author' => 'Tim Evomi',
                'published_at' => '2026-06-20',
                'image' => null,
                'content' => "Cuaca tropis membuat aroma lebih cepat membuka. Pilih komposisi yang tidak terlalu berat di base note.\n\nSemprot lebih sedikit di siang hari, dan simpan botol jauh dari sinar matahari langsung di dalam tas.\n\nRe-apply ringan di sore hari biasanya lebih nyaman daripada overspray di pagi hari.",
            ],
            [
                'id' => 6,
                'slug' => 'ritual-signature-scent',
                'title' => 'Membangun Ritual Signature Scent',
                'excerpt' => 'Signature scent bukan soal mahal — soal konsistensi dan cerita yang melekat padamu.',
                'category' => 'Cerita',
                'author' => 'Tim Evomi',
                'published_at' => '2026-07-08',
                'image' => null,
                'content' => "Pilih satu aroma utama untuk hari kerja atau rutinitas harian.\n\nPasangkan dengan moisturizer tanpa pewangi agar daya lekat lebih baik.\n\nDengan waktu, orang di sekitarmu akan mengaitkan aroma itu dengan kehadiranmu — itulah kekuatan signature scent.",
            ],
        ];
    }

    public static function findArticle(string $slug): ?array
    {
        foreach (self::articles() as $article) {
            if ($article['slug'] === $slug) {
                return $article;
            }
        }

        return null;
    }

    /**
     * @return array<string, list<array{q: string, a: string}>>
     */
    public static function faqs(): array
    {
        return [
            'Pesanan & Pembayaran' => [
                [
                    'q' => 'Bagaimana cara melacak pesanan saya?',
                    'a' => "Setelah pesanan diproses, Anda akan menerima email konfirmasi dengan nomor pelacakan yang dapat dipantau di halaman Status Pesanan.",
                ],
                [
                    'q' => 'Metode pembayaran apa yang tersedia?',
                    'a' => 'Kami menerima transfer bank, e-wallet (GoPay, OVO, Dana), dan kartu kredit.',
                ],
            ],
            'Pengiriman & Retur' => [
                [
                    'q' => 'Berapa lama estimasi pengiriman?',
                    'a' => 'Pengiriman reguler memakan waktu 2–4 hari kerja. Kami juga menyediakan opsi pengiriman lebih cepat untuk wilayah Jabodetabek.',
                ],
                [
                    'q' => 'Bisakah saya mengembalikan produk?',
                    'a' => 'Kami menerima retur jika produk rusak saat diterima. Pastikan melampirkan video unboxing sebagai syarat klaim.',
                ],
            ],
            'Tentang Aroma' => [
                [
                    'q' => 'Apakah parfum Evomi aman untuk kulit?',
                    'a' => 'Ya, setiap racikan Evomi menggunakan bahan yang dipilih dengan standar keamanan untuk pemakaian sehari-hari.',
                ],
                [
                    'q' => 'Bagaimana cara memilih aroma yang tepat?',
                    'a' => 'Coba Kuis Persona Evomi untuk rekomendasi berdasarkan kepribadian, atau jelajahi empat karakter di halaman Belanja.',
                ],
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function quizQuestions(): array
    {
        return [
            [
                'id' => 1,
                'text' => 'Di akhir pekan, kamu paling suka...',
                'options' => [
                    ['id' => 11, 'text' => 'Tenang di rumah dengan musik lembut', 'peaceful_calm' => 2, 'purpose_prestige' => 0, 'sweet_shy' => 1, 'rebel_brave' => 0],
                    ['id' => 12, 'text' => 'Rencana produktif dan checklist jelas', 'peaceful_calm' => 0, 'purpose_prestige' => 2, 'sweet_shy' => 0, 'rebel_brave' => 1],
                    ['id' => 13, 'text' => 'Ngopi santai bareng orang terdekat', 'peaceful_calm' => 1, 'purpose_prestige' => 0, 'sweet_shy' => 2, 'rebel_brave' => 0],
                    ['id' => 14, 'text' => 'Coba tempat baru yang belum pernah dikunjungi', 'peaceful_calm' => 0, 'purpose_prestige' => 1, 'sweet_shy' => 0, 'rebel_brave' => 2],
                ],
            ],
            [
                'id' => 2,
                'text' => 'Kalau memilih outfit, prioritasmu adalah...',
                'options' => [
                    ['id' => 21, 'text' => 'Nyaman dan tidak mencolok', 'peaceful_calm' => 2, 'purpose_prestige' => 0, 'sweet_shy' => 1, 'rebel_brave' => 0],
                    ['id' => 22, 'text' => 'Rapi, elegan, dan berkelas', 'peaceful_calm' => 0, 'purpose_prestige' => 2, 'sweet_shy' => 0, 'rebel_brave' => 0],
                    ['id' => 23, 'text' => 'Lembut, manis, dan hangat', 'peaceful_calm' => 0, 'purpose_prestige' => 0, 'sweet_shy' => 2, 'rebel_brave' => 1],
                    ['id' => 24, 'text' => 'Berani beda dan penuh ekspresi', 'peaceful_calm' => 0, 'purpose_prestige' => 1, 'sweet_shy' => 0, 'rebel_brave' => 2],
                ],
            ],
            [
                'id' => 3,
                'text' => 'Teman-teman biasanya menggambarkanmu sebagai...',
                'options' => [
                    ['id' => 31, 'text' => 'Pendengar yang menenangkan', 'peaceful_calm' => 2, 'purpose_prestige' => 0, 'sweet_shy' => 1, 'rebel_brave' => 0],
                    ['id' => 32, 'text' => 'Orang yang fokus dan berorientasi tujuan', 'peaceful_calm' => 0, 'purpose_prestige' => 2, 'sweet_shy' => 0, 'rebel_brave' => 1],
                    ['id' => 33, 'text' => 'Hangat dan mudah didekati', 'peaceful_calm' => 1, 'purpose_prestige' => 0, 'sweet_shy' => 2, 'rebel_brave' => 0],
                    ['id' => 34, 'text' => 'Energik dan tidak takut tantangan', 'peaceful_calm' => 0, 'purpose_prestige' => 1, 'sweet_shy' => 0, 'rebel_brave' => 2],
                ],
            ],
            [
                'id' => 4,
                'text' => 'Aroma yang paling kamu inginkan saat ini...',
                'options' => [
                    ['id' => 41, 'text' => 'Segar, lembut, menenangkan', 'peaceful_calm' => 2, 'purpose_prestige' => 0, 'sweet_shy' => 1, 'rebel_brave' => 0],
                    ['id' => 42, 'text' => 'Berkelas, woody, dan confident', 'peaceful_calm' => 0, 'purpose_prestige' => 2, 'sweet_shy' => 0, 'rebel_brave' => 1],
                    ['id' => 43, 'text' => 'Manis, creamy, dan hangat', 'peaceful_calm' => 1, 'purpose_prestige' => 0, 'sweet_shy' => 2, 'rebel_brave' => 0],
                    ['id' => 44, 'text' => 'Bold, spicy, dan berkarakter', 'peaceful_calm' => 0, 'purpose_prestige' => 1, 'sweet_shy' => 0, 'rebel_brave' => 2],
                ],
            ],
        ];
    }

    /**
     * @return array<string, array<string, string>>
     */
    public static function quizResults(): array
    {
        return [
            'purpose_prestige' => [
                'title' => 'Kamu adalah, Purpose Prestige',
                'description' => 'Menghadirkan aroma yang merefleksikan ketenangan, kepercayaan diri, dan kejelasan tujuan.',
                'color' => '#1172BA',
                'product_id' => '1',
            ],
            'peaceful_calm' => [
                'title' => 'Kamu adalah, Peaceful Calm',
                'description' => 'Menghadirkan aroma yang menenangkan, seimbang, dan menyatu dengan diri.',
                'color' => '#5EA14A',
                'product_id' => '2',
            ],
            'rebel_brave' => [
                'title' => 'Kamu adalah, Rebel Brave',
                'description' => 'Merepresentasikan keberanian, energi, dan semangat untuk mengekspresikan diri.',
                'color' => '#E33D35',
                'product_id' => '3',
            ],
            'sweet_shy' => [
                'title' => 'Kamu adalah, Sweet Shy',
                'description' => 'Menghadirkan aroma lembut yang merefleksikan sisi manis, hangat, dan penuh empati.',
                'color' => '#DD74A5',
                'product_id' => '4',
            ],
        ];
    }
}
