<?php

namespace App\Console\Commands;

use App\Services\OrderPaymentService;
use Illuminate\Console\Command;

class ExpireUnpaidOrders extends Command
{
    protected $signature = 'orders:expire-unpaid';

    protected $description = 'Batalkan pesanan online yang melewati batas waktu pembayaran 24 jam';

    public function handle(OrderPaymentService $payments): int
    {
        $count = $payments->expireDueOrders();
        $this->info("Expired {$count} unpaid order row(s).");

        return self::SUCCESS;
    }
}
