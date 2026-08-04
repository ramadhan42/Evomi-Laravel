<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Wishlist extends Model
{
    // Tambahkan $fillable untuk keamanan mass assignment
    protected $fillable = ['user_id', 'product_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relasi ke Product agar kita bisa memanggil ->with('product')
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}