<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'payment_expires_at')) {
                $table->timestamp('payment_expires_at')->nullable()->after('payment_status');
            }
            if (! Schema::hasColumn('orders', 'payment_provider')) {
                $table->string('payment_provider', 20)->nullable()->after('payment_expires_at');
            }
            if (! Schema::hasColumn('orders', 'payment_channel')) {
                $table->string('payment_channel', 20)->nullable()->after('payment_provider');
            }
            if (! Schema::hasColumn('orders', 'payment_ref')) {
                $table->string('payment_ref', 120)->nullable()->after('payment_channel');
            }
            if (! Schema::hasColumn('orders', 'payment_meta')) {
                $table->json('payment_meta')->nullable()->after('payment_ref');
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'payment_expires_at')) {
                $table->index('payment_expires_at', 'orders_payment_expires_at_index');
            }
            if (Schema::hasColumn('orders', 'payment_ref')) {
                $table->index('payment_ref', 'orders_payment_ref_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'payment_expires_at')) {
                $table->dropIndex('orders_payment_expires_at_index');
            }
            if (Schema::hasColumn('orders', 'payment_ref')) {
                $table->dropIndex('orders_payment_ref_index');
            }

            $cols = array_filter(
                ['payment_expires_at', 'payment_provider', 'payment_channel', 'payment_ref', 'payment_meta'],
                fn (string $col) => Schema::hasColumn('orders', $col),
            );

            if ($cols !== []) {
                $table->dropColumn($cols);
            }
        });
    }
};
