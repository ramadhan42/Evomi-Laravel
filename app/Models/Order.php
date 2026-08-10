<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    public const PAYMENT_PENDING = 'pending';

    public const PAYMENT_SUCCESS = 'success';

    public const PAYMENT_CANCELLED = 'cancelled';

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
        'payment_window_seconds',
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

    public function getPaymentWindowSecondsAttribute(): int
    {
        if (! $this->payment_expires_at || ! $this->isAwaitingOnlinePayment()) {
            return 0;
        }

        return max(0, (int) $this->payment_expires_at->getTimestamp() - now()->getTimestamp());
    }

    public function isPaymentSuccessful(): bool
    {
        return $this->payment_status === self::PAYMENT_SUCCESS;
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
}
