<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('alamat_awal_pengiriman')->nullable();
            $table->string('kondisi')->nullable();
            $table->string('kategori')->nullable();
            $table->decimal('berat_satuan', 8, 2)->nullable();
            $table->string('brand')->nullable();
            $table->string('etalase')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['alamat_awal_pengiriman', 'kondisi', 'kategori', 'berat_satuan', 'brand', 'etalase']);
        });
    }
};
