@php
    $admin = auth('admin')->user();
    $nav = [
        ['label' => 'داشبورد', 'route' => 'admin.dashboard', 'ability' => null, 'icon' => 'grid'],
        ['label' => 'آفرها', 'route' => 'admin.offers.index', 'ability' => 'manageContent', 'icon' => 'tag'],
        ['label' => 'ارائه‌دهنده‌ها', 'route' => 'admin.providers.index', 'ability' => 'manageContent', 'icon' => 'globe'],
        ['label' => 'دسته‌بندی‌ها', 'route' => 'admin.categories.index', 'ability' => 'manageContent', 'icon' => 'folder'],
        ['label' => 'مدل‌ها', 'route' => 'admin.ai-models.index', 'ability' => 'manageContent', 'icon' => 'cpu'],
        ['label' => 'سازندگان مدل', 'route' => 'admin.model-creators.index', 'ability' => 'manageContent', 'icon' => 'layers'],
        ['label' => 'برچسب‌ها', 'route' => 'admin.tags.index', 'ability' => 'manageContent', 'icon' => 'hash'],
        ['label' => 'روش‌های تأیید', 'route' => 'admin.verification-methods.index', 'ability' => 'manageContent', 'icon' => 'shield'],
        ['label' => 'تبلیغات', 'route' => 'admin.ads.index', 'ability' => 'manageContent', 'icon' => 'megaphone'],
        ['label' => 'تنظیمات', 'route' => 'admin.settings.index', 'ability' => 'manageContent', 'icon' => 'sliders'],
        ['label' => 'گزارش‌های کاربران', 'route' => 'admin.reports.index', 'ability' => 'moderate', 'icon' => 'flag'],
        ['label' => 'پیشنهادهای کاربران', 'route' => 'admin.submissions.index', 'ability' => 'moderate', 'icon' => 'inbox'],
        ['label' => 'گزارش تحلیلی', 'route' => 'admin.analytics.index', 'ability' => 'moderate', 'icon' => 'chart'],
        ['label' => 'محتوای AI (تلگرام)', 'route' => 'admin.ai-content.index', 'ability' => 'manageContent', 'icon' => 'sparkles'],
        ['label' => 'کراولر', 'route' => 'admin.crawler.index', 'ability' => 'manageCrawlers', 'icon' => 'refresh'],
        ['label' => 'مدیران', 'route' => 'admin.admins.index', 'ability' => 'manageAdmins', 'icon' => 'users'],
    ];
@endphp
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'پنل مدیریت') — {{ \App\Support\Settings::get(\App\Support\Settings::SITE_TITLE) }}</title>
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
</head>
<body class="admin">
<div class="admin__shell">
    <aside class="admin__sidebar">
        <div class="admin__brand">
            <span class="admin__brand-mark">◆</span>
            <div>
                <div class="admin__brand-title">پنل مدیریت</div>
                <div class="admin__brand-sub">{{ \App\Support\Settings::get(\App\Support\Settings::SITE_TITLE) }}</div>
            </div>
        </div>

        <nav class="admin__nav" aria-label="منوی مدیریت">
            @foreach($nav as $item)
                @if($item['ability'] === null || ($admin && $admin->hasAbility($item['ability'])))
                    @php $isActive = request()->routeIs($item['route']) || request()->routeIs(\Illuminate\Support\Str::beforeLast($item['route'], '.index').'.*'); @endphp
                    <a class="admin__nav-item {{ $isActive ? 'is-active' : '' }}" href="{{ route($item['route']) }}">
                        <span>{{ $item['label'] }}</span>
                        @if($item['route'] === 'admin.reports.index' && ($pendingReportCount ?? 0) > 0)
                            <span class="admin__badge">{{ \App\Support\FaDigits::convert($pendingReportCount) }}</span>
                        @endif
                        @if($item['route'] === 'admin.submissions.index' && ($pendingSubmissionCount ?? 0) > 0)
                            <span class="admin__badge">{{ \App\Support\FaDigits::convert($pendingSubmissionCount) }}</span>
                        @endif
                    </a>
                @endif
            @endforeach
        </nav>

        <div class="admin__sidebar-foot">
            <a class="admin__nav-item {{ request()->routeIs('admin.profile.*') ? 'is-active' : '' }}" href="{{ route('admin.profile.index') }}">
                <span>پروفایل و رمز عبور</span>
            </a>
            <a class="admin__nav-item" href="{{ url('/') }}" target="_blank" rel="noopener"><span>مشاهده سایت ↗</span></a>
        </div>
    </aside>

    <div class="admin__main">
        <header class="admin__topbar">
            <h1 class="admin__page-title">@yield('heading', $title ?? '')</h1>
            <div class="admin__user">
                <span class="admin__user-name">{{ $admin?->name }}</span>
                <span class="admin__user-role">{{ \App\Models\Admin::ROLE_LABELS[$admin?->role] ?? $admin?->role }}</span>
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button class="btn btn--ghost" type="submit">خروج</button>
                </form>
            </div>
        </header>

        <main class="admin__content">
            @include('admin.partials.flash')
            @yield('content')
        </main>
    </div>
</div>
</body>
</html>
