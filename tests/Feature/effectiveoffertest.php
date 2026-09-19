<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\AdminOverride;
use App\Models\Offer;
use App\Support\EffectiveOffer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EffectiveOfferTest extends TestCase
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

    public function test_override_beats_stored_column(): void
    {
        $offer = Offer::query()->firstOrFail();
        $offer->forceFill(['title_fa' => 'عنوان ذخیره‌شده'])->save();

        AdminOverride::create([
            'offer_id' => $offer->id,
            'field' => 'title_fa',
            'crawler_value' => 'عنوان ذخیره‌شده',
            'override_value' => 'عنوان بازنویسی‌شده',
            'reason' => 'اصلاح دستی',
            'actor' => 'admin@offers.local',
        ]);

        $values = EffectiveOffer::values($offer->fresh());

        $this->assertSame('عنوان بازنویسی‌شده', $values['title_fa']);
    }

    public function test_hydrate_sets_effective_attributes_without_n_plus_one_regression(): void
    {
        $offers = Offer::query()->limit(5)->get();
        EffectiveOffer::hydrate($offers);

        $this->assertNotNull($offers->first()->title_fa);
    }

    public function test_storing_override_snapshots_current_effective_value_and_actor(): void
    {
        $offer = Offer::query()->firstOrFail();

        $this->actingAs($this->admin(), 'admin')
            ->post('/admin/offers/'.$offer->id.'/overrides', [
                'field' => 'credits_amount',
                'override_value' => '12345',
                'reason' => 'طبق اعلام رسمی سرویس',
            ])
            ->assertRedirect();

        $override = AdminOverride::query()->where('offer_id', $offer->id)->firstOrFail();

        $this->assertSame('credits_amount', $override->field);
        $this->assertSame('12345', $override->override_value);
        $this->assertSame('admin@offers.local', $override->actor);
        $this->assertSame((string) $offer->credits_amount, (string) $override->crawler_value);
    }

    public function test_override_requires_reason(): void
    {
        $offer = Offer::query()->firstOrFail();

        $this->actingAs($this->admin(), 'admin')
            ->post('/admin/offers/'.$offer->id.'/overrides', [
                'field' => 'title_fa',
                'override_value' => 'چیزی',
            ])
            ->assertSessionHasErrors('reason');
    }

    public function test_override_can_be_deleted(): void
    {
        $offer = Offer::query()->firstOrFail();

        $override = AdminOverride::create([
            'offer_id' => $offer->id,
            'field' => 'status',
            'crawler_value' => 'active',
            'override_value' => 'expired',
            'reason' => 'test',
            'actor' => 'admin@offers.local',
        ]);

        $this->actingAs($this->admin(), 'admin')
            ->delete('/admin/offers/'.$offer->id.'/overrides/'.$override->id)
            ->assertRedirect();

        $this->assertDatabaseMissing('admin_overrides', ['id' => $override->id]);
    }

    public function test_public_home_shows_effective_title(): void
    {
        $offer = Offer::query()->firstOrFail();

        AdminOverride::create([
            'offer_id' => $offer->id,
            'field' => 'title_fa',
            'crawler_value' => $offer->title_fa,
            'override_value' => 'عنوان مؤثر برای نمایش عمومی',
            'reason' => 'test',
            'actor' => 'admin@offers.local',
        ]);

        $this->get('/')->assertOk()->assertSee('عنوان مؤثر برای نمایش عمومی');
    }
}
