<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_status', 20)
                ->default('pending')
                ->after('metode_pembayaran')
                ->index();
        });

        // Backfill existing rows so historical paid/fulfilled orders still count as revenue.
        if (Schema::hasTable('orders')) {
            DB::table('orders')->orderBy('created_at')->chunk(200, function ($orders): void {
                foreach ($orders as $order) {
                    $status = strtolower((string) ($order->status ?? ''));
                    $method = strtolower((string) ($order->metode_pembayaran ?? ''));

                    $paymentStatus = 'pending';

                    if ($status === 'dibatalkan') {
                        $paymentStatus = 'cancelled';
                    } elseif (
                        str_contains($method, 'qris')
                        || str_contains($method, 'midtrans')
                        || str_contains($method, 'xendit')
                        || in_array($status, ['pengemasan', 'dalam_perjalanan', 'diterima', 'selesai'], true)
                    ) {
                        $paymentStatus = 'success';
                    }

                    DB::table('orders')
                        ->where('id', $order->id)
                        ->update(['payment_status' => $paymentStatus]);
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('payment_status');
        });
    }
};
