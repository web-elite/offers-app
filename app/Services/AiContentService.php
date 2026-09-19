<?php

namespace App\Services;

use App\Models\Offer;
use RuntimeException;
use App\Models\Category;
use App\Models\Provider;
use Illuminate\Support\Str;
use App\Models\OfferVersion;
use App\Models\TelegramMessage;
use App\Models\AiAnalysisResult;

/**
 * Materializes a structured LLM response into the content model
 * (Provider + Offer + OfferVersion audit row).
 *
 * Mirrors the SubmissionController::approve pattern:
 *   - upsert a Provider by name
 *   - create an Offer with the requested status
 *   - write an OfferVersion audit row with source = 'ai'
 */
class AiContentService
{
    /**
     * Create the offer row from a successful LLM analysis.
     *
     * @param  array<string,mixed>  $analysis  the parsed LLM JSON
     * @param  ?TelegramMessage  $telegramMessage  origin message (optional)
     * @param  ?AiAnalysisResult  $result  the AiAnalysisResult row to update
     * @return Offer
     */
    public function materialize(array $analysis, ?TelegramMessage $telegramMessage, ?AiAnalysisResult $result): Offer
    {
        $providerName = trim((string) ($analysis['provider_name'] ?? ''));
        $offerTitle = trim((string) ($analysis['offer_title'] ?? ''));

        if ($providerName === '') {
            throw new RuntimeException('LLM did not return a provider_name.');
        }

        // 1. Upsert the provider.
        $provider = $this->findOrCreateProvider($analysis);

        // 2. Build the offer payload.
        $slug = $this->uniqueSlug($analysis, $provider);
        $tierType = $this->normalizeTierType((string) ($analysis['free_tier_type'] ?? ''));
        $accessTypes = $this->normalizeAccessTypes($analysis['access_types'] ?? []);
        $status = $this->normalizeStatus((string) ($analysis['offer_status'] ?? Offer::STATUS_ACTIVE));

        $offer = Offer::create([
            'provider_id' => $provider->id,
            'slug' => $slug,
            'title_fa' => $offerTitle !== '' ? $offerTitle : 'آفر '.Str::limit($providerName, 80),
            'description_fa' => (string) ($analysis['offer_description'] ?? ''),
            'free_tier_type' => $tierType,
            'status' => $status,
            'pricing_type' => $this->normalizePricingType((string) ($analysis['pricing_type'] ?? '')),
            'credits_amount' => isset($analysis['credits_amount']) ? (int) $analysis['credits_amount'] : null,
            'credits_unit' => $this->normalizeCreditsUnit((string) ($analysis['credits_unit'] ?? '')),
            'access_types' => $accessTypes,
            'raw_note_fa' => $telegramMessage?->text,
            'last_verified_at' => now(),
            'published_at' => $status === Offer::STATUS_ACTIVE ? now() : null,
        ]);

        // 3. Audit row.
        OfferVersion::create([
            'offer_id' => $offer->id,
            'old_data' => null,
            'new_data' => [
                'provider' => $provider->name,
                'source' => 'telegram+ai',
                'tier' => $tierType,
                'access' => $accessTypes,
            ],
            'detected_at' => now(),
            'source' => OfferVersion::SOURCE_AI,
        ]);

        // 4. Back-fill the analysis result.
        if ($result !== null) {
            $result->offer_slug = $slug;
            $result->offer_status = $status;
            $result->provider_name = $provider->name;
            $result->provider_slug = $provider->slug;
            $result->materialized = true;
            $result->save();
        }

        if ($telegramMessage !== null) {
            $telegramMessage->offer_id = $offer->id;
            $telegramMessage->save();
        }

        return $offer;
    }

    private function findOrCreateProvider(array $analysis): Provider
    {
        $name = trim((string) ($analysis['provider_name'] ?? ''));
        $slug = $this->uniqueSlug($analysis, null);

        $provider = Provider::query()->where('slug', $slug)->first();

        if ($provider === null) {
            $provider = Provider::query()->where('name', $name)->first();
        }

        if ($provider === null) {
            $provider = Provider::create([
                'slug' => $slug,
                'name' => $name,
                'category_id' => Category::query()->orderBy('sort_order')->value('id'),
                'canonical_url' => (string) ($analysis['provider_url'] ?? ''),
                'description_fa' => (string) ($analysis['provider_description'] ?? ''),
                'status' => Provider::STATUS_ACTIVE,
            ]);
        } else {
            // Refresh canonical_url when the LLM provides a new one.
            $newUrl = (string) ($analysis['provider_url'] ?? '');
            if ($newUrl !== '' && $provider->canonical_url !== $newUrl) {
                $provider->forceFill(['canonical_url' => $newUrl])->save();
            }
        }

        return $provider;
    }

    private function uniqueSlug(array $analysis, ?Provider $provider): string
    {
        // Prefer an explicit offer_slug from the LLM; otherwise derive from provider.
        $base = (string) ($analysis['offer_slug'] ?? '');
        if ($base === '') {
            $base = Str::slug((string) ($analysis['provider_name'] ?? 'provider'));
        }
        if ($base === '') {
            $base = 'offer';
        }

        $slug = $base;
        $i = 2;

        while (Offer::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }

    private function normalizeTierType(string $raw): string
    {
        $raw = strtolower(trim($raw));

        if (in_array($raw, Offer::FREE_TIER_TYPES, true)) {
            return $raw;
        }

        return Offer::TIER_UNKNOWN;
    }

    private function normalizeAccessTypes(array $raw): array
    {
        $clean = [];

        foreach ($raw as $t) {
            $t = strtolower(trim((string) $t));
            if (in_array($t, Offer::ACCESS_TYPES, true)) {
                $clean[] = $t;
            }
        }

        return $clean !== [] ? array_values(array_unique($clean)) : [Offer::ACCESS_UNKNOWN];
    }

    private function normalizeStatus(string $raw): string
    {
        $raw = strtolower(trim($raw));

        return in_array($raw, Offer::STATUSES, true) ? $raw : Offer::STATUS_ACTIVE;
    }

    private function normalizePricingType(string $raw): ?string
    {
        $raw = strtolower(trim($raw));

        return in_array($raw, Offer::PRICING_TYPES, true) ? $raw : null;
    }

    private function normalizeCreditsUnit(string $raw): ?string
    {
        $raw = strtolower(trim($raw));

        return in_array($raw, Offer::CREDIT_UNITS, true) ? $raw : null;
    }
}
