@extends('layouts.public')

@section('title', 'صفحه پیدا نشد | آفرهای رایگان AI')

@section('content')
    <div class="container page">
        <div class="empty-state">
            <span class="empty-state__icon" aria-hidden="true">۴۰۴</span>
            <h1 class="empty-state__title">صفحه‌ای که دنبالش بودی پیدا نشد</h1>
            <p class="empty-state__text">ممکن است این آفر حذف شده یا نشانی اشتباه باشد.</p>
            <a class="btn btn--primary" href="{{ url('/') }}">بازگشت به فهرست آفرها</a>
        </div>
    </div>
@endsection
