<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuizPersonalityResult extends Model
{
    protected $fillable = [
        'personality_key',
        'title',
        'title_en',
        'description',
        'description_en',
        'color',
        'bg_image',
        'product_image',
        'product_image_width_mobile',
        'product_image_width_desktop',
        'bg_image_width_mobile',
        'bg_image_width_desktop',
        'forced_product_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'product_image_width_mobile' => 'integer',
            'product_image_width_desktop' => 'integer',
            'bg_image_width_mobile' => 'integer',
            'bg_image_width_desktop' => 'integer',
        ];
    }
}
