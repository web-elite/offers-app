<article class="offer-card offer-card--clean" data-offer-slug="{{ $offer->slug }}">
    <div class="offer-card__head">
        <span class="offer-card__provider">{{ $offer->provider?->name }}</span>
        @include('public.partials.freshness-badge', ['offer' => $offer])
    </div>

    <h3 class="offer-card__title">
        <a href="{{ route('public.offers.show', $offer->slug) }}">{{ $offer->title_fa }}</a>
    </h3>

    @if(!empty($offer->description_fa))
        <p class="offer-card__desc">{{ \Illuminate\Support\Str::limit($offer->description_fa, 90) }}</p>
    @endif

    <div class="offer-card__badge">{{ \App\Support\OfferPresenter::amountBadge($offer) }}</div>

    <div class="offer-card__foot">
        <a class="offer-card__cta" href="{{ route('public.offers.show', $offer->slug) }}">{{ __('ui.offer_details_cta') }} <span aria-hidden="true">←</span></a>
        <a class="offer-card__goto" href="{{ route('public.go', $offer->slug) }}" rel="nofollow sponsored noopener" target="_blank" aria-label="{{ __('ui.offer_goto_site') }}">↗</a>
    </div>
</article>
