<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Provider extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUSES = [self::STATUS_ACTIVE, self::STATUS_INACTIVE];

    protected $fillable = ['slug', 'name', 'category_id', 'canonical_url', 'referral_url', 'description_fa', 'logo_url', 'status'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class);
    }

    public function crawlSources(): HasMany
    {
        return $this->hasMany(CrawlSource::class);
    }
}
