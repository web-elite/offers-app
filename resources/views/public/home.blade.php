@extends('layouts.public')

@section('title', \App\Support\Settings::get(\App\Support\Settings::SITE_TITLE).' | '.__('ui.home_title_suffix'))

@section('content')
    {{-- Mobile-only quick filter band (hidden on desktop). The desktop filter sidebar
         is rendered inline below, inside the two-column layout grid, so it aligns with
         the offers content instead of floating as a full-width band above it. --}}
    @include('public.partials.filter-bar')

    <div class="container page page--with-sidebar">
        <div class="filterbar__layout">
            {{-- Desktop: sticky filter sidebar — hidden on mobile (≤720px) --}}
            <form method="GET" action="{{ url('/') }}" class="filterbar__form filterbar__form--sidebar" aria-label="{{ __('ui.search') }}">
                <div class="filterbar__sidebar-head">
                    <h2 class="filterbar__sidebar-title">
                        @include('public.partials.icon', ['name' => 'filter'])
                        {{ __('ui.more_filters') }}
                    </h2>
                </div>
                @include('public.partials.filter-fields', [
                    'variant' => 'sidebar',
                ])
            </form>

            <div class="page__body">
            @if($showStrips)
            @if($freshOffers->isNotEmpty())
                <section class="strip" aria-labelledby="fresh-title">
                    <div class="strip__head">
                        <h2 id="fresh-title" class="strip__title">@include('public.partials.icon', ['name' => 'clock']){{ __('ui.latest_reviews') }}</h2>
                        <span class="muted">{{ \App\Support\FaDigits::convert($freshOffers->count()) }} {{ __('ui.offers_count') }}</span>
                    </div>
                    <div class="strip__scroller" role="list">
                        @foreach($freshOffers as $stripOffer)
                            @include('public.partials.offer-card', [
                                'offer' => $stripOffer,
                                'crawl' => $crawlMap[$stripOffer->provider_id] ?? null,
                                'variant' => 'strip',
                            ])
                        @endforeach
                    </div>
                </section>
            @endif

            @if($dailyOffers->isNotEmpty())
                <section class="strip" aria-labelledby="daily-title">
                    <div class="strip__head">
                        <h2 id="daily-title" class="strip__title">@include('public.partials.icon', ['name' => 'sparkles']){{ __('ui.daily_reset') }}</h2>
                        <span class="muted">{{ __('ui.daily_reset_sub') }}</span>
                    </div>
                    <div class="strip__scroller" role="list">
                        @foreach($dailyOffers as $dailyOffer)
                            @include('public.partials.offer-card', [
                                'offer' => $dailyOffer,
                                'crawl' => $crawlMap[$dailyOffer->provider_id] ?? null,
                                'variant' => 'strip',
                            ])
                        @endforeach
                    </div>
                </section>
            @endif
        @endif

        <section class="grid-section" aria-labelledby="all-title">
            <div class="grid-section__head">
                <h2 id="all-title" class="grid-section__title">{{ __('ui.all_offers') }}</h2>
                <span class="muted">{{ \App\Support\FaDigits::convert($offers->count()) }} {{ __('ui.offers_count') }}</span>
            </div>

            @include('public.partials.active-filters', ['activeChips' => $activeChips])

            @if($offers->isEmpty())
                @include('public.partials.empty-state')
            @else
                <div class="offer-grid">
                    @foreach($offers as $offer)
                        @include('public.partials.offer-card', [
                            'offer' => $offer,
                            'crawl' => $crawlMap[$offer->provider_id] ?? null,
                            'variant' => 'grid',
                        ])

                        @if(!$loop->last && isset($adSlots[$loop->iteration]))
                            @include('public.partials.ad-card', ['ad' => $adSlots[$loop->iteration]])
                        @endif
                    @endforeach

                    @if($afterGridAd)
                        @include('public.partials.ad-card', ['ad' => $afterGridAd])
                    @endif
                </div>
            @endif
        </section>
            </div>
        </div>
    </div>

    @include('public.partials.submit-form')
@endsection
