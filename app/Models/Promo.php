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
}
