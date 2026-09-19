<?php

namespace App\Support;

use App\Models\AnalyticsEvent;

class Analytics
{
    public const EVENT_OFFER_VIEW = 'offer_view';
    public const EVENT_OUTBOUND_CLICK = 'outbound_click';
    public const EVENT_QR_SCAN = 'qr_scan';
    public const EVENT_SEARCH = 'search';
    public const EVENT_FILTER_USE = 'filter_use';
    public const EVENT_AD_CLICK = 'ad_click';
    public const EVENT_AD_IMPRESSION = 'ad_impression';

    public const EVENT_TYPES = [
        self::EVENT_OFFER_VIEW,
        self::EVENT_OUTBOUND_CLICK,
        self::EVENT_QR_SCAN,
        self::EVENT_SEARCH,
        self::EVENT_FILTER_USE,
        self::EVENT_AD_CLICK,
        self::EVENT_AD_IMPRESSION,
    ];

    public static function record(string $type, ?int $offerId = null, array $payload = []): AnalyticsEvent
    {
        return AnalyticsEvent::create([
            'event_type' => $type,
            'offer_id' => $offerId,
            'payload' => $payload !== [] ? $payload : null,
            'created_at' => now(),
        ]);
    }
}
