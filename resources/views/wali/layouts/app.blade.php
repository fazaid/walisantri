{{-- File: resources/views/wali/layouts/app.blade.php --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0f766e">

    {{-- Sejak §1.8 Fase 1 portal wali satu host dengan profil publik yang JUSTRU
         ingin terindeks. Tanpa baris ini, halaman anak orang ikut masuk hasil
         pencarian. Pola sama dengan panduan.blade.php. --}}
    <meta name="robots" content="noindex, nofollow">

    {{-- PWA --}}
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Walisantri">
    <link rel="manifest" href="/manifest.json">
    <link rel="icon" type="image/svg+xml" href="{{ \App\Models\PlatformBrandingSetting::faviconUrl() }}">

    <title>@yield('title', 'Portal Wali Santri') — {{ config('app.name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.tema')
    @include('partials.analytics-head')
    @stack('head')
</head>
<body class="bg-gray-50 min-h-screen">
@include('partials.analytics-body')

    {{-- Grace period banner --}}
    @if(request()->attributes->get('grace_period_warning'))
    <div class="bg-amber-500 text-white text-center text-sm py-2 px-4">
        ⚠ Masa aktif pesantren telah berakhir. Akses read-only tersedia
        {{ request()->attributes->get('grace_days_left') }} hari lagi.
    </div>
    @endif

    {{-- Preview mode banner (admin/ustadz melihat tampilan wali) --}}
    @if($previewMode ?? false)
    <div class="bg-indigo-600 text-white dark:text-gray-900 text-center text-sm py-2 px-4">
        👁 Mode Preview — tampilan ini seperti yang dilihat wali santri
    </div>
    @endif

    {{-- Header --}}
    <header class="bg-teal-700 text-white dark:bg-teal-100 dark:text-gray-900 sticky top-0 z-10 shadow-md">
        <div class="max-w-lg mx-auto px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-3">
                @hasSection('back_url')
                <a href="@yield('back_url')" class="text-white/80 hover:text-white dark:text-gray-900/80 dark:hover:text-gray-900">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                @endif
                <div>
                    <h1 class="text-base font-semibold leading-tight">@yield('title', 'Portal Wali Santri')</h1>
                    <p class="text-xs text-teal-200 dark:text-teal-500">@yield('subtitle', config('app.name'))</p>
                </div>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                @include('partials.tema-tombol', [
                    'kelasWarna' => 'text-white/80 hover:text-white hover:bg-white/10 dark:text-gray-900/80 dark:hover:text-gray-900 dark:hover:bg-gray-900/10',
                ])

                @unless($previewMode ?? false)
                {{-- Pengunjung yang sedang MENCOBA demo butuh pintu keluar yang jelas
                     menyebut demo (§1.8) — sementara wali sungguhan, yang juga masuk
                     lewat magic link, tetap melihat "Keluar" biasa. --}}
                @php
                    // Blok penuh, bukan @php(...): direktif ringkasnya salah mengurai
                    // tanda kurung bersarang di ekspresi ini.
                    $sesiDemo = session('magic_link_session') && (auth()->user()?->pesantren?->is_demo ?? false);
                @endphp
                <form method="POST" action="{{ route('wali.logout') }}">
                    @csrf
                    <button type="submit" class="text-xs text-teal-200 dark:text-teal-500 hover:text-white dark:hover:text-gray-900 whitespace-nowrap">
                        {{ $sesiDemo ? 'Keluar dari demo' : 'Keluar' }}
                    </button>
                </form>
                @endunless
            </div>
        </div>
    </header>

    <main class="max-w-lg mx-auto px-4 py-5 pb-20">
        @yield('content')
    </main>

    {{-- ─── Bottom Navigation Bar ─────────────────────────────────────────── --}}
    @unless(session('magic_link_session') || ($previewMode ?? false))
    @php
        // Daftar tab dirakit sekali lalu di-loop — pola yang sama dengan
        // filament/admin/bottom-nav.blade.php. Tanpa itu, menyembunyikan satu tab
        // berarti membungkus satu blok <a> panjang dengan @if, dan tab berikutnya
        // diam-diam berubah lebarnya karena flex-1 membagi sisa ruang.
        //
        // Beranda dan Pengumuman tanpa syarat, jadi bar ini tidak pernah kosong
        // sekalipun keenam modul dimatikan.
        $tabs = [
            [
                'label'  => 'Beranda',
                'url'    => route('wali.dashboard'),
                'active' => request()->routeIs('wali.dashboard') || request()->routeIs('wali.santri.show'),
                'icon'   => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
            ],
        ];

        if (\App\Enums\Modul::Keuangan->aktif()) {
            $tabs[] = [
                'label'  => 'SPP',
                'url'    => route('wali.spp'),
                'active' => request()->routeIs('wali.spp'),
                'icon'   => 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z',
            ];
        }

        $tabs[] = [
            'label'  => 'Pengumuman',
            'url'    => route('wali.pengumuman'),
            'active' => request()->routeIs('wali.pengumuman'),
            'icon'   => 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9',
        ];

        if (\App\Enums\Modul::Keuangan->aktif()) {
            $tabs[] = [
                'label'  => 'Uang Saku',
                'url'    => route('wali.uang-saku'),
                'active' => request()->routeIs('wali.uang-saku') || request()->routeIs('wali.uang-saku.show'),
                'icon'   => 'M21 12a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 12m18 0v6a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 9m18 0V6a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 6v3',
            ];
        }

        if (\App\Enums\Modul::Rapor->aktif()) {
            $tabs[] = [
                'label'  => 'Rapor',
                'url'    => route('wali.rapor'),
                'active' => request()->routeIs('wali.rapor'),
                'icon'   => 'M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25M9 16.5v.75m3-3v3M15 12v5.25m-4.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z',
            ];
        }
    @endphp
    <nav class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 shadow-lg z-20">
        <div class="max-w-lg mx-auto flex items-stretch">
            @foreach($tabs as $tab)
                <a href="{{ $tab['url'] }}"
                   class="flex-1 flex flex-col items-center justify-center py-2 gap-0.5 text-xs font-medium transition-colors
                          {{ $tab['active'] ? 'text-teal-600' : 'text-gray-400 hover:text-gray-600' }}">
                    <svg class="w-6 h-6" fill="{{ $tab['active'] ? 'currentColor' : 'none' }}"
                         stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $tab['icon'] }}"/>
                    </svg>
                    {{ $tab['label'] }}
                </a>
            @endforeach
        </div>
    </nav>
    @endunless

    {{-- Service Worker registration --}}
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js').catch(() => {});
            });
        }
    </script>

</body>
</html>
