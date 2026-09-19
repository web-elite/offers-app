<?php

namespace Tests\Feature;

use App\Models\Offer;
use App\Models\OfferReport;
use App\Models\UserSubmission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_is_stored(): void
    {
        $this->seed();

        $offer = Offer::query()->where('slug', 'llm7')->firstOrFail();

        $this->post(route('public.offers.report', $offer->slug), [
            'report_type' => 'expired',
            'details_fa' => 'این آفر دیگر کار نمی‌کند.',
        ])->assertRedirect();

        $this->assertDatabaseHas('offer_reports', [
            'offer_id' => $offer->id,
            'report_type' => 'expired',
            'status' => OfferReport::STATUS_PENDING,
        ]);
    }

    public function test_report_via_ajax_returns_json_and_is_stored(): void
    {
        $this->seed();

        $offer = Offer::query()->where('slug', 'apimaster')->firstOrFail();

        $this->postJson(route('public.offers.report', $offer->slug), [
            'report_type' => 'not_free_anymore',
        ])->assertOk()->assertJsonPath('ok', true);

        $this->assertDatabaseHas('offer_reports', [
            'offer_id' => $offer->id,
            'report_type' => 'not_free_anymore',
        ]);
    }

    public function test_report_rejects_unknown_type(): void
    {
        $this->seed();

        $this->post(route('public.offers.report', 'llm7'), [
            'report_type' => 'nonsense',
        ])->assertSessionHasErrors('report_type');

        $this->assertDatabaseCount('offer_reports', 0);
    }

    public function test_report_unknown_offer_404s(): void
    {
        $this->seed();

        $this->post(route('public.offers.report', 'nope'), [
            'report_type' => 'other',
        ])->assertNotFound();
    }

    public function test_submission_is_stored(): void
    {
        $this->seed();

        $this->post('/submit', [
            'provider_name' => 'TestAI',
            'url' => 'https://testai.example.com',
            'offer_note_fa' => '۵۰ اعتبار هدیه ثبت‌نام',
        ])->assertRedirect();

        $this->assertDatabaseHas('user_submissions', [
            'provider_name' => 'TestAI',
            'url' => 'https://testai.example.com',
            'status' => UserSubmission::STATUS_PENDING,
        ]);
    }

    public function test_submission_with_filled_honeypot_is_silently_dropped(): void
    {
        $this->seed();

        $this->post('/submit', [
            'provider_name' => 'SpamBot',
            'url' => 'https://spam.example.com',
            'offer_note_fa' => 'buy now',
            'website' => 'http://spam.example.com',
        ])->assertRedirect();

        $this->assertDatabaseCount('user_submissions', 0);
    }

    public function test_submission_validates_required_fields(): void
    {
        $this->seed();

        $this->post('/submit', [
            'provider_name' => '',
            'url' => 'not-a-url',
        ])->assertSessionHasErrors(['provider_name', 'url']);

        $this->assertDatabaseCount('user_submissions', 0);
    }
}
