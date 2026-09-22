@php
    $siteTitle = \App\Support\Settings::get(\App\Support\Settings::SITE_TITLE);
    $footerText = \App\Support\Settings::get(\App\Support\Settings::FOOTER_TEXT, '');
    $defaultTitle = $siteTitle.' | دایرکتوری سرویس‌های هوش مصنوعی رایگان';
    $defaultDescription = 'دایرکتوری آفرهای رایگان و مقرون‌به‌صرفه سرویس‌های هوش مصنوعی: اعتبار رایگان، مدل‌های رایگان، شرایط استفاده و لینک ثبت‌نام — همراه با فیلترهای دقیق.';
    $canonical = url()->current();
@endphp
@php
    $locale = app()->getLocale();
    $dir = $locale === 'fa' ? 'rtl' : 'ltr';
    $theme = session('theme', 'dark');
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $dir }}" data-theme="{{ $theme }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', $defaultTitle)</title>
    <meta name="description" content="@yield('meta_description', $defaultDescription)">
    <link rel="canonical" href="@yield('canonical', $canonical)">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ $siteTitle }}">
    <meta property="og:title" content="@yield('og_title', $defaultTitle)">
    <meta property="og:description" content="@yield('meta_description', $defaultDescription)">
    <meta property="og:url" content="@yield('canonical', $canonical)">
    <meta name="twitter:card" content="summary">
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link rel="sitemap" type="application/xml" title="Sitemap" href="{{ route('public.sitemap') }}">
    @vite(['resources/css/app.css', 'resources/js/public-app.js', 'resources/js/qrcode-lib.js'])
    <script>
        // Restore persisted theme before paint to avoid a flash
        ;(function () {
            var t = localStorage.getItem('theme');
            if (t) {
                document.documentElement.setAttribute('data-theme', t);
            }
        })();
    </script>
</head>
<body>
<a class="skip-link" href="#main">{{ __('ui.go_to_content') }}</a>

<header class="site-header">
    <div class="container site-header__inner">
        <div class="brand">
            <span class="brand__mark" aria-hidden="true">@include('public.partials.icon', ['name' => 'sparkles'])</span>
            <div class="brand__text">
                <a class="brand__title" href="{{ url('/') }}">{{ $siteTitle }}</a>
                <p class="brand__tagline">{{ __('ui.tagline') }}</p>
            </div>
        </div>
        <form class="header-search" method="GET" action="{{ url('/') }}" role="search">
            <input type="search" name="q" value="{{ request('q') }}"
                   placeholder="{{ __('ui.search_placeholder') }}"
                   aria-label="{{ __('ui.search') }}">
            <button type="submit" class="icon-btn" aria-label="{{ __('ui.search') }}">
                @include('public.partials.icon', ['name' => 'search'])
            </button>
        </form>

        <div class="header-tools" role="toolbar" aria-label="{{ __('ui.go_to_content') }}">
            <a class="lang-toggle"
               href="{{ route('public.locale.switch', $locale === 'fa' ? 'en' : 'fa') }}"
               title="{{ $locale === 'fa' ? 'Switch to English' : 'فارسی' }}">
                <span class="lang-toggle__label">{{ $locale === 'fa' ? 'EN' : 'فا' }}</span>
            </a>
            <button class="theme-toggle" type="button" data-theme-toggle
                    aria-label="{{ $theme === 'dark' ? __('ui.theme_light') : __('ui.theme_dark') }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    @if($theme === 'dark')
                        <circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/>
                    @else
                        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
                    @endif
                </svg>
                <span class="theme-toggle__label">{{ $theme === 'dark' ? __('ui.theme_light') : __('ui.theme_dark') }}</span>
            </button>
        </div>
    </div>
</header>

<main id="main">
    @yield('content')
</main>

<footer class="site-footer">
    <div class="container site-footer__inner">
        <p><a href="{{ url('/#submit') }}">{{ __('ui.footer_link') }}</a></p>
        @if($footerText !== '')
            <p class="site-footer__note">{{ $footerText }}</p>
        @else
            <p class="site-footer__note">{{ __('ui.footer_note') }}</p>
        @endif
    </div>
</footer>

@include('public.partials.quickview-dialog')
@include('public.partials.qr-dialog')
@include('public.partials.report-modal')
</body>
</html>
