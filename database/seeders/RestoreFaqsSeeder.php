<?php

namespace Database\Seeders;

use App\Models\Faq;
use Illuminate\Database\Seeder;

class RestoreFaqsSeeder extends Seeder
{
    public function run(): void
    {
        $faqs = [
            [
                'Pesanan & Pembayaran',
                'Orders & Payment',
                'Bagaimana cara melacak pesanan saya?',
                'How can I track my order?',
                "Setelah pesanan diproses, Anda akan menerima email konfirmasi dengan nomor pelacakan yang dapat dipantau di halaman 'Status Pesanan'.",
                "After your order is processed, you will receive a confirmation email with a tracking number that you can monitor on the 'Order Status' page.",
                1,
            ],
            [
                'Pesanan & Pembayaran',
                'Orders & Payment',
                'Metode pembayaran apa yang tersedia?',
                'What payment methods are available?',
                'Kami menerima berbagai metode pembayaran termasuk transfer bank, e-wallet (GoPay, OVO, Dana), dan kartu kredit.',
                'We accept various payment methods including bank transfer, e-wallets (GoPay, OVO, Dana), and credit cards.',
                2,
            ],
            [
                'Pengiriman & Retur',
                'Shipping & Returns',
                'Berapa lama estimasi pengiriman?',
                'How long does shipping take?',
                'Pengiriman reguler memakan waktu 2-4 hari kerja. Kami juga menyediakan opsi pengiriman instan untuk wilayah Jabodetabek.',
                'Regular shipping takes 2–4 business days. We also offer instant shipping for the Greater Jakarta area.',
                3,
            ],
            [
                'Pengiriman & Retur',
                'Shipping & Returns',
                'Bisakah saya mengembalikan produk?',
                'Can I return a product?',
                'Kami menerima retur jika produk rusak saat diterima. Pastikan untuk melampirkan video unboxing sebagai syarat klaim.',
                'We accept returns if the product is damaged upon arrival. Please attach an unboxing video as a claim requirement.',
                4,
            ],
            [
                'Tentang Aroma',
                'About the Scents',
                'Apakah parfum Evomi aman untuk kulit?',
                'Is Evomi perfume safe for the skin?',
                'Ya, setiap racikan parfum Evomi menggunakan bahan-bahan yang telah tersertifikasi aman untuk kulit.',
                'Yes, every Evomi fragrance blend uses ingredients certified as skin-safe.',
                5,
            ],
            [
                'Tentang Aroma',
                'About the Scents',
                'Bagaimana cara memilih aroma yang tepat?',
                'How do I choose the right scent?',
                'Anda dapat mencoba Kuis Persona kami di halaman utama untuk mendapatkan rekomendasi aroma berdasarkan kepribadian Anda.',
                'You can try our Persona Quiz on the home page to get scent recommendations based on your personality.',
                6,
            ],
        ];

        foreach ($faqs as [$category, $categoryEn, $question, $questionEn, $answer, $answerEn, $sort]) {
            Faq::updateOrCreate(
                ['question' => $question],
                [
                    'category' => $category,
                    'category_en' => $categoryEn,
                    'question_en' => $questionEn,
                    'answer' => $answer,
                    'answer_en' => $answerEn,
                    'sort_order' => $sort,
                    'is_active' => true,
                ]
            );
        }

        // Hapus placeholder yang dibuat dari dashboard
        Faq::where('question', 'Pertanyaan baru')->delete();
    }
}
