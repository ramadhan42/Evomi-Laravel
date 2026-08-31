<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->string('title_heading_level', 4)->default('h1')->after('content_font_size');
            $table->json('heading_fonts')->nullable()->after('title_heading_level');
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn(['title_heading_level', 'heading_fonts']);
        });
    }
};
