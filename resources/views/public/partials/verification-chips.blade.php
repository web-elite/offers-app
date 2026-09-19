@php
    $chips = \App\Support\OfferPresenter::verificationChips($offer);
@endphp
<span class="vchips">
@if($chips === [])
    <span class="chip chip--noverif">@include('public.partials.icon', ['name' => 'check'])بدون تأیید هویت</span>
@else
    @foreach($chips as $chip)
        <span class="chip chip--verif">@include('public.partials.icon', ['name' => 'shield']){{ $chip['label'] }}</span>
    @endforeach
@endif
</span>
