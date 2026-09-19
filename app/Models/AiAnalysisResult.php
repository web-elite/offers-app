<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiAnalysisResult extends Model
{
    protected $fillable = [
        'telegram_message_id', 'model', 'response',
        'provider_name', 'provider_slug', 'provider_url',
        'offer_title', 'offer_description',
        'free_tier_type', 'access_types',
        'credits_amount', 'credits_unit', 'pricing_type',
        'offer_slug', 'offer_status',
        'materialized', 'error',
    ];

    protected function casts(): array
    {
        return [
            'response' => 'array',
            'access_types' => 'array',
            'credits_amount' => 'integer',
            'materialized' => 'boolean',
        ];
    }

    public function telegramMessage()
    {
        return $this->belongsTo(TelegramMessage::class, 'telegram_message_id');
    }
}
