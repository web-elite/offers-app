<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsTest extends TestCase
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

    public function test_settings_page_renders_with_defaults(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->get('/admin/settings')
            ->assertOk()
            ->assertSee('تنظیمات سایت');
    }

    public function test_site_title_override_shows_on_public_layout(): void
    {
        Settings::put(Settings::SITE_TITLE, 'عنوان سفارشی سایت');

        $this->get('/')->assertOk()->assertSee('عنوان سفارشی سایت');
    }

    public function test_footer_text_override_shows_on_public_layout(): void
    {
        Settings::put(Settings::FOOTER_TEXT, 'متن پاورقی ویژه برای تست');

        $this->get('/')->assertOk()->assertSee('متن پاورقی ویژه برای تست');
    }

    public function test_settings_can_be_updated_from_admin(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->put('/admin/settings', [
                'site_title_fa' => 'عنوان جدید',
                'ads_enabled' => '1',
                'ads_interval' => 6,
                'footer_text_fa' => 'پاورقی جدید',
            ])
            ->assertRedirect();

        Settings::flush();

        $this->assertSame('عنوان جدید', Settings::get(Settings::SITE_TITLE));
        $this->assertTrue(Settings::bool(Settings::ADS_ENABLED));
        $this->assertSame(6, Settings::int(Settings::ADS_INTERVAL));
        $this->assertSame('پاورقی جدید', Settings::get(Settings::FOOTER_TEXT));
    }

    public function test_ads_interval_is_validated(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->put('/admin/settings', [
                'site_title_fa' => 'x',
                'ads_interval' => 0,
            ])
            ->assertSessionHasErrors('ads_interval');
    }

    public function test_default_site_title_is_used_when_not_overridden(): void
    {
        $this->assertSame('آفرهای رایگان AI', Settings::get(Settings::SITE_TITLE));
        $this->get('/')->assertOk()->assertSee('آفرهای رایگان AI');
    }
}
