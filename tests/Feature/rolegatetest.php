<?php

namespace Tests\Feature;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RoleGateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function makeAdmin(string $role, string $email): Admin
    {
        return Admin::query()->create([
            'name' => ucfirst($role),
            'email' => $email,
            'password' => Hash::make('Password!2026'),
            'role' => $role,
        ]);
    }

    public function test_editor_can_manage_content(): void
    {
        $editor = $this->makeAdmin(Admin::ROLE_EDITOR, 'editor-gate@offers.local');

        $this->actingAs($editor, 'admin')->get('/admin/providers')->assertOk();
        $this->actingAs($editor, 'admin')->get('/admin/providers/create')->assertOk();
    }

    public function test_editor_cannot_access_crawler_section(): void
    {
        $editor = $this->makeAdmin(Admin::ROLE_EDITOR, 'editor-crawler@offers.local');

        $this->actingAs($editor, 'admin')->get('/admin/crawler')->assertForbidden();
    }

    public function test_crawler_manager_can_access_crawler_but_not_content(): void
    {
        $manager = $this->makeAdmin(Admin::ROLE_CRAWLER_MANAGER, 'crawler@offers.local');

        $this->actingAs($manager, 'admin')->get('/admin/crawler')->assertOk();
        $this->actingAs($manager, 'admin')->get('/admin/offers/create')->assertForbidden();
    }

    public function test_super_admin_can_access_everything(): void
    {
        $super = $this->makeAdmin(Admin::ROLE_SUPER_ADMIN, 'super-all@offers.local');

        foreach (['/admin/providers', '/admin/crawler', '/admin/admins', '/admin/settings', '/admin/reports', '/admin/submissions', '/admin/ads'] as $url) {
            $this->actingAs($super, 'admin')->get($url)->assertOk();
        }
    }

    public function test_guest_cannot_reach_any_admin_section(): void
    {
        foreach (['/admin/providers', '/admin/crawler', '/admin/settings', '/admin/ads'] as $url) {
            $this->get($url)->assertRedirect('/admin/login');
        }
    }
}
