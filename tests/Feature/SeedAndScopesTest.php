<?php

namespace Tests\Feature;

use App\Models\CrawlResult;
use App\Models\CrawlRun;
use App\Models\Offer;
use App\Models\Provider;
use App\Models\VerificationMethod;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeedAndScopesTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeders_produce_full_dataset_with_relations(): void
    {
        $this->seed();

        $this->assertGreaterThanOrEqual(20, Provider::count());
        $this->assertGreaterThanOrEqual(20, Offer::count());

        // verification pivot (apimaster: telegram + discord from user list)
        $apimaster = Offer::query()->where('slug', 'apimaster')->with('verificationMethods')->first();
        $this->assertNotNull($apimaster);
        $this->assertSame(['discord', 'telegram'], $apimaster->verificationMethods->pluck('key')->sort()->values()->all());

        // offer ↔ ai_model pivot
        $duck = Offer::query()->where('slug', 'duck-ai')->with('aiModels')->first();
        $this->assertNotNull($duck);
        $this->assertTrue($duck->aiModels->contains('slug', 'gpt-5.6'));

        $vsllm = Offer::query()->where('slug', 'vsllm')->with('aiModels')->first();
        $this->assertNotNull($vsllm);
        $this->assertTrue($vsllm->aiModels->contains('slug', 'glm-4.7-flash-free'));

        // tags derived from free_tier_type + access_types mapping
        $this->assertTrue($duck->tags->contains('slug', 'web-chat'));
        $this->assertFalse($duck->tags->contains('slug', 'api-access'));

        $llm7 = Offer::query()->where('slug', 'llm7')->with('tags')->first();
        $this->assertNotNull($llm7);
        $this->assertTrue($llm7->tags->contains('slug', 'daily-reset'));
        $this->assertTrue($llm7->tags->contains('slug', 'api-access'));

        // demo crawl data ("free models today")
        $this->assertSame(2, CrawlRun::where('status', 'ok')->count());
        $this->assertTrue(CrawlResult::query()->whereJsonContains('free_models', 'gpt-5.6')->exists());
        $this->assertTrue(CrawlResult::query()->whereJsonContains('free_models', 'glm-4.7-flash-free')->exists());
        $this->assertNotNull($vsllm->refresh()->last_crawl_at);
        $this->assertNotNull($vsllm->refresh()->next_crawl_at);

        // freshness demo
        $this->assertNotNull($apimaster->last_verified_at);
    }

    public function test_offer_query_scopes_filter_correctly(): void
    {
        $this->seed();

        $this->assertInstanceOf(Collection::class, Offer::freeOnly()->get());
        // 24 seeded offers minus 3 unknown tiers minus 1 trial = 20 fully-free
        $this->assertSame(20, Offer::freeOnly()->count());

        $this->assertInstanceOf(Collection::class, Offer::freeTierType('daily_reset')->get());
        $this->assertSame(5, Offer::freeTierType('daily_reset')->count());

        $this->assertInstanceOf(Collection::class, Offer::accessType('api')->get());
        $this->assertGreaterThan(0, Offer::accessType('api')->count());
        $this->assertTrue(Offer::accessType('telegram_bot')->get()->contains('slug', 'lightvela'));

        $this->assertInstanceOf(Collection::class, Offer::search('llm')->get());
        $this->assertTrue(Offer::search('llm')->get()->contains('slug', 'llm7'));
    }

    public function test_verification_scopes_respect_pivot_semantics(): void
    {
        $this->seed();

        $card = VerificationMethod::query()->where('key', 'card')->firstOrFail();
        $offer = Offer::query()->where('slug', 'kktoken')->firstOrFail();

        $this->assertFalse(Offer::hasVerification(['card'])->whereKey($offer->getKey())->exists());

        $offer->verificationMethods()->attach($card->getKey());

        $this->assertTrue(Offer::hasVerification(['card'])->whereKey($offer->getKey())->exists());
        $this->assertTrue(Offer::hasVerification(['card', 'phone'])->whereKey($offer->getKey())->exists());
        $this->assertFalse(Offer::withoutVerification(['card', 'phone'])->whereKey($offer->getKey())->exists());

        // every other seeded offer requires neither card nor phone
        $expected = Offer::query()->whereKeyNot($offer->getKey())->orderBy('id')->pluck('id')->all();
        $actual = Offer::withoutVerification(['card', 'phone'])->orderBy('id')->pluck('id')->all();
        $this->assertSame($expected, $actual);
    }
}
