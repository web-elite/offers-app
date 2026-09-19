<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\Settings;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index(): ViewContract
    {
        $this->authorizeContent();

        return view('admin.settings.index', [
            'values' => [
                Settings::SITE_TITLE => Settings::get(Settings::SITE_TITLE),
                Settings::ADS_ENABLED => Settings::bool(Settings::ADS_ENABLED) ? '1' : '0',
                Settings::ADS_INTERVAL => (string) Settings::int(Settings::ADS_INTERVAL, 4),
                Settings::FOOTER_TEXT => Settings::get(Settings::FOOTER_TEXT, ''),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorizeContent();

        $data = $request->validate([
            'site_title_fa' => ['required', 'string', 'max:191'],
            'ads_enabled' => ['nullable', 'boolean'],
            'ads_interval' => ['required', 'integer', 'min:1', 'max:50'],
            'footer_text_fa' => ['nullable', 'string', 'max:2000'],
        ]);

        Setting::updateOrCreate(['key' => Settings::SITE_TITLE], ['value' => $data['site_title_fa']]);
        Setting::updateOrCreate(['key' => Settings::ADS_ENABLED], ['value' => $request->boolean('ads_enabled') ? '1' : '0']);
        Setting::updateOrCreate(['key' => Settings::ADS_INTERVAL], ['value' => (string) $data['ads_interval']]);
        Setting::updateOrCreate(['key' => Settings::FOOTER_TEXT], ['value' => $data['footer_text_fa'] ?? '']);

        Settings::flush();

        return back()->with('status', 'تنظیمات ذخیره شد.');
    }

    private function authorizeContent(): void
    {
        $admin = auth('admin')->user();

        abort_unless($admin !== null && ($admin->hasAbility('manageContent') || $admin->hasAbility('manageAdmins')), 403);
    }
}
