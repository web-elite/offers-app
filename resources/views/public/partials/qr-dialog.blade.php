<dialog id="qr-dialog" class="dialog" aria-labelledby="qr-title">
    <div class="dialog__head">
        <h2 id="qr-title">کد QR پیوند</h2>
        <button type="button" class="icon-btn" data-close-dialog aria-label="بستن">
            @include('public.partials.icon', ['name' => 'x'])
        </button>
    </div>
    <p class="dialog__hint">برای باز کردن سریعِ آفر روی موبایل، این کد را اسکن کن.</p>
    <p class="qr-url" dir="ltr"><code id="qr-url"></code></p>
    <div class="qr-stage">
        <canvas id="qr-canvas" width="240" height="240" role="img" aria-label="کد QR پیوند آفر"></canvas>
        <p id="qr-error" class="dialog__error" hidden>کتابخانه QR در دسترس نیست.</p>
    </div>
    <div class="dialog__actions">
        <button type="button" class="btn btn--primary" id="qr-download">@include('public.partials.icon', ['name' => 'download'])دانلود PNG</button>
        <button type="button" class="btn btn--ghost" id="qr-copy">@include('public.partials.icon', ['name' => 'copy'])کپی URL</button>
        <a class="btn btn--ghost" id="qr-open" target="_blank" rel="nofollow sponsored noopener">باز کردن سایت <span aria-hidden="true">↗</span></a>
    </div>
</dialog>
