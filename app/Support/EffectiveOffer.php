<?php

namespace App\Support;

use App\Models\AdminOverride;
use App\Models\CrawlResult;
use App\Models\CrawlRun;
use App\Models\Offer;
use Illuminate\Support\Collection;

/**
 * Effective (public-facing) offer values.
 *
 * Precedence per field:
 *   1. latest admin_overrides row for that field,
 *   2. latest ok crawl_result mapping (title from extracted_json.title,
 *      credits from crawl_results; tier is never derived from crawls),
 *   3. the stored offer column.
 */
class EffectiveOffer
{
    public const FIELDS = ['title_fa', 'credits_amount', 'credits_unit', 'free_tier_type', 'status'];

    /** Effective values for a single offer (self-contained queries). */
    public static function values(Offer $offer): array
    {
        $overrides = self::latestOverrides(collect([$offer->id]));
        $crawls = self::latestCrawls($offers = collect([$offer]));

        $values = [];

        foreach (self::FIELDS as $field) {
            $values[$field] = self::resolve(
                $field,
                $offer,
                $overrides[$offer->id][$field] ?? null,
                $crawls[$offer->provider_id] ?? null,
            );
        }

        return $values;
    }

    /**
     * Hydrate effective values onto a collection of offers (batched, no N+1).
     * The public UI keeps reading model attributes afterwards.
     *
     * @param  Collection<int, Offer>  $offers
     */
    public static function hydrate(Collection $offers): void
    {
        if ($offers->isEmpty()) {
            return;
        }

        $overrides = self::latestOverrides($offers->pluck('id'));
        $crawls = self::latestCrawls($offers);

        foreach ($offers as $offer) {
            foreach (self::FIELDS as $field) {
                $offer->setAttribute(
                    $field,
                    self::resolve($field, $offer, $overrides[$offer->id][$field] ?? null, $crawls[$offer->provider_id] ?? null),
                );
            }
        }
    }

    /**
     * @param  Collection<int, int>  $offerIds
     * @return array<int, array<string, string>> offer_id => field => override value (latest row only)
     */
    private static function latestOverrides(Collection $offerIds): array
    {
        $map = [];

        AdminOverride::query()
            ->whereIn('offer_id', $offerIds)
            ->orderByDesc('id')
            ->get()
            ->each(function (AdminOverride $override) use (&$map): void {
                $map[$override->offer_id][$override->field] ??= $override->override_value;
            });

        return $map;
    }

    /**
     * Latest ok crawl result per provider.
     *
     * @param  Collection<int, Offer>  $offers
     * @return array<int, CrawlResult>
     */
    private static function latestCrawls(Collection $offers): array
    {
        $providerIds = $offers->pluck('provider_id')->filter()->unique()->values();

        if ($providerIds->isEmpty()) {
            return [];
        }

        $results = CrawlResult::query()
            ->select('crawl_results.*', 'crawl_sources.provider_id')
            ->join('crawl_runs', 'crawl_runs.id', '=', 'crawl_results.crawl_run_id')
            ->join('crawl_sources', 'crawl_sources.id', '=', 'crawl_runs.crawl_source_id')
            ->whereIn('crawl_sources.provider_id', $providerIds)
            ->where('crawl_runs.status', CrawlRun::STATUS_OK)
            ->orderByDesc('crawl_runs.started_at')
            ->get();

        $map = [];

        foreach ($results as $result) {
            $map[$result->provider_id] ??= $result; // keep only the latest ok run per provider
        }

        return $map;
    }

    private static function resolve(string $field, Offer $offer, ?string $override, ?CrawlResult $crawl): mixed
    {
        if ($override !== null) {
            return self::castOverride($field, $override);
        }

        return match ($field) {
            // free_models and tier/status are intentionally NOT derived from crawls
            'title_fa' => self::crawlTitle($crawl) ?? $offer->title_fa,
            'credits_amount' => $crawl?->credits_amount ?? $offer->credits_amount,
            'credits_unit' => $crawl?->credits_unit ?? $offer->credits_unit,
            default => $offer->{$field},
        };
    }

    private static function crawlTitle(?CrawlResult $crawl): ?string
    {
        $title = $crawl?->extracted_json['title'] ?? null;

        return (is_string($title) && trim($title) !== '') ? trim($title) : null;
    }

    private static function castOverride(string $field, string $value): mixed
    {
        return $field === 'credits_amount' ? (int) $value : $value;
    }
}
