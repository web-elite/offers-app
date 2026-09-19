@php
    $fieldLabels = [
        'title_fa' => 'عنوان آفر',
        'credits_amount' => 'مقدار اعتبار',
        'credits_unit' => 'واحد اعتبار',
        'free_tier_type' => 'نوع آفر',
        'status' => 'وضعیت آفر',
    ];
@endphp
<div class="card" id="overrides">
    <h2 class="card__title">بازنویسی دستی (Override)</h2>
    <p class="muted">مقدار دستی بر نتیجه‌ی کراولر و مقدار ذخیره‌شده اولویت دارد. کراولر هرگز این فیلدها را بازنویسی نمی‌کند.</p>

    @if($overrides->isEmpty())
        <p class="muted">هنوز بازنویسی‌ای ثبت نشده است.</p>
    @else
        <div class="table-wrap">
            <table class="table">
                <thead>
                <tr>
                    <th>فیلد</th>
                    <th>مقدار لحظه‌ی ثبت (کراولر/آفر)</th>
                    <th>مقدار بازنویسی</th>
                    <th>دلیل</th>
                    <th>ثبت‌کننده</th>
                    <th class="table__actions">عملیات</th>
                </tr>
                </thead>
                <tbody>
                @foreach($overrides as $override)
                    <tr>
                        <td>{{ $fieldLabels[$override->field] ?? $override->field }}</td>
                        <td>{{ $override->crawler_value ?? '—' }}</td>
                        <td><strong>{{ $override->override_value }}</strong></td>
                        <td>{{ $override->reason }}</td>
                        <td class="muted">{{ $override->actor }}</td>
                        <td class="table__actions">
                            <form method="POST" action="{{ route('admin.offers.overrides.destroy', [$item->id, $override->id]) }}"
                                  onsubmit="return confirm('بازنویسی حذف شود؟')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn--sm btn--danger" type="submit">حذف</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <h3 class="card__subtitle">مقدار مؤثر فعلی</h3>
    <div class="kv">
        @foreach($effective as $field => $value)
            <div class="kv__row">
                <span class="kv__key">{{ $fieldLabels[$field] ?? $field }}</span>
                <span class="kv__val">{{ $value ?? '—' }}</span>
            </div>
        @endforeach
    </div>

    <h3 class="card__subtitle">افزودن بازنویسی</h3>
    <form method="POST" action="{{ route('admin.offers.overrides.store', $item->id) }}" class="form-grid">
        @csrf
        <div class="field field--col-4">
            <label class="field__label" for="ov-field">فیلد</label>
            <select id="ov-field" name="field" required>
                @foreach($overrideFields as $field)
                    <option value="{{ $field }}">{{ $fieldLabels[$field] ?? $field }}</option>
                @endforeach
            </select>
        </div>
        <div class="field field--col-4">
            <label class="field__label" for="ov-value">مقدار بازنویسی</label>
            <input id="ov-value" name="override_value" dir="auto" required>
        </div>
        <div class="field field--col-4">
            <label class="field__label" for="ov-reason">دلیل</label>
            <input id="ov-reason" name="reason" dir="auto" required>
        </div>
        <div class="field field--col-12">
            <button class="btn btn--primary" type="submit">ثبت بازنویسی</button>
        </div>
    </form>
</div>
