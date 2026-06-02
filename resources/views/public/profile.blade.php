{{-- Situs profil publik {slug}.walisantri.com (§1.4)
     Read-only — tidak ada data santri di sini --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="{{ $pesantren->profil['deskripsi'] ?? $pesantren->nama_pesantren }}">
    <title>{{ $pesantren->nama_pesantren }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 text-gray-800 font-sans">

    {{-- Header --}}
    <header class="bg-teal-700 text-white">
        <div class="max-w-3xl mx-auto px-6 py-8 flex items-center gap-5">
            @if($pesantren->profil['logo'] ?? null)
                <img src="{{ $pesantren->profil['logo'] }}"
                     alt="Logo {{ $pesantren->nama_pesantren }}"
                     class="w-16 h-16 rounded-xl object-cover shadow-md">
            @else
                <div class="w-16 h-16 bg-white/20 rounded-xl flex items-center justify-center text-3xl">🕌</div>
            @endif
            <div>
                <h1 class="text-2xl font-bold">{{ $pesantren->nama_pesantren }}</h1>
                @if($pesantren->profil['alamat'] ?? null)
                    <p class="text-teal-200 text-sm mt-1">📍 {{ $pesantren->profil['alamat'] }}</p>
                @endif
            </div>
        </div>

        {{-- Nav --}}
        <div class="border-t border-teal-600">
            <div class="max-w-3xl mx-auto px-6 flex gap-6 text-sm py-3">
                <a href="{{ request()->url() }}" class="text-white font-medium">Beranda</a>
                <a href="{{ url('/pengumuman') }}" class="text-teal-200 hover:text-white">Pengumuman</a>
                <a href="{{ $loginUrl }}" class="ml-auto bg-white text-teal-700 font-semibold px-4 py-1.5 rounded-lg text-sm hover:bg-teal-50">
                    Portal Wali Santri →
                </a>
            </div>
        </div>
    </header>

    <main class="max-w-3xl mx-auto px-6 py-8 space-y-8">

        {{-- Deskripsi --}}
        @if($pesantren->profil['deskripsi'] ?? null)
            <section class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                <h2 class="font-semibold text-gray-900 mb-3">Tentang Pesantren</h2>
                <p class="text-gray-600 leading-relaxed">{{ $pesantren->profil['deskripsi'] }}</p>
            </section>
        @endif

        {{-- Kontak --}}
        @if(($pesantren->profil['kontak'] ?? null) || ($pesantren->profil['website'] ?? null))
            <section class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                <h2 class="font-semibold text-gray-900 mb-3">Kontak</h2>
                <div class="space-y-2 text-sm text-gray-600">
                    @if($pesantren->profil['kontak'] ?? null)
                        <p>📞 {{ $pesantren->profil['kontak'] }}</p>
                    @endif
                    @if($pesantren->profil['email_kontak'] ?? null)
                        <p>✉️ {{ $pesantren->profil['email_kontak'] }}</p>
                    @endif
                    @if($pesantren->profil['website'] ?? null)
                        <p>🌐 <a href="{{ $pesantren->profil['website'] }}" class="text-teal-700 underline" target="_blank">{{ $pesantren->profil['website'] }}</a></p>
                    @endif
                </div>
            </section>
        @endif

        {{-- Galeri --}}
        @if(!empty($pesantren->profil['galeri']))
            <section class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                <h2 class="font-semibold text-gray-900 mb-4">Galeri</h2>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                    @foreach($pesantren->profil['galeri'] as $img)
                        <img src="{{ $img }}" alt="Galeri" class="rounded-xl object-cover w-full h-32">
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Pengumuman --}}
        @if($pengumuman->count())
            <section class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="font-semibold text-gray-900">Pengumuman Terbaru</h2>
                    <a href="{{ url('/pengumuman') }}" class="text-sm text-teal-700 hover:underline">Lihat semua →</a>
                </div>
                <div class="space-y-4">
                    @foreach($pengumuman as $item)
                        <div class="border-b border-gray-50 pb-4 last:border-0 last:pb-0">
                            <p class="font-medium text-gray-900 text-sm">{{ $item->judul_maklumat }}</p>
                            <p class="text-gray-500 text-sm mt-1 line-clamp-2">{{ $item->isi_maklumat }}</p>
                            <p class="text-xs text-gray-400 mt-1">{{ $item->created_at->format('d M Y') }}</p>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- CTA Portal Wali --}}
        <section class="bg-teal-50 border border-teal-200 rounded-2xl p-6 text-center">
            <p class="text-teal-800 font-medium mb-3">Wali santri? Pantau perkembangan putra/putri Anda</p>
            <a href="{{ $loginUrl }}"
               class="inline-block bg-teal-700 text-white font-semibold px-6 py-2.5 rounded-xl hover:bg-teal-800 transition-colors">
                Masuk Portal Wali Santri
            </a>
        </section>

    </main>

    <footer class="border-t border-gray-100 py-6 text-center text-xs text-gray-400 mt-8">
        Ditenagai oleh <a href="/" class="text-teal-700">Walisantri.com</a>
    </footer>

</body>
</html>
