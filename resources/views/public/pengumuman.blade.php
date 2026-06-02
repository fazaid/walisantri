<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengumuman · {{ $pesantren->nama_pesantren }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 text-gray-800 font-sans">

    <header class="bg-teal-700 text-white">
        <div class="max-w-3xl mx-auto px-6 py-5 flex items-center gap-4">
            <a href="{{ url('/') }}" class="text-teal-200 hover:text-white text-sm">← Beranda</a>
            <h1 class="font-bold text-lg">Pengumuman · {{ $pesantren->nama_pesantren }}</h1>
            <a href="{{ $loginUrl }}" class="ml-auto bg-white text-teal-700 font-semibold px-4 py-1.5 rounded-lg text-sm">
                Portal Wali →
            </a>
        </div>
    </header>

    <main class="max-w-3xl mx-auto px-6 py-8">
        @forelse($pengumuman as $item)
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 mb-4">
                <p class="font-semibold text-gray-900">{{ $item->judul_maklumat }}</p>
                <p class="text-gray-600 text-sm mt-2 leading-relaxed whitespace-pre-line">{{ $item->isi_maklumat }}</p>
                <p class="text-xs text-gray-400 mt-3">{{ $item->created_at->format('d F Y, H:i') }}</p>
            </div>
        @empty
            <p class="text-center text-gray-400 py-12">Belum ada pengumuman.</p>
        @endforelse

        {{ $pengumuman->links() }}
    </main>

    <footer class="border-t border-gray-100 py-6 text-center text-xs text-gray-400">
        Ditenagai oleh <a href="/" class="text-teal-700">Walisantri.com</a>
    </footer>

</body>
</html>
