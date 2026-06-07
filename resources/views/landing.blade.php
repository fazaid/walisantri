<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Platform digitalisasi pesantren Indonesia — akademik, mutaba'ah, tahfidz, kesehatan, inventaris, dan komunikasi wali santri dalam satu platform.">
    <title>Walisantri.com — Standar Digitalisasi Pesantren</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white text-gray-800 font-sans">

    {{-- Nav --}}
    <nav class="bg-white border-b border-gray-100 sticky top-0 z-50">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-3 sm:py-4 flex items-center justify-between gap-2">
            <span class="text-teal-700 font-bold text-base sm:text-xl tracking-tight shrink-0">🕌 Walisantri.com</span>
            <div class="hidden md:flex items-center gap-6">
                <a href="#fitur" class="text-sm text-gray-500 hover:text-teal-700 font-medium">Fitur</a>
                <a href="#cara-kerja" class="text-sm text-gray-500 hover:text-teal-700 font-medium">Cara Kerja</a>
                <a href="{{ route('demo') }}" class="text-sm text-gray-500 hover:text-teal-700 font-medium">Demo</a>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <a href="{{ route('login') }}"
                   class="text-sm text-gray-600 hover:text-teal-700 font-medium px-3 py-1.5 sm:px-4 sm:py-2 rounded-lg hover:bg-gray-50 transition-colors whitespace-nowrap">
                    Masuk
                </a>
                <a href="{{ route('register') }}"
                   class="text-sm bg-teal-700 text-white font-medium px-3 py-1.5 sm:px-4 sm:py-2 rounded-lg hover:bg-teal-800 transition-colors whitespace-nowrap">
                    <span class="sm:hidden">Daftar</span>
                    <span class="hidden sm:inline">Daftar Gratis</span>
                </a>
            </div>
        </div>
    </nav>

    {{-- Hero --}}
    <section class="bg-gradient-to-b from-teal-50 to-white">
        <div class="max-w-4xl mx-auto px-6 py-24 text-center">
            <div class="inline-block bg-teal-100 text-teal-800 text-xs font-semibold px-3 py-1 rounded-full mb-6 uppercase tracking-wide">
                Platform Manajemen Pesantren
            </div>
            <h1 class="text-4xl md:text-5xl font-bold text-gray-900 leading-tight mb-6">
                Satu Platform untuk<br>
                <span class="text-teal-700">Semua Kebutuhan Pesantren</span>
            </h1>
            <p class="text-lg text-gray-500 mb-10 max-w-2xl mx-auto leading-relaxed">
                Kelola akademik, mutaba'ah ibadah, tahfidz Al-Quran, kesehatan santri, inventaris,
                SPP bulanan, prestasi santri, dan komunikasi wali — semuanya terintegrasi dalam satu platform yang mudah digunakan.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('register') }}"
                   class="inline-block bg-teal-700 text-white font-semibold px-8 py-3.5 rounded-xl text-base hover:bg-teal-800 transition-colors shadow-sm">
                    Mulai Gratis Sekarang →
                </a>
                <a href="{{ route('demo') }}"
                   class="inline-block bg-white text-teal-700 font-semibold px-8 py-3.5 rounded-xl text-base border-2 border-teal-200 hover:border-teal-400 hover:bg-teal-50 transition-colors">
                    Minta Demo Gratis
                </a>
            </div>
            <p class="text-sm text-gray-400 mt-4">Tidak perlu kartu kredit · Setup 5 menit</p>
        </div>
    </section>

    {{-- Stats --}}
    <section class="border-y border-gray-100 bg-white">
        <div class="max-w-4xl mx-auto px-6 py-10 grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
            @foreach([
                ['8+', 'Modul Lengkap'],
                ['100%', 'Berbasis Web'],
                ['Real-time', 'Update Data'],
                ['Multi-peran', 'Admin & Wali'],
            ] as $stat)
                <div>
                    <div class="text-2xl font-bold text-teal-700 mb-1">{{ $stat[0] }}</div>
                    <div class="text-sm text-gray-500">{{ $stat[1] }}</div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Fitur --}}
    <section id="fitur" class="max-w-6xl mx-auto px-6 py-20">
        <div class="text-center mb-14">
            <h2 class="text-3xl font-bold text-gray-900 mb-4">Semua yang Dibutuhkan Pesantren Modern</h2>
            <p class="text-gray-500 max-w-xl mx-auto">
                Dari pesantren rintisan hingga pesantren besar — Walisantri dirancang untuk tumbuh bersama Anda.
            </p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

            {{-- Akademik --}}
            <div class="border border-gray-100 rounded-2xl p-6 hover:shadow-md hover:border-teal-200 transition-all group">
                <div class="w-12 h-12 bg-teal-50 rounded-xl flex items-center justify-center mb-4 text-2xl group-hover:bg-teal-100 transition-colors">
                    📚
                </div>
                <h3 class="font-bold text-gray-900 text-lg mb-2">Akademik</h3>
                <p class="text-gray-500 text-sm leading-relaxed">
                    Kelola kelas, mata pelajaran, dan nilai santri. Input nilai mudah, rekap otomatis, dan ekspor rapor ke PDF.
                </p>
                <ul class="mt-4 space-y-1.5">
                    @foreach(['Manajemen kelas & mapel', 'Input & rekap nilai', 'Ekspor rapor PDF'] as $item)
                        <li class="flex items-center gap-2 text-sm text-gray-600">
                            <span class="text-teal-500">✓</span> {{ $item }}
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- Mutaba'ah --}}
            <div class="border border-gray-100 rounded-2xl p-6 hover:shadow-md hover:border-teal-200 transition-all group">
                <div class="w-12 h-12 bg-emerald-50 rounded-xl flex items-center justify-center mb-4 text-2xl group-hover:bg-emerald-100 transition-colors">
                    🕌
                </div>
                <h3 class="font-bold text-gray-900 text-lg mb-2">Mutaba'ah Ibadah</h3>
                <p class="text-gray-500 text-sm leading-relaxed">
                    Pantau kegiatan ibadah harian santri — shalat, puasa, tilawah, dan amalan lainnya secara real-time.
                </p>
                <ul class="mt-4 space-y-1.5">
                    @foreach(['Monitoring ibadah harian', 'Grafik perkembangan', 'Notifikasi ke wali'] as $item)
                        <li class="flex items-center gap-2 text-sm text-gray-600">
                            <span class="text-teal-500">✓</span> {{ $item }}
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- Tahfidz --}}
            <div class="border border-gray-100 rounded-2xl p-6 hover:shadow-md hover:border-teal-200 transition-all group">
                <div class="w-12 h-12 bg-amber-50 rounded-xl flex items-center justify-center mb-4 text-2xl group-hover:bg-amber-100 transition-colors">
                    📖
                </div>
                <h3 class="font-bold text-gray-900 text-lg mb-2">Tahfidz Al-Quran</h3>
                <p class="text-gray-500 text-sm leading-relaxed">
                    Tracking hafalan Al-Quran per santri. Catat pencapaian, evaluasi, dan progress menuju target.
                </p>
                <ul class="mt-4 space-y-1.5">
                    @foreach(['Catat setoran & murajaah', 'Progress per juz/halaman', 'Riwayat hafalan lengkap'] as $item)
                        <li class="flex items-center gap-2 text-sm text-gray-600">
                            <span class="text-teal-500">✓</span> {{ $item }}
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- Kesehatan --}}
            <div class="border border-gray-100 rounded-2xl p-6 hover:shadow-md hover:border-teal-200 transition-all group">
                <div class="w-12 h-12 bg-red-50 rounded-xl flex items-center justify-center mb-4 text-2xl group-hover:bg-red-100 transition-colors">
                    🏥
                </div>
                <h3 class="font-bold text-gray-900 text-lg mb-2">Kesehatan Santri</h3>
                <p class="text-gray-500 text-sm leading-relaxed">
                    Rekam medis digital santri, riwayat penyakit, dan laporan kesehatan yang bisa diakses kapan saja.
                </p>
                <ul class="mt-4 space-y-1.5">
                    @foreach(['Rekam medis digital', 'Riwayat kunjungan UKS', 'Ekspor laporan kesehatan'] as $item)
                        <li class="flex items-center gap-2 text-sm text-gray-600">
                            <span class="text-teal-500">✓</span> {{ $item }}
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- Inventaris --}}
            <div class="border border-gray-100 rounded-2xl p-6 hover:shadow-md hover:border-teal-200 transition-all group">
                <div class="w-12 h-12 bg-purple-50 rounded-xl flex items-center justify-center mb-4 text-2xl group-hover:bg-purple-100 transition-colors">
                    📦
                </div>
                <h3 class="font-bold text-gray-900 text-lg mb-2">Inventaris & Aset</h3>
                <p class="text-gray-500 text-sm leading-relaxed">
                    Manajemen aset dan barang pesantren. Lacak kondisi, lokasi, dan riwayat penggunaan dengan mudah.
                </p>
                <ul class="mt-4 space-y-1.5">
                    @foreach(['Katalog aset pesantren', 'Status & kondisi barang', 'Riwayat peminjaman'] as $item)
                        <li class="flex items-center gap-2 text-sm text-gray-600">
                            <span class="text-teal-500">✓</span> {{ $item }}
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- Portal Wali --}}
            <div class="border border-gray-100 rounded-2xl p-6 hover:shadow-md hover:border-teal-200 transition-all group">
                <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center mb-4 text-2xl group-hover:bg-blue-100 transition-colors">
                    👨‍👩‍👧
                </div>
                <h3 class="font-bold text-gray-900 text-lg mb-2">Portal Wali Santri</h3>
                <p class="text-gray-500 text-sm leading-relaxed">
                    Wali santri bisa pantau perkembangan anak langsung dari HP — tanpa perlu install aplikasi.
                </p>
                <ul class="mt-4 space-y-1.5">
                    @foreach(['Akses via link magic', 'Pantau ibadah, tahfidz & prestasi', 'Lihat tagihan SPP & kirim bukti'] as $item)
                        <li class="flex items-center gap-2 text-sm text-gray-600">
                            <span class="text-teal-500">✓</span> {{ $item }}
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- SPP & Keuangan --}}
            <div class="border border-gray-100 rounded-2xl p-6 hover:shadow-md hover:border-teal-200 transition-all group">
                <div class="w-12 h-12 bg-green-50 rounded-xl flex items-center justify-center mb-4 text-2xl group-hover:bg-green-100 transition-colors">
                    💳
                </div>
                <h3 class="font-bold text-gray-900 text-lg mb-2">SPP & Keuangan</h3>
                <p class="text-gray-500 text-sm leading-relaxed">
                    Kelola tagihan SPP bulanan secara digital. Wali konfirmasi transfer langsung dari HP, admin verifikasi dengan mudah.
                </p>
                <ul class="mt-4 space-y-1.5">
                    @foreach(['Tagihan bulanan per santri', 'Konfirmasi transfer oleh wali', 'Rekap tunggakan real-time'] as $item)
                        <li class="flex items-center gap-2 text-sm text-gray-600">
                            <span class="text-teal-500">✓</span> {{ $item }}
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- Prestasi Santri --}}
            <div class="border border-gray-100 rounded-2xl p-6 hover:shadow-md hover:border-teal-200 transition-all group">
                <div class="w-12 h-12 bg-yellow-50 rounded-xl flex items-center justify-center mb-4 text-2xl group-hover:bg-yellow-100 transition-colors">
                    🏆
                </div>
                <h3 class="font-bold text-gray-900 text-lg mb-2">Prestasi Santri</h3>
                <p class="text-gray-500 text-sm leading-relaxed">
                    Catat dan pamerkan pencapaian santri — dari tingkat internal pesantren hingga kompetisi nasional.
                </p>
                <ul class="mt-4 space-y-1.5">
                    @foreach(['Catat prestasi & penghargaan', 'Upload sertifikat digital', 'Tampil di portal wali'] as $item)
                        <li class="flex items-center gap-2 text-sm text-gray-600">
                            <span class="text-teal-500">✓</span> {{ $item }}
                        </li>
                    @endforeach
                </ul>
            </div>

        </div>
    </section>

    {{-- Highlight 3 kolom --}}
    <section class="bg-teal-50 py-16">
        <div class="max-w-5xl mx-auto px-6 grid grid-cols-1 md:grid-cols-3 gap-8">
            <div>
                <div class="text-4xl mb-4">📢</div>
                <h3 class="text-xl font-bold text-gray-900 mb-2">Pengumuman Langsung ke Wali</h3>
                <p class="text-gray-600 text-sm leading-relaxed">
                    Kirim pengumuman ke seluruh wali santri dalam hitungan detik — tersedia di portal wali tanpa perlu grup WhatsApp yang ramai.
                </p>
            </div>
            <div>
                <div class="text-4xl mb-4">💳</div>
                <h3 class="text-xl font-bold text-gray-900 mb-2">SPP Tanpa Kerumitan</h3>
                <p class="text-gray-600 text-sm leading-relaxed">
                    Wali cukup foto struk transfer dan kirim dari HP. Admin verifikasi sekali klik. Tidak ada lagi antrian bayar SPP manual.
                </p>
            </div>
            <div>
                <div class="text-4xl mb-4">📊</div>
                <h3 class="text-xl font-bold text-gray-900 mb-2">Laporan Komprehensif</h3>
                <p class="text-gray-600 text-sm leading-relaxed">
                    Laporan akademik, kesehatan, dan ibadah bisa diekspor ke PDF maupun Excel — cocok untuk evaluasi bulanan dan rapat wali santri.
                </p>
            </div>
        </div>
    </section>

    {{-- Cara Kerja --}}
    <section id="cara-kerja" class="max-w-4xl mx-auto px-6 py-20">
        <div class="text-center mb-14">
            <h2 class="text-3xl font-bold text-gray-900 mb-4">Mulai dalam 3 Langkah Mudah</h2>
            <p class="text-gray-500">Tidak perlu instalasi. Tidak perlu IT khusus.</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            @foreach([
                ['1', 'Daftar Pesantren', 'Buat akun dengan nama pesantren dan subdomain Anda. Selesai dalam 2 menit.', 'bg-teal-100 text-teal-700'],
                ['2', 'Input Data Santri', 'Tambahkan data santri, buat kelas, dan atur modul yang ingin digunakan.', 'bg-emerald-100 text-emerald-700'],
                ['3', 'Aktifkan Portal Wali', 'Bagikan link magic ke wali santri — mereka langsung bisa pantau perkembangan anak.', 'bg-blue-100 text-blue-700'],
            ] as $step)
                <div class="text-center">
                    <div class="w-14 h-14 {{ $step[3] }} rounded-2xl flex items-center justify-center text-2xl font-bold mx-auto mb-4">
                        {{ $step[0] }}
                    </div>
                    <h4 class="font-bold text-gray-900 mb-2">{{ $step[1] }}</h4>
                    <p class="text-sm text-gray-500 leading-relaxed">{{ $step[2] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- CTA Demo --}}
    <section class="bg-gray-900 py-20">
        <div class="max-w-2xl mx-auto px-6 text-center">
            <h2 class="text-3xl font-bold text-white mb-4">Ingin Lihat Langsung?</h2>
            <p class="text-gray-400 mb-8 leading-relaxed">
                Tim kami siap memberikan demo eksklusif dan membantu setup awal pesantren Anda secara gratis.
                Daftarkan pesantren Anda di waiting list demo sekarang.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('demo') }}"
                   class="inline-block bg-teal-600 text-white font-semibold px-8 py-3.5 rounded-xl text-base hover:bg-teal-500 transition-colors">
                    Daftar Waiting List Demo →
                </a>
                <a href="{{ route('register') }}"
                   class="inline-block bg-transparent text-white font-semibold px-8 py-3.5 rounded-xl text-base border border-gray-600 hover:border-gray-400 transition-colors">
                    Coba Sendiri Gratis
                </a>
            </div>
        </div>
    </section>

    {{-- Footer --}}
    <footer class="border-t border-gray-100 py-10">
        <div class="max-w-6xl mx-auto px-6 flex flex-col md:flex-row items-center justify-between gap-4">
            <span class="text-teal-700 font-bold text-lg">🕌 Walisantri.com</span>
            <p class="text-sm text-gray-400 text-center">
                Platform Digitalisasi Pesantren Indonesia
            </p>
            <div class="flex gap-6">
                <a href="{{ route('demo') }}" class="text-sm text-gray-500 hover:text-teal-700">Demo</a>
                <a href="{{ route('register') }}" class="text-sm text-gray-500 hover:text-teal-700">Daftar</a>
                <a href="{{ route('login') }}" class="text-sm text-gray-500 hover:text-teal-700">Masuk</a>
            </div>
        </div>
        <div class="text-center mt-6 text-xs text-gray-400">
            © {{ date('Y') }} Walisantri.com · Hak Cipta Dilindungi
        </div>
    </footer>

</body>
</html>
