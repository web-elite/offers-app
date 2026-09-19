<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserSubmission extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUSES = [self::STATUS_PENDING, self::STATUS_APPROVED, self::STATUS_REJECTED];

    protected $fillable = ['provider_name', 'url', 'offer_note_fa', 'contact', 'status'];
}
