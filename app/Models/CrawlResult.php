<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrawlResult extends Model
{
    protected $fillable = [
        'crawl_run_id', 'extracted_json', 'free_models', 'credits_amount', 'credits_unit',
        'requires_card', 'requires_phone', 'evidence_text',
    ];

    protected function casts(): array
    {
        return [
            'extracted_json' => 'array',
            'free_models' => 'array',
            'credits_amount' => 'integer',
            'requires_card' => 'boolean',
            'requires_phone' => 'boolean',
        ];
    }

    public function crawlRun(): BelongsTo
    {
        return $this->belongsTo(CrawlRun::class);
    }
}
