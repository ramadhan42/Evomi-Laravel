<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderTracking extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'tracking_number',
        'status',
        'estimated_delivery',
        'courier',
        'recipient_name',
        'recipient_phone',
        'recipient_address',
        'timeline',
    ];

    protected $casts = [
        'estimated_delivery' => 'date',
        'timeline' => 'array',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }

    /**
     * Find tracking by courier resi (tracking_number) or order/invoice number.
     */
    public static function findByResiOrOrder(string $query): ?self
    {
        $query = trim(urldecode($query));
        if ($query === '') {
            return null;
        }

        $byResi = self::query()
            ->whereNotNull('tracking_number')
            ->where('tracking_number', '!=', '')
            ->whereRaw('LOWER(tracking_number) = ?', [mb_strtolower($query)])
            ->first();

        if ($byResi) {
            return $byResi;
        }

        $invoiceRoot = Order::invoiceRoot($query);

        $byOrder = self::query()->where('order_id', $invoiceRoot)->first()
            ?: self::query()->where('order_id', $query)->first()
            ?: self::query()
                ->where('order_id', 'like', $invoiceRoot.'-%')
                ->orderBy('id')
                ->first();

        if ($byOrder) {
            if ((string) $byOrder->order_id !== $invoiceRoot && Order::existsForInvoice($invoiceRoot)) {
                $byOrder->order_id = $invoiceRoot;
                $byOrder->save();
            }

            return $byOrder;
        }

        if (Order::existsForInvoice($invoiceRoot)) {
            return self::ensureForInvoice($invoiceRoot);
        }

        return null;
    }

    /**
     * Delete tracking rows for an invoice when no order lines remain.
     */
    public static function deleteIfOrderGone(string $orderId): int
    {
        $invoiceRoot = Order::invoiceRoot($orderId);
        if (Order::existsForInvoice($invoiceRoot)) {
            return 0;
        }

        return self::query()
            ->where(function ($q) use ($invoiceRoot, $orderId) {
                $q->where('order_id', $invoiceRoot)
                    ->orWhere('order_id', $orderId)
                    ->orWhere('order_id', 'like', $invoiceRoot.'-%');
            })
            ->delete();
    }

    /**
     * Remove tracking rows that no longer have matching orders.
     */
    public static function purgeOrphans(): int
    {
        $deleted = 0;
        self::query()->orderBy('id')->chunkById(100, function ($rows) use (&$deleted) {
            foreach ($rows as $tracking) {
                $root = Order::invoiceRoot((string) $tracking->order_id);
                if (! Order::existsForInvoice($root)) {
                    $tracking->delete();
                    $deleted++;
                }
            }
        });

        return $deleted;
    }

    /**
     * Ensure a tracking row exists for an invoice, seeded from the primary order line.
     *
     * @param  array{recipient_name?: string, recipient_phone?: string, recipient_address?: string, courier?: string|null, status?: string|null}  $seed
     */
    public static function ensureForInvoice(string $invoiceRoot, ?Order $order = null, array $seed = []): self
    {
        $invoiceRoot = Order::invoiceRoot($invoiceRoot);

        $tracking = self::query()->where('order_id', $invoiceRoot)->first();
        if (! $tracking) {
            $tracking = self::query()
                ->where('order_id', 'like', $invoiceRoot.'-%')
                ->orderBy('id')
                ->first();
            if ($tracking) {
                $tracking->order_id = $invoiceRoot;
                $tracking->save();
            }
        }

        if ($tracking) {
            return $tracking;
        }

        $order ??= Order::query()
            ->where(function ($q) use ($invoiceRoot) {
                $q->where('id', $invoiceRoot)
                    ->orWhere('id', 'like', $invoiceRoot.'-%');
            })
            ->with('user')
            ->orderBy('id')
            ->first();

        $awaitingPay = $order
            && $order->payment_status === Order::PAYMENT_PENDING
            && in_array((string) $order->payment_channel, ['qris', 'va'], true);

        $status = $seed['status']
            ?? ($awaitingPay
                ? 'Menunggu Pembayaran'
                : Order::trackingStatusLabel($order?->status));

        return self::create([
            'order_id' => $invoiceRoot,
            'status' => $status,
            'courier' => $seed['courier'] ?? null,
            'recipient_name' => $seed['recipient_name'] ?? ($order?->user?->name ?: 'Pelanggan'),
            'recipient_phone' => $seed['recipient_phone'] ?? '',
            'recipient_address' => $seed['recipient_address'] ?? '',
            'timeline' => [
                [
                    'status' => $status,
                    'date' => now()->toIso8601String(),
                    'time' => now()->toIso8601String(),
                    'description' => 'Disinkronkan dari data pesanan',
                ],
            ],
        ]);
    }
}
