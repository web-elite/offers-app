<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class VerificationMethod extends Model
{
    public const KEYS = ['none', 'email', 'phone', 'card', 'identity', 'telegram', 'discord', 'unknown'];

    protected $fillable = ['key', 'label_fa'];

    public function offers(): BelongsToMany
    {
        return $this->belongsToMany(Offer::class);
    }
}
