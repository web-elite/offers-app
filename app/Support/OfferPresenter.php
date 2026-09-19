<?php

namespace App\Support;

use App\Models\Offer;
use App\Models\CrawlRun;
use App\Models\OfferVersion;

/**
 * Persian presentation helpers for the public offer UI.
 *
 * All strings are user facing (fa); identifiers stay English.
 */
class OfferPresenter
{
    /** Verification methods a user actually has to satisfy. */
    public const REAL_VERIFICATION_KEYS = ['email', 'phone', 'card', 'identity', 'telegram', 'discord'];

    public const ACCESS_LABELS = [
        Offer::ACCESS_API => 'API',
        Offer::ACCESS_WEB => 'وب',
        Offer::ACCESS_CHAT_UI => 'چت',
        Offer::ACCESS_IDE => 'IDE',
        Offer::ACCESS_TELEGRAM_BOT => 'ربات تلگرام',
        Offer::ACCESS_UNKNOWN => 'نامشخص',
    ];

    public const TIER_LABELS = [
        Offer::TIER_FOREVER_FREE => 'رایگان همیشگی',
        Offer::TIER_DAILY_RESET => 'ریست روزانه',
        Offer::TIER_MONTHLY_CREDIT => 'اعتبار ماهانه',
        Offer::TIER_SIGNUP_BONUS => 'هدیه ثبت‌نام',
        Offer::TIER_REFERRAL_BONUS => 'پاداش معرفی',
        Offer::TIER_TRIAL => 'دوره آزمایشی',
        Offer::TIER_FREE_MODELS => 'مدل‌های رایگان',
        Offer::TIER_FREE_CREDITS => 'اعتبار رایگان',
        Offer::TIER_UNKNOWN => 'نامشخص',
    ];

    public const PRICING_LABELS = [
        Offer::PRICING_FREE => 'رایگان',
        Offer::PRICING_FREEMIUM => 'فریمیوم',
        Offer::PRICING_TRIAL => 'دوره آزمایشی',
        Offer::PRICING_PAY_AS_YOU_GO => 'پرداخت به‌ازای مصرف',
        Offer::PRICING_SUBSCRIPTION => 'اشتراکی',
    ];

    public const STATUS_LABELS = [
        Offer::STATUS_ACTIVE => 'فعال',
        Offer::STATUS_EXPIRED => 'منقضی شده',
        Offer::STATUS_TEMPORARILY_UNAVAILABLE => 'موقتاً در دسترس نیست',
        Offer::STATUS_REQUIRES_VERIFICATION => 'نیازمند تأیید هویت',
        Offer::STATUS_MANUAL_REVIEW => 'در حال بررسی دستی',
        Offer::STATUS_UNKNOWN => 'نامشخص',
    ];

    public const REPORT_LABELS = [
        'expired' => 'منقضی شده است',
        'fake' => 'جعلی / کلاهبرداری',
        'broken_link' => 'لینک خراب است',
        'not_free_anymore' => 'دیگر رایگان نیست',
        'wrong_info' => 'اطلاعات نادرست است',
        'unexpected_verification' => 'تأیید هویت غیرمنتظره',
        'other' => 'موضوع دیگر',
    ];

    public const VERSION_SOURCE_LABELS = [
        OfferVersion::SOURCE_CRAWLER => 'خزنده',
        OfferVersion::SOURCE_ADMIN => 'مدیر',
        OfferVersion::SOURCE_USER_REPORT => 'گزارش کاربر',
        OfferVersion::SOURCE_AI => 'تحلیل AI',
    ];

    /** Short badge text when no numeric quota is known. */
    private const TIER_BADGES = [
        Offer::TIER_FOREVER_FREE => 'رایگان همیشگی',
        Offer::TIER_DAILY_RESET => 'ریست روزانه',
        Offer::TIER_MONTHLY_CREDIT => 'اعتبار ماهانه',
        Offer::TIER_SIGNUP_BONUS => 'هدیه ثبت‌نام',
        Offer::TIER_REFERRAL_BONUS => 'پاداش معرفی',
        Offer::TIER_TRIAL => 'دوره آزمایشی',
        Offer::TIER_FREE_MODELS => 'مدل‌های رایگان',
        Offer::TIER_FREE_CREDITS => 'اعتبار رایگان',
        Offer::TIER_UNKNOWN => 'سهمیه نامشخص',
    ];

    /**
     * Amount badge, e.g. «۷۵$» / «۱M توکن/روز» / «۴ مدل رایگان».
     */
    public static function amountBadge(Offer $offer): string
    {
        $amount = $offer->credits_amount;

        if ($amount === null) {
            return self::TIER_BADGES[$offer->free_tier_type] ?? 'رایگان';
        }

        $value = match ($offer->credits_unit) {
            'token' => self::compact($amount).' توکن',
            'credit' => self::compact($amount).' اعتبار',
            'model' => FaDigits::convert($amount).' مدل رایگان',
            'domain' => FaDigits::convert($amount).' دامنه رایگان',
            'usd' => FaDigits::convert(self::grouped($amount)).'$',
            default => FaDigits::convert(self::grouped($amount)),
        };

        if ($offer->credits_unit === 'token' && $offer->free_tier_type === Offer::TIER_DAILY_RESET) {
            $value .= '/روز';
        }

        return $value;
    }

    /** Verification chips (only methods a user must actually satisfy). */
    public static function verificationChips(Offer $offer): array
    {
        return $offer->verificationMethods
            ->filter(fn ($method) => in_array($method->key, self::REAL_VERIFICATION_KEYS, true))
            ->map(fn ($method) => ['key' => $method->key, 'label' => $method->label_fa])
            ->values()
            ->all();
    }

    /** Access type labels, e.g. ['API', 'وب']. */
    public static function accessLabels(Offer $offer): array
    {
        return collect($offer->access_types ?? [])
            ->map(fn (string $type) => self::ACCESS_LABELS[$type] ?? $type)
            ->unique()
            ->values()
            ->all();
    }

    public static function pricingLabel(Offer $offer): ?string
    {
        if ($offer->pricing_type === null) {
            return null;
        }

        $label = self::PRICING_LABELS[$offer->pricing_type] ?? $offer->pricing_type;

        if ($offer->price !== null && (float) $offer->price > 0) {
            $label .= ' — از '.FaDigits::convert(number_format((float) $offer->price, 0, '.', '٬')).' '.$offer->currency;
        }

        return $label;
    }

    public static function versionSummary(OfferVersion $version): string
    {
        $changed = array_keys($version->new_data ?? []);

        return $changed === [] ? 'به‌روزرسانی داده‌ها' : implode('، ', $changed);
    }

    /**
     * Payload consumed by the quick-view dialog (rendered as a JSON island).
     *
     * @param  array{models?: array<int, string>, crawled_at?: \DateTimeInterface|null}|null  $crawl
     */
    public static function quickviewPayload(Offer $offer, ?array $crawl = null): array
    {
        $chips = self::verificationChips($offer);
        $freeModels = $crawl['models'] ?? [];
        $crawledAt = $crawl['crawled_at'] ?? null;
        $lastRunStatus = $crawl['status'] ?? null;

        return [
            'slug' => $offer->slug,
            'title' => $offer->title_fa,
            'provider' => $offer->provider?->name,
            'category' => $offer->provider?->category?->name_fa,
            'detail_url' => route('public.offers.show', $offer->slug),
            'go_url' => route('public.go', $offer->slug),
            'description' => $offer->description_fa ?? '',
            'note' => $offer->raw_note_fa ?? '',
            'badge' => self::amountBadge($offer),
            'tier' => self::TIER_LABELS[$offer->free_tier_type] ?? null,
            'status' => self::STATUS_LABELS[$offer->status] ?? null,
            'pricing' => self::pricingLabel($offer),
            'verification' => array_column($chips, 'label'),
            'no_verification' => $chips === [],
            'access' => self::accessLabels($offer),
            'models' => $offer->aiModels->pluck('name')->values()->all(),
            'free_models' => array_values($freeModels),
            'free_models_status' => $lastRunStatus,
            'last_crawl' => FaDigits::diffForHumans($crawledAt, ''),
            'last_verified' => FaDigits::diffForHumans($offer->last_verified_at, ''),
            'versions' => $offer->versions
                ->sortByDesc('detected_at')
                ->take(3)
                ->map(fn (OfferVersion $version) => [
                    'date' => FaDigits::diffForHumans($version->detected_at, ''),
                    'source' => self::VERSION_SOURCE_LABELS[$version->source] ?? $version->source,
                    'summary' => self::versionSummary($version),
                ])
                ->values()
                ->all(),
        ];
    }

    private static function grouped(int $amount): string
    {
        return number_format($amount, 0, '.', '٬');
    }

    /** 1_000_000 → «۱M», 45_000 → «۴۵K». */
    private static function compact(int $amount): string
    {
        if ($amount >= 1_000_000) {
            return FaDigits::convert(self::trimDecimal($amount / 1_000_000)).'M';
        }

        if ($amount >= 10_000) {
            return FaDigits::convert(self::trimDecimal($amount / 1_000)).'K';
        }

        return FaDigits::convert(self::grouped($amount));
    }

    private static function trimDecimal(float $value): string
    {
        return rtrim(rtrim(number_format($value, 1, '.', ''), '0'), '.');
    }

    /** Output the badge for a crawl result's free model list. */
    public static function crawlStatusLabel(?string $status): ?string
    {
        return match ($status) {
            CrawlRun::STATUS_OK => 'آخرین بررسی خودکار موفق',
            CrawlRun::STATUS_PARSE_FAILED, CrawlRun::STATUS_CHANGED_LAYOUT => 'آخرین بررسی خودکار ناقص',
            CrawlRun::STATUS_HTTP_ERROR, CrawlRun::STATUS_TIMEOUT => 'آخرین بررسی خودکار ناموفق',
            CrawlRun::STATUS_CAPTCHA, CrawlRun::STATUS_BLOCKED => 'آخرین بررسی خودکار مسدود شد',
            CrawlRun::STATUS_NOT_FOUND => 'آخرین بررسی خودکار یافت نشد',
            default => null,
        };
    }
}
