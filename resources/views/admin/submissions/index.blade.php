@extends('admin.layouts.app')

@section('title', 'پیشنهادهای کاربران')
@section('heading', 'پیشنهادهای کاربران')

@section('content')
    <div class="card">
        <div class="toolbar">
            <div class="tabs">
                @foreach(['pending' => 'در انتظار', 'approved' => 'تأییدشده', 'rejected' => 'ردشده'] as $key => $label)
                    <a class="tabs__item {{ $status === $key ? 'is-active' : '' }}"
                       href="{{ route('admin.submissions.index', ['status' => $key]) }}">{{ $label }}</a>
                @endforeach
            </div>
            <div class="toolbar__spacer"></div>
            <span class="muted">در انتظار بررسی: {{ \App\Support\FaDigits::convert($pendingCount) }}</span>
        </div>

        <div class="table-wrap">
            <table class="table">
                <thead>
                <tr>
                    <th>نام سرویس</th>
                    <th>لینک</th>
                    <th>توضیح</th>
                    <th>تماس</th>
                    <th>وضعیت</th>
                    <th class="table__actions">عملیات</th>
                </tr>
                </thead>
                <tbody>
                @forelse($submissions as $submission)
                    <tr>
                        <td>{{ $submission->provider_name }}</td>
                        <td dir="ltr"><a href="{{ $submission->url }}" target="_blank" rel="noopener nofollow">{{ $submission->url }}</a></td>
                        <td>{{ $submission->offer_note_fa ?? '—' }}</td>
                        <td>{{ $submission->contact ?? '—' }}</td>
                        <td>{{ $submission->status }}</td>
                        <td class="table__actions">
                            @if($submission->status === 'pending')
                                <form method="POST" action="{{ route('admin.submissions.approve', $submission->id) }}"
                                      onsubmit="return confirm('تأیید شود و آفر پیش‌نویس ساخته شود؟')">
                                    @csrf
                                    <button class="btn btn--sm btn--primary" type="submit">تأیید</button>
                                </form>
                                <form method="POST" action="{{ route('admin.submissions.reject', $submission->id) }}">
                                    @csrf
                                    <button class="btn btn--sm" type="submit">رد</button>
                                </form>
                            @else
                                <span class="muted">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="table__empty">پیشنهادی در این وضعیت وجود ندارد.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="pager">{{ $submissions->links() }}</div>
    </div>
@endsection
