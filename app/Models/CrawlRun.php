<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrawlRun extends Model
{
    public const STATUS_OK = 'ok';
    public const STATUS_HTTP_ERROR = 'http_error';
    public const STATUS_TIMEOUT = 'timeout';
    public const STATUS_CAPTCHA = 'captcha';
    public const STATUS_BLOCKED = 'blocked';
    public const STATUS_PARSE_FAILED = 'parse_failed';
    public const STATUS_CHANGED_LAYOUT = 'changed_layout';
    public const STATUS_NOT_FOUND = 'not_found';

    public const STATUSES = [
        self::STATUS_OK,
        self::STATUS_HTTP_ERROR,
        self::STATUS_TIMEOUT,
        self::STATUS_CAPTCHA,
        self::STATUS_BLOCKED,
        self::STATUS_PARSE_FAILED,
        self::STATUS_CHANGED_LAYOUT,
        self::STATUS_NOT_FOUND,
    ];

    protected $fillable = [
        'crawl_source_id', 'started_at', 'finished_at', 'http_status', 'status',
        'duration_ms', 'error_message',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'http_status' => 'integer',
            'duration_ms' => 'integer',
        ];
    }

    public function crawlSource(): BelongsTo
    {
        return $this->belongsTo(CrawlSource::class);
    }

    public function crawlResults(): HasMany
    {
        return $this->hasMany(CrawlResult::class);
    }
}
