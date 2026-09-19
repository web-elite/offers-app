<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Category;
use App\Models\Offer;
use App\Models\OfferVersion;
use App\Models\Provider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminCrudTest extends TestCase
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

    private function superAdmin(): Admin
    {
        return Admin::query()->create([
            'name' => 'Super',
            'email' => 'super@offers.local',
            'password' => Hash::make('Password!2026'),
            'role' => Admin::ROLE_SUPER_ADMIN,
        ]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin/providers')->assertRedirect('/admin/login');
    }

    public function test_provider_index_lists_seeded_rows(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->get('/admin/providers')
            ->assertOk()
            ->assertSee('ارائه‌دهنده‌ها');
    }

    public function test_provider_create_and_store(): void
    {
        $category = Category::query()->firstOrFail();

        $this->actingAs($this->admin(), 'admin')
            ->post('/admin/providers', [
                'name' => 'سرویس تستی',
                'slug' => 'test-provider',
                'category_id' => $category->id,
                'status' => 'active',
                'canonical_url' => 'https://example.com',
                'referral_url' => 'https://example.com/?ref=1',
                'description_fa' => 'توضیح تستی',
            ])
            ->assertRedirect('/admin/providers');

        $this->assertDatabaseHas('providers', ['slug' => 'test-provider']);
    }

    public function test_provider_update(): void
    {
        $provider = Provider::query()->firstOrFail();

        $this->actingAs($this->admin(), 'admin')
            ->put('/admin/providers/'.$provider->id, [
                'name' => 'نام ویرایش‌شده',
                'slug' => $provider->slug,
                'category_id' => $provider->category_id,
                'status' => 'inactive',
                'canonical_url' => $provider->canonical_url,
            ])
            ->assertRedirect('/admin/providers');

        $this->assertDatabaseHas('providers', ['id' => $provider->id, 'name' => 'نام ویرایش‌شده']);
    }

    public function test_offer_update_syncs_tags(): void
    {
        $offer = Offer::query()->with('tags')->firstOrFail();

        $this->actingAs($this->admin(), 'admin')
            ->put('/admin/offers/'.$offer->id, [
                'provider_id' => $offer->provider_id,
                'title_fa' => $offer->title_fa,
                'slug' => $offer->slug,
                'status' => $offer->status,
                'free_tier_type' => $offer->free_tier_type,
                'access_types' => $offer->access_types ?? [],
                'verification_method_ids' => [],
                'ai_model_ids' => [],
                'tag_ids' => [],
            ])
            ->assertRedirect('/admin/offers');

        $this->assertSame(0, $offer->fresh()->tags()->count());
    }

    public function test_bulk_mark_expired_sets_status_and_version(): void
    {
        $offers = Offer::query()->limit(3)->get();

        $this->actingAs($this->admin(), 'admin')
            ->post('/admin/offers/bulk', [
                'ids' => $offers->pluck('id')->all(),
                'action' => 'mark_expired',
            ])
            ->assertRedirect('/admin/offers');

        foreach ($offers as $offer) {
            $this->assertSame(Offer::STATUS_EXPIRED, $offer->fresh()->status);
        }

        $this->assertSame(3, OfferVersion::query()->where('source', OfferVersion::SOURCE_ADMIN)->count());
    }

    public function test_admin_management_is_super_admin_only(): void
    {
        $editor = Admin::query()->create([
            'name' => 'Editor',
            'email' => 'editor@offers.local',
            'password' => Hash::make('Password!2026'),
            'role' => Admin::ROLE_EDITOR,
        ]);

        $this->actingAs($editor, 'admin')->get('/admin/admins')->assertForbidden();
        $this->actingAs($this->admin(), 'admin')->get('/admin/admins')->assertOk();
    }

    public function test_admin_can_be_created_by_super_admin(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->post('/admin/admins', [
                'name' => 'New Admin',
                'email' => 'new-admin@offers.local',
                'role' => Admin::ROLE_REVIEWER,
                'password' => 'Password!2026',
            ])
            ->assertRedirect('/admin/admins');

        $this->assertDatabaseHas('admins', ['email' => 'new-admin@offers.local']);
    }

    public function test_seeded_admin_password_is_usable_for_login(): void
    {
        $this->assertTrue(Hash::check(\Database\Seeders\AdminSeeder::DEMO_PASSWORD, $this->admin()->password));
    }
}
