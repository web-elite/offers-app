@extends('admin.layouts.app')

@section('title', 'محتوای AI (تلگرام)')
@section('heading', 'محتوای AI (تلگرام)')

@section('content')
    <div class="card">
        <div class="toolbar">
            <div class="tabs">
                @php
                    $tabLabels = [
                        '' => 'همه',
                        'pending' => 'در انتظار',
                        'processed' => 'پردازش‌شده',
                        'success' => 'موفق',
                        'failed' => 'ناموفق',
                        'skipped' => 'ردشده',
                    ];
                @endphp
                @foreach($tabLabels as $key => $label)
                    <a class="tabs__item {{ $status === $key ? 'is-active' : '' }}"
                       href="{{ route('admin.ai-content.index', $key !== '' ? ['status' => $key] : []) }}">{{ $label }}</a>
                @endforeach
            </div>
            <div class="toolbar__spacer"></div>
            <div>
                <a class="btn btn--sm" href="{{ route('admin.ai-content.settings') }}">⚙️ تنظیمات LLM / تلگرام</a>
            </div>
        </div>

        <div class="toolbar" style="margin-bottom: .5rem;">
            <form method="GET" class="inline-form">
                <input type="hidden" name="status" value="{{ $status }}">
                <input type="search" name="q" placeholder="جستجو در متن / ارسال‌کننده / URL…"
                       value="{{ request('q') }}" dir="ltr">
                <button class="btn btn--sm" type="submit">فیلتر</button>
            </form>
        </div>

        <div class="table-wrap">
            <table class="table">
                <thead>
                <tr>
                    <th>#</th>
                    <th>متن / URL</th>
                    <th>ارسال‌کننده</th>
                    <th>ارائه‌دهنده (AI)</th>
                    <th>آفر</th>
                    <th>وضعیت</th>
                    <th>وقتی</th>
                    <th class="table__actions">عملیات</th>
                </tr>
                </thead>
                <tbody>
                @forelse($messages as $m)
                    <tr>
                        <td dir="ltr">{{ \App\Support\FaDigits::convert($m->id) }}</td>
                        <td dir="ltr" class="truncate" title="{{ $m->text }}">
                            @if($m->url)
                                <a href="{{ $m->url }}" target="_blank" rel="noopener nofollow">{{ \Illuminate\Support\Str::limit($m->url, 50) }}</a>
                            @else
                                {{ \Illuminate\Support\Str::limit((string) $m->text, 60) }}
                            @endif
                        </td>
                        <td>{{ $m->sender_name ?? '—' }}</td>
                        <td>
                            @if($m->aiAnalysisResult)
                                {{ $m->aiAnalysisResult->provider_name ?? '—' }}
                            @else
                                <span class="muted">—</span>
                            @endif
                        </td>
                        <td>
                            @if($m->offer)
                                <a href="{{ route('public.offers.show', $m->offer->slug) }}" target="_blank">
                                    {{ \Illuminate\Support\Str::limit($m->offer->title_fa, 40) }}
                                </a>
                                <div class="muted small">{{ $m->offer->status }}</div>
                            @else
                                <span class="muted">—</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge badge--{{ $m->status }}">{{ $m->status }}</span>
                            @if($m->status === 'failed' && $m->error)
                                <div class="muted small" dir="ltr">{{ \Illuminate\Support\Str::limit($m->error, 80) }}</div>
                            @endif
                        </td>
                        <td class="muted small">{{ \App\Support\FaDigits::diffForHumans($m->created_at) }}</td>
                        <td class="table__actions">
                            <a class="btn btn--sm" href="{{ route('admin.ai-content.show', $m->id) }}">جزئیات</a>
                            @if(in_array($m->status, ['failed', 'skipped'], true))
                                <form method="POST" action="{{ route('admin.ai-content.retry', $m->id) }}">
                                    @csrf
                                    <button class="btn btn--sm btn--primary" type="submit">تلاش دوباره</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="table__empty">موردی یافت نشد.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="pager">{{ $messages->links() }}</div>
    </div>
@endsection
