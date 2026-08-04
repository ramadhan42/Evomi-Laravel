<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Orders sudah dibuat dengan string PK di migration create.
     * File ini hanya mempertahankan kompatibilitas untuk DB PostgreSQL lama.
     */
    public function up(): void
    {
        if (!Schema::hasTable('orders')) {
            return;
        }

        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE orders ALTER COLUMN id DROP DEFAULT');
        DB::statement('ALTER TABLE orders ALTER COLUMN id TYPE VARCHAR(255) USING id::VARCHAR');
    }

    public function down(): void
    {
        // no-op
    }
};
