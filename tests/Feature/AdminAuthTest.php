<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Admin;
use App\Models\Offer;
use App\Support\FaDigits;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin')->assertRedirect(route('admin.login'));
        $this->get('/admin/login')->assertOk();
    }

    public function test_admin_can_login_with_seeded_credentials(): void
    {
        $this->seed();

        $this->post(route('admin.login.attempt'), [
            'email' => AdminSeeder::DEMO_EMAIL,
            'password' => AdminSeeder::DEMO_PASSWORD,
        ])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticated('admin');
    }

    public function test_login_rejects_wrong_password(): void
    {
        $this->seed();

        $this->from(route('admin.login'))->post(route('admin.login.attempt'), [
            'email' => AdminSeeder::DEMO_EMAIL,
            'password' => 'wrong-password',
        ])
            ->assertRedirect(route('admin.login'));

        $this->assertGuest('admin');
    }

    public function test_authenticated_admin_sees_dashboard_counts(): void
    {
        $this->seed();

        $admin = Admin::query()->where('email', AdminSeeder::DEMO_EMAIL)->firstOrFail();

        $this->actingAs($admin, 'admin')
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(FaDigits::convert(Offer::count()));
    }

    public function test_admin_can_logout(): void
    {
        $this->seed();

        $admin = Admin::query()->where('email', AdminSeeder::DEMO_EMAIL)->firstOrFail();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.logout'))
            ->assertRedirect(route('admin.login'));

        $this->assertGuest('admin');
    }
}
