{{-- File: resources/views/wali/layouts/app.blade.php --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0f766e">
    <title>@yield('title', 'Portal Wali Santri') — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
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
            @unless(session('magic_link_session'))
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-xs text-teal-200 hover:text-white">Keluar</button>
            </form>
            @endunless
        </div>
    </header>

    {{-- Content --}}
    <main class="max-w-lg mx-auto px-4 py-5">
        @yield('content')
    </main>

    {{-- Bottom nav padding --}}
    <div class="h-6"></div>

</body>
</html>