<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfferVersion extends Model
{
    public const SOURCE_CRAWLER = 'crawler';
    public const SOURCE_ADMIN = 'admin';
    public const SOURCE_USER_REPORT = 'user_report';
    public const SOURCE_AI = 'ai';
    public const SOURCES = [self::SOURCE_CRAWLER, self::SOURCE_ADMIN, self::SOURCE_USER_REPORT, self::SOURCE_AI];

    protected $fillable = ['offer_id', 'old_data', 'new_data', 'detected_at', 'source'];

    protected function casts(): array
    {
        return [
            'old_data' => 'array',
            'new_data' => 'array',
            'detected_at' => 'datetime',
        ];
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }
}
