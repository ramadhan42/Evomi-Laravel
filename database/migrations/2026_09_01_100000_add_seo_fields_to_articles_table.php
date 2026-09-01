<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            // Search-result copy. Falls back to title/excerpt when left empty.
            $table->string('meta_title')->nullable()->after('excerpt_en');
            $table->string('meta_title_en')->nullable()->after('meta_title');
            $table->string('meta_description', 500)->nullable()->after('meta_title_en');
            $table->string('meta_description_en', 500)->nullable()->after('meta_description');
            $table->string('meta_keywords', 255)->nullable()->after('meta_description_en');
            $table->string('canonical_url')->nullable()->after('meta_keywords');
            $table->boolean('noindex')->default(false)->after('canonical_url');

            // Structured data: a type for the generated JSON-LD, an optional
            // hand-written override, and the FAQ pairs that become FAQPage.
            $table->string('schema_type', 40)->default('BlogPosting')->after('noindex');
            $table->longText('schema_json')->nullable()->after('schema_type');
            $table->json('faqs')->nullable()->after('schema_json');
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn([
                'meta_title',
                'meta_title_en',
                'meta_description',
                'meta_description_en',
                'meta_keywords',
                'canonical_url',
                'noindex',
                'schema_type',
                'schema_json',
                'faqs',
            ]);
        });
    }
};
