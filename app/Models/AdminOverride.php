<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminOverride extends Model
{
    protected $fillable = ['offer_id', 'field', 'crawler_value', 'override_value', 'reason', 'actor'];

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }
}
