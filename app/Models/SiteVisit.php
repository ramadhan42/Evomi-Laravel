<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteVisit extends Model
{
    public const TYPE_GUEST = 'guest';

    public const TYPE_USER = 'user';

    public const ONLINE_WINDOW_SECONDS = 300;

    protected $fillable = [
        'visitor_key',
        'user_id',
        'visitor_type',
        'ip_address',
        'country',
        'country_code',
        'region',
        'city',
        'path',
        'full_url',
        'referrer',
        'user_agent',
        'device',
        'browser',
        'platform',
        'visited_at',
        'last_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'visited_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isOnline(): bool
    {
        $at = $this->last_seen_at ?? $this->visited_at;

        return $at !== null && $at->gte(now()->subSeconds(self::ONLINE_WINDOW_SECONDS));
    }
}
