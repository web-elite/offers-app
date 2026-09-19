<?php

namespace Database\Seeders;

use App\Models\Ad;
use App\Support\Settings;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Settings::DEFAULTS as $key => $value) {
            Settings::put($key, $value);
        }

        Ad::updateOrCreate(
            ['title' => 'نمونه‌ی تبلیغ — جایگاه بین آفرها'],
            [
                'html_snippet' => '<div style="text-align:center; padding:18px; border:1px dashed rgba(148,163,184,.45); border-radius:10px; font-size:14px; line-height:2">جای این تبلیغ رزرو شده است — از پنل ادمین، بخش «تبلیغات»، بنر خود را اضافه کنید.</div>',
                'target_url' => 'https://offers.webelitee.ir',
                'position' => Ad::POSITION_BETWEEN_CARDS,
                'is_active' => true,
                'priority' => 0,
            ]
        );
    }
}
