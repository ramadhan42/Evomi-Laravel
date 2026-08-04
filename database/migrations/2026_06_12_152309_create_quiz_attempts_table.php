<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Akumulasi total skor akhir yang diperoleh user
            $table->integer('total_prestige')->default(0);
            $table->integer('total_peaceful_calm')->default(0);
            $table->integer('total_rebel_brave')->default(0);
            $table->integer('total_sweet_shy')->default(0);
            
            // Hasil kategori kepribadian tertinggi/dominan
            $table->enum('dominant_personality', ['prestige', 'peaceful_calm', 'rebel_brave', 'sweet_shy']);
            
            // Relasi ke tabel products (Parfum yang direkomendasikan berdasarkan hasil kuis)
            $table->foreignId('product_id')->nullable()->constrained('products')->onDelete('set null');
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_attempts');
    }
};
