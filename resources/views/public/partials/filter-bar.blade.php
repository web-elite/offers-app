@php
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
<section class="filterbar filterbar--minimal" role="region" aria-label="{{ __('ui.search') }}">
    <form id="filter-form" method="GET" action="{{ url('/') }}" class="container filterbar__form">
        <div class="filterbar__row">
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

            <details class="filterbar__more" @if($anyAdvanced) open @endif>
                <summary class="filterbar__more-toggle">
                    @include('public.partials.icon', ['name' => 'filter']){{ __('ui.more_filters') }}
                </summary>
                <div class="filterbar__more-panel">
                    <fieldset class="filterbar__group">
                        <legend>{{ __('ui.filter_group_verification') }}</legend>
                        @foreach($verificationMethods as $method)
                            <label class="check">
                                <input type="checkbox" name="verification[]" value="{{ $method->key }}" @checked(in_array($method->key, $verificationChecked, true))>
                                <span>{{ $method->label_fa }}</span>
                            </label>
                        @endforeach
                    </fieldset>

                    <fieldset class="filterbar__group">
                        <legend>{{ __('ui.filter_group_model') }}</legend>
                        @foreach($aiModels as $model)
                            <label class="check">
                                <input type="checkbox" name="model[]" value="{{ $model->slug }}" @checked(in_array($model->slug, $modelChecked, true))>
                                <span dir="ltr">{{ $model->name }}</span>
                            </label>
                        @endforeach
                    </fieldset>

                    <fieldset class="filterbar__group">
                        <legend>{{ __('ui.filter_group_bonus_type') }}</legend>
                        @foreach($tierOptions as $tierValue => $tierLabel)
                            <label class="check">
                                <input type="checkbox" name="tier[]" value="{{ $tierValue }}" @checked(in_array($tierValue, $tierChecked, true))>
                                <span>{{ $tierLabel }}</span>
                            </label>
                        @endforeach
                    </fieldset>

                    <fieldset class="filterbar__group">
                        <legend>{{ __('ui.filter_group_access') }}</legend>
                        @foreach($accessOptions as $accessValue => $accessLabel)
                            <label class="check">
                                <input type="checkbox" name="access[]" value="{{ $accessValue }}" @checked(in_array($accessValue, $accessChecked, true))>
                                <span>{{ $accessLabel }}</span>
                            </label>
                        @endforeach
                    </fieldset>

                    <div class="filterbar__group filterbar__group--toggles">
                        <label class="chip-toggle">
                            <input type="checkbox" name="no_card" value="1" @checked(request()->boolean('no_card'))>
                            <span>{{ __('ui.filter_no_card') }}</span>
                        </label>
                        <label class="chip-toggle">
                            <input type="checkbox" name="no_phone" value="1" @checked(request()->boolean('no_phone'))>
                            <span>{{ __('ui.filter_no_mobile') }}</span>
                        </label>
                        <label class="sortbox">
                            <span class="sortbox__label">{{ __('ui.filter_sort') }}</span>
                            <select name="sort">
                                <option value="fresh" @selected($sort === 'fresh')>{{ __('ui.sort_newest') }}</option>
                                <option value="credits" @selected($sort === 'credits')>{{ __('ui.sort_free_amount') }}</option>
                            </select>
                        </label>
                    </div>

                    <div class="filterbar__actions">
                        <button type="submit" class="btn btn--primary btn--sm">{{ __('ui.filter_apply') }}</button>
                        <a class="btn btn--ghost btn--sm" href="{{ url('/') }}">{{ __('ui.filter_clear') }}</a>
                    </div>
                </div>
            </details>

            @if($anyAdvanced || request()->boolean('free') || request('q'))
                <a class="filterbar__clear" href="{{ url('/') }}">{{ __('ui.filter_clear') }}</a>
            @endif
        </div>
    </form>
</section>
