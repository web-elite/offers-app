@php
    // Context passed by filter-bar.blade.php
    $variant = $variant ?? 'sidebar';
    $verificationChecked = $verificationChecked ?? [];
    $modelChecked = $modelChecked ?? [];
    $accessChecked = $accessChecked ?? [];
    $tierChecked = $tierChecked ?? [];
    $tierOptions = $tierOptions ?? [];
    $accessOptions = $accessOptions ?? [];
    $sort = $sort ?? 'fresh';

    $anyFilterSet = request('q') || request()->boolean('free') || !empty($verificationChecked)
        || !empty($modelChecked) || !empty($accessChecked) || !empty($tierChecked)
        || request()->boolean('no_card') || request()->boolean('no_phone')
        || request()->boolean('no_verification');
@endphp

{{-- Quick filter row: search + free chip + no-verification chip + sort + clear --}}
<div class="filter-fields__quick" @if($variant === 'drawer') @hidden @endif>
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

    @if($anyFilterSet)
        <a class="filterbar__clear" href="{{ url('/') }}">{{ __('ui.filter_clear') }}</a>
    @endif
</div>

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
</div>

<div class="filterbar__actions">
    <button type="submit" class="btn btn--primary btn--sm">{{ __('ui.filter_apply') }}</button>
    <a class="btn btn--ghost btn--sm" href="{{ url('/') }}">{{ __('ui.filter_clear') }}</a>
</div>
