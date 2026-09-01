<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SeoSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'page',
        'meta_title',
        'meta_title_en',
        'meta_description',
        'meta_description_en',
        'meta_keywords',
        'og_image',
        'noindex',
    ];

    protected $casts = [
        'noindex' => 'boolean',
    ];
}
