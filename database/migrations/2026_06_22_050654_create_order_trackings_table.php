<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_trackings', function (Blueprint $table) {
            $table->id();
            $table->string('order_id')->unique(); // Contoh: ORD-8829102
            $table->string('tracking_number')->nullable(); // Nomor Resi
            $table->string('status')->default('Menunggu Konfirmasi');
            $table->date('estimated_delivery')->nullable(); // Estimasi Tiba
            $table->string('courier')->nullable(); // Informasi Kurir, misal: JNE Express - REG
            
            // Informasi Tujuan
            $table->string('recipient_name');
            $table->string('recipient_phone');
            $table->text('recipient_address');
            
            // JSON portable (MySQL/MariaDB/PostgreSQL via Laravel)
            $table->json('timeline')->nullable(); 
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_trackings');
    }
};