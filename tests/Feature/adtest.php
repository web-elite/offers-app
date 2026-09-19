<?php

namespace Tests\Feature;

use App\Models\Ad;
use App\Models\Admin;
use App\Models\Offer;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function admin(): Admin
    {
        return Admin::query()->where('email', 'admin@offers.local')->firstOrFail();
    }

    private function enableAds(int $interval = 4): void
    {
        Settings::put(Settings::ADS_ENABLED, '1');
        Settings::put(Settings::ADS_INTERVAL, (string) $interval);
    }

    public function test_ads_are_disabled_by_default(): void
    {
        Settings::put(Settings::ADS_ENABLED, '0');
        Ad::query()->update(['is_active' => true, 'position' => Ad::POSITION_BETWEEN_CARDS]);

        $this->get('/')->assertOk()->assertDontSee('class="ad-card"', false);
    }

    public function test_active_ad_is_injected_between_cards(): void
    {
        $this->enableAds(4);

        Ad::query()->update(['is_active' => false]);
        Ad::create([
            'title' => 'تبلیغ تستی بین کارت‌ها',
            'target_url' => 'https://sponsor.example',
            'position' => Ad::POSITION_BETWEEN_CARDS,
            'is_active' => true,
            'priority' => 5,
        ]);

        $response = $this->get('/');
        $response->assertOk();

        // 24 seeded offers, interval 4 → 5 injection points (4, 8, 12, 16, 20)
        $this->assertSame(5, substr_count($response->getContent(), 'class="ad-card"'));
    }

    public function test_inactive_or_out_of_window_ads_are_excluded(): void
    {
        $this->enableAds(4);
        Ad::query()->update(['is_active' => false]);

        Ad::create([
            'title' => 'غیرفعال',
            'target_url' => 'https://x.example',
            'position' => Ad::POSITION_BETWEEN_CARDS,
            'is_active' => false,
        ]);

        Ad::create([
            'title' => 'پایان‌یافته',
            'target_url' => 'https://y.example',
            'position' => Ad::POSITION_BETWEEN_CARDS,
            'is_active' => true,
            'ends_at' => now()->subDay(),
        ]);

        $this->get('/')->assertOk()->assertDontSee('class="ad-card"', false);
    }

    public function test_ad_render_increments_impressions(): void
    {
        $this->enableAds(4);
        Ad::query()->update(['is_active' => false]);

        $ad = Ad::create([
            'title' => 'شمارش نمایش',
            'target_url' => 'https://count.example',
            'position' => Ad::POSITION_BETWEEN_CARDS,
            'is_active' => true,
        ]);

        $this->get('/')->assertOk();

        $this->assertGreaterThanOrEqual(5, $ad->fresh()->impressions);
    }

    public function test_ad_click_increments_clicks_and_redirects(): void
    {
        $ad = Ad::create([
            'title' => 'کلیک',
            'target_url' => 'https://click-target.example',
            'position' => Ad::POSITION_BETWEEN_CARDS,
            'is_active' => true,
        ]);

        $this->get('/ads/'.$ad->id.'/click')
            ->assertRedirect('https://click-target.example');

        $this->assertSame(1, $ad->fresh()->clicks);
        $this->assertDatabaseHas('analytics_events', ['event_type' => 'ad_click']);
    }

    public function test_admin_can_create_ad(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->post('/admin/ads', [
                'title' => 'تبلیغ پنل',
                'target_url' => 'https://panel-ad.example',
                'position' => Ad::POSITION_BETWEEN_CARDS,
                'priority' => 2,
                'is_active' => '1',
                'html_snippet' => '<b>متن تبلیغ</b>',
            ])
            ->assertRedirect('/admin/ads');

        $this->assertDatabaseHas('ads', ['title' => 'تبلیغ پنل']);
    }

    public function test_ad_index_shows_stats_and_ctr(): void
    {
        $ad = Ad::create([
            'title' => 'آمار',
            'target_url' => 'https://stats.example',
            'position' => Ad::POSITION_AFTER_GRID,
            'impressions' => 200,
            'clicks' => 10,
            'is_active' => true,
        ]);

        $this->actingAs($this->admin(), 'admin')
            ->get('/admin/ads')
            ->assertOk()
            ->assertSee('آمار')
            ->assertSee('۵');
    }

    public function test_after_grid_position_renders_at_the_end(): void
    {
        $this->enableAds(4);
        Ad::query()->update(['is_active' => false]);

        Ad::create([
            'title' => 'انتهای فهرست',
            'target_url' => 'https://end.example',
            'position' => Ad::POSITION_AFTER_GRID,
            'is_active' => true,
        ]);

        $this->get('/')->assertOk()->assertSee('انتهای فهرست');
    }

    public function test_ads_do_not_break_offer_cards(): void
    {
        $this->enableAds(2);

        $response = $this->get('/')->assertOk();

        $this->assertSame(24, substr_count($response->getContent(), 'offer-card'));
        $this->assertGreaterThan(0, Offer::count());
    }
}
