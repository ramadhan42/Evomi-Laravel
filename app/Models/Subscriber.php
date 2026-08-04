<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscriber extends Model
{
    use HasFactory;

    // Menentukan nama tabel (opsional jika mengikuti konvensi penamaan Laravel)
    protected $table = 'subscribers';

    // Kolom yang diizinkan untuk diisi secara mass-assignment
    protected $fillable = [
        'email',
    ];
}