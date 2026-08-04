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
        // Menambahkan kolom di tabel contact_messages (untuk admin)
        Schema::table('contact_messages', function (Blueprint $table) {
            if (!Schema::hasColumn('contact_messages', 'is_read_by_admin')) {
                $table->boolean('is_read_by_admin')->default(false)->after('message');
            }
        });

        // Menambahkan kolom di tabel contact_replies (untuk user)
        Schema::table('contact_replies', function (Blueprint $table) {
            if (!Schema::hasColumn('contact_replies', 'is_read_by_user')) {
                $table->boolean('is_read_by_user')->default(false)->after('reply_message');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contact_messages', function (Blueprint $table) {
            $table->dropColumn('is_read_by_admin');
        });

        Schema::table('contact_replies', function (Blueprint $table) {
            $table->dropColumn('is_read_by_user');
        });
    }
};