<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            
            // TAMBAHKAN FIELD COLOR DI SINI
            $table->string('color')->nullable()->comment('Kode hex warna, misal: #FF5733'); 

            $table->decimal('price', 12, 2);

            // Karakter Parfum (PENTING: Untuk relasi dengan Hasil Kuis)
            $table->enum('personality_type', ['prestige', 'peaceful_calm', 'rebel_brave', 'sweet_shy'])->nullable();

            // Notes Parfum
            $table->string('top_note')->nullable();
            $table->string('middle_note')->nullable();
            $table->string('base_note')->nullable();

            // Gambar & Spesifikasi
            $table->string('image_produk_belanja')->nullable();
            $table->string('image_1');
            $table->string('image_2')->nullable();
            $table->string('image_3')->nullable();
            $table->string('image_4')->nullable();
            $table->integer('bottle_size');
            $table->string('perfume_type');
            $table->enum('gender', ['unisex', 'male', 'female']);

            // Stok
            $table->integer('quantity')->default(0);
            $table->enum('stock_status', ['tersedia', 'minim', 'habis'])->default('tersedia');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};