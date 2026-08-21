<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kurir_tarifs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('kurir_id')->constrained('kurirs')->cascadeOnDelete();
            $table->string('kota_tujuan', 120);

            // Berat paket dalam gram (inclusive range).
            $table->unsignedInteger('berat_min_gram');
            $table->unsignedInteger('berat_max_gram');

            $table->decimal('harga', 15, 2)->default(0);
            $table->unsignedTinyInteger('estimasi_hari')->default(3);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(['kurir_id', 'kota_tujuan', 'berat_min_gram', 'berat_max_gram'], 'kurir_tarif_unique');
            $table->index(['kota_tujuan', 'berat_min_gram', 'berat_max_gram']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kurir_tarifs');
    }
};

