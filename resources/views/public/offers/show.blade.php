@extends('layouts.public')

@php
    $amountBadge = \App\Support\OfferPresenter::amountBadge($offer);
    $tierLabel = \App\Support\OfferPresenter::TIER_LABELS[$offer->free_tier_type] ?? null;
    $pricingLabel = \App\Support\OfferPresenter::pricingLabel($offer);
    $statusLabel = \App\Support\OfferPresenter::STATUS_LABELS[$offer->status] ?? null;
    $accessLabels = \App\Support\OfferPresenter::accessLabels($offer);
    $verificationChips = \App\Support\OfferPresenter::verificationChips($offer);
    $noVerification = $verificationChips === [];
    $providerName = $offer->provider?->name ?? 'نامشخص';
    $description = $offer->description_fa ?: __('ui.detail_no_description');
    $versions = $offer->versions->sortByDesc('detected_at')->take(3);
    $goUrl = route('public.go', $offer->slug);
@endphp

@section('title', $offer->title_fa.' | '.__('ui.detail_tagline'))
@section('og_title', $offer->title_fa)
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags((string) $offer->description_fa), 155, '…'))
@section('canonical', url()->current())

@section('content')
    <nav class="breadcrumb container" aria-label="مسیر صفحه">
        <a href="{{ url('/') }}">{{ __('ui.tagline') }}</a>
        <span aria-hidden="true">/</span>
        <span aria-current="page">{{ $providerName }}</span>
    </nav>

    <article class="offer-detail container">
        {{-- Hero: plain-language summary a 5-year-old can follow --}}
        <section class="detail-hero" aria-labelledby="detail-hero-title">
            <div class="detail-hero__head">
                @if($offer->provider?->category)
                    <span class="chip chip--category">{{ $offer->provider->category->name_fa }}</span>
                @endif
                @if($offer->provider?->canonical_url)
                    <a class="detail-hero__provider" href="{{ $offer->provider->canonical_url }}" target="_blank" rel="noopener nofollow">
                        {{ $offer->provider->name }} <span aria-hidden="true">↗</span>
                    </a>
                @else
                    <span class="detail-hero__provider">{{ $providerName }}</span>
                @endif
                @include('public.partials.freshness-badge', ['offer' => $offer])
            </div>

            <h1 id="detail-hero-title" class="detail-hero__title">{{ $offer->title_fa }}</h1>

            <div class="detail-hero__summary">
                <div class="summary-card">
                    <span class="summary-card__label">{{ __('ui.detail_hero_q1') }}</span>
                    <p class="summary-card__text">{{ $description }}</p>
                </div>

                <div class="summary-card summary-card--value">
                    <span class="summary-card__label">{{ __('ui.detail_hero_q2') }}</span>
                    <p class="summary-card__big">{{ $amountBadge }}</p>
                    @if($tierLabel)
                        <span class="summary-card__sub">{{ $tierLabel }}</span>
                    @endif
                </div>

                <div class="summary-card">
                    <span class="summary-card__label">{{ __('ui.detail_hero_q3') }}</span>
                    <div class="chips chips--wrap">
                        @forelse($accessLabels as $accessLabel)
                            <span class="chip">{{ $accessLabel }}</span>
                        @empty
                            <span class="chip">{{ 'نامشخص' }}</span>
                        @endforelse
                    </div>
                </div>

                <div class="summary-card">
                    <span class="summary-card__label">{{ __('ui.detail_hero_q4') }}</span>
                    @if($noVerification)
                        <p class="summary-card__text summary-card__text--ok">{{ __('ui.detail_hero_q4_no') }}</p>
                    @else
                        <div class="chips chips--wrap">
                            @foreach($verificationChips as $chip)
                                <span class="chip">{{ $chip['label'] }}</span>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <div class="detail-hero__cta">
                <a class="btn btn--primary btn--lg" href="{{ $goUrl }}" target="_blank" rel="nofollow sponsored noopener">
                    {{ __('ui.detail_goto_site') }} <span aria-hidden="true">↗</span>
                </a>
                @if($statusLabel && $statusLabel !== 'فعال')
                    <span class="detail-hero__status">{{ $statusLabel }}</span>
                @endif
                @if($pricingLabel)
                    <span class="detail-hero__pricing">{{ $pricingLabel }}</span>
                @endif
            </div>
        </section>

        <div class="offer-detail__layout">
            <div class="offer-detail__main">
                @if($offer->raw_note_fa)
                    <section class="detail-section" aria-labelledby="s-note">
                        <h2 id="s-note">{{ __('ui.detail_section_1') }}</h2>
                        <p class="muted" style="margin: -4px 0 8px; font-size: 0.78rem;">{{ __('ui.detail_section_1_hint') }}</p>
                        <blockquote class="detail-quote">{{ $offer->raw_note_fa }}</blockquote>
                    </section>
                @endif

                <section class="detail-section" aria-labelledby="s-specs">
                    <h2 id="s-specs">{{ __('ui.detail_section_2') }}</h2>
                    <dl class="meta-list">
                        <div class="meta-list__row">
                            <dt>{{ __('ui.detail_meta_free_tier') }}</dt>
                            <dd>{{ $amountBadge }}</dd>
                        </div>
                        @if($offer->aiModels->isNotEmpty())
                            <div class="meta-list__row">
                                <dt>{{ __('ui.detail_meta_verified') }}</dt>
                                <dd>
                                    @foreach($offer->aiModels as $model)
                                        <code class="chip chip--model" dir="ltr">{{ $model->name }}</code>
                                    @endforeach
                                </dd>
                            </div>
                        @endif
                        @if($offer->tags->isNotEmpty())
                            <div class="meta-list__row">
                                <dt>برچسب‌ها</dt>
                                <dd>
                                    @foreach($offer->tags as $tag)
                                        <span class="chip">{{ $tag->name_fa }}</span>
                                    @endforeach
                                </dd>
                            </div>
                        @endif
                        <div class="meta-list__row">
                            <dt>آخرین بررسی دستی</dt>
                            <dd>{{ \App\Support\FaDigits::diffForHumans($offer->last_verified_at, 'بررسی نشده') }}</dd>
                        </div>
                    </dl>
                </section>

                @if(!empty($crawl['models']) || $offer->last_crawl_at !== null)
                    <section class="detail-section" aria-labelledby="s-crawl">
                        <h2 id="s-crawl">{{ __('ui.detail_section_3') }}</h2>
                        @if(!empty($crawl['models']))
                            <div class="chips chips--wrap">
                                @foreach($crawl['models'] as $freeModel)
                                    <code class="chip chip--model" dir="ltr">{{ $freeModel }}</code>
                                @endforeach
                            </div>
                        @endif
                        <p class="muted">
                            {{ \App\Support\OfferPresenter::crawlStatusLabel($crawl['status'] ?? null) }}
                            @if($crawl['crawled_at'] ?? null)
                                — آخرین بررسی خودکار: {{ \App\Support\FaDigits::diffForHumans($crawl['crawled_at']) }}
                            @elseif($offer->last_crawl_at !== null)
                                — آخرین بررسی خودکار: {{ \App\Support\FaDigits::diffForHumans($offer->last_crawl_at) }}
                            @endif
                        </p>                    </section>
                @endif

                @if($versions->isNotEmpty())
                    <section class="detail-section" aria-labelledby="s-history">
                        <h2 id="s-history">{{ __('ui.detail_section_4') }}</h2>
                        <ul class="version-list">
                            @foreach($versions as $version)
                                <li class="version">
                                    <span class="version__date">@include('public.partials.icon', ['name' => 'clock']){{ \App\Support\FaDigits::diffForHumans($version->detected_at) }}</span>
                                    <span class="chip">{{ \App\Support\OfferPresenter::VERSION_SOURCE_LABELS[$version->source] ?? $version->source }}</span>
                                    <span class="version__summary">{{ \App\Support\OfferPresenter::versionSummary($version) }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif
            </div>

            <aside class="offer-detail__aside">
                <div class="aside-card">
                    <a class="btn btn--primary btn--block" href="{{ $goUrl }}" target="_blank" rel="nofollow sponsored noopener">
                        {{ __('ui.detail_goto_site') }} <span aria-hidden="true">↗</span>
                    </a>
                    <button type="button" class="btn btn--ghost btn--block" data-qr data-qr-url="{{ $goUrl }}" data-offer-slug="{{ $offer->slug }}" data-offer-id="{{ $offer->id }}">
                        @include('public.partials.icon', ['name' => 'qr']){{ __('ui.qr_title') }}
                    </button>
                    <button type="button" class="btn btn--ghost btn--block" data-copy="{{ $goUrl }}">
                        @include('public.partials.icon', ['name' => 'copy']){{ __('ui.copy_link') }}
                    </button>
                    <button type="button" class="btn btn--ghost btn--block btn--danger-ghost" data-report data-offer-slug="{{ $offer->slug }}" data-offer-name="{{ $providerName }}">
                        @include('public.partials.icon', ['name' => 'flag']){{ __('ui.report_issue') }}
                    </button>
                    <p class="aside-card__note">{{ __('ui.report_note') }}</p>
                </div>
            </aside>
        </div>
    </article>
@endsection
