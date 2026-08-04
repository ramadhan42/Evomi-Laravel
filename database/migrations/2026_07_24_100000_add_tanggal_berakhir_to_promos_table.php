<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promos', function (Blueprint $table) {
            if (!Schema::hasColumn('promos', 'tanggal_berakhir_promo')) {
                $table->date('tanggal_berakhir_promo')->nullable()->after('tanggal_berlaku_promo');
            }
        });
    }

    public function down(): void
    {
        Schema::table('promos', function (Blueprint $table) {
            if (Schema::hasColumn('promos', 'tanggal_berakhir_promo')) {
                $table->dropColumn('tanggal_berakhir_promo');
            }
        });
    }
};
