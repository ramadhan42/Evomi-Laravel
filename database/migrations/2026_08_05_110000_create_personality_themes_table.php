<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personality_themes', function (Blueprint $table) {
            $table->id();
            $table->string('personality_key')->unique();
            $table->string('badge', 60);
            $table->string('badge_key', 40);
            $table->string('accent', 20);
            $table->string('soft_accent', 20);
            $table->string('character')->nullable();
            $table->string('fallback_img')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personality_themes');
    }
};
