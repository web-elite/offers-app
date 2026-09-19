@extends('admin.layouts.app')

@section('title', 'داشبورد')
@section('heading', 'داشبورد')

@section('content')
    <div class="stat-row">
        <div class="stat"><div class="stat__label">ارائه‌دهنده‌ها</div><div class="stat__value">{{ \App\Support\FaDigits::convert($providersCount) }}</div></div>
        <div class="stat"><div class="stat__label">کل پیشنهادها</div><div class="stat__value">{{ \App\Support\FaDigits::convert($offersCount) }}</div></div>
        <div class="stat"><div class="stat__label">آفرهای فعال</div><div class="stat__value">{{ \App\Support\FaDigits::convert($activeOffersCount) }}</div></div>
        <div class="stat"><div class="stat__label">گزارش‌های در انتظار</div><div class="stat__value">{{ \App\Support\FaDigits::convert($pendingReportsCount) }}</div></div>
        <div class="stat"><div class="stat__label">پیشنهادهای در انتظار</div><div class="stat__value">{{ \App\Support\FaDigits::convert($pendingSubmissionsCount) }}</div></div>
    </div>

    <div class="card">
        <h2 class="card__title">آخرین اجرای کراولر</h2>
        @if ($lastCrawlRun)
            <div>{{ $lastCrawlRun->crawlSource?->provider?->name }} — {{ $lastCrawlRun->status }}</div>
            <div class="muted">
                شروع: {{ $lastCrawlRun->started_at?->format('Y-m-d H:i') }}
                @if ($lastCrawlRun->duration_ms) — مدت: {{ \App\Support\FaDigits::convert($lastCrawlRun->duration_ms) }}ms @endif
            </div>
        @else
            <div class="muted">هنوز اجرایی ثبت نشده است.</div>
        @endif
    </div>

    <div class="card">
        <h2 class="card__title">دسترسی سریع</h2>
        <div class="toolbar">
            <a class="btn btn--primary" href="{{ route('admin.offers.create') }}">افزودن آفر</a>
            <a class="btn" href="{{ route('admin.providers.create') }}">افزودن ارائه‌دهنده</a>
            <a class="btn" href="{{ route('admin.ads.create') }}">افزودن تبلیغ</a>
            <a class="btn" href="{{ route('admin.settings.index') }}">تنظیمات سایت</a>
            <a class="btn" href="{{ route('admin.analytics.index') }}">گزارش تحلیلی</a>
        </div>
    </div>
@endsection
