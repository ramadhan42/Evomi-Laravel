<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_question_id')->constrained()->onDelete('cascade');
            $table->text('option_text'); // Teks pilihan jawaban
            
            // Bobot skor untuk masing-masing kepribadian (default 0 jika tidak menghasilkan poin)
            $table->integer('prestige_score')->default(0);
            $table->integer('peaceful_calm_score')->default(0);
            $table->integer('rebel_brave_score')->default(0);
            $table->integer('sweet_shy_score')->default(0);
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_options');
    }
};
