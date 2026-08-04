<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_contents', function (Blueprint $table) {
            $table->string('locale', 5)->default('id')->after('key');
        });

        // Drop old unique and add new one including locale
        Schema::table('site_contents', function (Blueprint $table) {
            $table->dropUnique(['page', 'section', 'key']);
            $table->unique(['page', 'section', 'key', 'locale']);
            $table->index(['page', 'locale']);
        });

        DB::table('site_contents')->whereNull('locale')->orWhere('locale', '')->update(['locale' => 'id']);
    }

    public function down(): void
    {
        Schema::table('site_contents', function (Blueprint $table) {
            $table->dropUnique(['page', 'section', 'key', 'locale']);
            $table->dropIndex(['page', 'locale']);
            $table->dropColumn('locale');
            $table->unique(['page', 'section', 'key']);
        });
    }
};
