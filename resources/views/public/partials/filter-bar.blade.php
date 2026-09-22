@php
    $sort = $sort ?? 'fresh';
    $verificationChecked = (array) request('verification', []);
    $modelChecked = (array) request('model', []);
    $accessChecked = (array) request('access', []);
    $tierChecked = (array) request('tier', []);
    // Map DB keys to translatable labels. Unknown keys fall back to the key.
    $tierOptions = array_map(
        fn ($key) => __('ui.bonus_'.$key) !== 'ui.bonus_'.$key ? __('ui.bonus_'.$key) : $key,
        ['forever_free', 'daily_reset', 'monthly_credit', 'free_models', 'free_credits', 'signup_bonus', 'referral_bonus', 'trial']
    );
    $accessOptions = array_map(
        fn ($key) => __('ui.access_'.$key) !== 'ui.access_'.$key ? __('ui.access_'.$key) : $key,
        ['api', 'web', 'chat_ui', 'ide', 'telegram_bot']
    );
@endphp
@php
    $anyAdvanced = !empty($verificationChecked) || !empty($modelChecked) || !empty($accessChecked)
        || !empty($tierChecked) || request()->boolean('no_verification') || request()->boolean('no_card')
        || request()->boolean('no_phone');
@endphp

{{-- Mobile-only quick band: search + chips + sort + clear, plus the FAB and filter drawer.
     The desktop filter sidebar is rendered inline inside the home page's two-column grid
     (see home.blade.php) so it aligns with the offers content. This section is hidden
     on desktop (≥721px) via CSS. --}}
<section class="filterbar filterbar--sidebar" role="region" aria-label="{{ __('ui.search') }}">
    <div class="container filterbar__mobile">
        {{-- Mobile: compact quick row (search + chips) visible only ≤720px --}}
        <form method="GET" action="{{ url('/') }}" class="filterbar__quickrow" aria-label="{{ __('ui.search') }}">
            <div class="filter-fields__quick filter-fields__quick--row">
                <div class="searchbox">
                    <input type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('ui.search_placeholder') }}" aria-label="{{ __('ui.search_placeholder') }}">
                    <button type="submit" class="icon-btn" aria-label="{{ __('ui.search') }}">
                        @include('public.partials.icon', ['name' => 'search'])
                    </button>
                </div>

                <label class="chip-toggle">
                    <input type="checkbox" name="free" value="1" @checked(request()->boolean('free'))>
                    <span>{{ __('ui.quick_all_free') }}</span>
                </label>

                <label class="chip-toggle">
                    <input type="checkbox" name="no_verification" value="1" @checked(request()->boolean('no_verification'))>
                    <span>{{ __('ui.quick_no_verify') }}</span>
                </label>

                <label class="sortbox">
                    <span class="sortbox__label">{{ __('ui.filter_sort') }}</span>
                    <select name="sort">
                        <option value="fresh" @selected($sort === 'fresh')>{{ __('ui.sort_newest') }}</option>
                        <option value="credits" @selected($sort === 'credits')>{{ __('ui.sort_free_amount') }}</option>
                    </select>
                </label>

                @if(request('q') || request()->boolean('free') || !empty($verificationChecked) || !empty($modelChecked) || !empty($accessChecked) || !empty($tierChecked) || request()->boolean('no_card') || request()->boolean('no_phone') || request()->boolean('no_verification'))
                    <a class="filterbar__clear" href="{{ url('/') }}">{{ __('ui.filter_clear') }}</a>
                @endif
            </div>
        </form>
    </div>

    {{-- Floating filter button — mobile only (≤720px) --}}
    <button class="filter-fab" type="button" data-filter-fab-toggle
            aria-controls="filter-drawer" aria-expanded="false"
            aria-label="{{ __('ui.more_filters') }}">
        @include('public.partials.icon', ['name' => 'filter'])
        @if($anyAdvanced)
        <span class="filter-fab__badge">{{ count(
            array_merge($verificationChecked, $modelChecked, $accessChecked, $tierChecked,
                array_filter([
                    request()->boolean('no_verification') ? 'nv' : null,
                    request()->boolean('no_card') ? 'nc' : null,
                    request()->boolean('no_phone') ? 'np' : null,
                ])
            )
        )}}</span>
        @endif
    </button>

    {{-- Mobile filter drawer — opened via the FAB (≤720px) --}}
    <div class="filter-drawer" id="filter-drawer" @if(!$anyAdvanced) hidden @endif
         role="dialog" aria-modal="true" aria-labelledby="filter-drawer-title">
        <div class="filter-drawer__scrim" data-filter-close></div>
        <aside class="filter-drawer__panel">
            <header class="filter-drawer__head">
                <h2 id="filter-drawer-title" class="filter-drawer__title">
                    @include('public.partials.icon', ['name' => 'filter'])
                    {{ __('ui.more_filters') }}
                </h2>
                <button class="filter-drawer__close icon-btn" type="button" data-filter-close
                        aria-label="{{ __('ui.filter_clear') }}">
                    @include('public.partials.icon', ['name' => 'x'])
                </button>
            </header>
            <div class="filter-drawer__body">
                <form method="GET" action="{{ url('/') }}" class="filter-drawer__form">
                    @include('public.partials.filter-fields', ['variant' => 'drawer'])
                </form>
            </div>
        </aside>
    </div>
</section>
