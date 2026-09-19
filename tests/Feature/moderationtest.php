<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Offer;
use App\Models\OfferReport;
use App\Models\Provider;
use App\Models\UserSubmission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ModerationTest extends TestCase
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

    public function test_reports_index_lists_pending(): void
    {
        $offer = Offer::query()->firstOrFail();
        OfferReport::create(['offer_id' => $offer->id, 'report_type' => 'expired', 'status' => 'pending']);

        $this->actingAs($this->admin(), 'admin')
            ->get('/admin/reports')
            ->assertOk()
            ->assertSee($offer->title_fa);
    }

    public function test_resolving_report_expires_the_offer(): void
    {
        $offer = Offer::query()->firstOrFail();
        $report = OfferReport::create(['offer_id' => $offer->id, 'report_type' => 'expired', 'status' => 'pending']);

        $this->actingAs($this->admin(), 'admin')
            ->post('/admin/reports/'.$report->id.'/resolve')
            ->assertRedirect();

        $this->assertSame(Offer::STATUS_EXPIRED, $offer->fresh()->status);
        $this->assertSame(OfferReport::STATUS_RESOLVED, $report->fresh()->status);
        $this->assertDatabaseHas('offer_versions', ['offer_id' => $offer->id, 'source' => 'user_report']);
    }

    public function test_rejecting_report_keeps_offer_untouched(): void
    {
        $offer = Offer::query()->firstOrFail();
        $original = $offer->status;
        $report = OfferReport::create(['offer_id' => $offer->id, 'report_type' => 'fake', 'status' => 'pending']);

        $this->actingAs($this->admin(), 'admin')
            ->post('/admin/reports/'.$report->id.'/reject')
            ->assertRedirect();

        $this->assertSame($original, $offer->fresh()->status);
        $this->assertSame(OfferReport::STATUS_REJECTED, $report->fresh()->status);
    }

    public function test_approving_submission_creates_provider_and_draft_offer(): void
    {
        $submission = UserSubmission::create([
            'provider_name' => 'New AI Service',
            'url' => 'https://new-service.example',
            'offer_note_fa' => 'پیشنهاد تستی',
            'status' => 'pending',
        ]);

        $this->actingAs($this->admin(), 'admin')
            ->post('/admin/submissions/'.$submission->id.'/approve')
            ->assertRedirect();

        $this->assertDatabaseHas('providers', ['name' => 'New AI Service']);
        $this->assertSame(
            Offer::STATUS_MANUAL_REVIEW,
            Offer::query()->where('slug', 'new-ai-service')->firstOrFail()->status
        );
        $this->assertSame(UserSubmission::STATUS_APPROVED, $submission->fresh()->status);
    }

    public function test_rejecting_submission_creates_nothing(): void
    {
        $submission = UserSubmission::create([
            'provider_name' => 'Rejected Service',
            'url' => 'https://rejected.example',
            'status' => 'pending',
        ]);

        $countBefore = Provider::count();

        $this->actingAs($this->admin(), 'admin')
            ->post('/admin/submissions/'.$submission->id.'/reject')
            ->assertRedirect();

        $this->assertSame($countBefore, Provider::count());
        $this->assertSame(UserSubmission::STATUS_REJECTED, $submission->fresh()->status);
    }

    public function test_reviewer_role_can_moderate_but_not_manage_content(): void
    {
        $reviewer = Admin::query()->create([
            'name' => 'Reviewer',
            'email' => 'reviewer@offers.local',
            'password' => Hash::make('Password!2026'),
            'role' => Admin::ROLE_REVIEWER,
        ]);

        $this->actingAs($reviewer, 'admin')->get('/admin/reports')->assertOk();
        $this->actingAs($reviewer, 'admin')->get('/admin/providers/create')->assertForbidden();
    }
}
