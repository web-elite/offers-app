<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Offer;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SmokeRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /** The pre-existing public feature set must keep working after the admin expansion. */
    public function test_public_pages_still_work(): void
    {
        $this->get('/')->assertOk();
        $this->get('/?free=1&no_card=1')->assertOk();
        $this->get('/?q=llm')->assertOk()->assertSee('LLM7');
        $this->get('/offers/llm7')->assertOk();
        $this->get('/go/llm7')->assertRedirect('https://dash.llm7.io/?r=2AR');
        $this->get('/sitemap.xml')->assertOk();
        $this->get('/admin/login')->assertOk();
    }

    public function test_admin_seeded_login_still_works(): void
    {
        $response = $this->post('/admin/login', [
            'email' => 'admin@offers.local',
            'password' => 'ChangeMe!2026',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs(
            Admin::query()->where('email', 'admin@offers.local')->firstOrFail(),
            'admin'
        );
    }

    public function test_effective_offer_hydrate_runs_on_home(): void
    {
        $offer = Offer::query()->firstOrFail();
        $offer->forceFill(['title_fa' => 'عنوان تستی هیدریت'])->save();

        $this->get('/')->assertOk()->assertSee('عنوان تستی هیدریت');
    }

    public function test_settings_table_missing_values_fall_back(): void
    {
        Settings::flush();

        $this->assertSame('آفرهای رایگان AI', Settings::get('site_title_fa'));
        $this->assertFalse(Settings::bool('ads_enabled'));
        $this->assertSame(4, Settings::int('ads_interval'));
    }

    public function test_hashed_admin_password_survives_update_without_password_field(): void
    {
        $admin = Admin::query()->where('email', 'admin@offers.local')->firstOrFail();

        $this->actingAs($admin, 'admin')
            ->put('/admin/offers/'.Offer::query()->firstOrFail()->id, [
                'provider_id' => Offer::query()->value('provider_id'),
                'title_fa' => 'بدون تغییر رمز',
                'slug' => Offer::query()->value('slug'),
                'status' => 'active',
                'free_tier_type' => 'daily_reset',
                'access_types' => ['api'],
            ])
            ->assertRedirect('/admin/offers');

        $this->assertTrue(Hash::check('ChangeMe!2026', $admin->fresh()->password));
    }
}
