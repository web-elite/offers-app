<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrawlSource extends Model
{
    public const TYPE_WEBSITE = 'website';
    public const TYPE_PRICING = 'pricing';
    public const TYPE_MODELS = 'models';
    public const TYPE_DOCS = 'docs';
    public const TYPE_TELEGRAM = 'telegram';
    public const TYPE_GITHUB = 'github';

    public const SOURCE_TYPES = [
        self::TYPE_WEBSITE,
        self::TYPE_PRICING,
        self::TYPE_MODELS,
        self::TYPE_DOCS,
        self::TYPE_TELEGRAM,
        self::TYPE_GITHUB,
    ];

    protected $fillable = ['provider_id', 'source_type', 'url', 'parser_hint', 'is_enabled', 'crawl_frequency_hours'];

    protected function casts(): array
    {
        return [
            'parser_hint' => 'array',
            'is_enabled' => 'boolean',
            'crawl_frequency_hours' => 'integer',
        ];
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function crawlRuns(): HasMany
    {
        return $this->hasMany(CrawlRun::class);
    }
}
