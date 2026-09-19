@extends('admin.layouts.app')

@section('title', 'محتوای AI — جزئیات')
@section('heading', 'جزئیات پیام')

@section('content')
    @php $result = $message->aiAnalysisResult; $offer = $message->offer; @endphp

    <div class="card">
        <div class="section">
            <h3 class="section__title">پیام تلگرام</h3>
            <dl class="dl">
                <dt>Identifier</dt>
                <dd dir="ltr">#{{ \App\Support\FaDigits::convert($message->id) }} @if($message->telegram_message_id)(TG {{ $message->telegram_message_id }})@endif</dd>
                <dt>Chat</dt>
                <dd dir="ltr">{{ $message->chat_id ?? '—' }}</dd>
                <dt>ارسال‌کننده</dt>
                <dd>{{ $message->sender_name ?? '—' }}</dd>
                <dt>وضعیت</dt>
                <dd><span class="badge badge--{{ $message->status }}">{{ $message->status }}</span></dd>
                <dt>وقتی</dt>
                <dd>{{ \App\Support\FaDigits::diffForHumans($message->created_at) }}</dd>
                @if($message->url)
                    <dt>URL</dt>
                    <dd dir="ltr"><a href="{{ $message->url }}" target="_blank" rel="noopener">{{ $message->url }}</a></dd>
                @endif
            </dl>
        </div>

        <div class="section">
            <h3 class="section__title">متن (خام)</h3>
            <pre class="pre" dir="auto">{{ $message->text ?? '(تخمی)' }}</pre>
        </div>

        @if($message->error)
            <div class="section">
                <h3 class="section__title">خطا</h3>
                <pre class="pre pre--error" dir="ltr">{{ $message->error }}</pre>
            </div>
        @endif

        @if($result)
            <div class="section">
                <h3 class="section__title">نتیجه AI</h3>
                <dl class="dl">
                    <dt>مدل</dt>
                    <dd dir="ltr">{{ $result->model ?? '—' }}</dd>
                    <dt>ارائه‌دهنده</dt>
                    <dd>{{ $result->provider_name ?? '—' }}</dd>
                    <dt>URL ارائه‌دهنده</dt>
                    <dd dir="ltr">{{ $result->provider_url ?? '—' }}</dd>
                    <dt>عنوان آفر</dt>
                    <dd>{{ $result->offer_title ?? '—' }}</dd>
                    <dt>توصیف آفر</dt>
                    <dd>{{ $result->offer_description ?? '—' }}</dd>
                    <dt>نوع آفر</dt>
                    <dd dir="ltr">{{ $result->free_tier_type ?? '—' }}</dd>
                    <dt>نوع دسترسی</dt>
                    <dd dir="ltr">{{ implode(', ', $result->access_types ?? []) }}</dd>
                    <dt>اعتبار</dt>
                    <dd dir="ltr">{{ $result->credits_amount ?? '—' }} {{ $result->credits_unit ?? '' }}</dd>
                    <dt>وضعیت آفر</dt>
                    <dd dir="ltr">{{ $result->offer_status ?? '—' }}</dd>
                    <dt>ماده‌سازی</dt>
                    <dd>{{ $result->materialized ? 'بله' : 'خیر' }}</dd>
                </dl>

                <h4 class="section__subtitle">Response خام LLM</h4>
                <pre class="pre" dir="ltr">{{ json_encode($result->response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            </div>
        @else
            <div class="card card--empty">هنوز نتیجه AI موجود نیست.</div>
        @endif

        @if($offer)
            <div class="section">
                <h3 class="section__title">آفر ساخته‌شده</h3>
                <dl class="dl">
                    <dt>عنوان</dt>
                    <dd>{{ $offer->title_fa }}</dd>
                    <dt>ارائه‌دهنده</dt>
                    <dd>{{ $offer->provider?->name ?? '—' }}</dd>
                    <dt>Slug</dt>
                    <dd dir="ltr">{{ $offer->slug }}</dd>
                    <dt>نوع آفر</dt>
                    <dd dir="ltr">{{ $offer->free_tier_type }}</dd>
                    <dt>وضعیت</dt>
                    <dd dir="ltr">{{ $offer->status }}</dd>
                </dl>
                <div class="form-actions">
                    <a class="btn btn--primary" href="{{ route('public.offers.show', $offer->slug) }}" target="_blank">مشاهده در سایت ↗</a>
                    <a class="btn" href="{{ route('admin.offers.edit', $offer->id) }}">ویرایش آفر</a>
                </div>
            </div>
        @endif

        <div class="section">
            <h3 class="section__title">Payload خام تلگرام</h3>
            <details>
                <summary class="muted">نمایش JSON کامل (برای عیب‌یابی)</summary>
                <pre class="pre" dir="ltr">{{ json_encode($message->raw_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            </details>
        </div>
    </div>
@endsection
