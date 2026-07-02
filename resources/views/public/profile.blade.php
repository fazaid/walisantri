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

    @include('public.partials.header', ['pesantren' => $pesantren, 'loginUrl' => $loginUrl])

    <main class="max-w-3xl mx-auto px-6 py-8 space-y-8">

        {{-- Deskripsi --}}
        @if($pesantren->profil['deskripsi'] ?? null)
            <section class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                <h2 class="font-semibold text-gray-900 mb-3">Tentang Pesantren</h2>
                <p class="text-gray-600 leading-relaxed">{{ $pesantren->profil['deskripsi'] }}</p>
            </section>
        @endif

        {{-- Kontak --}}
        @if(($pesantren->profil['telepon'] ?? null) || ($pesantren->profil['website'] ?? null))
            <section class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                <h2 class="font-semibold text-gray-900 mb-3">Kontak</h2>
                <div class="space-y-2 text-sm text-gray-600">
                    @if($pesantren->profil['telepon'] ?? null)
                        <p>📞 {{ $pesantren->profil['telepon'] }}</p>
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

        {{-- Statistik Ringkas --}}
        <section class="grid grid-cols-3 gap-3">
            <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 text-center">
                <p class="text-2xl font-bold text-teal-700">{{ $pesantren->jumlahSantriAktif() }}</p>
                <p class="text-xs text-gray-500 mt-1">Santri Aktif</p>
            </div>
            <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 text-center">
                <p class="text-2xl font-bold text-teal-700">{{ $pesantren->profil['tahun_berdiri'] ?? '–' }}</p>
                <p class="text-xs text-gray-500 mt-1">Tahun Berdiri</p>
            </div>
            <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 text-center">
                <p class="text-2xl font-bold text-teal-700">{{ $pesantren->profil['akreditasi'] ?? '–' }}</p>
                <p class="text-xs text-gray-500 mt-1">Akreditasi</p>
            </div>
        </section>

        {{-- Program & Jenjang Pendidikan --}}
        @if(!empty($pesantren->profil['program']))
            <section class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                <h2 class="font-semibold text-gray-900 mb-4">Program & Jenjang Pendidikan</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    @foreach($pesantren->profil['program'] as $item)
                        <div class="border border-gray-100 rounded-xl p-3">
                            <p class="font-medium text-gray-900 text-sm">{{ $item['nama'] ?? '' }}</p>
                            @if($item['jenjang'] ?? null)
                                <p class="text-gray-500 text-xs mt-1">{{ $item['jenjang'] }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Galeri --}}
        @if(!empty($pesantren->galeri_urls))
            <section class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                <h2 class="font-semibold text-gray-900 mb-4">Galeri</h2>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                    @foreach($pesantren->galeri_urls as $img)
                        <img src="{{ $img }}" alt="Galeri" class="rounded-xl object-cover w-full h-32">
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
