<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Offer;
use App\Models\AnalyticsEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;

class HomeControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_renders_one_pager_with_offer_cards(): void
    {
        $this->seed();

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('آفرهای رایگان');
        $response->assertSee('جدیدترین بررسی‌شده‌ها');
        $response->assertSee('ریست روزانه');
        $response->assertSee('/go/llm7', false);
        $response->assertSee('/offers/llm7', false);
        $response->assertSee('جزئیات و شرایط', false);
    }

    public function test_free_filter_returns_subset(): void
    {
        $this->seed();

        $response = $this->get('/?free=1&no_card=1');

        $response->assertOk();
        // llm7 is daily_reset (fully free, no card verification); aifreeforever has an unknown tier
        $response->assertSee('/go/llm7', false);
        $response->assertDontSee('/go/aifreeforever', false);
        $this->assertSame(20, Offer::query()->freeOnly()->withoutVerification(['card'])->count());
    }

    public function test_search_q_llm_finds_the_two_seeded_offers(): void
    {
        $this->seed();

        $response = $this->get('/?q=llm');

        $response->assertOk();
        $response->assertSee('/go/llm7', false);
        $response->assertSee('/go/vsllm', false);
        $response->assertDontSee('/go/kktoken', false);
    }

    public function test_model_filter_matches_linked_offers(): void
    {
        $this->seed();

        $response = $this->get('/?model[]=gpt-5.6');

        $response->assertOk();
        $response->assertSee('/go/duck-ai', false);
        $response->assertDontSee('/go/llm7', false);
    }

    public function test_tier_and_access_filters_apply(): void
    {
        $this->seed();

        $response = $this->get('/?tier[]=daily_reset&access[]=telegram_bot');

        $response->assertOk();
        // lightvela is the only offer with telegram_bot access, but its tier is trial
        $response->assertDontSee('/go/lightvela', false);
        $response->assertDontSee('/go/duck-ai', false); // daily_reset but no telegram_bot access
    }

    public function test_impossible_filter_combination_shows_friendly_empty_state(): void
    {
        $this->seed();

        $response = $this->get('/?verification[]=card&no_card=1');

        $response->assertOk();
        $response->assertSee('چیزی پیدا نشد');
        $response->assertSee('حذف همه فیلترها');
    }

    public function test_detail_page_renders_with_seo_meta(): void
    {
        $this->seed();

        $response = $this->get('/offers/llm7');

        $response->assertOk();
        $response->assertSee('<link rel="canonical"', false);
        $response->assertSee('og:title', false);
        $response->assertSee('گزارش مشکل');

        $this->get('/offers/does-not-exist')->assertNotFound();
    }

    public function test_analytics_event_is_recorded(): void
    {
        $this->seed();
        $offerId = Offer::query()->where('slug', 'llm7')->value('id');

        $this->postJson('/analytics/event', [
            'type' => 'offer_view',
            'offer_id' => $offerId,
            'payload' => ['slug' => 'llm7', 'source' => 'quickview'],
        ])->assertOk()->assertJsonPath('ok', true);

        $this->assertDatabaseHas('analytics_events', [
            'event_type' => 'offer_view',
            'offer_id' => $offerId,
        ]);

        $this->postJson('/analytics/event', ['type' => 'bogus'])->assertUnprocessable();

        $this->postJson('/analytics/event', [
            'type' => 'filter_use',
            'payload' => ['param' => 'free', 'value' => '1'],
        ])->assertOk();

        $this->assertTrue(AnalyticsEvent::query()->where('event_type', 'filter_use')->exists());
    }
}
