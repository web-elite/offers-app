<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public const DEMO_EMAIL = 'admin@offers.local';
    public const DEMO_PASSWORD = 'ChangeMe!2026';

    public function run(): void
    {
        Admin::updateOrCreate(
            ['email' => self::DEMO_EMAIL],
            [
                'name' => 'مدیر اصلی',
                'password' => self::DEMO_PASSWORD,
                'role' => Admin::ROLE_SUPER_ADMIN,
            ]
        );
    }
}
