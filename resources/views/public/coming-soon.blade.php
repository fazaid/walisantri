{{-- Placeholder "Segera Hadir" untuk menu profil publik yang belum diimplementasikan (§1.4) --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="{{ $menu }} — {{ $pesantren->nama_pesantren }}">
    <title>{{ $menu }} — {{ $pesantren->nama_pesantren }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 text-gray-800 font-sans">

    @include('public.partials.header', ['pesantren' => $pesantren, 'loginUrl' => $loginUrl])

    <main class="max-w-3xl mx-auto px-6 py-16">
        <section class="bg-white rounded-2xl p-10 shadow-sm border border-gray-100 text-center">
            <div class="text-5xl mb-4">🚧</div>
            <h2 class="text-xl font-semibold text-gray-900 mb-2">{{ $menu }} Segera Hadir</h2>
            <p class="text-gray-500 max-w-md mx-auto">
                Halaman ini sedang kami siapkan. Nantikan pembaruan {{ Str::lower($menu) }} dari {{ $pesantren->nama_pesantren }} di sini.
            </p>
            <a href="{{ url('/') }}" class="inline-block mt-6 text-teal-700 font-medium hover:underline">← Kembali ke Beranda</a>
        </section>
    </main>

    <footer class="border-t border-gray-100 py-6 text-center text-xs text-gray-400 mt-8">
        Ditenagai oleh <a href="/" class="text-teal-700">Walisantri.com</a>
    </footer>

</body>
</html>
