{{-- Kerangka halaman auth tanpa branding tenant.

     Halaman login punya varian branding per pesantren (?tenant={slug}), sedangkan
     alur reset kata sandi hanya untuk staf — tidak ada konteks pesantren yang perlu
     ditampilkan, jadi cukup tampilan platform. --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0f766e">
    <title>@yield('judul') · Walisantri.com</title>
    <link rel="icon" type="image/svg+xml" href="{{ \App\Models\PlatformBrandingSetting::faviconUrl() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.tema')
</head>
<body class="min-h-screen flex items-center justify-center px-4 bg-teal-700 dark:bg-teal-100">
    <div class="w-full max-w-sm">

        <div class="text-center mb-8">
            <div class="w-16 h-16 bg-white rounded-2xl flex items-center justify-center mx-auto mb-3 shadow-lg">
                <img src="{{ \App\Models\PlatformBrandingSetting::logoUrl() }}" alt="Walisantri.com" class="w-10 h-10 object-contain">
            </div>
            <h1 class="text-white dark:text-gray-900 text-2xl font-bold">
                <a href="{{ route('landing') }}">Walisantri.com</a>
            </h1>
            <p class="text-teal-200 dark:text-teal-500 text-sm mt-1">@yield('subjudul')</p>
        </div>

        <div class="bg-white rounded-2xl shadow-xl p-6">
            @if (session('status'))
                <div class="bg-teal-50 border border-teal-200 text-teal-800 text-sm rounded-xl px-4 py-3 mb-4">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl px-4 py-3 mb-4">
                    {{ $errors->first() }}
                </div>
            @endif

            @yield('isi')
        </div>

        <p class="text-center text-xs mt-6 text-teal-200 dark:text-teal-500">
            @yield('catatan')
        </p>
    </div>
</body>
</html>
