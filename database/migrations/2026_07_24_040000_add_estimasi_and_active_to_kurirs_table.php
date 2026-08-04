<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kurirs', function (Blueprint $table) {
            if (!Schema::hasColumn('kurirs', 'estimasi_hari')) {
                $table->unsignedTinyInteger('estimasi_hari')->default(3)->after('destinasi');
            }
            if (!Schema::hasColumn('kurirs', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('estimasi_hari');
            }
        });
    }

    public function down(): void
    {
        Schema::table('kurirs', function (Blueprint $table) {
            if (Schema::hasColumn('kurirs', 'is_active')) {
                $table->dropColumn('is_active');
            }
            if (Schema::hasColumn('kurirs', 'estimasi_hari')) {
                $table->dropColumn('estimasi_hari');
            }
        });
    }
};
