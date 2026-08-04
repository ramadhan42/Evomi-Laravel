<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('contact_replies', function (Blueprint $table) {
            $table->id();
            // Relasi ke tabel contact_messages
            $table->foreignId('contact_message_id')->constrained('contact_messages')->onDelete('cascade');
            $table->text('reply_message');
            $table->unsignedBigInteger('replied_by')->nullable(); // ID Admin
            $table->boolean('is_read_by_user')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('contact_replies');
    }
};