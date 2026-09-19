@if(!empty($activeChips))
    <div class="active-filters" aria-label="فیلترهای فعال">
        <span class="active-filters__label">فیلترهای فعال:</span>
        @foreach($activeChips as $chip)
            <a class="chip chip--active" href="{{ $chip['url'] }}">
                {{ $chip['label'] }}
                <span class="chip__x" aria-hidden="true">×</span>
                <span class="visually-hidden">— حذف این فیلتر</span>
            </a>
        @endforeach
        <a class="active-filters__clear" href="{{ url('/') }}">حذف همه</a>
    </div>
@endif
