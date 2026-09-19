<div class="empty-state">
    <span class="empty-state__icon" aria-hidden="true">@include('public.partials.icon', ['name' => 'search'])</span>
    <h3 class="empty-state__title">چیزی پیدا نشد</h3>
    <p class="empty-state__text">فیلترها را تغییر بده یا عبارت دیگری را جستجو کن.</p>
    <a class="btn btn--primary" href="{{ url('/') }}">حذف همه فیلترها</a>
</div>
