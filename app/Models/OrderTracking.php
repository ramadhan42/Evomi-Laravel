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
}
