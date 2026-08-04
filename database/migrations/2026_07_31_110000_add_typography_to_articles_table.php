<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->string('title_font_family', 40)->default('nohemi')->after('author');
            $table->string('title_font_weight', 10)->default('700')->after('title_font_family');
            $table->string('title_font_style', 10)->default('normal')->after('title_font_weight');
            $table->string('title_font_size', 20)->default('40')->after('title_font_style');

            $table->string('excerpt_font_family', 40)->default('parkinsans')->after('title_font_size');
            $table->string('excerpt_font_weight', 10)->default('400')->after('excerpt_font_family');
            $table->string('excerpt_font_style', 10)->default('normal')->after('excerpt_font_weight');
            $table->string('excerpt_font_size', 20)->default('18')->after('excerpt_font_style');

            $table->string('content_font_family', 40)->default('parkinsans')->after('excerpt_font_size');
            $table->string('content_font_weight', 10)->default('400')->after('content_font_family');
            $table->string('content_font_style', 10)->default('normal')->after('content_font_weight');
            $table->string('content_font_size', 20)->default('17')->after('content_font_style');
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn([
                'title_font_family',
                'title_font_weight',
                'title_font_style',
                'title_font_size',
                'excerpt_font_family',
                'excerpt_font_weight',
                'excerpt_font_style',
                'excerpt_font_size',
                'content_font_family',
                'content_font_weight',
                'content_font_style',
                'content_font_size',
            ]);
        });
    }
};
