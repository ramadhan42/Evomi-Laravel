<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_personality_results', function (Blueprint $table) {
            $table->id();
            $table->string('personality_key')->unique();
            $table->string('title');
            $table->string('title_en')->nullable();
            $table->text('description');
            $table->text('description_en')->nullable();
            $table->string('color', 32)->nullable();
            $table->string('bg_image')->nullable();
            $table->string('product_image')->nullable();
            $table->string('forced_product_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_personality_results');
    }
};
