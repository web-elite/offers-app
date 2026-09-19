<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramMessage extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSED = 'processed';
    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED = 'failed';
    public const STATUS_SKIPPED = 'skipped';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_PROCESSED,
        self::STATUS_SUCCESS,
        self::STATUS_FAILED,
        self::STATUS_SKIPPED,
    ];

    protected $fillable = [
        'telegram_message_id', 'chat_id', 'sender_name', 'raw_payload', 'text', 'url',
        'status', 'error', 'ai_analysis_result_id', 'offer_id',
    ];

    protected function casts(): array
    {
        return [
            'raw_payload' => 'array',
        ];
    }

    public function aiAnalysisResult(): BelongsTo
    {
        return $this->belongsTo(AiAnalysisResult::class, 'ai_analysis_result_id');
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class, 'offer_id');
    }
}
