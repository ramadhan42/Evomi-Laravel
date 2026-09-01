<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per public page, plus a "default" row the others fall back to.
     * This is what the dashboard SEO menu edits.
     */
    public function up(): void
    {
        Schema::create('seo_settings', function (Blueprint $table) {
            $table->id();
            $table->string('page', 40)->unique();
            $table->string('meta_title')->nullable();
            $table->string('meta_title_en')->nullable();
            $table->string('meta_description', 500)->nullable();
            $table->string('meta_description_en', 500)->nullable();
            $table->string('meta_keywords', 255)->nullable();
            $table->string('og_image')->nullable();
            $table->boolean('noindex')->default(false);
            $table->timestamps();
        });

        // Seed the copy the pages currently hard-code, so the dashboard opens
        // pre-filled instead of blank.
        $now = now();
        $seed = [
            [
                'page' => 'default',
                'meta_title' => 'Evomi Perfume',
                'meta_title_en' => 'Evomi Perfume',
                'meta_description' => 'Temukan keharuman eksklusif Evomi yang mencerminkan kepribadian Anda.',
                'meta_description_en' => 'Discover the exclusive Evomi fragrances that mirror your personality.',
            ],
            [
                'page' => 'beranda',
                'meta_title' => 'Evomi Perfume',
                'meta_title_en' => 'Evomi Perfume',
                'meta_description' => 'Temukan keharuman eksklusif Evomi yang mencerminkan kepribadian Anda.',
                'meta_description_en' => 'Discover the exclusive Evomi fragrances that mirror your personality.',
            ],
            [
                'page' => 'belanja',
                'meta_title' => 'Belanja Parfum Evomi',
                'meta_title_en' => 'Shop Evomi Perfume',
                'meta_description' => 'Jelajahi koleksi parfum Evomi dan temukan aroma yang paling cocok untuk Anda.',
                'meta_description_en' => 'Browse the Evomi perfume collection and find the scent that suits you best.',
            ],
            [
                'page' => 'artikel',
                'meta_title' => 'Artikel Parfum',
                'meta_title_en' => 'Perfume Articles',
                'meta_description' => 'Tips, panduan, dan cerita seputar parfum dari jurnal Evomi.',
                'meta_description_en' => 'Tips, guides and stories about perfume from the Evomi journal.',
            ],
            [
                'page' => 'kuis',
                'meta_title' => 'Kuis Kepribadian Parfum',
                'meta_title_en' => 'Perfume Personality Quiz',
                'meta_description' => 'Jawab beberapa pertanyaan singkat dan temukan parfum Evomi yang paling cocok dengan kepribadian Anda.',
                'meta_description_en' => 'Answer a few short questions and find the Evomi perfume that matches your personality.',
            ],
            [
                'page' => 'faq',
                'meta_title' => 'FAQ',
                'meta_title_en' => 'FAQ',
                'meta_description' => 'Pertanyaan yang sering diajukan seputar produk, pemesanan, dan pengiriman Evomi.',
                'meta_description_en' => 'Frequently asked questions about Evomi products, orders and shipping.',
            ],
            [
                'page' => 'kontak',
                'meta_title' => 'Kontak',
                'meta_title_en' => 'Contact',
                'meta_description' => 'Hubungi tim Evomi untuk pertanyaan seputar produk, pesanan, atau kerja sama.',
                'meta_description_en' => 'Get in touch with the Evomi team about products, orders or partnerships.',
            ],
            [
                'page' => 'pengiriman',
                'meta_title' => 'Lacak Pengiriman',
                'meta_title_en' => 'Track Shipment',
                'meta_description' => 'Lacak status pengiriman pesanan Evomi Anda dengan nomor resi.',
                'meta_description_en' => 'Track your Evomi order with its tracking number.',
            ],
        ];

        foreach ($seed as $row) {
            DB::table('seo_settings')->insert($row + [
                'noindex' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_settings');
    }
};
