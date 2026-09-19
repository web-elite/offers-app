@php
    $verifiedAt = $offer->last_verified_at;

    if ($verifiedAt === null) {
        $dot = 'dot--grey';
        $label = 'بررسی نشده';
    } elseif ($verifiedAt->gt(now()->subHours(48))) {
        $dot = 'dot--green';
        $label = 'بررسی‌شده '.\App\Support\FaDigits::diffForHumans($verifiedAt);
    } elseif ($verifiedAt->gt(now()->subDays(7))) {
        $dot = 'dot--yellow';
        $label = 'بررسی‌شده '.\App\Support\FaDigits::diffForHumans($verifiedAt);
    } else {
        $dot = 'dot--red';
        $label = 'بررسی‌شده '.\App\Support\FaDigits::diffForHumans($verifiedAt);
    }
@endphp
<span class="freshness">
    <span class="dot {{ $dot }}" aria-hidden="true"></span>
    <span class="freshness__text">{{ $label }}</span>
</span>
