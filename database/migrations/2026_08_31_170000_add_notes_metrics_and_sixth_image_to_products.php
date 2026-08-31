<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The notes card on the product page now carries the same figures as the
 * printed notes sheet — olfactory family, sillage, projection, longevity —
 * and that sheet itself becomes the sixth slider image.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('image_5')->nullable()->after('image_4');

            $table->string('olfactory_family')->nullable()->after('base_note_en');
            $table->string('olfactory_family_en')->nullable()->after('olfactory_family');
            $table->string('sillage')->nullable()->after('olfactory_family_en');
            $table->string('sillage_en')->nullable()->after('sillage');
            $table->string('projection')->nullable()->after('sillage_en');
            $table->string('projection_en')->nullable()->after('projection');
            $table->string('longevity')->nullable()->after('projection_en');
            $table->string('longevity_en')->nullable()->after('longevity');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'image_5',
                'olfactory_family', 'olfactory_family_en',
                'sillage', 'sillage_en',
                'projection', 'projection_en',
                'longevity', 'longevity_en',
            ]);
        });
    }
};
