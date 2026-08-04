<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_contents', function (Blueprint $table) {
            $table->id();
            $table->string('page', 50);
            $table->string('section', 80);
            $table->string('key', 80);
            $table->string('type', 20)->default('string'); // string|text|image
            $table->longText('value')->nullable();
            $table->timestamps();

            $table->unique(['page', 'section', 'key']);
            $table->index(['page', 'section']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_contents');
    }
};
