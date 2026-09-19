<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ورود مدیر — {{ \App\Support\Settings::get(\App\Support\Settings::SITE_TITLE) }}</title>
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    <style>
        body.admin { display:flex; align-items:center; justify-content:center; min-height:100vh; }
        .login-card { width:min(92vw, 380px); background:var(--surface); border:1px solid var(--line); border-radius:var(--radius); padding:26px 22px; }
        .login-card h1 { font-size:16px; margin:0 0 4px; }
        .login-card .hint { margin:0 0 20px; font-size:12.5px; color:var(--muted); }
        .login-card label { display:block; font-size:12.5px; color:var(--muted); margin:0 0 6px; }
        .login-card input { margin-bottom:14px; }
        .login-card .row { display:flex; align-items:center; gap:8px; margin-bottom:16px; font-size:13px; }
        .login-card .row input { width:auto; margin:0; }
        .login-card button { width:100%; }
    </style>
</head>
<body class="admin">
    <main class="login-card">
        <h1>ورود مدیر</h1>
        <p class="hint">دسترسی فقط برای مدیران مجاز است.</p>

        @if ($errors->any())
            <div class="alert alert--err">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.login.attempt') }}">
            @csrf
            <label for="email">ایمیل</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username" dir="ltr">

            <label for="password">رمز عبور</label>
            <input id="password" type="password" name="password" required autocomplete="current-password">

            <div class="row">
                <input id="remember" name="remember" type="checkbox" value="1">
                <label for="remember" style="margin:0">مرا به خاطر بسپار</label>
            </div>

            <button class="btn btn--primary" type="submit">ورود</button>
        </form>
    </main>
</body>
</html>
