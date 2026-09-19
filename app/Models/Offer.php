<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Offer extends Model
{
    public const TIER_FOREVER_FREE = 'forever_free';
    public const TIER_DAILY_RESET = 'daily_reset';
    public const TIER_MONTHLY_CREDIT = 'monthly_credit';
    public const TIER_SIGNUP_BONUS = 'signup_bonus';
    public const TIER_REFERRAL_BONUS = 'referral_bonus';
    public const TIER_TRIAL = 'trial';
    public const TIER_FREE_MODELS = 'free_models';
    public const TIER_FREE_CREDITS = 'free_credits';
    public const TIER_UNKNOWN = 'unknown';

    public const FREE_TIER_TYPES = [
        self::TIER_FOREVER_FREE,
        self::TIER_DAILY_RESET,
        self::TIER_MONTHLY_CREDIT,
        self::TIER_SIGNUP_BONUS,
        self::TIER_REFERRAL_BONUS,
        self::TIER_TRIAL,
        self::TIER_FREE_MODELS,
        self::TIER_FREE_CREDITS,
        self::TIER_UNKNOWN,
    ];

    public const STATUS_ACTIVE = 'active';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_TEMPORARILY_UNAVAILABLE = 'temporarily_unavailable';
    public const STATUS_REQUIRES_VERIFICATION = 'requires_verification';
    public const STATUS_MANUAL_REVIEW = 'manual_review';
    public const STATUS_UNKNOWN = 'unknown';

    public const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_EXPIRED,
        self::STATUS_TEMPORARILY_UNAVAILABLE,
        self::STATUS_REQUIRES_VERIFICATION,
        self::STATUS_MANUAL_REVIEW,
        self::STATUS_UNKNOWN,
    ];

    public const PRICING_FREE = 'free';
    public const PRICING_FREEMIUM = 'freemium';
    public const PRICING_TRIAL = 'trial';
    public const PRICING_PAY_AS_YOU_GO = 'pay_as_you_go';
    public const PRICING_SUBSCRIPTION = 'subscription';

    public const PRICING_TYPES = [
        self::PRICING_FREE,
        self::PRICING_FREEMIUM,
        self::PRICING_TRIAL,
        self::PRICING_PAY_AS_YOU_GO,
        self::PRICING_SUBSCRIPTION,
    ];

    public const ACCESS_API = 'api';
    public const ACCESS_WEB = 'web';
    public const ACCESS_CHAT_UI = 'chat_ui';
    public const ACCESS_IDE = 'ide';
    public const ACCESS_TELEGRAM_BOT = 'telegram_bot';
    public const ACCESS_UNKNOWN = 'unknown';

    public const ACCESS_TYPES = [
        self::ACCESS_API,
        self::ACCESS_WEB,
        self::ACCESS_CHAT_UI,
        self::ACCESS_IDE,
        self::ACCESS_TELEGRAM_BOT,
        self::ACCESS_UNKNOWN,
    ];

    public const CREDIT_UNITS = ['usd', 'credit', 'token', 'model', 'domain'];

    /** Tier types that yield usable free capacity without a paid commitment. */
    public const FREE_ONLY_TIERS = [
        self::TIER_FOREVER_FREE,
        self::TIER_DAILY_RESET,
        self::TIER_FREE_MODELS,
        self::TIER_FREE_CREDITS,
        self::TIER_SIGNUP_BONUS,
        self::TIER_REFERRAL_BONUS,
    ];

    protected $fillable = [
        'provider_id', 'slug', 'title_fa', 'description_fa', 'free_tier_type', 'status',
        'pricing_type', 'price', 'currency', 'credits_amount', 'credits_unit', 'raw_note_fa',
        'access_types', 'last_verified_at', 'last_crawl_at', 'next_crawl_at', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'access_types' => 'array',
            'price' => 'decimal:2',
            'credits_amount' => 'integer',
            'last_verified_at' => 'datetime',
            'last_crawl_at' => 'datetime',
            'next_crawl_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function verificationMethods(): BelongsToMany
    {
        return $this->belongsToMany(VerificationMethod::class);
    }

    public function aiModels(): BelongsToMany
    {
        return $this->belongsToMany(AiModel::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(OfferVersion::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(OfferReport::class);
    }

    public function overrides(): HasMany
    {
        return $this->hasMany(AdminOverride::class);
    }

    public function analyticsEvents(): HasMany
    {
        return $this->hasMany(AnalyticsEvent::class);
    }

    /** Offers that are free to use without a paid commitment. */
    public function scopeFreeOnly(Builder $query): Builder
    {
        return $query->whereIn('free_tier_type', self::FREE_ONLY_TIERS);
    }

    /** Offers requiring at least one of the given verification keys. */
    public function scopeHasVerification(Builder $query, array $keys): Builder
    {
        return $query->whereHas('verificationMethods', fn (Builder $q) => $q->whereIn('key', $keys));
    }

    /** Offers requiring none of the given verification keys. */
    public function scopeWithoutVerification(Builder $query, array $keys): Builder
    {
        return $query->whereDoesntHave('verificationMethods', fn (Builder $q) => $q->whereIn('key', $keys));
    }

    public function scopeFreeTierType(Builder $query, string $type): Builder
    {
        return $query->where('free_tier_type', $type);
    }

    public function scopeAccessType(Builder $query, string $type): Builder
    {
        return $query->whereJsonContains('access_types', $type);
    }

    public function scopeStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeVerifiedSince(Builder $query, \DateTimeInterface $since): Builder
    {
        return $query->where('last_verified_at', '>=', $since);
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        $term = trim($term);

        if ($term === '') {
            return $query;
        }

        $like = '%'.$term.'%';

        return $query->where(function (Builder $q) use ($like) {
            $q->where('title_fa', 'like', $like)
                ->orWhere('description_fa', 'like', $like)
                ->orWhere('raw_note_fa', 'like', $like)
                ->orWhereHas('provider', fn (Builder $p) => $p->where('name', 'like', $like)->orWhere('slug', 'like', $like));
        });
    }
}
