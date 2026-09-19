@extends('admin.layouts.app')

@section('title', 'تنظیمات سایت')
@section('heading', 'تنظیمات سایت')

@section('content')
    <form method="POST" action="{{ route('admin.settings.update') }}">
        @csrf
        <div class="card">
            <div class="form-grid">
                <div class="field field--col-6">
                    <label class="field__label" for="site_title_fa">عنوان سایت</label>
                    <input id="site_title_fa" name="site_title_fa" value="{{ $values['site_title_fa'] }}" dir="auto" required>
                </div>
                <div class="field field--col-6">
                    <label class="field__label" for="ads_interval">فاصله نمایش تبلیغ (تعداد کارت)</label>
                    <input id="ads_interval" name="ads_interval" type="number" min="1" max="50" value="{{ $values['ads_interval'] }}">
                    <p class="field__help">مثلاً ۴ یعنی بعد از هر ۴ کارت آفر، یک تبلیغ نمایش داده می‌شود.</p>
                </div>
                <div class="field field--col-12">
                    <label class="field__check">
                        <input type="hidden" name="ads_enabled" value="0">
                        <input type="checkbox" name="ads_enabled" value="1" @checked($values['ads_enabled'] === '1')>
                        <span>نمایش تبلیغات در سایت فعال باشد</span>
                    </label>
                </div>
                <div class="field field--col-12">
                    <label class="field__label" for="footer_text_fa">متن پاورقی سایت</label>
                    <textarea id="footer_text_fa" name="footer_text_fa" rows="3" dir="auto">{{ $values['footer_text_fa'] }}</textarea>
                </div>
            </div>
        </div>

        <div class="form-actions">
            <button class="btn btn--primary" type="submit">ذخیره تنظیمات</button>
        </div>
    </form>
@endsection
