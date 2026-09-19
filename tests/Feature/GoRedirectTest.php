<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Offer;
use App\Models\AnalyticsEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;

class GoRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_go_records_outbound_click_and_redirects_to_referral_url(): void
    {
        $this->seed();

        $offer = Offer::query()->where('slug', 'llm7')->with('provider')->firstOrFail();

        $response = $this->get('/go/llm7');

        $response->assertStatus(302);
        $response->assertRedirect('https://dash.llm7.io/?r=2AR');

        $this->assertDatabaseHas('analytics_events', [
            'event_type' => 'outbound_click',
            'offer_id' => $offer->id,
        ]);
    }

    public function test_go_falls_back_to_canonical_url_without_referral(): void
    {
        $this->seed();

        $offer = Offer::query()->where('slug', 'archive-tell')->with('provider')->firstOrFail();

        $response = $this->get('/go/archive-tell');

        $response->assertStatus(302);
        $response->assertRedirect($offer->provider->canonical_url);

        $this->assertTrue(
            AnalyticsEvent::query()
                ->where('event_type', 'outbound_click')
                ->where('offer_id', $offer->id)
                ->exists()
        );
    }

    public function test_go_with_unknown_slug_returns_404(): void
    {
        $this->seed();

        $this->get('/go/does-not-exist')->assertNotFound();

        $this->assertSame(0, AnalyticsEvent::query()->where('event_type', 'outbound_click')->count());
    }
}
