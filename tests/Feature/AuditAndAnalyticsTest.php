<?php

namespace Tests\Feature;

use App\Models\AnalyticsEvent;
use App\Models\AuditLog;
use App\Models\Offer;
use App\Models\Provider;
use App\Support\Analytics;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditAndAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_offer_update_writes_audit_log(): void
    {
        $this->seed();

        $offer = Offer::query()->where('slug', 'kktoken')->firstOrFail();
        $oldTitle = $offer->title_fa;

        $offer->update(['title_fa' => 'عنوان آزمایشی']);

        $log = AuditLog::query()->latest('id')->first();

        $this->assertNotNull($log);
        $this->assertSame('offer', $log->entity);
        $this->assertSame($offer->getKey(), $log->entity_id);
        $this->assertSame(AuditLog::ACTION_UPDATED, $log->action);
        $this->assertSame(['title_fa'], array_keys($log->new_value ?? []));
        $this->assertSame($oldTitle, $log->old_value['title_fa'] ?? null);
        $this->assertSame('عنوان آزمایشی', $log->new_value['title_fa'] ?? null);
        // no authenticated admin in this context → actor stays null-safe
        $this->assertNull($log->actor);
    }

    public function test_provider_update_writes_audit_log(): void
    {
        $this->seed();

        $provider = Provider::query()->where('slug', 'duck-ai')->firstOrFail();
        $oldName = $provider->name;

        $provider->update(['name' => 'Duck AI']);

        $log = AuditLog::query()->where('entity', 'provider')->latest('id')->first();

        $this->assertNotNull($log);
        $this->assertSame($provider->getKey(), $log->entity_id);
        $this->assertSame($oldName, $log->old_value['name'] ?? null);
        $this->assertSame('Duck AI', $log->new_value['name'] ?? null);
    }

    public function test_analytics_helper_persists_events(): void
    {
        $this->seed();

        Analytics::record(Analytics::EVENT_OFFER_VIEW, null, ['source' => 'test']);

        $offerId = Offer::query()->where('slug', 'duck-ai')->value('id');
        Analytics::record(Analytics::EVENT_QR_SCAN, $offerId);

        $this->assertDatabaseHas('analytics_events', ['event_type' => Analytics::EVENT_OFFER_VIEW]);
        $this->assertDatabaseHas('analytics_events', ['event_type' => Analytics::EVENT_QR_SCAN, 'offer_id' => $offerId]);

        $event = AnalyticsEvent::query()->latest('id')->first();
        $this->assertSame($offerId, $event->offer_id);
        $this->assertNotNull($event->created_at);
    }
}
