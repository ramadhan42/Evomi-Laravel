<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KurirTarif extends Model
{
    use HasFactory;

    protected $fillable = [
        'kurir_id',
        'kota_asal',
        'kota_tujuan',
        'berat_min_gram',
        'berat_max_gram',
        'harga',
        'estimasi_hari',
        'is_active',
    ];

    protected $casts = [
        'berat_min_gram' => 'integer',
        'berat_max_gram' => 'integer',
        'harga' => 'decimal:2',
        'estimasi_hari' => 'integer',
        'is_active' => 'boolean',
    ];

    public function kurir(): BelongsTo
    {
        return $this->belongsTo(Kurir::class, 'kurir_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

