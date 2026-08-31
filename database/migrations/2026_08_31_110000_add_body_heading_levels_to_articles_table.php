<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->string('excerpt_heading_level', 8)->default('normal')->after('title_heading_level');
            $table->string('content_heading_level', 8)->default('normal')->after('excerpt_heading_level');
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn(['excerpt_heading_level', 'content_heading_level']);
        });
    }
};
