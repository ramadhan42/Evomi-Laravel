<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\QuizOption;
use App\Models\QuizPersonalityResult;
use App\Models\QuizQuestion;
use App\Models\SiteContent;
use Illuminate\Database\Seeder;

class LocaleSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedSiteContentEn();
        $this->seedUiAndAdmin();
        $this->seedProductEn();
        $this->seedQuizEn();
    }

    private function put(string $page, string $section, string $key, string $type, ?string $value, string $locale = 'en'): void
    {
        SiteContent::updateOrCreate(
            ['page' => $page, 'section' => $section, 'key' => $key, 'locale' => $locale],
            ['type' => $type, 'value' => $value]
        );
    }

    private function seedSiteContentEn(): void
    {
        // Navbar
        $this->put('navbar', 'site', 'browser_title', 'string', 'Evomi Website', 'id');
        $this->put('navbar', 'site', 'dashboard_browser_title', 'string', 'Evomi Dashboard', 'id');
        $this->put('navbar', 'site', 'favicon', 'image', '/favicon.png', 'id');
        $this->put('navbar', 'site', 'browser_title', 'string', 'Evomi Website');
        $this->put('navbar', 'site', 'dashboard_browser_title', 'string', 'Evomi Dashboard');
        $this->put('navbar', 'site', 'favicon', 'image', '/favicon.png');
        foreach ([
            'beranda' => 'Home',
            'tentang' => 'About',
            'belanja' => 'Shop',
            'artikel' => 'Articles',
            'kuis' => 'Find Your Scent',
            'login' => 'Login',
            'register' => 'Sign Up',
            'logout' => 'Logout',
        ] as $key => $value) {
            $this->put('navbar', 'menu', $key, 'string', $value);
        }

        // Footer
        $this->put('footer', 'bulletin', 'title', 'string', 'Evomi Bulletin');
        $this->put('footer', 'bulletin', 'desc', 'text', 'Subscribe to receive the latest collections, exclusive offers, and stories behind every scent character.');
        $this->put('footer', 'bulletin', 'cta', 'string', 'Subscribe');
        $this->put('footer', 'menu', 'heading', 'string', 'Menu');
        $this->put('footer', 'menu', 'beranda', 'string', 'Home');
        $this->put('footer', 'menu', 'belanja', 'string', 'Shop');
        $this->put('footer', 'menu', 'artikel', 'string', 'Articles');
        $this->put('footer', 'menu', 'kuis', 'string', 'Find Your Scent');
        $this->put('footer', 'help', 'heading', 'string', 'Help');
        $this->put('footer', 'help', 'faq', 'string', 'FAQ');
        $this->put('footer', 'help', 'pengiriman', 'string', 'Shipping Status');
        $this->put('footer', 'help', 'kontak', 'string', 'Contact');
        $this->put('footer', 'social', 'heading', 'string', 'Follow Us');
        $this->put('footer', 'legal', 'copyright', 'string', '© Evomi. All rights reserved.');

        // Kontak
        $this->put('kontak', 'header', 'title', 'string', 'Contact Us');
        $this->put('kontak', 'header', 'subtitle', 'text', 'Have a question or want to collaborate? The Evomi team is ready to listen.');
        $this->put('kontak', 'info', 'email_label', 'string', 'Email');
        $this->put('kontak', 'info', 'phone_label', 'string', 'WhatsApp');
        $this->put('kontak', 'info', 'address_label', 'string', 'Head Office');
        $this->put('kontak', 'info', 'address_value', 'string', 'Jakarta, Indonesia');

        // Beranda hero headlines
        $this->put('beranda', 'hero', 'headline_1', 'string', 'Discover');
        $this->put('beranda', 'hero', 'headline_2', 'string', 'your');
        $this->put('beranda', 'hero', 'headline_3', 'string', 'scent');
        $this->put('beranda', 'hero', 'headline_4', 'string', 'at Evomi');
        $this->put('beranda', 'hero', 'badge_left', 'string', 'Eau de Parfum');
        $this->put('beranda', 'hero', 'badge_right', 'string', 'Recycle Bottle Cap');
        $this->put('beranda', 'hero', 'marquee_text', 'string', 'Every Version of Me');
        $this->put('beranda', 'hero', 'product1_badge_label', 'string', 'Purpose Prestige');
        $this->put('beranda', 'hero', 'product2_badge_label', 'string', 'Rebel Brave');
        $this->put('beranda', 'hero', 'product3_badge_label', 'string', 'Peaceful Calm');
        $this->put('beranda', 'hero', 'product4_badge_label', 'string', 'Sweet Shy');

        // Hero typography (id + en — style, same defaults)
        foreach (['id', 'en'] as $loc) {
            foreach ([
                ['headline_1_font_family', 'nohemi'],
                ['headline_1_font_weight', '600'],
                ['headline_1_font_style', 'normal'],
                ['headline_2_font_family', 'nohemi'],
                ['headline_2_font_weight', '600'],
                ['headline_2_font_style', 'normal'],
                ['headline_3_font_family', 'nohemi'],
                ['headline_3_font_weight', '600'],
                ['headline_3_font_style', 'normal'],
                ['headline_4_font_family', 'nohemi'],
                ['headline_4_font_weight', '600'],
                ['headline_4_font_style', 'normal'],
                ['badge_left_font_family', 'nohemi'],
                ['badge_left_font_weight', '700'],
                ['badge_left_font_style', 'normal'],
                ['badge_right_font_family', 'nohemi'],
                ['badge_right_font_weight', '700'],
                ['badge_right_font_style', 'normal'],
                ['marquee_font_family', 'nohemi'],
                ['marquee_font_weight', '500'],
                ['marquee_font_style', 'normal'],
            ] as [$key, $value]) {
                $this->put('beranda', 'hero', $key, 'string', $value, $loc);
            }
        }

        // Hero wave SVGs + positions (id + en — style, same defaults)
        foreach (['id', 'en'] as $loc) {
            $this->put('beranda', 'hero', 'wave_left_icon', 'image', '/src/images/section 1/sayap-kiri.svg', $loc);
            $this->put('beranda', 'hero', 'wave_right_icon', 'image', '/src/images/section 1/sayap-kanan.svg', $loc);
            $this->put('beranda', 'hero', 'wave_left_left_mobile', 'string', '-24%', $loc);
            $this->put('beranda', 'hero', 'wave_left_left_desktop', 'string', '-11%', $loc);
            $this->put('beranda', 'hero', 'wave_left_top_mobile', 'string', '-44%', $loc);
            $this->put('beranda', 'hero', 'wave_left_top_desktop', 'string', '-35%', $loc);
            $this->put('beranda', 'hero', 'wave_right_right_mobile', 'string', '-17%', $loc);
            $this->put('beranda', 'hero', 'wave_right_right_desktop', 'string', '-11%', $loc);
            $this->put('beranda', 'hero', 'wave_right_top_mobile', 'string', '-74%', $loc);
            $this->put('beranda', 'hero', 'wave_right_top_desktop', 'string', '-50%', $loc);
        }

        $this->put('beranda', 'second', 'headline_1', 'string', 'Meet our');
        $this->put('beranda', 'second', 'headline_2', 'string', 'characters ');
        $this->put('beranda', 'second', 'headline_3', 'string', 'today!');
        $this->put('beranda', 'second', 'cta_label', 'string', 'See All Characters');
        $this->put('beranda', 'fifth', 'title_1', 'string', 'Made by ');
        $this->put('beranda', 'fifth', 'title_2', 'string', 'Evomi');
        $this->put('beranda', 'fifth', 'subtitle', 'text', 'Four scent characters that represent different sides of you.');
        $this->put('beranda', 'fifth', 'cta_label', 'string', 'View Collection');
        $this->put('beranda', 'fifth', 'card1_badge', 'string', 'Optimistic');
        $this->put('beranda', 'fifth', 'card1_desc', 'text', 'A scent that reflects calm and clarity of purpose.');
        $this->put('beranda', 'fifth', 'card2_badge', 'string', 'Peaceful');
        $this->put('beranda', 'fifth', 'card2_desc', 'text', 'A calming scent that blends with who you are.');
        $this->put('beranda', 'fifth', 'card3_badge', 'string', 'Brave');
        $this->put('beranda', 'fifth', 'card3_desc', 'text', 'Courage and spirit to express yourself.');
        $this->put('beranda', 'fifth', 'card4_badge', 'string', 'Sweet');
        $this->put('beranda', 'fifth', 'card4_desc', 'text', 'A gentle scent that feels close to you.');
        $this->put('beranda', 'seventh', 'en_l1', 'string', 'Find your');
        $this->put('beranda', 'seventh', 'en_l2', 'string', 'scent by');
        $this->put('beranda', 'seventh', 'en_l3', 'string', 'playing the ');
        $this->put('beranda', 'seventh', 'en_l4', 'string', 'quiz');
        $this->put('beranda', 'seventh', 'cta_label', 'string', 'Find Your Scent');
        $this->put('beranda', 'seventh', 'label1_text', 'string', 'Prestige');
        $this->put('beranda', 'seventh', 'label1_title', 'string', 'Purpose Prestige');
        $this->put('beranda', 'seventh', 'label2_text', 'string', 'Calm');
        $this->put('beranda', 'seventh', 'label2_title', 'string', 'Peaceful Calm');
        $this->put('beranda', 'seventh', 'label3_text', 'string', 'Rebel');
        $this->put('beranda', 'seventh', 'label3_title', 'string', 'Rebel Brave');
        $this->put('beranda', 'seventh', 'label4_text', 'string', 'Sweet');
        $this->put('beranda', 'seventh', 'label4_title', 'string', 'Sweet Shy');

        // Section 7 label layout/style (shared id+en)
        foreach ([
            ['label1_color', '#5CB2ED'],
            ['label1_fs_mobile', '9px'],
            ['label1_fs_desktop', '16px'],
            ['label1_left_mobile', '61%'],
            ['label1_left_desktop', '61%'],
            ['label1_right_mobile', ''],
            ['label1_right_desktop', ''],
            ['label1_top_mobile', '33%'],
            ['label1_top_desktop', '33%'],
            ['label1_bottom_mobile', ''],
            ['label1_bottom_desktop', ''],
            ['label2_color', '#5EA14A'],
            ['label2_fs_mobile', '9px'],
            ['label2_fs_desktop', '16px'],
            ['label2_left_mobile', '82%'],
            ['label2_left_desktop', '82%'],
            ['label2_right_mobile', ''],
            ['label2_right_desktop', ''],
            ['label2_top_mobile', '15%'],
            ['label2_top_desktop', '15%'],
            ['label2_bottom_mobile', ''],
            ['label2_bottom_desktop', ''],
            ['label3_color', '#E33D35'],
            ['label3_fs_mobile', '9px'],
            ['label3_fs_desktop', '16px'],
            ['label3_left_mobile', '26%'],
            ['label3_left_desktop', '26%'],
            ['label3_right_mobile', ''],
            ['label3_right_desktop', ''],
            ['label3_top_mobile', '15%'],
            ['label3_top_desktop', '15%'],
            ['label3_bottom_mobile', ''],
            ['label3_bottom_desktop', ''],
            ['label4_color', '#DD74A5'],
            ['label4_fs_mobile', '9px'],
            ['label4_fs_desktop', '16px'],
            ['label4_left_mobile', '47.5%'],
            ['label4_left_desktop', '47.5%'],
            ['label4_right_mobile', ''],
            ['label4_right_desktop', ''],
            ['label4_top_mobile', '-3%'],
            ['label4_top_desktop', '-3%'],
            ['label4_bottom_mobile', ''],
            ['label4_bottom_desktop', ''],
        ] as [$key, $value]) {
            $this->put('beranda', 'seventh', $key, 'string', $value, 'id');
            $this->put('beranda', 'seventh', $key, 'string', $value, 'en');
        }

        $this->put('beranda', 'third', 'card1_desc', 'text', 'Every scent is designed to represent different versions of self, emotion, and character — so perfume becomes personal expression, not just fragrance.');
        $this->put('beranda', 'third', 'card2_desc', 'text', 'We care for the environment by recycling plastic bottle caps into part of the product identity — a small step toward less waste and more sustainability.');
        $this->put('beranda', 'third', 'card3_desc', 'text', 'Packed with a playful, expressive visual language that connects with younger generations for a more personal and joyful perfume experience.');
    }

    private function seedUiAndAdmin(): void
    {
        $uiId = [
            ['common', 'loading', 'string', 'Memuat...'],
            ['common', 'save', 'string', 'Simpan'],
            ['common', 'cancel', 'string', 'Batal'],
            ['common', 'confirm', 'string', 'Konfirmasi'],
            ['common', 'success', 'string', 'Berhasil'],
            ['common', 'error', 'string', 'Gagal'],
            ['common', 'back', 'string', 'Kembali'],
            ['nav', 'my_account', 'string', 'Akun Saya'],
            ['nav', 'cart', 'string', 'Keranjang'],
            ['nav', 'wishlist', 'string', 'Wishlist'],
            ['nav', 'history', 'string', 'Riwayat'],
            ['nav', 'chat', 'string', 'Chat'],
            ['nav', 'settings', 'string', 'Pengaturan'],
            ['auth', 'login_title', 'string', 'Masuk'],
            ['auth', 'register_title', 'string', 'Daftar'],
            ['auth', 'email', 'string', 'Email'],
            ['auth', 'password', 'string', 'Password'],
            ['auth', 'name', 'string', 'Nama'],
            ['auth', 'phone', 'string', 'Telepon'],
            ['belanja', 'title', 'string', 'Koleksi Aroma Evomi'],
            ['belanja', 'see_detail', 'string', 'Lihat Detail'],
            ['belanja', 'add_cart', 'string', 'Tambah Keranjang'],
            ['faq', 'title', 'string', 'Pertanyaan yang Sering Diajukan'],
            ['faq', 'subtitle', 'string', 'Temukan jawaban seputar pesanan, pengiriman, dan aroma Evomi.'],
            ['kontak', 'name', 'string', 'Nama'],
            ['kontak', 'email', 'string', 'Email'],
            ['kontak', 'subject', 'string', 'Subjek'],
            ['kontak', 'message', 'string', 'Pesan'],
            ['kontak', 'send', 'string', 'Kirim Pesan'],
            ['profile', 'settings', 'string', 'Pengaturan Profil'],
            ['profile', 'cart', 'string', 'Keranjang Belanja'],
            ['profile', 'wishlist', 'string', 'Wishlist'],
            ['profile', 'history', 'string', 'Riwayat Belanja'],
            ['profile', 'messages', 'string', 'Pesan Anda'],
            ['profile', 'menu_title', 'string', 'Menu Akun'],
            ['profile', 'menu_subtitle', 'string', 'Kelola aktivitas & akun Anda'],
            ['profile', 'cart_loading', 'string', 'Memuat keranjang...'],
            ['profile', 'messages_loading', 'string', 'Memuat pesan...'],
            ['profile', 'history_loading', 'string', 'Memuat riwayat belanja...'],
            ['profile', 'wishlist_loading', 'string', 'Memuat wishlist...'],
            ['kuis', 'start', 'string', 'Mulai Kuis'],
            ['kuis', 'next', 'string', 'Lanjut'],
            ['kuis', 'submit', 'string', 'Kirim Jawaban'],
            ['checkout', 'title', 'string', 'Checkout'],
            ['checkout', 'pay', 'string', 'Bayar Sekarang'],
        ];

        $uiEn = [
            ['common', 'loading', 'string', 'Loading...'],
            ['common', 'save', 'string', 'Save'],
            ['common', 'cancel', 'string', 'Cancel'],
            ['common', 'confirm', 'string', 'Confirm'],
            ['common', 'success', 'string', 'Success'],
            ['common', 'error', 'string', 'Error'],
            ['common', 'back', 'string', 'Back'],
            ['nav', 'my_account', 'string', 'My Account'],
            ['nav', 'cart', 'string', 'Cart'],
            ['nav', 'wishlist', 'string', 'Wishlist'],
            ['nav', 'history', 'string', 'Order History'],
            ['nav', 'chat', 'string', 'Chat'],
            ['nav', 'settings', 'string', 'Settings'],
            ['auth', 'login_title', 'string', 'Login'],
            ['auth', 'register_title', 'string', 'Sign Up'],
            ['auth', 'email', 'string', 'Email'],
            ['auth', 'password', 'string', 'Password'],
            ['auth', 'name', 'string', 'Name'],
            ['auth', 'phone', 'string', 'Phone'],
            ['belanja', 'title', 'string', 'Evomi Scent Collection'],
            ['belanja', 'see_detail', 'string', 'View Details'],
            ['belanja', 'add_cart', 'string', 'Add to Cart'],
            ['faq', 'title', 'string', 'Frequently Asked Questions'],
            ['faq', 'subtitle', 'string', 'Find answers about orders, shipping, and Evomi scents.'],
            ['kontak', 'name', 'string', 'Name'],
            ['kontak', 'email', 'string', 'Email'],
            ['kontak', 'subject', 'string', 'Subject'],
            ['kontak', 'message', 'string', 'Message'],
            ['kontak', 'send', 'string', 'Send Message'],
            ['profile', 'settings', 'string', 'Profile Settings'],
            ['profile', 'cart', 'string', 'Shopping Cart'],
            ['profile', 'wishlist', 'string', 'Wishlist'],
            ['profile', 'history', 'string', 'Order History'],
            ['profile', 'messages', 'string', 'Your Messages'],
            ['profile', 'menu_title', 'string', 'Account Menu'],
            ['profile', 'menu_subtitle', 'string', 'Manage your activity & account'],
            ['profile', 'cart_loading', 'string', 'Loading cart...'],
            ['profile', 'messages_loading', 'string', 'Loading messages...'],
            ['profile', 'history_loading', 'string', 'Loading order history...'],
            ['profile', 'wishlist_loading', 'string', 'Loading wishlist...'],
            ['kuis', 'start', 'string', 'Start Quiz'],
            ['kuis', 'next', 'string', 'Next'],
            ['kuis', 'submit', 'string', 'Submit Answers'],
            ['checkout', 'title', 'string', 'Checkout'],
            ['checkout', 'pay', 'string', 'Pay Now'],
        ];

        foreach ($uiId as [$section, $key, $type, $value]) {
            $this->put('ui', $section, $key, $type, $value, 'id');
        }
        foreach ($uiEn as [$section, $key, $type, $value]) {
            $this->put('ui', $section, $key, $type, $value, 'en');
        }

        $adminId = [
            // Sidebar
            ['sidebar', 'dashboard', 'string', 'Dashboard'],
            ['sidebar', 'cms', 'string', 'CMS'],
            ['sidebar', 'products', 'string', 'Produk'],
            ['sidebar', 'kurirs', 'string', 'Kurir'],
            ['sidebar', 'quiz', 'string', 'Kuis'],
            ['sidebar', 'orders', 'string', 'Pesanan'],
            ['sidebar', 'trackings', 'string', 'Pelacakan'],
            ['sidebar', 'messages', 'string', 'Pesan'],
            ['sidebar', 'cart', 'string', 'Keranjang'],
            ['sidebar', 'wishlist', 'string', 'Wishlist'],
            ['sidebar', 'users', 'string', 'Semua User'],
            ['sidebar', 'subscribers', 'string', 'Subscriber'],
            ['sidebar', 'profile', 'string', 'Profil Admin'],
            ['sidebar', 'logout', 'string', 'Keluar'],
            // Common table / actions
            ['common', 'save', 'string', 'Simpan'],
            ['common', 'save_changes', 'string', 'Simpan Perubahan'],
            ['common', 'saving', 'string', 'Menyimpan...'],
            ['common', 'cancel', 'string', 'Batal'],
            ['common', 'edit', 'string', 'Edit'],
            ['common', 'delete', 'string', 'Hapus'],
            ['common', 'add', 'string', 'Tambah'],
            ['common', 'search', 'string', 'Cari'],
            ['common', 'actions', 'string', 'Aksi'],
            ['common', 'loading', 'string', 'Memuat...'],
            ['common', 'refresh', 'string', 'Refresh Data'],
            ['common', 'yes_delete', 'string', 'Ya, Hapus'],
            ['common', 'confirm_delete', 'string', 'Hapus data?'],
            ['common', 'empty', 'string', 'Tidak ada data.'],
            ['common', 'status', 'string', 'Status'],
            ['common', 'user', 'string', 'Pengguna'],
            ['common', 'product', 'string', 'Produk'],
            ['common', 'price', 'string', 'Harga'],
            ['common', 'quantity', 'string', 'Jumlah'],
            ['common', 'date', 'string', 'Tanggal'],
            ['common', 'email', 'string', 'Email'],
            ['common', 'name', 'string', 'Nama'],
            ['common', 'id', 'string', 'ID'],
            ['common', 'reply', 'string', 'Balas'],
            ['common', 'close', 'string', 'Tutup'],
            ['common', 'back', 'string', 'Kembali'],
            // Auth gate
            ['auth', 'verifying', 'string', 'Memverifikasi akses...'],
            ['auth', 'denied_title', 'string', 'Akses Dilarang'],
            ['auth', 'denied_message', 'string', 'Akses ditolak! Anda tidak memiliki izin sebagai Administrator.'],
            ['auth', 'back_login', 'string', 'Kembali ke Login'],
            // Products
            ['products', 'title', 'string', 'Manajemen Produk'],
            ['products', 'subtitle', 'string', 'Kelola inventaris, harga, notes, dan ketersediaan parfum Anda.'],
            ['products', 'add', 'string', 'Tambah Produk'],
            ['products', 'search_ph', 'string', 'Cari nama parfum atau tipe kepribadian...'],
            ['products', 'col_product', 'string', 'Produk'],
            ['products', 'col_type', 'string', 'Tipe / Ukuran'],
            ['products', 'col_price', 'string', 'Harga'],
            ['products', 'col_stock', 'string', 'Stok'],
            ['products', 'col_status', 'string', 'Status'],
            ['products', 'empty', 'string', 'Tidak ada produk yang ditemukan.'],
            ['products', 'modal_add', 'string', 'Tambah Parfum Baru'],
            ['products', 'modal_edit', 'string', 'Edit Parfum'],
            ['products', 'save_product', 'string', 'Simpan Produk'],
            ['products', 'title_id', 'string', 'Judul (ID)'],
            ['products', 'title_en', 'string', 'Judul (EN)'],
            ['products', 'desc_id', 'string', 'Deskripsi (ID)'],
            ['products', 'desc_en', 'string', 'Deskripsi (EN)'],
            // Orders
            ['orders', 'title', 'string', 'Pesanan'],
            ['orders', 'subtitle', 'string', 'Kelola status dan detail pesanan pelanggan.'],
            ['orders', 'search_ph', 'string', 'Cari ID pesanan atau nama user...'],
            ['orders', 'col_order', 'string', 'ID Pesanan'],
            ['orders', 'col_customer', 'string', 'Pelanggan'],
            ['orders', 'col_items', 'string', 'Item'],
            ['orders', 'col_total', 'string', 'Total'],
            ['orders', 'col_status', 'string', 'Status'],
            ['orders', 'empty', 'string', 'Tidak ada pesanan ditemukan.'],
            ['orders', 'change_status', 'string', 'Ubah Status Pesanan'],
            // Trackings
            ['trackings', 'title', 'string', 'Pelacakan Pesanan'],
            ['trackings', 'subtitle', 'string', 'Pantau dan kelola status pengiriman pesanan.'],
            ['trackings', 'search_ph', 'string', 'Cari ID pesanan, no resi, atau nama penerima...'],
            ['trackings', 'col_order', 'string', 'ID Pesanan'],
            ['trackings', 'col_recipient', 'string', 'Penerima'],
            ['trackings', 'col_courier', 'string', 'Kurir'],
            ['trackings', 'col_resi', 'string', 'No Resi'],
            ['trackings', 'col_status', 'string', 'Status'],
            ['trackings', 'no_tracking_number', 'string', 'Belum ada no resi'],
            ['trackings', 'empty', 'string', 'Tidak ada data tracking yang ditemukan.'],
            ['trackings', 'add_log', 'string', 'Tambah Log'],
            // Messages
            ['messages', 'title', 'string', 'Pesan Masuk'],
            ['messages', 'subtitle', 'string', 'Balas pesan dan pertanyaan pelanggan.'],
            ['messages', 'search_ph', 'string', 'Cari nama, email, subjek...'],
            ['messages', 'col_customer', 'string', 'Pelanggan'],
            ['messages', 'col_subject', 'string', 'Subjek'],
            ['messages', 'col_preview', 'string', 'Pesan'],
            ['messages', 'col_date', 'string', 'Tanggal'],
            ['messages', 'empty', 'string', 'Tidak ada pesan ditemukan'],
            // Cart / Wishlist
            ['cart', 'title', 'string', 'Keranjang'],
            ['cart', 'subtitle', 'string', 'Lihat item keranjang semua pengguna.'],
            ['cart', 'search_ph', 'string', 'Cari user atau nama produk...'],
            ['cart', 'empty', 'string', 'Tidak ada item yang sesuai dengan pencarian Anda.'],
            ['wishlist', 'title', 'string', 'Wishlist'],
            ['wishlist', 'subtitle', 'string', 'Lihat wishlist semua pengguna.'],
            ['wishlist', 'search_ph', 'string', 'Cari user atau nama produk...'],
            ['wishlist', 'empty', 'string', 'Tidak ada data ditemukan.'],
            // Users / Subscribers
            ['users', 'title', 'string', 'Semua Pengguna'],
            ['users', 'subtitle', 'string', 'Kelola akun pelanggan dan admin.'],
            ['users', 'search_ph', 'string', 'Cari nama atau email...'],
            ['users', 'col_user', 'string', 'Pengguna'],
            ['users', 'col_address', 'string', 'Alamat / Info'],
            ['users', 'col_joined', 'string', 'Bergabung'],
            ['users', 'empty', 'string', 'Tidak ada pengguna yang cocok dengan pencarian.'],
            ['users', 'confirm_delete', 'string', 'Hapus Pengguna?'],
            ['subscribers', 'title', 'string', 'Subscriber'],
            ['subscribers', 'subtitle', 'string', 'Daftar email bulletin Evomi.'],
            ['subscribers', 'col_email', 'string', 'Email'],
            ['subscribers', 'col_date', 'string', 'Tanggal'],
            // Quiz
            ['quiz', 'title', 'string', 'Manajemen Kuis'],
            ['quiz', 'subtitle', 'string', 'Kelola soal, jawaban, dan skor kuis.'],
            ['quiz', 'add_question', 'string', 'Tambah Soal'],
            ['quiz', 'search_questions', 'string', 'Cari teks soal...'],
            ['quiz', 'search_scores', 'string', 'Cari nama, email, atau kepribadian...'],
            ['quiz', 'tab_questions', 'string', 'Soal'],
            ['quiz', 'tab_scores', 'string', 'Skor'],
            ['quiz', 'col_question', 'string', 'Soal'],
            ['quiz', 'col_options', 'string', 'Opsi'],
            ['quiz', 'modal_add', 'string', 'Tambah Soal Quiz'],
            ['quiz', 'modal_edit', 'string', 'Edit Soal Quiz'],
            ['quiz', 'add_option', 'string', 'Tambah Opsi'],
            // Home
            ['home', 'welcome', 'string', 'Selamat datang kembali'],
            ['home', 'active', 'string', 'Aktif'],
            ['home', 'this_month', 'string', 'Bulan ini'],
            ['home', 'sales_chart', 'string', 'Grafik Penjualan'],
            ['home', 'recent_orders', 'string', 'Pesanan Terbaru'],
            ['home', 'missing_products', 'string', 'Produk Hilang'],
            // Profile
            ['profile', 'title', 'string', 'Profil Admin'],
            ['profile', 'edit', 'string', 'Edit Profil'],
            // CMS chrome
            ['cms', 'locale_id', 'string', 'Bahasa Indonesia'],
            ['cms', 'locale_en', 'string', 'English'],
            ['cms', 'save', 'string', 'Simpan Perubahan'],
            ['cms', 'add_faq', 'string', 'Tambah FAQ'],
            ['cms', 'save_faq', 'string', 'Simpan FAQ'],
            ['cms', 'tab_beranda', 'string', 'Beranda'],
            ['cms', 'tab_faq', 'string', 'FAQ'],
            ['cms', 'tab_kontak', 'string', 'Kontak'],
            ['cms', 'tab_navfooter', 'string', 'Nav & Footer'],
            ['cms', 'tab_ui', 'string', 'UI Website'],
            ['cms', 'tab_admin', 'string', 'UI Admin'],
        ];

        $adminEn = [
            ['sidebar', 'dashboard', 'string', 'Dashboard'],
            ['sidebar', 'cms', 'string', 'CMS'],
            ['sidebar', 'products', 'string', 'Products'],
            ['sidebar', 'kurirs', 'string', 'Couriers'],
            ['sidebar', 'quiz', 'string', 'Quiz'],
            ['sidebar', 'orders', 'string', 'Orders'],
            ['sidebar', 'trackings', 'string', 'Trackings'],
            ['sidebar', 'messages', 'string', 'Messages'],
            ['sidebar', 'cart', 'string', 'Cart'],
            ['sidebar', 'wishlist', 'string', 'Wishlist'],
            ['sidebar', 'users', 'string', 'All Users'],
            ['sidebar', 'subscribers', 'string', 'Subscribers'],
            ['sidebar', 'profile', 'string', 'Admin Profile'],
            ['sidebar', 'logout', 'string', 'Logout'],
            ['common', 'save', 'string', 'Save'],
            ['common', 'save_changes', 'string', 'Save Changes'],
            ['common', 'saving', 'string', 'Saving...'],
            ['common', 'cancel', 'string', 'Cancel'],
            ['common', 'edit', 'string', 'Edit'],
            ['common', 'delete', 'string', 'Delete'],
            ['common', 'add', 'string', 'Add'],
            ['common', 'search', 'string', 'Search'],
            ['common', 'actions', 'string', 'Actions'],
            ['common', 'loading', 'string', 'Loading...'],
            ['common', 'refresh', 'string', 'Refresh Data'],
            ['common', 'yes_delete', 'string', 'Yes, Delete'],
            ['common', 'confirm_delete', 'string', 'Delete this data?'],
            ['common', 'empty', 'string', 'No data found.'],
            ['common', 'status', 'string', 'Status'],
            ['common', 'user', 'string', 'User'],
            ['common', 'product', 'string', 'Product'],
            ['common', 'price', 'string', 'Price'],
            ['common', 'quantity', 'string', 'Qty'],
            ['common', 'date', 'string', 'Date'],
            ['common', 'email', 'string', 'Email'],
            ['common', 'name', 'string', 'Name'],
            ['common', 'id', 'string', 'ID'],
            ['common', 'reply', 'string', 'Reply'],
            ['common', 'close', 'string', 'Close'],
            ['common', 'back', 'string', 'Back'],
            ['auth', 'verifying', 'string', 'Verifying access...'],
            ['auth', 'denied_title', 'string', 'Access Denied'],
            ['auth', 'denied_message', 'string', 'Access denied! You do not have Administrator permission.'],
            ['auth', 'back_login', 'string', 'Back to Login'],
            ['products', 'title', 'string', 'Product Management'],
            ['products', 'subtitle', 'string', 'Manage inventory, pricing, notes, and perfume availability.'],
            ['products', 'add', 'string', 'Add Product'],
            ['products', 'search_ph', 'string', 'Search perfume name or personality type...'],
            ['products', 'col_product', 'string', 'Product'],
            ['products', 'col_type', 'string', 'Type / Size'],
            ['products', 'col_price', 'string', 'Price'],
            ['products', 'col_stock', 'string', 'Stock'],
            ['products', 'col_status', 'string', 'Status'],
            ['products', 'empty', 'string', 'No products found.'],
            ['products', 'modal_add', 'string', 'Add New Perfume'],
            ['products', 'modal_edit', 'string', 'Edit Perfume'],
            ['products', 'save_product', 'string', 'Save Product'],
            ['products', 'title_id', 'string', 'Title (ID)'],
            ['products', 'title_en', 'string', 'Title (EN)'],
            ['products', 'desc_id', 'string', 'Description (ID)'],
            ['products', 'desc_en', 'string', 'Description (EN)'],
            ['orders', 'title', 'string', 'Orders'],
            ['orders', 'subtitle', 'string', 'Manage customer order status and details.'],
            ['orders', 'search_ph', 'string', 'Search order ID or user name...'],
            ['orders', 'col_order', 'string', 'Order ID'],
            ['orders', 'col_customer', 'string', 'Customer'],
            ['orders', 'col_items', 'string', 'Items'],
            ['orders', 'col_total', 'string', 'Total'],
            ['orders', 'col_status', 'string', 'Status'],
            ['orders', 'empty', 'string', 'No orders found.'],
            ['orders', 'change_status', 'string', 'Change Order Status'],
            ['trackings', 'title', 'string', 'Order Tracking'],
            ['trackings', 'subtitle', 'string', 'Monitor and manage shipment status.'],
            ['trackings', 'search_ph', 'string', 'Search order ID, tracking no., or recipient...'],
            ['trackings', 'col_order', 'string', 'Order ID'],
            ['trackings', 'col_recipient', 'string', 'Recipient'],
            ['trackings', 'col_courier', 'string', 'Courier'],
            ['trackings', 'col_resi', 'string', 'Tracking No.'],
            ['trackings', 'col_status', 'string', 'Status'],
            ['trackings', 'no_tracking_number', 'string', 'No tracking number yet'],
            ['trackings', 'empty', 'string', 'No tracking data found.'],
            ['trackings', 'add_log', 'string', 'Add Log'],
            ['messages', 'title', 'string', 'Inbox'],
            ['messages', 'subtitle', 'string', 'Reply to customer messages and questions.'],
            ['messages', 'search_ph', 'string', 'Search name, email, subject...'],
            ['messages', 'col_customer', 'string', 'Customer'],
            ['messages', 'col_subject', 'string', 'Subject'],
            ['messages', 'col_preview', 'string', 'Message'],
            ['messages', 'col_date', 'string', 'Date'],
            ['messages', 'empty', 'string', 'No messages found'],
            ['cart', 'title', 'string', 'Cart'],
            ['cart', 'subtitle', 'string', 'View cart items across all users.'],
            ['cart', 'search_ph', 'string', 'Search user or product name...'],
            ['cart', 'empty', 'string', 'No items match your search.'],
            ['wishlist', 'title', 'string', 'Wishlist'],
            ['wishlist', 'subtitle', 'string', 'View wishlists across all users.'],
            ['wishlist', 'search_ph', 'string', 'Search user or product name...'],
            ['wishlist', 'empty', 'string', 'No data found.'],
            ['users', 'title', 'string', 'All Users'],
            ['users', 'subtitle', 'string', 'Manage customer and admin accounts.'],
            ['users', 'search_ph', 'string', 'Search name or email...'],
            ['users', 'col_user', 'string', 'User'],
            ['users', 'col_address', 'string', 'Address / Info'],
            ['users', 'col_joined', 'string', 'Joined'],
            ['users', 'empty', 'string', 'No users match your search.'],
            ['users', 'confirm_delete', 'string', 'Delete User?'],
            ['subscribers', 'title', 'string', 'Subscribers'],
            ['subscribers', 'subtitle', 'string', 'Evomi bulletin email list.'],
            ['subscribers', 'col_email', 'string', 'Email'],
            ['subscribers', 'col_date', 'string', 'Date'],
            ['quiz', 'title', 'string', 'Quiz Management'],
            ['quiz', 'subtitle', 'string', 'Manage quiz questions, answers, and scores.'],
            ['quiz', 'add_question', 'string', 'Add Question'],
            ['quiz', 'search_questions', 'string', 'Search question text...'],
            ['quiz', 'search_scores', 'string', 'Search name, email, or personality...'],
            ['quiz', 'tab_questions', 'string', 'Questions'],
            ['quiz', 'tab_scores', 'string', 'Scores'],
            ['quiz', 'col_question', 'string', 'Question'],
            ['quiz', 'col_options', 'string', 'Options'],
            ['quiz', 'modal_add', 'string', 'Add Quiz Question'],
            ['quiz', 'modal_edit', 'string', 'Edit Quiz Question'],
            ['quiz', 'add_option', 'string', 'Add Option'],
            ['home', 'welcome', 'string', 'Welcome back'],
            ['home', 'active', 'string', 'Active'],
            ['home', 'this_month', 'string', 'This month'],
            ['home', 'sales_chart', 'string', 'Sales Chart'],
            ['home', 'recent_orders', 'string', 'Recent Orders'],
            ['home', 'missing_products', 'string', 'Missing Products'],
            ['profile', 'title', 'string', 'Admin Profile'],
            ['profile', 'edit', 'string', 'Edit Profile'],
            ['cms', 'locale_id', 'string', 'Indonesian'],
            ['cms', 'locale_en', 'string', 'English'],
            ['cms', 'save', 'string', 'Save Changes'],
            ['cms', 'add_faq', 'string', 'Add FAQ'],
            ['cms', 'save_faq', 'string', 'Save FAQ'],
            ['cms', 'tab_beranda', 'string', 'Home'],
            ['cms', 'tab_faq', 'string', 'FAQ'],
            ['cms', 'tab_kontak', 'string', 'Contact'],
            ['cms', 'tab_navfooter', 'string', 'Nav & Footer'],
            ['cms', 'tab_ui', 'string', 'Website UI'],
            ['cms', 'tab_admin', 'string', 'Admin UI'],
        ];

        foreach ($adminId as [$section, $key, $type, $value]) {
            $this->put('admin', $section, $key, $type, $value, 'id');
        }
        foreach ($adminEn as [$section, $key, $type, $value]) {
            $this->put('admin', $section, $key, $type, $value, 'en');
        }
    }

    private function seedProductEn(): void
    {
        $map = [
            'prestige' => [
                'title_en' => 'Evomi Purpose Prestige',
                'description_en' => 'A refined scent that reflects clarity, ambition, and purposeful presence.',
                'perfume_type_en' => 'Eau de Parfum',
                'gender_en' => 'Unisex',
                'stock_status_en' => 'Available',
                'kondisi_en' => 'New',
                'kategori_en' => 'Perfume',
                'brand_en' => 'Evomi',
            ],
            'peaceful_calm' => [
                'title_en' => 'Evomi Peaceful Calm',
                'description_en' => 'A soothing scent that brings calm and quiet confidence.',
                'perfume_type_en' => 'Eau de Parfum',
                'gender_en' => 'Unisex',
                'stock_status_en' => 'Available',
                'kondisi_en' => 'New',
                'kategori_en' => 'Perfume',
                'brand_en' => 'Evomi',
            ],
            'rebel_brave' => [
                'title_en' => 'Evomi Rebel Brave',
                'description_en' => 'A bold scent for those who dare to stand out and express themselves.',
                'perfume_type_en' => 'Eau de Parfum',
                'gender_en' => 'Unisex',
                'stock_status_en' => 'Available',
                'kondisi_en' => 'New',
                'kategori_en' => 'Perfume',
                'brand_en' => 'Evomi',
            ],
            'sweet_shy' => [
                'title_en' => 'Evomi Sweet Shy',
                'description_en' => 'A soft, sweet scent that feels gentle and intimate.',
                'perfume_type_en' => 'Eau de Parfum',
                'gender_en' => 'Unisex',
                'stock_status_en' => 'Available',
                'kondisi_en' => 'New',
                'kategori_en' => 'Perfume',
                'brand_en' => 'Evomi',
            ],
        ];

        foreach ($map as $personality => $fields) {
            Product::where('personality_type', $personality)->update($fields);
        }
    }

    private function seedQuizEn(): void
    {
        $questionMap = [
            'Apa aktivitas akhir pekan favoritmu?' => "What's your favorite weekend activity?",
            'Bagaimana gaya berpakaian andalanmu sehari-hari?' => "What's your go-to everyday style?",
            'Aroma seperti apa yang paling menarik perhatianmu?' => 'What kind of scent catches your attention most?',
            'Kesan apa yang ingin kamu tinggalkan saat bertemu orang baru?' => 'What impression do you want to leave when meeting someone new?',
            'Pilih suasana cuaca yang paling membuat mood kamu naik:' => 'Pick the weather that lifts your mood the most:',
        ];

        $optionMap = [
            'Bersantai menikmati ketenangan alam' => "Relaxing while enjoying nature's calm",
            'Makan malam mewah dan eksklusif' => 'An exclusive fine-dining dinner',
            'Piknik santai membaca buku' => 'A casual picnic while reading a book',
            'Olahraga atau aktivitas menantang' => 'Sports or challenging activities',
            'Casual, simpel, dan nyaman' => 'Casual, simple, and comfortable',
            'Elegan, rapi, dan terstruktur' => 'Elegant, neat, and structured',
            'Warna pastel dan lembut' => 'Soft pastel colors',
            'Sporty, edgy, dan berani' => 'Sporty, edgy, and bold',
            'Aroma laut dan udara yang sejuk' => 'Ocean scent and cool fresh air',
            'Aroma kayu-kayuan dan rempah mewah' => 'Woody notes and luxurious spices',
            'Aroma bunga-bunga yang manis' => 'Sweet floral notes',
            'Aroma citrus yang tajam dan segar' => 'Sharp, fresh citrus',
            'Tenang, suportif, dan mudah didekati' => 'Calm, supportive, and approachable',
            'Misterius, karismatik, dan berwibawa' => 'Mysterious, charismatic, and authoritative',
            'Hangat, pemalu, namun menggemaskan' => 'Warm, shy, yet adorable',
            'Penuh semangat, percaya diri, dan tegas' => 'Energetic, confident, and assertive',
            'Pagi hari yang sejuk dan tenang' => 'A cool, peaceful morning',
            'Malam hari yang dingin dan syahdu' => 'A cold, serene night',
            'Sore hari musim semi yang hangat' => 'A warm spring afternoon',
            'Siang hari yang terik untuk beraktivitas' => 'A hot midday for staying active',
        ];

        QuizQuestion::query()->get()->each(function (QuizQuestion $q) use ($questionMap) {
            $en = $questionMap[$q->question_text] ?? $q->question_text_en;
            if ($en) {
                $q->update(['question_text_en' => $en]);
            }
        });

        QuizOption::query()->get()->each(function (QuizOption $o) use ($optionMap) {
            $en = $optionMap[$o->option_text] ?? $o->option_text_en;
            if ($en) {
                $o->update(['option_text_en' => $en]);
            }
        });

        $results = [
            [
                'personality_key' => 'purpose_prestige',
                'title' => 'Kamu adalah, Purpose Prestige',
                'title_en' => 'You are, Purpose Prestige',
                'description' => 'Menghadirkan aroma yang merefleksikan ketenangan, kepercayaan diri, dan kejelasan tujuan.',
                'description_en' => 'Presenting a scent that reflects calmness, confidence, and clarity of purpose.',
                'color' => '#1172BA',
                'bg_image' => '/src/images/kuis/purpose-kanan.png',
                'product_image' => '/src/images/kuis/purpose-produk.png',
                'forced_product_id' => '1',
            ],
            [
                'personality_key' => 'peaceful_calm',
                'title' => 'Kamu adalah, Peaceful Calm',
                'title_en' => 'You are, Peaceful Calm',
                'description' => 'Menghadirkan aroma yang menenangkan, seimbang, dan menyatu dengan diri.',
                'description_en' => 'Presenting a soothing, balanced scent that feels at one with yourself.',
                'color' => '#5EA14A',
                'bg_image' => '/src/images/kuis/peaceful-kanan.png',
                'product_image' => '/src/images/kuis/peaceful-produk.png',
                'forced_product_id' => '2',
            ],
            [
                'personality_key' => 'rebel_brave',
                'title' => 'Kamu adalah, Rebel Brave',
                'title_en' => 'You are, Rebel Brave',
                'description' => 'Merepresentasikan keberanian, energi, dan semangat untuk mengekspresikan diri.',
                'description_en' => 'Representing courage, energy, and the spirit of self-expression.',
                'color' => '#E33D35',
                'bg_image' => '/src/images/kuis/rebel-kanan.png',
                'product_image' => '/src/images/kuis/rebel-produk.png',
                'forced_product_id' => '3',
            ],
            [
                'personality_key' => 'sweet_shy',
                'title' => 'Kamu adalah, Sweet Shy',
                'title_en' => 'You are, Sweet Shy',
                'description' => 'Menghadirkan aroma lembut yang merefleksikan sisi manis, hangat, dan penuh empati.',
                'description_en' => 'Presenting a soft scent that reflects a sweet, warm, and empathetic side.',
                'color' => '#DD74A5',
                'bg_image' => '/src/images/kuis/sweet-kanan.png',
                'product_image' => '/src/images/kuis/sweet-produk.png',
                'forced_product_id' => '4',
            ],
        ];

        foreach ($results as $row) {
            QuizPersonalityResult::updateOrCreate(
                ['personality_key' => $row['personality_key']],
                $row,
            );
        }
    }
}
