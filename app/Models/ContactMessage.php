<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'email', 'subject', 'message', 'is_read_by_admin'];

    // Tambahkan baris ini:
    protected $casts = [
        'is_read_by_admin' => 'boolean',
    ];
    /**
     * Mendapatkan semua riwayat balasan dari admin untuk pesan ini.
     * Diurutkan dari yang paling lama ke paling baru (seperti alur chat).
     */
    public function replies()
    {
        return $this->hasMany(ContactReply::class)->orderBy('created_at', 'asc');
    }
}