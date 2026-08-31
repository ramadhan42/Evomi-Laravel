<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Article extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'title_en',
        'slug',
        'excerpt',
        'excerpt_en',
        'content',
        'content_en',
        'image',
        'category',
        'author',
        'title_font_family',
        'title_font_weight',
        'title_font_style',
        'title_font_size',
        'excerpt_font_family',
        'excerpt_font_weight',
        'excerpt_font_style',
        'excerpt_font_size',
        'content_font_family',
        'content_font_weight',
        'content_font_style',
        'content_font_size',
        'title_heading_level',
        'excerpt_heading_level',
        'content_heading_level',
        'heading_fonts',
        'is_published',
        'published_at',
    ];

    protected $casts = [
        'heading_fonts' => 'array',
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('is_published', true)
            ->where(function (Builder $q) {
                $q->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            });
    }

    public static function makeUniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'artikel';
        $slug = $base;
        $i = 2;

        while (
            static::query()
                ->when($ignoreId, fn (Builder $q) => $q->where('id', '!=', $ignoreId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
