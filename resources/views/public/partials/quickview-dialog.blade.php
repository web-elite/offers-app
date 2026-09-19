<dialog id="quickview" class="dialog dialog--wide" aria-labelledby="quickview-title">
    <div class="dialog__head">
        <div>
            <p class="quickview__provider" id="quickview-provider"></p>
            <h2 id="quickview-title"></h2>
        </div>
        <button type="button" class="icon-btn" data-close-dialog aria-label="بستن">
            @include('public.partials.icon', ['name' => 'x'])
        </button>
    </div>

    <div class="quickview__badges" id="quickview-badges"></div>
    <p class="quickview__desc" id="quickview-desc"></p>
    <blockquote class="quickview__note" id="quickview-note" hidden></blockquote>

    <dl class="quickview__meta" id="quickview-meta"></dl>

    <div class="quickview__section" id="quickview-freemodels" hidden>
        <h3>مدل‌های رایگان امروز</h3>
        <div class="chips chips--wrap" id="quickview-freemodels-chips"></div>
        <p class="quickview__note-muted" id="quickview-freemodels-note"></p>
    </div>

    <div class="quickview__section" id="quickview-versions" hidden>
        <h3>تغییرات</h3>
        <ul class="versions" id="quickview-versions-list"></ul>
    </div>

    <div class="dialog__actions">
        <a class="btn btn--primary" id="quickview-go" target="_blank" rel="nofollow sponsored noopener">رفتن به سایت <span aria-hidden="true">↗</span></a>
        <button type="button" class="btn btn--ghost" id="quickview-qr">@include('public.partials.icon', ['name' => 'qr'])نمایش QR</button>
        <button type="button" class="btn btn--ghost" id="quickview-copy">@include('public.partials.icon', ['name' => 'copy'])کپی URL</button>
        <a class="btn btn--ghost" id="quickview-detail">صفحه کامل</a>
    </div>
</dialog>
