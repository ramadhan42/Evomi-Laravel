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
    ];

    protected $appends = [
        'grand_total',
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

    public function isPaymentSuccessful(): bool
    {
        return $this->payment_status === self::PAYMENT_SUCCESS;
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
     * Resolve payment status for a new checkout.
     * QRIS (already settled on frontend) → success; COD → pending unless explicit.
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

        $method = strtolower((string) $paymentMethod);

        if (
            str_contains($method, 'qris')
            || str_contains($method, 'midtrans')
            || str_contains($method, 'xendit')
        ) {
            return self::PAYMENT_SUCCESS;
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
