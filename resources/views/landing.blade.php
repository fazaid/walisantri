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
                <a href="#harga" class="text-sm text-gray-500 hover:text-teal-700 font-medium">Harga</a>
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

    {{-- Harga --}}
    <section id="harga" class="max-w-6xl mx-auto px-6 py-20">
        @php
            use App\Models\BillingSetting;

            $bonusTahunan      = BillingSetting::get('bonus_bulan_tahunan', 2);
            $bulanBayarTahunan = 12 - $bonusTahunan;

            $paketHarga = [
                [
                    'nama'      => 'Gratis',
                    'harga'     => 0,
                    'kuota'     => BillingSetting::get('kuota_gratis', 5),
                    'popular'   => false,
                    'deskripsi' => 'Untuk mencoba dan evaluasi platform.',
                    'fitur'     => ['Semua modul tersedia', 'Portal wali santri', 'Support via email'],
                    'cta'       => ['label' => 'Daftar Gratis', 'href' => route('register'), 'style' => 'border'],
                ],
                [
                    'nama'      => 'Rintisan',
                    'harga'     => BillingSetting::get('harga_rintisan', 150_000),
                    'kuota'     => BillingSetting::get('kuota_rintisan', 100),
                    'popular'   => true,
                    'deskripsi' => 'Untuk pesantren yang baru berkembang.',
                    'fitur'     => ['Semua modul tersedia', 'Portal wali santri', 'Ekspor PDF & Excel', 'Support prioritas'],
                    'cta'       => ['label' => 'Mulai Sekarang', 'href' => route('register'), 'style' => 'solid'],
                ],
                [
                    'nama'      => 'Berkembang',
                    'harga'     => BillingSetting::get('harga_berkembang', 350_000),
                    'kuota'     => BillingSetting::get('kuota_berkembang', 500),
                    'popular'   => false,
                    'deskripsi' => 'Untuk pesantren menengah yang aktif.',
                    'fitur'     => ['Semua modul tersedia', 'Portal wali santri', 'Ekspor PDF & Excel', 'Support prioritas', 'Onboarding gratis'],
                    'cta'       => ['label' => 'Mulai Sekarang', 'href' => route('register'), 'style' => 'border'],
                ],
                [
                    'nama'      => 'Maju',
                    'harga'     => BillingSetting::get('harga_maju_base', 750_000),
                    'kuota'     => null,
                    'popular'   => false,
                    'deskripsi' => 'Untuk pesantren besar dengan kebutuhan khusus.',
                    'fitur'     => ['Semua modul tersedia', 'Portal wali santri', 'Ekspor PDF & Excel', 'Support prioritas', 'Onboarding gratis', 'Kuota custom 1.000+ santri'],
                    'cta'       => ['label' => 'Hubungi Kami', 'href' => route('demo'), 'style' => 'border'],
                ],
            ];
        @endphp

        <div class="text-center mb-10">
            <h2 class="text-3xl font-bold text-gray-900 mb-4">Harga Transparan, Tanpa Biaya Tersembunyi</h2>
            <p class="text-gray-500 max-w-xl mx-auto">
                Mulai gratis, upgrade kapan saja sesuai pertumbuhan pesantren Anda.
            </p>
        </div>

        {{-- Toggle bulanan / tahunan --}}
        <div class="flex justify-center mb-10">
            <div class="bg-gray-100 p-1 rounded-xl inline-flex gap-1">
                <button id="btn-bulanan" onclick="setPeriode('bulanan')"
                        class="px-5 py-2 rounded-lg text-sm font-semibold transition-all bg-white text-gray-900 shadow-sm">
                    Bulanan
                </button>
                <button id="btn-tahunan" onclick="setPeriode('tahunan')"
                        class="px-5 py-2 rounded-lg text-sm font-semibold transition-all text-gray-500">
                    Tahunan
                    <span class="ml-1.5 bg-teal-100 text-teal-700 text-xs font-semibold px-2 py-0.5 rounded-full">
                        Hemat {{ $bonusTahunan }} bulan
                    </span>
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 items-start">
            @foreach($paketHarga as $paket)
            @php
                $hargaBulanan         = $paket['harga'];
                $hargaTahunanPerBulan = $hargaBulanan > 0
                    ? (int) round($hargaBulanan * $bulanBayarTahunan / 12)
                    : 0;
                $totalTahunan         = $hargaBulanan * $bulanBayarTahunan;

                $kuotaCalc            = $paket['kuota'] ?? BillingSetting::get('kuota_maju_base', 1000);
                $hargaPerSantri       = $hargaBulanan > 0
                    ? (int) ceil($hargaBulanan / $kuotaCalc)
                    : null;
                $hargaPerSantriTahunan = $hargaTahunanPerBulan > 0
                    ? (int) ceil($hargaTahunanPerBulan / $kuotaCalc)
                    : null;
            @endphp
            <div class="relative border rounded-2xl p-6 flex flex-col gap-4
                {{ $paket['popular'] ? 'border-teal-500 shadow-lg shadow-teal-100 ring-1 ring-teal-500' : 'border-gray-200 hover:border-gray-300' }}">

                @if($paket['popular'])
                <div class="absolute -top-3 left-1/2 -translate-x-1/2">
                    <span class="bg-teal-600 text-white text-xs font-semibold px-3 py-1 rounded-full">Paling Populer</span>
                </div>
                @endif

                <div>
                    <div class="font-bold text-gray-900 text-lg mb-1">{{ $paket['nama'] }}</div>
                    <div class="text-gray-500 text-sm">{{ $paket['deskripsi'] }}</div>
                </div>

                <div>
                    @if($hargaBulanan === 0)
                        <span class="text-3xl font-bold text-gray-900">Gratis</span>
                        <div class="mt-1 text-sm text-gray-400">selamanya</div>
                    @else
                        <div class="flex items-baseline gap-1">
                            <span class="text-xs text-gray-400">Mulai</span>
                        </div>
                        <div>
                            <span class="text-3xl font-bold text-gray-900 price-display"
                                  data-bulanan="{{ number_format($hargaBulanan, 0, ',', '.') }}"
                                  data-tahunan="{{ number_format($hargaTahunanPerBulan, 0, ',', '.') }}">
                                Rp {{ number_format($hargaBulanan, 0, ',', '.') }}
                            </span>
                            <span class="text-sm text-gray-400">/bulan</span>
                        </div>
                        <div class="mt-1 text-xs text-gray-400 tagihan-info"
                             data-bulanan="Ditagih bulanan"
                             data-tahunan="Ditagih Rp {{ number_format($totalTahunan, 0, ',', '.') }}/tahun">
                            Ditagih bulanan
                        </div>
                    @endif
                    <div class="mt-1 text-sm font-medium {{ $paket['popular'] ? 'text-teal-600' : 'text-gray-500' }}">
                        @if($paket['kuota'])
                            hingga {{ number_format($paket['kuota'], 0, ',', '.') }} santri
                        @else
                            kuota custom 1.000+ santri
                        @endif
                    </div>
                </div>

                <ul class="space-y-2 flex-1">
                    @foreach($paket['fitur'] as $fitur)
                    <li class="flex items-start gap-2 text-sm text-gray-600">
                        <span class="text-teal-500 mt-0.5 shrink-0">✓</span>
                        {{ $fitur }}
                    </li>
                    @endforeach
                    @if($hargaPerSantri)
                    <li class="flex items-start gap-2 text-sm font-medium text-teal-700">
                        <span class="mt-0.5 shrink-0">✓</span>
                        <span class="harga-per-santri"
                              data-bulanan="hanya Rp {{ number_format($hargaPerSantri, 0, ',', '.') }}/santri"
                              data-tahunan="hanya Rp {{ number_format($hargaPerSantriTahunan, 0, ',', '.') }}/santri">
                            hanya Rp {{ number_format($hargaPerSantri, 0, ',', '.') }}/santri
                        </span>
                    </li>
                    @endif
                </ul>

                <a href="{{ $paket['cta']['href'] }}"
                   class="block text-center text-sm font-semibold px-4 py-2.5 rounded-xl transition-colors
                       {{ $paket['cta']['style'] === 'solid'
                           ? 'bg-teal-600 text-white hover:bg-teal-700'
                           : 'border border-gray-300 text-gray-700 hover:border-teal-400 hover:text-teal-700' }}">
                    {{ $paket['cta']['label'] }}
                </a>
            </div>
            @endforeach
        </div>

        <p class="text-center text-sm text-gray-400 mt-8">
            Paket 6 bulan juga tersedia dengan bonus 1 bulan gratis.
            <a href="{{ route('demo') }}" class="text-teal-600 hover:underline">Konsultasi gratis →</a>
        </p>
    </section>

    <script>
        function setPeriode(periode) {
            const isAnnual = periode === 'tahunan';

            document.getElementById('btn-bulanan').className = isAnnual
                ? 'px-5 py-2 rounded-lg text-sm font-semibold transition-all text-gray-500'
                : 'px-5 py-2 rounded-lg text-sm font-semibold transition-all bg-white text-gray-900 shadow-sm';

            document.getElementById('btn-tahunan').className = isAnnual
                ? 'px-5 py-2 rounded-lg text-sm font-semibold transition-all bg-white text-gray-900 shadow-sm'
                : 'px-5 py-2 rounded-lg text-sm font-semibold transition-all text-gray-500';

            document.querySelectorAll('.price-display').forEach(el => {
                el.textContent = 'Rp ' + el.dataset[periode];
            });

            document.querySelectorAll('.tagihan-info').forEach(el => {
                el.textContent = el.dataset[periode];
            });

            document.querySelectorAll('.harga-per-santri').forEach(el => {
                el.textContent = el.dataset[periode];
            });
        }
    </script>

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
                <a href="#harga" class="text-sm text-gray-500 hover:text-teal-700">Harga</a>
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
