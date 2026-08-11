<?php

namespace Database\Seeders;

use App\Models\Faq;
use App\Models\SiteContent;
use App\Support\BerandaCmsDefaults;
use Illuminate\Database\Seeder;

class CmsSeeder extends Seeder
{
    public function run(): void
    {
        $contents = array_merge(
            // Beranda defaults = current storefront UI catalog (insert-missing only)
            BerandaCmsDefaults::seederRows(),
            [
                // ---- KONTAK ----
                ['kontak', 'header', 'title', 'string', 'Hubungi Kami'],
                ['kontak', 'header', 'subtitle', 'text', 'Punya pertanyaan atau ingin berkolaborasi? Tim Evomi siap mendengarkan Anda.'],
                ['kontak', 'info', 'email_label', 'string', 'Email'],
                ['kontak', 'info', 'email_value', 'string', 'hello@evomi.id'],
                ['kontak', 'info', 'phone_label', 'string', 'WhatsApp'],
                ['kontak', 'info', 'phone_value', 'string', '+62 812-3456-7890'],
                ['kontak', 'info', 'address_label', 'string', 'Kantor Pusat'],
                ['kontak', 'info', 'address_value', 'string', 'Jakarta, Indonesia'],

                // ---- NAVBAR ----
                ['navbar', 'site', 'browser_title', 'string', 'Evomi Website'],
                ['navbar', 'site', 'dashboard_browser_title', 'string', 'Evomi Dashboard'],
                ['navbar', 'site', 'favicon', 'image', '/favicon.png'],
                ['navbar', 'menu', 'beranda', 'string', 'Beranda'],
                ['navbar', 'menu', 'tentang', 'string', 'Tentang'],
                ['navbar', 'menu', 'belanja', 'string', 'Belanja'],
                ['navbar', 'menu', 'artikel', 'string', 'Artikel'],
                ['navbar', 'menu', 'kuis', 'string', 'Temukan Aromamu'],
                ['navbar', 'menu', 'login', 'string', 'Login'],
                ['navbar', 'menu', 'register', 'string', 'Daftar'],
                ['navbar', 'menu', 'logout', 'string', 'Logout'],

                // ---- FOOTER ----
                ['footer', 'bulletin', 'title', 'string', 'Buletin Evomi'],
                ['footer', 'bulletin', 'desc', 'text', 'Daftar untuk menerima koleksi terbaru, penawaran eksklusif, dan cerita tentang setiap karakter aroma.'],
                ['footer', 'bulletin', 'cta', 'string', 'Daftar'],
                ['footer', 'menu', 'heading', 'string', 'Menu'],
                ['footer', 'menu', 'beranda', 'string', 'Beranda'],
                ['footer', 'menu', 'belanja', 'string', 'Belanja'],
                ['footer', 'menu', 'artikel', 'string', 'Artikel'],
                ['footer', 'menu', 'kuis', 'string', 'Temukan Aromamu'],
                ['footer', 'help', 'heading', 'string', 'Bantuan'],
                ['footer', 'help', 'faq', 'string', 'FAQ'],
                ['footer', 'help', 'pengiriman', 'string', 'Status Pengiriman'],
                ['footer', 'help', 'kontak', 'string', 'Kontak'],
                ['footer', 'social', 'heading', 'string', 'Ikuti Kami'],
                ['footer', 'social', 'instagram_url', 'string', 'https://instagram.com/evomi.id'],
                ['footer', 'social', 'twitter_url', 'string', 'https://twitter.com/evomi'],
                ['footer', 'social', 'facebook_url', 'string', 'https://facebook.com/evomi'],
                ['footer', 'legal', 'copyright', 'string', '© Evomi. All rights reserved.'],
            ],
        );

        foreach ($contents as [$page, $section, $key, $type, $value]) {
            SiteContent::firstOrCreate(
                ['page' => $page, 'section' => $section, 'key' => $key, 'locale' => 'id'],
                ['type' => $type, 'value' => $value]
            );
        }

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
    }
}
