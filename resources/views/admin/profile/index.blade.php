@extends('admin.layouts.app')

@section('title', 'پروفایل و رمز عبور')
@section('heading', 'پروفایل و رمز عبور')

@section('content')
    <div class="card">
        <h2 class="card__title">اطلاعات حساب</h2>
        <div class="kv">
            <div class="kv__row"><span class="kv__key">نام</span><span class="kv__val">{{ $admin->name }}</span></div>
            <div class="kv__row"><span class="kv__key">ایمیل</span><span class="kv__val" dir="ltr">{{ $admin->email }}</span></div>
            <div class="kv__row"><span class="kv__key">نقش</span><span class="kv__val">{{ \App\Models\Admin::ROLE_LABELS[$admin->role] ?? $admin->role }}</span></div>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.profile.update') }}">
        @csrf
        @method('PUT')
        <div class="card">
            <h2 class="card__title">تغییر رمز عبور</h2>
            <div class="form-grid">
                <div class="field field--col-4">
                    <label class="field__label" for="current_password">رمز عبور فعلی</label>
                    <input id="current_password" type="password" name="current_password" required autocomplete="current-password">
                </div>
                <div class="field field--col-4">
                    <label class="field__label" for="password">رمز عبور جدید</label>
                    <input id="password" type="password" name="password" required minlength="8" autocomplete="new-password">
                </div>
                <div class="field field--col-4">
                    <label class="field__label" for="password_confirmation">تکرار رمز جدید</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
                </div>
            </div>
        </div>

        <div class="form-actions">
            <button class="btn btn--primary" type="submit">تغییر رمز</button>
        </div>
    </form>
@endsection
