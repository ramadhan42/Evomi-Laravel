<?php

namespace App\Models;

use App\Support\OrderNumber;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    public const PAYMENT_PENDING = 'pending';

    public const PAYMENT_SUCCESS = 'success';

    public const PAYMENT_CANCELLED = 'cancelled';

    public const CANCEL_WINDOW_HOURS = 24;

    /**
     * Fulfillment statuses that mean the parcel has already left Evomi.
     *
     * @var list<string>
     */
    public const SHIPPED_STATUSES = [
        'dalam_perjalanan',
        'diterima',
        'selesai',
    ];

    /**
     * @var list<string>
     */
    public const PAYMENT_STATUSES = [
        self::PAYMENT_PENDING,
        self::PAYMENT_SUCCESS,
        self::PAYMENT_CANCELLED,
    ];

    protected $fillable = [
        'id',
        'user_id',
        'guest_email',
        'product_id',
        'quantity',
        'total_price',
        'shipping_cost',
        'promo_discount',
        'status',
        'metode_pembayaran',
        'payment_status',
        'payment_expires_at',
        'payment_provider',
        'payment_channel',
        'payment_ref',
        'payment_meta',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'payment_status' => self::PAYMENT_PENDING,
    ];

    protected $casts = [
        'total_price' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'promo_discount' => 'decimal:2',
        'quantity' => 'integer',
        'payment_expires_at' => 'datetime',
        'payment_meta' => 'array',
    ];

    protected $appends = [
        'grand_total',
        'is_awaiting_payment',
        'is_cod_payment',
        'is_awaiting_cod_payment',
        'payment_window_seconds',
        'order_number',
    ];

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * Total bayar = harga produk (katalog) + ongkir − promo.
     */
    public function getGrandTotalAttribute(): float
    {
        return max(
            0,
            (float) $this->total_price
                + (float) ($this->shipping_cost ?? 0)
                - (float) ($this->promo_discount ?? 0)
        );
    }

    public function getIsAwaitingPaymentAttribute(): bool
    {
        return $this->isAwaitingOnlinePayment();
    }

    public function getIsCodPaymentAttribute(): bool
    {
        return $this->isCodPayment();
    }

    public function getIsAwaitingCodPaymentAttribute(): bool
    {
        return $this->isAwaitingCodPayment();
    }

    public function getPaymentWindowSecondsAttribute(): int
    {
        $expires = $this->cancelWindowExpiresAt();
        if (! $expires) {
            return 0;
        }

        if ($this->isAwaitingOnlinePayment()) {
            return max(0, (int) $expires->getTimestamp() - now()->getTimestamp());
        }

        if ($this->canUserCancelCod()) {
            return max(0, (int) $expires->getTimestamp() - now()->getTimestamp());
        }

        if ($this->canUserCancelUnpaid()) {
            return max(0, (int) $expires->getTimestamp() - now()->getTimestamp());
        }

        return 0;
    }

    public function cancelWindowExpiresAt(): ?\Illuminate\Support\Carbon
    {
        if ($this->payment_expires_at) {
            return $this->payment_expires_at;
        }

        if ($this->isCodPayment() && $this->created_at) {
            return $this->created_at->copy()->addHours(self::CANCEL_WINDOW_HOURS);
        }

        return null;
    }

    public function getOrderNumberAttribute(): string
    {
        return OrderNumber::display((string) $this->id);
    }

    public function isPaymentSuccessful(): bool
    {
        return $this->payment_status === self::PAYMENT_SUCCESS;
    }

    public function isCodPayment(): bool
    {
        $channel = strtolower(trim((string) $this->payment_channel));
        if ($channel === 'cod') {
            return true;
        }

        $method = strtolower(trim((string) $this->metode_pembayaran));
        if ($method === '') {
            return false;
        }

        return str_contains($method, 'cash on delivery')
            || preg_match('/\bcod\b/', $method) === 1;
    }

    public function isAwaitingCodPayment(): bool
    {
        return $this->payment_status === self::PAYMENT_PENDING && $this->isCodPayment();
    }

    public function hasShipped(): bool
    {
        return in_array(strtolower((string) $this->status), self::SHIPPED_STATUSES, true);
    }

    public function canUserCancelCod(): bool
    {
        return $this->isCodPayment() && $this->canUserCancelUnpaid();
    }

    public function canUserCancelUnpaid(): bool
    {
        if ($this->payment_status !== self::PAYMENT_PENDING) {
            return false;
        }

        $status = strtolower((string) $this->status);
        if ($status === 'dibatalkan' || $this->hasShipped()) {
            return false;
        }

        if ($this->isCodPayment()) {
            return true;
        }

        return $this->isAwaitingOnlinePayment();
    }

    public function isAwaitingOnlinePayment(): bool
    {
        if ($this->payment_status !== self::PAYMENT_PENDING) {
            return false;
        }

        if (! in_array($this->payment_channel, ['qris', 'va'], true)) {
            return false;
        }

        if ($this->payment_expires_at && $this->payment_expires_at->isPast()) {
            return false;
        }

        return true;
    }

    public function isAwaitingAnyPayment(): bool
    {
        return $this->isAwaitingOnlinePayment() || $this->isAwaitingCodPayment();
    }

    /**
     * Only successful payments count toward admin revenue.
     *
     * @param  Builder<Order>  $query
     * @return Builder<Order>
     */
    public function scopeSuccessfulPayment(Builder $query): Builder
    {
        return $query->where('payment_status', self::PAYMENT_SUCCESS);
    }

    /**
     * @param  Builder<Order>  $query
     * @return Builder<Order>
     */
    public function scopeAwaitingOnlinePayment(Builder $query): Builder
    {
        return $query
            ->where('payment_status', self::PAYMENT_PENDING)
            ->whereIn('payment_channel', ['qris', 'va'])
            ->where(function ($q) {
                $q->whereNull('payment_expires_at')
                    ->orWhere('payment_expires_at', '>', now());
            });
    }

    /**
     * Online window (QRIS/VA) plus unpaid COD until admin confirms collection.
     *
     * @param  Builder<Order>  $query
     * @return Builder<Order>
     */
    public function scopeAwaitingAnyPayment(Builder $query): Builder
    {
        return $query->where('payment_status', self::PAYMENT_PENDING)
            ->where(function ($q) {
                $q->where(function ($online) {
                    $online->whereIn('payment_channel', ['qris', 'va'])
                        ->where(function ($exp) {
                            $exp->whereNull('payment_expires_at')
                                ->orWhere('payment_expires_at', '>', now());
                        });
                })->orWhere('payment_channel', 'cod')
                    ->orWhereRaw('LOWER(COALESCE(metode_pembayaran, "")) LIKE ?', ['%cash on delivery%'])
                    ->orWhereRaw('LOWER(COALESCE(metode_pembayaran, "")) = ?', ['cod']);
            });
    }

    /**
     * Resolve payment status for a new checkout.
     * Online methods stay pending until gateway confirms; COD → pending unless explicit.
     */
    public static function resolveCheckoutPaymentStatus(
        ?string $paymentMethod,
        ?string $explicitStatus = null,
    ): string {
        if (
            is_string($explicitStatus)
            && in_array($explicitStatus, self::PAYMENT_STATUSES, true)
        ) {
            return $explicitStatus;
        }

        return self::PAYMENT_PENDING;
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Invoice root for multi-line checkouts (INV-ts-rand[-n] → INV-ts-rand).
     */
    public static function invoiceRoot(string $orderId): string
    {
        return OrderNumber::invoiceRoot($orderId);
    }

    /**
     * Whether any order line still exists for this invoice root.
     */
    public static function existsForInvoice(string $invoiceRoot): bool
    {
        $invoiceRoot = self::invoiceRoot($invoiceRoot);

        return self::query()
            ->where(function ($q) use ($invoiceRoot) {
                $q->where('id', $invoiceRoot)
                    ->orWhere('id', 'like', $invoiceRoot.'-%');
            })
            ->exists();
    }

    /**
     * Map fulfillment status to a storefront/admin tracking label.
     */
    public static function trackingStatusLabel(?string $status): string
    {
        return match (strtolower((string) $status)) {
            'menunggu_konfirmasi' => 'Menunggu Konfirmasi',
            'pengemasan' => 'Dikemas',
            'dalam_perjalanan' => 'Dalam Perjalanan',
            'diterima', 'selesai' => 'Terkirim',
            'dibatalkan' => 'Dibatalkan',
            default => 'Diproses',
        };
    }
}
