{{-- File: resources/views/wali/layouts/app.blade.php --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0f766e">

    {{-- PWA --}}
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Walisantri">
    <link rel="manifest" href="/manifest.json">

    <title>@yield('title', 'Portal Wali Santri') — {{ config('app.name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="bg-gray-50 min-h-screen">

    {{-- Grace period banner --}}
    @if(request()->attributes->get('grace_period_warning'))
    <div class="bg-amber-500 text-white text-center text-sm py-2 px-4">
        ⚠ Masa aktif pesantren telah berakhir. Akses read-only tersedia
        {{ request()->attributes->get('grace_days_left') }} hari lagi.
    </div>
    @endif

    {{-- Header --}}
    <header class="bg-teal-700 text-white sticky top-0 z-10 shadow-md">
        <div class="max-w-lg mx-auto px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-3">
                @hasSection('back_url')
                <a href="@yield('back_url')" class="text-white/80 hover:text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                @endif
                <div>
                    <h1 class="text-base font-semibold leading-tight">@yield('title', 'Portal Wali Santri')</h1>
                    <p class="text-xs text-teal-200">@yield('subtitle', config('app.name'))</p>
                </div>
            </div>
            <form method="POST" action="{{ route('wali.logout') }}">
                @csrf
                <button type="submit" class="text-xs text-teal-200 hover:text-white">Keluar</button>
            </form>
        </div>
    </header>

    <main class="max-w-lg mx-auto px-4 py-5 pb-20">
        @yield('content')
    </main>

    {{-- ─── Bottom Navigation Bar ─────────────────────────────────────────── --}}
    @unless(session('magic_link_session'))
    @php
        $isBerandaActive    = request()->routeIs('wali.dashboard');
        $isSantriActive     = request()->routeIs('wali.santri.show');
        $isPengumumanActive = request()->routeIs('wali.pengumuman');

        // Build santri tab URL — use first active child if authenticated
        $firstSantriId  = auth()->user()?->anakSantri()->value('id');
        $santriTabUrl   = $firstSantriId
            ? route('wali.santri.show', $firstSantriId)
            : route('wali.dashboard');
    @endphp
    <nav class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 shadow-lg z-20">
        <div class="max-w-lg mx-auto flex items-stretch">

            {{-- Tab: Beranda --}}
            <a href="{{ route('wali.dashboard') }}"
               class="flex-1 flex flex-col items-center justify-center py-2 gap-0.5 text-xs font-medium transition-colors
                      {{ $isBerandaActive ? 'text-teal-600' : 'text-gray-400 hover:text-gray-600' }}">
                <svg class="w-6 h-6" fill="{{ $isBerandaActive ? 'currentColor' : 'none' }}"
                     stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                Beranda
            </a>

            {{-- Tab: Santri --}}
            <a href="{{ $santriTabUrl }}"
               class="flex-1 flex flex-col items-center justify-center py-2 gap-0.5 text-xs font-medium transition-colors
                      {{ $isSantriActive ? 'text-teal-600' : 'text-gray-400 hover:text-gray-600' }}">
                <svg class="w-6 h-6" fill="{{ $isSantriActive ? 'currentColor' : 'none' }}"
                     stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                Santri
            </a>

            {{-- Tab: Pengumuman --}}
            <a href="{{ route('wali.pengumuman') }}"
               class="flex-1 flex flex-col items-center justify-center py-2 gap-0.5 text-xs font-medium transition-colors
                      {{ $isPengumumanActive ? 'text-teal-600' : 'text-gray-400 hover:text-gray-600' }}">
                <svg class="w-6 h-6" fill="{{ $isPengumumanActive ? 'currentColor' : 'none' }}"
                     stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
                Pengumuman
            </a>

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
