@php
    $attributes = 'viewBox="0 0 24 24" width="1em" height="1em" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"';
@endphp
<svg {!! $attributes !!}>
@switch($name)
    @case('search')
        <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
        @break
    @case('filter')
        <path d="M22 3H2l8 9.46V19l4 2v-8.54L22 3z"/>
        @break
    @case('qr')
        <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/>
        <path d="M14 14h3v3h-3z"/><path d="M21 14v3"/><path d="M17.5 21H14"/>
        @break
    @case('copy')
        <rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
        @break
    @case('external-link')
        <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><path d="M15 3h6v6"/><path d="M10 14 21 3"/>
        @break
    @case('flag')
        <path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><path d="M4 22v-7"/>
        @break
    @case('clock')
        <circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>
        @break
    @case('check')
        <path d="M20 6 9 17l-5-5"/>
        @break
    @case('x')
        <path d="M18 6 6 18"/><path d="m6 6 12 12"/>
        @break
    @case('sparkles')
        <path d="M12 3l1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9L12 3z"/><path d="M19 17.5l.7 1.8 1.8.7-1.8.7-.7 1.8-.7-1.8-1.8-.7 1.8-.7z"/>
        @break
    @case('shield')
        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
        @break
    @case('download')
        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M7 10l5 5 5-5"/><path d="M12 15V3"/>
        @break
    @case('arrow-left')
        <path d="M19 12H5"/><path d="m12 19-7-7 7-7"/>
        @break
    @case('inbox')
        <path d="M22 12h-6l-2 3h-4l-2-3H2"/><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/>
        @break
@endswitch
</svg>
