<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('title_en')->nullable()->after('title');
            $table->text('description_en')->nullable()->after('description');
            $table->string('personality_type_en')->nullable()->after('personality_type');
            $table->string('top_note_en')->nullable()->after('top_note');
            $table->string('middle_note_en')->nullable()->after('middle_note');
            $table->string('base_note_en')->nullable()->after('base_note');
            $table->string('perfume_type_en')->nullable()->after('perfume_type');
            $table->string('gender_en')->nullable()->after('gender');
            $table->string('stock_status_en')->nullable()->after('stock_status');
            $table->string('kondisi_en')->nullable()->after('kondisi');
            $table->string('kategori_en')->nullable()->after('kategori');
            $table->string('brand_en')->nullable()->after('brand');
            $table->string('etalase_en')->nullable()->after('etalase');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'title_en',
                'description_en',
                'personality_type_en',
                'top_note_en',
                'middle_note_en',
                'base_note_en',
                'perfume_type_en',
                'gender_en',
                'stock_status_en',
                'kondisi_en',
                'kategori_en',
                'brand_en',
                'etalase_en',
            ]);
        });
    }
};
