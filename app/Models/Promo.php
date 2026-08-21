<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Promo extends Model
{
    use HasFactory;

    protected $fillable = [
        'harga_promo',
        'persentase_promo',
        'tanggal_berlaku_promo',
        'tanggal_berakhir_promo',
    ];

    protected $casts = [
        'harga_promo' => 'decimal:2',
        'persentase_promo' => 'decimal:2',
        'tanggal_berlaku_promo' => 'date',
        'tanggal_berakhir_promo' => 'date',
    ];

    /**
     * Promo yang sedang aktif hari ini (berlaku ≤ today ≤ berakhir).
     */
    public function scopeActive(Builder $query): Builder
    {
        $today = now()->toDateString();

        return $query
            ->whereNotNull('tanggal_berlaku_promo')
            ->whereDate('tanggal_berlaku_promo', '<=', $today)
            ->where(function (Builder $q) use ($today) {
                $q->whereNull('tanggal_berakhir_promo')
                    ->orWhereDate('tanggal_berakhir_promo', '>=', $today);
            });
    }

    public static function current(): ?self
    {
        return static::query()->active()->orderByDesc('id')->first();
    }

    /**
     * Satu potongan per checkout/keranjang, bukan per produk.
     * Persentase diprioritaskan; jika kosong memakai harga_promo (nominal tetap).
     */
    public static function discountForSubtotal(float $subtotal): float
    {
        $subtotal = max(0, $subtotal);
        if ($subtotal <= 0) {
            return 0.0;
        }

        $promo = static::current();
        if (! $promo) {
            return 0.0;
        }

        $percent = (float) ($promo->persentase_promo ?? 0);
        $flat = (float) ($promo->harga_promo ?? 0);
        $amount = 0.0;

        if ($percent > 0) {
            $amount = round($subtotal * ($percent / 100), 2);
        } elseif ($flat > 0) {
            $amount = $flat;
        }

        return min(max(0, $amount), $subtotal);
    }
}
