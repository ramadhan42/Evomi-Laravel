<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactReply extends Model
{
    use HasFactory;

    protected $fillable = ['contact_message_id', 'reply_message', 'replied_by', 'is_read_by_user'];

    // Tambahkan baris ini:
    protected $casts = [
        'is_read_by_user' => 'boolean',
    ];
    /**
     * Relasi balik ke pesan utama.
     */
    public function contactMessage()
    {
        return $this->belongsTo(ContactMessage::class);
    }
}