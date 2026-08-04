<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_personality_results', function (Blueprint $table) {
            $table->unsignedSmallInteger('product_image_width_mobile')->nullable()->after('product_image');
            $table->unsignedSmallInteger('product_image_width_desktop')->nullable()->after('product_image_width_mobile');
            $table->unsignedSmallInteger('bg_image_width_mobile')->nullable()->after('product_image_width_desktop');
            $table->unsignedSmallInteger('bg_image_width_desktop')->nullable()->after('bg_image_width_mobile');
        });
    }

    public function down(): void
    {
        Schema::table('quiz_personality_results', function (Blueprint $table) {
            $table->dropColumn([
                'product_image_width_mobile',
                'product_image_width_desktop',
                'bg_image_width_mobile',
                'bg_image_width_desktop',
            ]);
        });
    }
};
