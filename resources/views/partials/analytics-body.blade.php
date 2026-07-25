{{--
    Bagian <noscript> Google Tag Manager — WAJIB tepat setelah tag <body>.
    Hanya untuk pengunjung tanpa JavaScript. GA4 (gtag) tidak butuh bagian ini.
--}}
@php
    $analyticsEnabled = \App\Models\AnalyticsSetting::enabled();
    $gtmId = \App\Models\AnalyticsSetting::gtmId();
@endphp
@if($analyticsEnabled && $gtmId)
    {{-- Google Tag Manager (noscript) --}}
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ $gtmId }}"
    height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    {{-- End Google Tag Manager (noscript) --}}
@endif
