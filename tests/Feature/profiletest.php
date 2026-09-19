<?php

namespace Tests\Feature;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileTest extends TestCase
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

    public function test_profile_page_renders(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->get('/admin/profile')
            ->assertOk()
            ->assertSee('تغییر رمز عبور');
    }

    public function test_password_can_be_changed_with_correct_current_password(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->put('/admin/profile', [
                'current_password' => \Database\Seeders\AdminSeeder::DEMO_PASSWORD,
                'password' => 'BrandNewPass!2026',
                'password_confirmation' => 'BrandNewPass!2026',
            ])
            ->assertRedirect();

        $this->assertTrue(Hash::check('BrandNewPass!2026', $this->admin()->fresh()->password));
    }

    public function test_wrong_current_password_is_rejected(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->put('/admin/profile', [
                'current_password' => 'definitely-wrong',
                'password' => 'BrandNewPass!2026',
                'password_confirmation' => 'BrandNewPass!2026',
            ])
            ->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check(\Database\Seeders\AdminSeeder::DEMO_PASSWORD, $this->admin()->fresh()->password));
    }

    public function test_new_password_must_be_confirmed_and_long_enough(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->put('/admin/profile', [
                'current_password' => \Database\Seeders\AdminSeeder::DEMO_PASSWORD,
                'password' => 'short',
                'password_confirmation' => 'nope',
            ])
            ->assertSessionHasErrors('password');
    }
}
