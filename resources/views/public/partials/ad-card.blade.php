@php
    $ad = $ad ?? null;
@endphp
@if($ad)
    @php $ad->increment('impressions'); @endphp
    <article class="ad-card" data-ad-id="{{ $ad->id }}" aria-label="تبلیغ">
        <span class="ad-card__label">تبلیغ</span>
        <a class="ad-card__link" href="{{ route('public.ads.click', $ad->id) }}" rel="nofollow sponsored noopener" target="_blank">
            @if($ad->image_url)
                <img class="ad-card__image" src="{{ $ad->image_url }}" alt="{{ $ad->title }}" loading="lazy">
            @elseif($ad->html_snippet)
                {!! $ad->html_snippet !!}
            @else
                <span class="ad-card__text">{{ $ad->title }}</span>
            @endif
        </a>
    </article>
@endif
