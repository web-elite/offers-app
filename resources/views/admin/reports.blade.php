@extends('admin.layouts.app')
@section('title', 'گزارش تحلیلی')
@section('heading', 'گزارش تحلیلی')

@section('content')
    {{-- ── Period selector ── ─────────────────────────────────────── ──
    --}}
    <div class="toolbar">
        <div class="toolbar__filters">
            <label for="days" style="font-size:13px;color:var(--muted)">بازه:</label>
            <select name="days" id="days" onchange="this.form.submit()" style="width:auto">
                <option value="7"   {{ $days === 7   ? 'selected' : '' }}>۷ روز اخیر</option>
                <option value="30"  {{ $days === 30  ? 'selected' : '' }}>۳۰ روز اخیر</option>
                <option value="90"  {{ $days === 90  ? 'selected' : '' }}>۹۰ روز اخیر</option>
                <option value="365" {{ $days === 365 ? 'selected' : '' }}>یک سال اخیر</option>
            </select>
            <input type="hidden" name="days" value="{{ $days }}">
        </div>
        <div class="toolbar__spacer"></div>
        <a class="btn btn--primary" href="{{ route('admin.analytics.export', ['days' => $days]) }}">
            ⬇ خروجی CSV رویدادها
        </a>
    </div>

    {{-- ── KPI stat row ── ─────────────────────────────────────────── ──
    --}}
    <div class="stat-row">
        <div class="stat">
            <div class="stat__label">کل رویدادها ({{ $days }} روزه)</div>
            <div class="stat__value">{{ \App\Support\FaDigits::convert($totalEvents) }}</div>
        </div>
        <div class="stat">
            <div class="stat__label">گزارش‌های در انتظار</div>
            <div class="stat__value">{{ \App\Support\FaDigits::convert($pendingReports) }}</div>
        </div>
        <div class="stat">
            <div class="stat__label">پیشنهادهای در انتظار</div>
            <div class="stat__value">{{ \App\Support\FaDigits::convert($pendingSubmissions) }}</div>
        </div>
        <div class="stat">
            <div class="stat__label">اجرای کراولر</div>
            <div class="stat__value">{{ \App\Support\FaDigits::convert($crawlerRuns->count()) }}</div>
        </div>
        <div class="stat">
            <div class="stat__label">نرخ موفقیت کراولر</div>
            <div class="stat__value" style="color:{{ $crawlerRate >= 80 ? 'var(--ok)' : ($crawlerRate >= 50 ? 'var(--warn)' : 'var(--danger)') }}">
                {{ \App\Support\FaDigits::convert($crawlerRate) }}٪
            </div>
        </div>
    </div>

    {{-- ── Event breakdown ── ──────────────────────────────────────── ──
    --}}
    <div class="card">
        <h2 class="card__title">تفکیک رویدادها</h2>
        <p class="muted">تعداد رویدادها بر اساس نوع در بازه انتخاب‌شده</p>
        <table class="table">
            <thead>
                <tr><th>نوع رویداد</th><th>تعداد</th><th style="width:30%">نوار</th></tr>
            </thead>
            <tbody>
                @php $maxEvent = max(array_values($eventCounts) ?: [1]) @endphp
                @foreach($eventCounts as $type => $count)
                    @php $pct = $maxEvent > 0 ? round($count / $maxEvent * 100) : 0 @endphp
                    <tr>
                        <td><code>{{ $type }}</code></td>
                        <td>{{ \App\Support\FaDigits::convert($count) }}</td>
                        <td>
                            <div style="background:var(--surface-2);border-radius:4px;overflow:hidden;height:10px">
                                <div style="width:{{ $pct }}%;height:100%;background:var(--accent);border-radius:4px"></div>
                            </div>
                        </td>
                    </tr>
                @endforeach
                @if(empty($eventCounts))
                    <tr><td colspan="3" class="table__empty">رویدادی در این بازه ثبت نشده است.</td></tr>
                @endif
            </tbody>
        </table>
    </div>

    {{-- ── Top offers ── ───────────────────────────────────────────── ──
    --}}
    <div class="card">
        <h2 class="card__title">محبوب‌ترین آفرها (نمایش + کلیک + QR)</h2>
        <p class="muted">۱۰ آفر برتر بر اساس مجموع تعاملات</p>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>آفر</th>
                        <th>نمایش</th>
                        <th>کلیک</th>
                        <th>QR</th>
                        <th>مجموع</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($topOffers as $row)
                        <tr>
                            <td>
                                <a href="{{ route('public.offers.show', $row['slug']) }}" target="_blank">
                                    {{ $row['title'] }}
                                </a>
                                <div class="muted">/{{ $row['slug'] }}</div>
                            </td>
                            <td>{{ \App\Support\FaDigits::convert($row['views']) }}</td>
                            <td>{{ \App\Support\FaDigits::convert($row['clicks']) }}</td>
                            <td>{{ \App\Support\FaDigits::convert($row['qr']) }}</td>
                            <td style="font-weight:700;color:var(--accent)">{{ \App\Support\FaDigits::convert($row['total']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="table__empty">داده‌ای موجود نیست.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ── Crawler pipeline ── ──────────────────────────────────────── ──
    --}}
    <div class="card">
        <h2 class="card__title">وضعیت کراولر</h2>
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:14px">
            <div style="text-align:center;padding:12px;background:var(--surface-2);border-radius:var(--radius-sm)">
                <div style="font-size:24px;font-weight:700;font-family:'Vazirmatn FD'">{{ \App\Support\FaDigits::convert($crawlerOk) }}</div>
                <div class="muted">موفق</div>
            </div>
            <div style="text-align:center;padding:12px;background:var(--surface-2);border-radius:var(--radius-sm)">
                <div style="font-size:24px;font-weight:700;font-family:'Vazirmatn FD';color:var(--danger)">{{ \App\Support\FaDigits::convert($crawlerFail) }}</div>
                <div class="muted">ناموفق</div>
            </div>
            <div style="text-align:center;padding:12px;background:var(--surface-2);border-radius:var(--radius-sm)">
                <div style="font-size:24px;font-weight:700;font-family:'Vazirmatn FD';color:var(--accent)">{{ \App\Support\FaDigits::convert($crawlerRate) }}٪</div>
                <div class="muted">نرخ موفقیت</div>
            </div>
        </div>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr><th>منبع</th><th>حالت</th><th>شروع</th><th>مدت</th><th>خطا</th></tr>
                </thead>
                <tbody>
                    @forelse($crawlerRuns as $run)
                        <tr>
                            <td>{{ $run->crawlSource?->provider?->name ?? '—' }}</td>
                            <td>
                                <span style="color:{{ $run->status === 'ok' ? 'var(--ok)' : 'var(--danger)' }};font-weight:700">
                                    {{ $run->status }}
                                </span>
                            </td>
                            <td class="muted">{{ $run->started_at?->format('Y-m-d H:i') }}</td>
                            <td class="muted">{{ $run->duration_ms ? \App\Support\FaDigits::convert($run->duration_ms).'ms' : '—' }}</td>
                            <td class="muted" style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $run->error_message }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="table__empty">اجرای کراولری در این بازه ثبت نشده است.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ── Version source mix ── ────────────────────────────────────── ──
    --}}
    <div class="card">
        <h2 class="card__title">منبع تغییرات (OfferVersion)</h2>
        <p class="muted">تعداد نسخه‌های ثبت‌شده بر اساس منبع، در بازه انتخابی</p>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:10px">
            @php
                $sourceLabels = [
                    'crawler'     => 'کراولر',
                    'admin'       => 'مدیر',
                    'user_report' => 'گزارش کاربر',
                    'ai'          => 'هوش مصنوعی',
                ];
            @endphp
            @foreach($sourceMix as $source => $count)
                <div style="background:var(--surface-2);border-radius:var(--radius-sm);padding:12px;text-align:center">
                    <div style="font-size:20px;font-weight:700;font-family:'Vazirmatn FD'">{{ \App\Support\FaDigits::convert($count) }}</div>
                    <div class="muted">{{ $sourceLabels[$source] ?? $source }}</div>
                </div>
            @endforeach
            @if(empty($sourceMix))
                <div class="muted" style="grid-column:1/-1">تغییری در این بازه ثبت نشده است.</div>
            @endif
        </div>
    </div>

    {{-- ── Recent audit log ── ──────────────────────────────────────── ──
    --}}
    <div class="card">
        <h2 class="card__title">آخرین عملیات مدیران (Audit Log)</h2>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr><th>مدیر</th><th>عملیات</th><th>آیتم</th><th>تاریخ</th></tr>
                </thead>
                <tbody>
                    @forelse($recentAudits as $log)
                        <tr>
                            <td>{{ $log->actor }}</td>
                            <td>
                                <span style="font-weight:700;
                                    color:{{ $log->action === 'created' ? 'var(--ok)' : ($log->action === 'deleted' ? 'var(--danger)' : 'var(--accent)' }}">
                                    {{ $log->action }}
                                </span>
                            </td>
                            <td class="muted">{{ $log->entity }} #{{ \App\Support\FaDigits::convert($log->entity_id) }}</td>
                            <td class="muted">{{ $log->created_at?->format('Y-m-d H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="table__empty">موردی ثبت نشده است.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
