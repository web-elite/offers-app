<dialog id="report-dialog" class="dialog" aria-labelledby="report-title">
    <div class="dialog__head">
        <h2 id="report-title">گزارش مشکل</h2>
        <button type="button" class="icon-btn" data-close-dialog aria-label="بستن">
            @include('public.partials.icon', ['name' => 'x'])
        </button>
    </div>
    <p class="dialog__hint">مشکل مربوط به <strong id="report-offer-name"></strong> چیست؟</p>
    <form id="report-form" method="POST" action="">
        @csrf
        <label class="field">نوع مشکل
            <select name="report_type" required>
                @foreach(\App\Support\OfferPresenter::REPORT_LABELS as $reportValue => $reportLabel)
                    <option value="{{ $reportValue }}">{{ $reportLabel }}</option>
                @endforeach
            </select>
        </label>
        <label class="field">توضیحات (اختیاری)
            <textarea name="details_fa" rows="3" maxlength="2000" placeholder="اگر توضیحی داری بنویس…"></textarea>
        </label>
        <p id="report-status" class="dialog__status" role="status"></p>
        <div class="dialog__actions">
            <button type="submit" class="btn btn--primary" id="report-submit">ارسال گزارش</button>
            <button type="button" class="btn btn--ghost" data-close-dialog>انصراف</button>
        </div>
    </form>
</dialog>
