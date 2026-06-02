<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Platform digitalisasi pesantren Indonesia — akademik, pengasuhan, kesehatan, inventaris, komunikasi dalam satu platform.">
    <title>Walisantri.com — Standar Digitalisasi Pesantren</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white text-gray-800 font-sans">

    {{-- Nav --}}
    <nav class="bg-white border-b border-gray-100 px-6 py-4 flex items-center justify-between max-w-6xl mx-auto">
        <span class="text-teal-700 font-bold text-xl">🕌 Walisantri.com</span>
        <div class="flex gap-3">
            <a href="{{ route('login') }}"
               class="text-sm text-gray-600 hover:text-teal-700 font-medium px-4 py-2 rounded-lg hover:bg-gray-50">
                Masuk
            </a>
            <a href="{{ route('register') }}"
               class="text-sm bg-teal-700 text-white font-medium px-4 py-2 rounded-lg hover:bg-teal-800">
                Daftar Gratis
            </a>
        </div>
    </nav>

    {{-- Hero --}}
    <section class="max-w-4xl mx-auto px-6 py-20 text-center">
        <h1 class="text-4xl md:text-5xl font-bold text-gray-900 leading-tight mb-6">
            Digitalisasi Pesantren<br>
            <span class="text-teal-700">Mulai Dari Gratis</span>
        </h1>
        <p class="text-lg text-gray-500 mb-8 max-w-2xl mx-auto">
            Satu platform untuk akademik, pengasuhan, kesehatan, inventaris, dan komunikasi wali santri.
            Dari pesantren rintisan hingga pesantren besar.
        </p>
        <a href="{{ route('register') }}"
           class="inline-block bg-teal-700 text-white font-semibold px-8 py-3.5 rounded-xl text-lg hover:bg-teal-800 transition-colors">
            Mulai Trial 14 Hari Gratis →
        </a>
        <p class="text-sm text-gray-400 mt-3">Tidak perlu kartu kredit</p>
    </section>

    {{-- Paket --}}
    <section class="max-w-5xl mx-auto px-6 pb-20">
        <h2 class="text-2xl font-bold text-center text-gray-900 mb-10">Pilih Paket yang Tepat</h2>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-5">
            @foreach([
                ['Gratis', 'Rp 0', '10 santri', 'Akademik + Mutaba\'ah + Portal Wali', 'gray'],
                ['Rintisan', 'Rp 150rb', '100 santri', '+ Export Excel/PDF', 'teal'],
                ['Berkembang', 'Rp 450rb', '500 santri', '+ Modul Kesehatan + Export Rekam Medis', 'blue'],
                ['Maju', 'Rp 750rb', '1.000 santri', '+ Modul Inventaris + AI (segera)', 'purple'],
            ] as $paket)
                <div class="border rounded-2xl p-5 {{ $paket[4] === 'teal' ? 'border-teal-300 bg-teal-50' : 'border-gray-200' }}">
                    <div class="font-bold text-lg text-gray-900 mb-1">{{ $paket[0] }}</div>
                    <div class="text-2xl font-bold text-{{ $paket[4] }}-700 mb-1">{{ $paket[1] }}<span class="text-sm font-normal text-gray-500">/bln</span></div>
                    <div class="text-sm text-gray-500 mb-3">{{ $paket[2] }}</div>
                    <p class="text-sm text-gray-600">{{ $paket[3] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    <footer class="border-t border-gray-100 py-8 text-center text-sm text-gray-400">
        © {{ date('Y') }} Walisantri.com · Platform Digitalisasi Pesantren Indonesia
    </footer>

</body>
</html>
