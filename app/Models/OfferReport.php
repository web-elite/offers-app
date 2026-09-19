<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfferReport extends Model
{
    public const TYPE_EXPIRED = 'expired';
    public const TYPE_FAKE = 'fake';
    public const TYPE_BROKEN_LINK = 'broken_link';
    public const TYPE_NOT_FREE_ANYMORE = 'not_free_anymore';
    public const TYPE_WRONG_INFO = 'wrong_info';
    public const TYPE_UNEXPECTED_VERIFICATION = 'unexpected_verification';
    public const TYPE_OTHER = 'other';

    public const TYPES = [
        self::TYPE_EXPIRED,
        self::TYPE_FAKE,
        self::TYPE_BROKEN_LINK,
        self::TYPE_NOT_FREE_ANYMORE,
        self::TYPE_WRONG_INFO,
        self::TYPE_UNEXPECTED_VERIFICATION,
        self::TYPE_OTHER,
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUSES = [self::STATUS_PENDING, self::STATUS_RESOLVED, self::STATUS_REJECTED];

    protected $fillable = ['offer_id', 'report_type', 'details_fa', 'status'];

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }
}
