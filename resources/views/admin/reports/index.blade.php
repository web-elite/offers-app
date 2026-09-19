@extends('admin.layouts.app')

@section('title', 'گزارش‌های کاربران')
@section('heading', 'گزارش‌های کاربران')

@section('content')
    <div class="card">
        <div class="toolbar">
            <div class="tabs">
                @foreach(['pending' => 'در انتظار', 'resolved' => 'تأییدشده', 'rejected' => 'ردشده'] as $key => $label)
                    <a class="tabs__item {{ $status === $key ? 'is-active' : '' }}"
                       href="{{ route('admin.reports.index', ['status' => $key]) }}">{{ $label }}</a>
                @endforeach
            </div>
            <div class="toolbar__spacer"></div>
            <span class="muted">در انتظار بررسی: {{ \App\Support\FaDigits::convert($pendingCount) }}</span>
        </div>

        <div class="table-wrap">
            <table class="table">
                <thead>
                <tr>
                    <th>آفر</th>
                    <th>ارائه‌دهنده</th>
                    <th>نوع گزارش</th>
                    <th>توضیح کاربر</th>
                    <th>وضعیت</th>
                    <th class="table__actions">عملیات</th>
                </tr>
                </thead>
                <tbody>
                @forelse($reports as $report)
                    <tr>
                        <td>{{ $report->offer?->title_fa ?? '—' }}</td>
                        <td>{{ $report->offer?->provider?->name ?? '—' }}</td>
                        <td>{{ \App\Support\OfferPresenter::REPORT_LABELS[$report->report_type] ?? $report->report_type }}</td>
                        <td>{{ $report->details_fa ?? '—' }}</td>
                        <td>{{ $report->status }}</td>
                        <td class="table__actions">
                            @if($report->status === 'pending')
                                <form method="POST" action="{{ route('admin.reports.resolve', $report->id) }}"
                                      onsubmit="return confirm('گزارش تأیید شود و آفر منقضی علامت بخورد؟')">
                                    @csrf
                                    <button class="btn btn--sm btn--primary" type="submit">تأیید و منقضی کردن آفر</button>
                                </form>
                                <form method="POST" action="{{ route('admin.reports.reject', $report->id) }}">
                                    @csrf
                                    <button class="btn btn--sm" type="submit">رد</button>
                                </form>
                            @else
                                <span class="muted">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="table__empty">گزارشی در این وضعیت وجود ندارد.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="pager">{{ $reports->links() }}</div>
    </div>
@endsection
