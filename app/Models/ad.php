<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Ad extends Model
{
    public const POSITION_BETWEEN_CARDS = 'between_cards';
    public const POSITION_AFTER_GRID = 'after_grid';
    public const POSITION_HEADER_BANNER = 'header_banner';

    public const POSITIONS = [
        self::POSITION_BETWEEN_CARDS,
        self::POSITION_AFTER_GRID,
        self::POSITION_HEADER_BANNER,
    ];

    public const POSITION_LABELS = [
        self::POSITION_BETWEEN_CARDS => 'بین کارت‌های آفر',
        self::POSITION_AFTER_GRID => 'انتهای فهرست',
        self::POSITION_HEADER_BANNER => 'بنر بالای صفحه',
    ];

    protected $fillable = [
        'title', 'image_url', 'html_snippet', 'target_url', 'position',
        'is_active', 'priority', 'starts_at', 'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'priority' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'impressions' => 'integer',
            'clicks' => 'integer',
        ];
    }

    /** Ads currently eligible to render (active + inside the schedule window). */
    public function scopeActiveNow(Builder $query): Builder
    {
        $now = now();

        return $query->where('is_active', true)
            ->where(function (Builder $q) use ($now) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })
            ->where(function (Builder $q) use ($now) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
            });
    }

    public function scopePosition(Builder $query, string $position): Builder
    {
        return $query->where('position', $position);
    }

    public function ctr(): float
    {
        return $this->impressions > 0 ? round($this->clicks * 100 / $this->impressions, 2) : 0.0;
    }
}
