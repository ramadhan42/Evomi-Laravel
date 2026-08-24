<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderTracking;
use App\Support\OrderNumber;
use App\Models\Product;
use App\Services\Midtrans\MidtransClient;
use App\Services\Xendit\XenditClient;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderPaymentService
{
    public const WINDOW_HOURS = 24;

    public function __construct(
        private MidtransClient $midtrans,
        private XenditClient $xendit,
    ) {}

    /**
     * @return Collection<int, Order>
     */
    public function ordersForInvoice(string $invoiceId): Collection
    {
        $invoiceId = trim($invoiceId);
        if ($invoiceId === '') {
            return collect();
        }

        return Order::query()
            ->with('product')
            ->where(function ($q) use ($invoiceId) {
                $q->where('id', $invoiceId)
                    ->orWhere('id', 'like', $invoiceId.'-%');
            })
            ->orderBy('id')
            ->get();
    }

    public function primaryOrder(string $invoiceId): ?Order
    {
        $orders = $this->ordersForInvoice($invoiceId);
        if ($orders->isEmpty()) {
            return null;
        }

        return $orders->firstWhere('id', $invoiceId) ?: $orders->first();
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public function attachPaymentIntent(
        string $invoiceId,
        string $provider,
        string $channel,
        string $paymentRef,
        array $meta = [],
    ): ?Order {
        $orders = $this->ordersForInvoice($invoiceId);
        if ($orders->isEmpty()) {
            return null;
        }

        foreach ($orders as $order) {
            $order->forceFill([
                'payment_provider' => $provider,
                'payment_channel' => $channel,
                'payment_ref' => $paymentRef,
                'payment_meta' => array_merge(
                    is_array($order->payment_meta) ? $order->payment_meta : [],
                    $meta,
                ),
            ])->save();
        }

        return $this->primaryOrder($invoiceId);
    }

    public function markInvoicePaid(string $invoiceId, ?array $metaPatch = null): int
    {
        $orders = $this->ordersForInvoice($invoiceId);
        if ($orders->isEmpty()) {
            return 0;
        }

        $updated = 0;
        foreach ($orders as $order) {
            if ($order->payment_status === Order::PAYMENT_SUCCESS) {
                continue;
            }
            if ($order->payment_status === Order::PAYMENT_CANCELLED) {
                continue;
            }

            $meta = is_array($order->payment_meta) ? $order->payment_meta : [];
            if (is_array($metaPatch)) {
                $meta = array_merge($meta, $metaPatch);
            }
            $meta['paid_at'] = now()->toIso8601String();

            $order->forceFill([
                'payment_status' => Order::PAYMENT_SUCCESS,
                'payment_meta' => $meta,
            ])->save();
            $updated++;
        }

        if ($updated > 0) {
            $this->bumpTrackingPaid($invoiceId);
        }

        return $updated;
    }

    public function expireInvoice(string $invoiceId, string $reason = 'payment_window_expired'): int
    {
        $orders = $this->ordersForInvoice($invoiceId);
        if ($orders->isEmpty()) {
            return 0;
        }

        return (int) DB::transaction(function () use ($orders, $invoiceId, $reason) {
            $expired = 0;
            foreach ($orders as $order) {
                if ($order->payment_status !== Order::PAYMENT_PENDING) {
                    continue;
                }

                $locked = Order::where('id', $order->id)->lockForUpdate()->first();
                if (! $locked || $locked->payment_status !== Order::PAYMENT_PENDING) {
                    continue;
                }

                $isCod = $locked->isCodPayment();
                $isOnline = in_array($locked->payment_channel, ['qris', 'va'], true);
                if (! $isCod && ! $isOnline) {
                    continue;
                }
                if ($isCod && $locked->hasShipped()) {
                    continue;
                }

                $meta = is_array($locked->payment_meta) ? $locked->payment_meta : [];
                $meta['expired_at'] = now()->toIso8601String();
                $meta['expire_reason'] = $reason;

                $product = Product::where('id', $locked->product_id)->lockForUpdate()->first();
                if ($product) {
                    $product->restoreStock((int) $locked->quantity);
                }

                $locked->forceFill([
                    'payment_status' => Order::PAYMENT_CANCELLED,
                    'status' => 'dibatalkan',
                    'payment_meta' => $meta,
                ])->save();
                $expired++;
            }

            if ($expired > 0) {
                $tracking = OrderTracking::where('order_id', $invoiceId)->first();
                if ($tracking) {
                    $timeline = is_array($tracking->timeline) ? $tracking->timeline : [];
                    $timeline[] = [
                        'status' => $this->cancelTimelineLabel($reason),
                        'date' => now()->toIso8601String(),
                    ];
                    $tracking->forceFill([
                        'status' => 'Dibatalkan',
                        'timeline' => $timeline,
                    ])->save();
                }
            }

            return $expired;
        });
    }

    public function expireDueOrders(): int
    {
        $now = now();
        $codCutoff = $now->copy()->subHours(Order::CANCEL_WINDOW_HOURS);

        $onlineIds = Order::query()
            ->where('payment_status', Order::PAYMENT_PENDING)
            ->whereIn('payment_channel', ['qris', 'va'])
            ->whereNotNull('payment_expires_at')
            ->where('payment_expires_at', '<', $now)
            ->pluck('id');

        $codIds = Order::query()
            ->where('payment_status', Order::PAYMENT_PENDING)
            ->whereNotIn('status', [...Order::SHIPPED_STATUSES, 'dibatalkan'])
            ->where(function ($q) {
                $q->where('payment_channel', 'cod')
                    ->orWhereRaw('LOWER(COALESCE(metode_pembayaran, "")) LIKE ?', ['%cash on delivery%'])
                    ->orWhereRaw('LOWER(COALESCE(metode_pembayaran, "")) = ?', ['cod']);
            })
            ->where(function ($q) use ($now, $codCutoff) {
                $q->where(function ($exp) use ($now) {
                    $exp->whereNotNull('payment_expires_at')
                        ->where('payment_expires_at', '<', $now);
                })->orWhere(function ($exp) use ($codCutoff) {
                    $exp->whereNull('payment_expires_at')
                        ->where('created_at', '<', $codCutoff);
                });
            })
            ->pluck('id');

        $invoiceIds = $onlineIds
            ->concat($codIds)
            ->map(fn ($id) => $this->invoiceRoot((string) $id))
            ->unique()
            ->values();

        $total = 0;
        foreach ($invoiceIds as $invoiceId) {
            $reason = $this->invoiceLooksCod($invoiceId)
                ? 'cod_window_expired'
                : 'payment_window_expired';
            $total += $this->expireInvoice($invoiceId, $reason);
        }

        return $total;
    }

    private function invoiceLooksCod(string $invoiceId): bool
    {
        $order = $this->primaryOrder($invoiceId);

        return $order?->isCodPayment() === true;
    }

    private function cancelTimelineLabel(string $reason): string
    {
        return match ($reason) {
            'user_cancelled' => 'Pesanan dibatalkan oleh pelanggan',
            'cod_window_expired' => 'Pesanan COD dibatalkan — belum dikirim dalam 24 jam',
            default => 'Pesanan dibatalkan — batas waktu pembayaran habis',
        };
    }

    /**
     * Poll gateway and sync local payment_status.
     *
     * @return array{paid: bool, expired: bool, status: string, payment_status: string}
     */
    public function syncGatewayStatus(Order $order): array
    {
        $invoiceId = $this->invoiceRoot($order->id);

        if ($order->payment_status === Order::PAYMENT_SUCCESS) {
            return [
                'paid' => true,
                'expired' => false,
                'status' => 'paid',
                'payment_status' => Order::PAYMENT_SUCCESS,
            ];
        }

        if ($order->payment_status === Order::PAYMENT_CANCELLED) {
            return [
                'paid' => false,
                'expired' => true,
                'status' => 'cancelled',
                'payment_status' => Order::PAYMENT_CANCELLED,
            ];
        }

        if (
            $order->payment_expires_at
            && $order->payment_expires_at->isPast()
            && $order->payment_status === Order::PAYMENT_PENDING
        ) {
            $this->expireInvoice($invoiceId);

            return [
                'paid' => false,
                'expired' => true,
                'status' => 'expired',
                'payment_status' => Order::PAYMENT_CANCELLED,
            ];
        }

        $paid = $this->isGatewayPaid($order);
        if ($paid) {
            $this->markInvoicePaid($invoiceId);

            return [
                'paid' => true,
                'expired' => false,
                'status' => 'paid',
                'payment_status' => Order::PAYMENT_SUCCESS,
            ];
        }

        return [
            'paid' => false,
            'expired' => false,
            'status' => 'pending',
            'payment_status' => Order::PAYMENT_PENDING,
        ];
    }

    public function invoiceRoot(string $orderId): string
    {
        return OrderNumber::invoiceRoot($orderId);
    }

    private function isGatewayPaid(Order $order): bool
    {
        $provider = strtolower((string) $order->payment_provider);
        $channel = strtolower((string) $order->payment_channel);
        $ref = trim((string) $order->payment_ref);
        if ($ref === '' || ! in_array($provider, ['midtrans', 'xendit'], true)) {
            return false;
        }

        try {
            if ($provider === 'midtrans') {
                $status = $this->midtrans->getTransactionStatus($ref);
                $tx = strtolower((string) ($status['transaction_status'] ?? ''));

                return in_array($tx, ['settlement', 'capture', 'success'], true);
            }

            if ($channel === 'qris') {
                $qr = $this->xendit->getQrCode($ref);
                $st = strtoupper((string) ($qr['status'] ?? ''));

                return in_array($st, ['INACTIVE', 'COMPLETED', 'SUCCEEDED'], true);
            }

            if ($channel === 'va') {
                $va = $this->xendit->getVirtualAccount($ref);
                $st = strtoupper((string) ($va['status'] ?? ''));

                return $st === 'INACTIVE';
            }
        } catch (\Throwable $e) {
            Log::warning('Payment sync failed', [
                'order_id' => $order->id,
                'detail' => $e->getMessage(),
            ]);
        }

        return false;
    }

    private function bumpTrackingPaid(string $invoiceId): void
    {
        $tracking = OrderTracking::where('order_id', $invoiceId)->first();
        if (! $tracking) {
            return;
        }

        $timeline = is_array($tracking->timeline) ? $tracking->timeline : [];
        $timeline[] = [
            'status' => 'Pembayaran berhasil',
            'date' => now()->toIso8601String(),
        ];
        $tracking->forceFill([
            'status' => 'Menunggu Konfirmasi',
            'timeline' => $timeline,
        ])->save();
    }
}
