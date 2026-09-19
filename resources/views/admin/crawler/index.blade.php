@extends('admin.layouts.app')

@section('title', 'کراولر')
@section('heading', 'کراولر')

@section('content')
    @if(!$engineReady)
        <div class="alert alert--warn">
            موتور کراولر در فاز بعدی فعال می‌شود. این صفحه در حال حاضر فقط منابع ثبت‌شده و تاریخچه اجرا را نشان می‌دهد.
        </div>
    @endif

    <div class="stat-row">
        <div class="stat"><div class="stat__label">منابع</div><div class="stat__value">{{ \App\Support\FaDigits::convert($stats['sources']) }}</div></div>
        <div class="stat"><div class="stat__label">فعال</div><div class="stat__value">{{ \App\Support\FaDigits::convert($stats['enabled']) }}</div></div>
        <div class="stat"><div class="stat__label">اجرای موفق</div><div class="stat__value">{{ \App\Support\FaDigits::convert($stats['ok']) }}</div></div>
        <div class="stat"><div class="stat__label">اجرای ناموفق</div><div class="stat__value">{{ \App\Support\FaDigits::convert($stats['failed']) }}</div></div>
    </div>

    <div class="card">
        <h2 class="card__title">منابع کراول</h2>
        <div class="table-wrap">
            <table class="table">
                <thead>
                <tr>
                    <th>ارائه‌دهنده</th>
                    <th>نوع منبع</th>
                    <th>آدرس</th>
                    <th>فعال</th>
                    <th>تناوب (ساعت)</th>
                    <th>آخرین اجرا</th>
                    <th>وضعیت</th>
                </tr>
                </thead>
                <tbody>
                @forelse($sources as $source)
                    @php $lastRun = $source->crawlRuns->first(); @endphp
                    <tr>
                        <td>{{ $source->provider?->name ?? '—' }}</td>
                        <td>{{ $source->source_type }}</td>
                        <td dir="ltr">{{ \Illuminate\Support\Str::limit($source->url, 40) }}</td>
                        <td>{{ $source->is_enabled ? 'بله' : 'خیر' }}</td>
                        <td>{{ \App\Support\FaDigits::convert($source->crawl_frequency_hours) }}</td>
                        <td>{{ $lastRun?->started_at?->format('Y-m-d H:i') ?? '—' }}</td>
                        <td>{{ $lastRun?->status ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="table__empty">منبعی ثبت نشده است.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="pager">{{ $sources->links() }}</div>
    </div>

    <div class="card">
        <h2 class="card__title">تاریخچه اجراها</h2>
        <div class="table-wrap">
            <table class="table">
                <thead>
                <tr>
                    <th>ارائه‌دهنده</th>
                    <th>شروع</th>
                    <th>وضعیت</th>
                    <th>کد HTTP</th>
                    <th>مدت (میلی‌ثانیه)</th>
                    <th>خطا</th>
                </tr>
                </thead>
                <tbody>
                @forelse($runs as $run)
                    <tr>
                        <td>{{ $run->crawlSource?->provider?->name ?? '—' }}</td>
                        <td>{{ $run->started_at?->format('Y-m-d H:i') ?? '—' }}</td>
                        <td>{{ $run->status }}</td>
                        <td>{{ $run->http_status ?? '—' }}</td>
                        <td>{{ $run->duration_ms !== null ? \App\Support\FaDigits::convert($run->duration_ms) : '—' }}</td>
                        <td>{{ \Illuminate\Support\Str::limit($run->error_message ?? '—', 60) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="table__empty">اجرایی ثبت نشده است.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
