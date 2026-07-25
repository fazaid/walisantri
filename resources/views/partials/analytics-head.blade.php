{{--
    Snippet Google Tag Manager / Google Analytics 4 untuk <head>.
    Dikelola dari panel Super Admin → Langganan → Pengaturan Analytics.
    Hanya dirender bila tracking diaktifkan DAN ID terisi. ID sudah divalidasi
    ketat (regex GTM-/G-) di form sebelum disimpan, jadi aman ditaruh di JS.
--}}
@php
    $analyticsEnabled = \App\Models\AnalyticsSetting::enabled();
    $gtmId = \App\Models\AnalyticsSetting::gtmId();
    $ga4Id = \App\Models\AnalyticsSetting::ga4Id();
@endphp
@if($analyticsEnabled && $gtmId)
    {{-- Google Tag Manager --}}
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','{{ $gtmId }}');</script>
    {{-- End Google Tag Manager --}}
@endif
@if($analyticsEnabled && $ga4Id)
    {{-- Google tag (gtag.js) --}}
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $ga4Id }}"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', '{{ $ga4Id }}');
    </script>
    {{-- End Google tag (gtag.js) --}}
@endif
