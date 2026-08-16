<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Walisantri.com menghubungkan wali santri dengan pesantren — pantau kehadiran, ibadah, hafalan Al-Quran, nilai, kesehatan, dan SPP anak langsung dari HP. Pengurus dapat presensi kartu QR, rapor, dan alat evaluasi lengkap.">
    <title>Walisantri.com — Pesantren Transparan, Wali Santri Tenang</title>
    <link rel="icon" type="image/svg+xml" href="{{ \App\Models\PlatformBrandingSetting::faviconUrl() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.tema')
    @include('partials.analytics-head')
    <style>
        details summary::-webkit-details-marker { display: none; }
        details summary { list-style: none; }
        details[open] .faq-icon-plus { display: none; }
        details:not([open]) .faq-icon-minus { display: none; }

        /* Pemilih siklus harga: radio tersembunyi + selector sibling, supaya
           landing tetap bebas JavaScript seperti FAQ di atas. */
        #siklus-bulanan:checked ~ * .harga-tahunan,
        #siklus-tahunan:checked ~ * .harga-bulanan { display: none; }
        /* Pakai variabel palet, bukan heksa mati: keduanya ikut membalik saat
           mode gelap menyala (lihat resources/css/app.css). */
        #siklus-bulanan:checked ~ .siklus-tab label[for="siklus-bulanan"],
        #siklus-tahunan:checked ~ .siklus-tab label[for="siklus-tahunan"] {
            background-color: var(--color-white);
            color: var(--color-teal-700);
            box-shadow: 0 1px 2px rgb(0 0 0 / 0.1);
        }
        /* Input-nya sr-only, jadi fokus keyboard harus terlihat lewat label. */
        #siklus-bulanan:focus-visible ~ .siklus-tab label[for="siklus-bulanan"],
        #siklus-tahunan:focus-visible ~ .siklus-tab label[for="siklus-tahunan"] {
            outline: 2px solid var(--color-teal-600);
            outline-offset: 2px;
        }
    </style>
</head>
<body class="bg-white text-gray-800 font-sans">
@include('partials.analytics-body')

    @include('partials.situs-nav')

    {{-- Hero --}}
    <section class="bg-gradient-to-b from-teal-50 to-white">
        <div class="max-w-4xl mx-auto px-6 py-20 text-center">
            <div class="inline-flex items-center gap-2 bg-teal-100 text-teal-800 text-xs font-semibold px-3 py-1.5 rounded-full mb-6 uppercase tracking-wide">
                <span class="w-2 h-2 bg-teal-500 rounded-full animate-pulse"></span>
                Menghubungkan Wali Santri & Pesantren
            </div>
            <h1 class="text-4xl md:text-5xl font-bold text-gray-900 leading-tight mb-6">
                Wali Santri Pantau Anaknya,<br>
                <span class="text-teal-700">Pesantren Makin Transparan</span>
            </h1>
            <p class="text-lg text-gray-500 mb-8 max-w-2xl mx-auto leading-relaxed">
                Wali santri bisa memantau ibadah, hafalan Al-Quran, nilai, kesehatan, hingga SPP anaknya
                langsung dari HP — sementara pengurus dan ustadz mendapat alat lengkap untuk mencatat dan
                mengevaluasi perkembangan setiap santri.
            </p>
            <div class="flex flex-wrap justify-center gap-x-6 gap-y-2 mb-10">
                @foreach(['Wali pantau dari HP masing-masing', 'Tanpa instalasi aplikasi', 'Tanpa keahlian IT khusus'] as $benefit)
                    <span class="flex items-center gap-1.5 text-sm text-gray-600">
                        <span class="text-teal-500 font-bold">✓</span> {{ $benefit }}
                    </span>
                @endforeach
            </div>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                @if($registrationOpen)
                    <a href="{{ route('register') }}"
                       class="inline-block bg-teal-700 text-white font-semibold px-8 py-3.5 rounded-xl text-base hover:bg-teal-800 transition-colors shadow-sm">
                        Daftar Sekarang →
                    </a>
                @elseif($demoOpen)
                    <a href="{{ route('demo') }}"
                       class="inline-block bg-teal-700 text-white font-semibold px-8 py-3.5 rounded-xl text-base hover:bg-teal-800 transition-colors shadow-sm">
                        Ajukan Demo →
                    </a>
                @endif
                @if($demoWaliUrl)
                    {{-- Menukar tautan lemah (sekadar scroll) dengan produk sungguhan. --}}
                    <a href="{{ route('sandbox.coba') }}"
                       class="inline-block bg-white text-teal-700 font-semibold px-8 py-3.5 rounded-xl text-base border border-teal-200 hover:bg-teal-50 transition-colors">
                        Lihat Portal Wali →
                    </a>
                @else
                    <a href="#fitur"
                       class="inline-block bg-white text-teal-700 font-semibold px-8 py-3.5 rounded-xl text-base border border-teal-200 hover:bg-teal-50 transition-colors">
                        Lihat Fitur Lengkap ↓
                    </a>
                @endif
            </div>
            @unless($registrationOpen || $demoOpen)
                <p class="mt-8 inline-flex items-center gap-2 text-sm text-gray-600 bg-gray-100 border border-gray-200 rounded-xl px-5 py-3">
                    <span aria-hidden="true">🔒</span>
                    Pendaftaran pesantren baru sedang ditutup sementara.
                </p>
            @endunless
        </div>
    </section>


    {{-- UI Mockup --}}
    <section class="max-w-6xl mx-auto px-6 py-20">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-gray-900 mb-4">Wali Santri Terhubung, Pengurus Tetap Mengontrol</h2>
            <p class="text-gray-500 max-w-xl mx-auto">
                Wali memantau anak dari HP tanpa ribet, sementara admin & ustadz punya dashboard lengkap
                untuk mencatat dan mengevaluasi setiap santri.
            </p>
        </div>

        {{-- 2-kolom: Phone Wali (kiri) + Browser Admin (kanan) --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-start max-w-5xl mx-auto">

            {{-- Kiri: Browser Mockup Dashboard Ustadz --}}
            <div class="flex flex-col gap-3 order-2">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide text-center">🖥️ Dashboard Ustadz</p>
                <div class="rounded-2xl shadow-xl border border-gray-200 overflow-hidden">
                    {{-- Browser Chrome --}}
                    <div class="bg-gray-100 border-b border-gray-200 px-4 py-2.5 flex items-center gap-3">
                        <div class="flex gap-1.5">
                            <div class="w-2.5 h-2.5 rounded-full bg-red-400"></div>
                            <div class="w-2.5 h-2.5 rounded-full bg-yellow-400"></div>
                            <div class="w-2.5 h-2.5 rounded-full bg-green-400"></div>
                        </div>
                        <div class="flex-1 bg-white rounded-md px-3 py-1 text-xs text-gray-400 border border-gray-200 max-w-xs">
                            app.walisantri.com/admin
                        </div>
                    </div>

                    {{-- App Shell --}}
                    <div class="flex bg-gray-50">
                        {{-- Sidebar --}}
                        <div class="w-36 bg-teal-800 text-white dark:bg-teal-100 dark:text-gray-900 flex-shrink-0 hidden sm:flex flex-col">
                            <div class="px-3 py-3 border-b border-teal-700 dark:border-teal-300">
                                <div class="text-xs font-bold text-teal-300 dark:text-teal-500 uppercase tracking-wide">Walisantri</div>
                                <div class="text-xs font-semibold mt-0.5">Dashboard Ustadz</div>
                            </div>
                            <nav class="px-1.5 py-2 space-y-0.5">
                                @foreach([
                                    ['🏠', 'Dashboard', true],
                                    ['👦', 'Santri', false],
                                    ['📚', 'Akademik', false],
                                    ['📖', 'Tahfidz', false],
                                    ['✅', 'Presensi', false],
                                    ['🕌', 'Mutaba\'ah', false],
                                    ['🛡️', 'Kesantrian', false],
                                    ['📋', 'Rapor', false],
                                ] as $menu)
                                    <div class="flex items-center gap-1.5 px-2 py-1.5 rounded-lg {{ $menu[2] ? 'bg-teal-700 text-white dark:bg-teal-200 dark:text-gray-900' : 'text-teal-200 dark:text-gray-500' }} cursor-default">
                                        <span class="text-xs">{{ $menu[0] }}</span>
                                        <span class="text-xs">{{ $menu[1] }}</span>
                                    </div>
                                @endforeach
                            </nav>
                        </div>

                        {{-- Main Content --}}
                        <div class="flex-1 p-4 overflow-hidden">
                            <div class="mb-3">
                                <h3 class="text-xs font-bold text-gray-800">Selamat Datang, Ust. Fauzan 👋</h3>
                                <p class="text-xs text-gray-400">Pesantren Al-Hikmah · Halaqah 2A</p>
                            </div>

                            {{-- Stats Cards --}}
                            <div class="grid grid-cols-2 gap-2 mb-3">
                                @foreach([
                                    ['18', 'Santri Binaan', 'bg-teal-50 text-teal-700'],
                                    ['14', 'Setoran Hari Ini', 'bg-green-50 text-green-700'],
                                    ['3', 'Belum Mutaba\'ah', 'bg-amber-50 text-amber-700'],
                                    ['1', 'Santri Sakit', 'bg-red-50 text-red-700'],
                                ] as $card)
                                    <div class="{{ $card[2] }} rounded-lg p-2">
                                        <div class="text-sm font-bold">{{ $card[0] }}</div>
                                        <div class="text-xs opacity-75">{{ $card[1] }}</div>
                                    </div>
                                @endforeach
                            </div>

                            {{-- Mini Table --}}
                            <div class="bg-white rounded-lg border border-gray-100 overflow-hidden">
                                <div class="px-3 py-2 border-b border-gray-100 flex items-center justify-between">
                                    <span class="text-xs font-semibold text-gray-700">Setoran Hari Ini</span>
                                    <span class="text-xs text-teal-600">Lihat semua →</span>
                                </div>
                                <table class="w-full text-xs">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-3 py-1.5 text-left text-gray-500 font-medium">Nama</th>
                                            <th class="px-3 py-1.5 text-left text-gray-500 font-medium">Nilai</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-50">
                                        @foreach([
                                            ['Ahmad Zaky', 'Mumtaz', 'text-green-600 bg-green-50'],
                                            ['Fatimah Azzahra', 'Jayyid Jiddan', 'text-teal-600 bg-teal-50'],
                                            ['Muhammad Rizal', 'Belum Setor', 'text-amber-600 bg-amber-50'],
                                        ] as $row)
                                            <tr>
                                                <td class="px-3 py-2 text-gray-800 font-medium">{{ $row[0] }}</td>
                                                <td class="px-3 py-2">
                                                    <span class="px-1.5 py-0.5 rounded-full text-xs font-medium {{ $row[2] }}">{{ $row[1] }}</span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Kanan: Phone Mockup Portal Wali --}}
            <div class="flex flex-col items-center gap-3 order-1">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide text-center">📱 Portal Wali Santri</p>
                {{-- Phone Frame --}}
                <div class="relative bg-gray-800 dark:bg-gray-200 rounded-[2.5rem] p-2.5 shadow-2xl border-4 border-gray-700 dark:border-gray-300 w-64">
                    {{-- Notch --}}
                    <div class="absolute top-0 left-1/2 -translate-x-1/2 w-20 h-5 bg-gray-800 dark:bg-gray-200 rounded-b-xl z-10"></div>
                    {{-- Screen --}}
                    <div class="bg-gray-100 rounded-[2rem] overflow-hidden">
                        {{-- Status bar --}}
                        <div class="bg-teal-700 px-4 pt-5 pb-3">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-teal-200 text-xs">9:41</span>
                                <div class="flex gap-1 items-center">
                                    <span class="text-teal-200 text-xs">●●●</span>
                                    <span class="text-teal-200 text-xs">▲</span>
                                    <span class="text-teal-200 text-xs">🔋</span>
                                </div>
                            </div>
                            <p class="text-teal-200 text-xs">Assalamu'alaikum,</p>
                            <p class="text-white text-sm font-bold">Bpk. Hasan</p>
                        </div>

                        {{-- Santri Info Card --}}
                        <div class="mx-3 -mt-3 bg-white rounded-2xl shadow-md p-3 border border-gray-100">
                            <div class="flex items-center gap-2.5">
                                <div class="w-10 h-10 rounded-full bg-teal-100 text-teal-700 flex items-center justify-center text-sm font-bold flex-shrink-0">AF</div>
                                <div>
                                    <p class="text-xs font-bold text-gray-800">Ahmad Fauzan</p>
                                    <p class="text-xs text-gray-400">NIS 2024001 · Kelas 2A</p>
                                </div>
                            </div>
                        </div>

                        {{-- Summary Cards 2x2 --}}
                        <div class="grid grid-cols-2 gap-2 mx-3 mt-2">
                            @foreach([
                                ['📖', 'Hafalan', '12 Juz', 'bg-teal-50 text-teal-700'],
                                ['✨', 'Amalan', '87%', 'bg-green-50 text-green-700'],
                                ['🏥', 'Kesehatan', 'Sehat', 'bg-green-50 text-green-700'],
                                ['⭐', 'Rapor', '88.5', 'bg-blue-50 text-blue-700'],
                            ] as $c)
                                <div class="{{ $c[3] }} rounded-xl p-2.5">
                                    <div class="text-xs opacity-60 mb-0.5">{{ $c[0] }} {{ $c[1] }}</div>
                                    <div class="text-sm font-bold">{{ $c[2] }}</div>
                                </div>
                            @endforeach
                        </div>

                        {{-- Setoran Terakhir --}}
                        <div class="mx-3 mt-2 mb-2 bg-white rounded-xl border border-gray-100 overflow-hidden">
                            <div class="px-3 py-2 border-b border-gray-50 flex items-center justify-between">
                                <span class="text-xs font-semibold text-gray-700">Setoran Terakhir</span>
                                <span class="text-xs text-teal-600">Lihat →</span>
                            </div>
                            <div class="px-3 py-2">
                                <p class="text-xs font-medium text-gray-800">Juz 1 · Halaman 3–5</p>
                                <div class="flex gap-1 mt-1">
                                    <span class="text-xs bg-green-50 text-green-700 px-1.5 py-0.5 rounded-full font-medium">Sabaq</span>
                                    <span class="text-xs bg-green-50 text-green-700 px-1.5 py-0.5 rounded-full font-medium">Mumtaz</span>
                                </div>
                            </div>
                        </div>

                        {{-- Pengumuman mini --}}
                        <div class="mx-3 mb-2 bg-white rounded-xl border border-gray-100 overflow-hidden">
                            <div class="px-3 py-2 border-b border-gray-50">
                                <span class="text-xs font-semibold text-gray-700">Pengumuman</span>
                            </div>
                            <div class="px-3 py-2">
                                <p class="text-xs font-medium text-gray-800">Libur Idul Adha 1446 H</p>
                                <p class="text-xs text-gray-400 mt-0.5">2 jam lalu</p>
                            </div>
                        </div>

                        {{-- Bottom Nav --}}
                        <div class="bg-white border-t border-gray-100 px-2 py-2">
                            <div class="flex justify-around items-center">
                                @foreach([
                                    ['🏠', 'Beranda', true],
                                    ['💳', 'SPP', false],
                                    ['💵', 'Uang Saku', false],
                                    ['📋', 'Rapor', false],
                                ] as $tab)
                                    <div class="flex flex-col items-center gap-0.5 cursor-default">
                                        <span class="text-sm leading-none">{{ $tab[0] }}</span>
                                        <span class="text-[10px] {{ $tab[2] ? 'text-teal-600 font-semibold' : 'text-gray-400' }}">{{ $tab[1] }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                <p class="text-xs text-gray-400 text-center max-w-48">Akses via link — tanpa install aplikasi</p>
            </div>

        </div>

        {{-- 3 Highlight di bawah mockup --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-8 max-w-5xl mx-auto">
            @foreach([
                ['📱', 'Portal Wali Santri', 'Wali cukup klik link yang dikirim — langsung bisa pantau anaknya tanpa perlu install apapun.'],
                ['🖥️', 'Dashboard Ustadz', 'Ustadz pantau & evaluasi santri binaannya dalam satu layar — hafalan, mutaba\'ah, nilai, hingga kesehatan.'],
                ['📖', 'Modul Tahfidz', 'Wali lihat progress hafalan anaknya, ustadz catat setoran, murajaah, dan progress per juz secara rutin.'],
            ] as $highlight)
                <div class="bg-gray-50 border border-gray-100 rounded-xl p-5">
                    <div class="text-2xl mb-3">{{ $highlight[0] }}</div>
                    <h4 class="font-bold text-gray-800 mb-1.5 text-sm">{{ $highlight[1] }}</h4>
                    <p class="text-gray-500 text-xs leading-relaxed">{{ $highlight[2] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Presensi & Kartu QR --}}
    <section id="presensi" class="bg-gray-900 dark:bg-gray-50 py-20">
        <div class="max-w-6xl mx-auto px-6">
            <div class="text-center mb-14">
                <div class="inline-flex items-center gap-2 bg-teal-900/60 dark:bg-teal-100/60 text-teal-300 dark:text-teal-500 text-xs font-semibold px-3 py-1.5 rounded-full mb-5 uppercase tracking-wide">
                    Modul Terbaru
                </div>
                <h2 class="text-3xl font-bold text-white dark:text-gray-900 mb-4">Absensi Santri, Selesai Sebelum Kelas Dimulai</h2>
                <p class="text-gray-400 max-w-2xl mx-auto leading-relaxed">
                    Cukup pindai kartu santri — presensi tercatat, rekapnya jadi sendiri, dan wali bisa
                    melihat kehadiran anaknya tanpa perlu bertanya.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach([
                    ['📷', 'Pindai Kartu QR', 'Setiap santri punya kartu QR yang bisa dicetak per kelas. Pindai lewat kamera HP petugas atau alat scanner USB/Bluetooth — keduanya didukung.'],
                    ['🕌', 'Harian atau Per Jam Pelajaran', 'Catat kehadiran sekali sehari, atau per jam pelajaran lengkap dengan mata pelajarannya. Pesantren memilih sendiri di pengaturan.'],
                    ['📅', 'Kalender Hari Libur', 'Tandai libur mingguan dan tanggal merah pesantren. Hari libur tidak pernah ikut dihitung sebagai ketidakhadiran.'],
                    ['📝', 'Tujuh Status Kehadiran', 'Hadir, Terlambat, Sakit, Izin, Alpa, Pulang, dan Dispensasi — bukan sekadar hadir atau tidak, karena alasannya penting saat dibicarakan dengan wali.'],
                    ['📨', 'Pengajuan Izin dari Wali', 'Wali mengajukan izin atau sakit dari HP beserta surat keterangannya. Pengurus tinggal menyetujui, dan presensi hari itu terisi otomatis.'],
                    ['📊', 'Rekap & Rapor Kehadiran', 'Persentase kehadiran per santri dan per kelas, bisa diekspor ke Excel, dan ikut tercetak di rapor PDF yang dibaca wali.'],
                ] as $item)
                    <div class="bg-gray-800/60 dark:bg-gray-100/60 border border-gray-700 dark:border-gray-200 rounded-2xl p-6">
                        <div class="text-3xl mb-4">{{ $item[0] }}</div>
                        <h3 class="font-bold text-white dark:text-gray-900 text-lg mb-2">{{ $item[1] }}</h3>
                        <p class="text-gray-400 text-sm leading-relaxed">{{ $item[2] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Fitur --}}
    <section id="fitur" class="max-w-6xl mx-auto px-6 py-20">
        <div class="text-center mb-14">
            <h2 class="text-3xl font-bold text-gray-900 mb-4">Satu Platform, Wali Santri & Pengurus Sama-Sama Terbantu</h2>
            <p class="text-gray-500 max-w-xl mx-auto">
                Wali santri pantau perkembangan anak dari HP, pengurus & ustadz punya alat lengkap untuk
                mencatat dan mengevaluasi setiap santri.
            </p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

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
                    Wali cukup foto struk transfer dan kirim dari HP; admin verifikasi sekali klik — tidak ada lagi antre bayar SPP secara manual.
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
                    Setiap pencapaian anak — dari lomba internal hingga kompetisi nasional — otomatis tampil di portal wali, jadi orang tua ikut bangga secara real-time.
                </p>
                <ul class="mt-4 space-y-1.5">
                    @foreach(['Catat prestasi & penghargaan', 'Upload sertifikat digital', 'Tampil di portal wali'] as $item)
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
                    Wali bisa lihat progress hafalan Al-Quran anaknya kapan saja, sementara ustadz mencatat setoran, murajaah, dan evaluasi harian dengan mudah.
                </p>
                <ul class="mt-4 space-y-1.5">
                    @foreach(['Catat setoran & murajaah', 'Progress per juz/halaman', 'Riwayat hafalan lengkap'] as $item)
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
                    Amalan harian santri — shalat, puasa, tilawah — dicatat ustadz dan bisa dipantau wali secara real-time, tanpa perlu bertanya lewat telepon.
                </p>
                <ul class="mt-4 space-y-1.5">
                    @foreach(['Monitoring ibadah harian', 'Grafik perkembangan', 'Rekap bulanan & rapor'] as $item)
                        <li class="flex items-center gap-2 text-sm text-gray-600">
                            <span class="text-teal-500">✓</span> {{ $item }}
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- Akademik --}}
            <div class="border border-gray-100 rounded-2xl p-6 hover:shadow-md hover:border-teal-200 transition-all group">
                <div class="w-12 h-12 bg-teal-50 rounded-xl flex items-center justify-center mb-4 text-2xl group-hover:bg-teal-100 transition-colors">
                    📚
                </div>
                <h3 class="font-bold text-gray-900 text-lg mb-2">Akademik</h3>
                <p class="text-gray-500 text-sm leading-relaxed">
                    Ustadz input nilai dengan mudah, lalu satu halaman rapor menggabungkan nilai akademik, tahfidz, mutaba'ah, karakter, dan kehadiran jadi satu PDF.
                </p>
                <ul class="mt-4 space-y-1.5">
                    @foreach(['Manajemen kelas & mapel', 'Input nilai massal per kelas', 'Rapor gabungan 5 modul, ekspor PDF'] as $item)
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
                    Rekam medis digital santri tercatat rapi oleh pengurus, dan wali bisa tenang karena riwayat kesehatan anak selalu bisa diakses.
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
                    Manajemen aset dan barang pesantren untuk pengurus. Lacak kondisi, lokasi, dan riwayat penggunaan dengan mudah.
                </p>
                <ul class="mt-4 space-y-1.5">
                    @foreach(['Katalog aset pesantren', 'Status & kondisi barang', 'Riwayat peminjaman'] as $item)
                        <li class="flex items-center gap-2 text-sm text-gray-600">
                            <span class="text-teal-500">✓</span> {{ $item }}
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- Uang Saku --}}
            <div class="border border-gray-100 rounded-2xl p-6 hover:shadow-md hover:border-teal-200 transition-all group">
                <div class="w-12 h-12 bg-lime-50 rounded-xl flex items-center justify-center mb-4 text-2xl group-hover:bg-lime-100 transition-colors">
                    👛
                </div>
                <h3 class="font-bold text-gray-900 text-lg mb-2">Uang Saku Santri</h3>
                <p class="text-gray-500 text-sm leading-relaxed">
                    Setoran dan pengambilan uang saku dicatat sebagai buku kas per santri — wali bisa melihat sisa saldo anaknya kapan saja tanpa perlu menelepon pengurus.
                </p>
                <ul class="mt-4 space-y-1.5">
                    @foreach(['Catat setoran & pengambilan', 'Saldo berjalan per santri', 'Wali pantau dari portal'] as $item)
                        <li class="flex items-center gap-2 text-sm text-gray-600">
                            <span class="text-teal-500">✓</span> {{ $item }}
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- Ekstrakurikuler --}}
            <div class="border border-gray-100 rounded-2xl p-6 hover:shadow-md hover:border-teal-200 transition-all group">
                <div class="w-12 h-12 bg-orange-50 rounded-xl flex items-center justify-center mb-4 text-2xl group-hover:bg-orange-100 transition-colors">
                    ⚽
                </div>
                <h3 class="font-bold text-gray-900 text-lg mb-2">Ekstrakurikuler</h3>
                <p class="text-gray-500 text-sm leading-relaxed">
                    Kelola daftar ekskul beserta pembinanya, catat siapa ikut apa dan sampai level mana — semuanya ikut tampil di halaman santri yang dibuka wali.
                </p>
                <ul class="mt-4 space-y-1.5">
                    @foreach(['Master ekskul & pembina', 'Level Pemula sampai Mahir', 'Tampil di portal wali'] as $item)
                        <li class="flex items-center gap-2 text-sm text-gray-600">
                            <span class="text-teal-500">✓</span> {{ $item }}
                        </li>
                    @endforeach
                </ul>
            </div>

        </div>
    </section>

    {{-- Highlight 4 kolom --}}
    <section class="bg-teal-50 py-16">
        <div class="max-w-6xl mx-auto px-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
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
            <div>
                <div class="text-4xl mb-4">🌐</div>
                <h3 class="text-xl font-bold text-gray-900 mb-2">Website Profil Pesantren Sendiri</h3>
                <p class="text-gray-600 text-sm leading-relaxed">
                    Setiap pesantren otomatis dapat website profil publik di subdomain sendiri, lengkap logo, galeri foto, dan statistik — siap dibagikan ke calon wali santri.
                </p>
            </div>
        </div>
    </section>

    {{-- Harga --}}
    <section id="harga" class="max-w-6xl mx-auto px-6 py-20">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-gray-900 mb-4">Harga Transparan, Sesuai Jumlah Santri</h2>
            <p class="text-gray-500 max-w-2xl mx-auto leading-relaxed">
                Semua modul terbuka di semua paket; yang membedakan hanya kuota santri.
                Pendaftaran tidak meminta kartu kredit.
            </p>
        </div>

        {{-- Pemilih siklus. Radio-nya sengaja jadi saudara langsung <section> agar
             CSS di <head> bisa menyembunyikan harga yang tidak dipilih tanpa JS. --}}
        <input type="radio" name="siklus-harga" id="siklus-bulanan" class="sr-only" checked>
        <input type="radio" name="siklus-harga" id="siklus-tahunan" class="sr-only">

        <div class="siklus-tab flex justify-center mb-10">
            <div class="inline-flex items-center gap-1 bg-gray-100 p-1 rounded-full">
                <label for="siklus-bulanan"
                       class="cursor-pointer rounded-full px-5 py-2 text-sm font-semibold text-gray-500 transition-colors">
                    Bulanan
                </label>
                <label for="siklus-tahunan"
                       class="cursor-pointer rounded-full px-5 py-2 text-sm font-semibold text-gray-500 transition-colors whitespace-nowrap">
                    Tahunan
                    @if($bonusTahunan > 0)
                        <span class="ml-1 text-xs font-bold text-teal-600">{{ $bonusTahunan }} bulan gratis</span>
                    @endif
                </label>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 items-start">
            @foreach($paketList as $paket)
                <div class="relative border rounded-2xl p-6 flex flex-col gap-4 h-full
                    {{ $paket['populer'] ? 'border-teal-500 shadow-lg shadow-teal-100 ring-1 ring-teal-500' : 'border-gray-200 hover:border-gray-300' }}">

                    @if($paket['populer'])
                        <div class="absolute -top-3 left-1/2 -translate-x-1/2">
                            <span class="bg-teal-600 text-white text-xs font-semibold px-3 py-1 rounded-full whitespace-nowrap">Paling Populer</span>
                        </div>
                    @endif

                    <div>
                        <div class="font-bold text-gray-900 text-lg mb-1">{{ $paket['nama'] }}</div>
                        <div class="text-gray-500 text-sm leading-relaxed">{{ $paket['deskripsi'] }}</div>
                    </div>

                    {{-- Yang ditonjolkan tarif per santri; harga paket tetap ditulis di
                         bawahnya karena itulah yang ditagih. min-h menahan tinggi kartu
                         supaya tidak melompat saat siklus diganti. --}}
                    <div class="min-h-[7.5rem]">
                        <div class="harga-bulanan">
                            <span class="text-3xl font-bold text-gray-900">{{ $paket['perSantriBulanan'] }}</span>
                            <span class="text-gray-500 text-sm">/santri/bulan</span>
                            <div class="text-sm text-gray-700 mt-1.5">
                                <span class="font-semibold">{{ $paket['harga'] }}</span>/bulan per pesantren
                            </div>
                            <div class="text-xs text-gray-500 mt-0.5">
                                Setara pada kuota {{ number_format($paket['kuota'], 0, ',', '.') }} santri; tagihannya per paket.
                            </div>
                        </div>
                        <div class="harga-tahunan">
                            <span class="text-3xl font-bold text-gray-900">{{ $paket['perSantriTahunan'] }}</span>
                            <span class="text-gray-500 text-sm">/santri/tahun</span>
                            <div class="text-sm text-gray-700 mt-1.5">
                                <span class="font-semibold">{{ $paket['hargaTahunan'] }}</span>/tahun per pesantren
                                @if($paket['adaHematTahunan'])
                                    <span class="text-gray-400 line-through">{{ $paket['hargaTahunanNormal'] }}</span>
                                @endif
                            </div>
                            @if($paket['adaHematTahunan'])
                                <div class="text-xs font-semibold text-teal-700 mt-0.5">
                                    Hemat {{ $paket['hematTahunan'] }} — bayar {{ $bulanBayarTahunan }} bulan, aktif {{ $totalBulanTahunan }} bulan.
                                </div>
                            @endif
                            <div class="text-xs text-gray-500 mt-0.5">
                                Setara pada kuota {{ number_format($paket['kuota'], 0, ',', '.') }} santri; tagihannya per paket.
                            </div>
                        </div>
                    </div>

                    <div class="text-sm text-gray-600 border-t border-gray-100 pt-4">
                        <span class="font-semibold text-gray-900">
                            Sampai {{ number_format($paket['kuota'], 0, ',', '.') }} santri
                        </span>
                        @if($paket['hubungiKami'])
                            <div class="text-xs text-gray-500 mt-1">Kuota bisa ditambah per 100 santri.</div>
                        @endif
                    </div>

                    <ul class="space-y-1.5 text-sm text-gray-600">
                        @foreach(['Seluruh modul terbuka', 'Portal wali & Magic Link', 'Website profil pesantren', 'Ekspor PDF & Excel'] as $fitur)
                            <li class="flex items-start gap-2">
                                <span class="text-teal-500 shrink-0">✓</span> {{ $fitur }}
                            </li>
                        @endforeach
                    </ul>

                    @if($paket['hubungiKami'] && $demoOpen)
                        <a href="{{ route('demo') }}"
                           class="mt-auto block text-center border border-teal-200 text-teal-700 font-semibold px-4 py-2.5 rounded-xl text-sm hover:bg-teal-50 transition-colors">
                            Hubungi Kami
                        </a>
                    @elseif($registrationOpen)
                        <a href="{{ route('register') }}"
                           class="mt-auto block text-center font-semibold px-4 py-2.5 rounded-xl text-sm transition-colors
                               {{ $paket['populer'] ? 'bg-teal-700 text-white hover:bg-teal-800' : 'border border-teal-200 text-teal-700 hover:bg-teal-50' }}">
                            Daftar Sekarang
                        </a>
                    @elseif($demoOpen)
                        <a href="{{ route('demo') }}"
                           class="mt-auto block text-center border border-teal-200 text-teal-700 font-semibold px-4 py-2.5 rounded-xl text-sm hover:bg-teal-50 transition-colors">
                            Ajukan Demo
                        </a>
                    @endif
                </div>
            @endforeach
        </div>

        <p class="text-center text-sm text-gray-500 mt-8 leading-relaxed">
            Tersedia juga durasi 6 bulan dengan <span class="font-semibold text-gray-700">{{ $bonusEnam }} bulan gratis</span>.
            Pembayaran lewat transfer bank.
            Harga dapat berubah sewaktu-waktu — yang tercantum di halaman ini adalah harga yang berlaku hari ini.
        </p>
    </section>

    {{-- Masalah yang Diselesaikan --}}
    <section class="max-w-6xl mx-auto px-6 py-20">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-gray-900 mb-4">Masalah yang Diselesaikan</h2>
            <p class="text-gray-500 max-w-xl mx-auto">
                Tiga keluhan yang hampir selalu muncul di pesantren — dan bagaimana Walisantri menutupnya.
            </p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @foreach([
                [
                    '📑',
                    'Rekap menumpuk di spreadsheet',
                    'Data setoran, ibadah, dan nilai tersebar di banyak file dan hanya dimengerti orang yang membuatnya. Saat dibutuhkan untuk rapat atau rapor, semuanya harus disalin ulang.',
                    'Semua tercatat di satu tempat, rekap dan rapornya jadi sendiri.',
                ],
                [
                    '📞',
                    'Wali menelepon untuk menanyakan kabar anak',
                    'Pertanyaan yang sama datang berulang lewat telepon dan grup WhatsApp — sudah setor berapa, sakit tidak, SPP sudah masuk belum — dan pengurus menjawabnya satu per satu.',
                    'Wali membuka portalnya sendiri kapan saja, cukup dari satu tautan.',
                ],
                [
                    '🗒️',
                    'Absensi kertas yang tidak pernah jadi laporan',
                    'Daftar hadir diisi di buku, lalu berhenti di situ. Menghitung persentase kehadiran satu semester berarti membuka kembali puluhan lembar.',
                    'Pindai kartu QR, persentase kehadiran langsung terhitung sampai ke rapor.',
                ],
            ] as $m)
                <div class="bg-white border border-gray-100 rounded-2xl p-6 shadow-sm flex flex-col">
                    <div class="text-3xl mb-4">{{ $m[0] }}</div>
                    <h3 class="font-bold text-gray-900 mb-2">{{ $m[1] }}</h3>
                    <p class="text-gray-500 text-sm leading-relaxed mb-4">{{ $m[2] }}</p>
                    <p class="mt-auto flex items-start gap-2 text-sm text-teal-800 bg-teal-50 rounded-xl px-4 py-3 leading-relaxed">
                        <span class="text-teal-500 font-bold shrink-0">✓</span>
                        <span>{{ $m[3] }}</span>
                    </p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Cara Kerja --}}
    <section id="cara-kerja" class="bg-gray-50 py-20">
        <div class="max-w-4xl mx-auto px-6">
            <div class="text-center mb-14">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">Mulai dalam 3 Langkah Mudah</h2>
                <p class="text-gray-500">Tidak perlu instalasi. Tidak perlu IT khusus.</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                @foreach([
                    ['1', 'Daftar Akun Pesantren', 'Isi form singkat — akun pesantren Anda langsung aktif dengan fitur penuh, tanpa menunggu persetujuan.', 'bg-teal-100 text-teal-700'],
                    ['2', 'Input Data Santri', 'Tambahkan santri satu per satu atau impor sekaligus dari file Excel, lalu buat kelas dan kamarnya.', 'bg-emerald-100 text-emerald-700'],
                    ['3', 'Aktifkan Portal Wali', 'Bagikan link magic ke wali santri — mereka langsung bisa pantau ibadah, hafalan, dan nilai anak dari HP.', 'bg-blue-100 text-blue-700'],
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
        </div>
    </section>

    {{-- FAQ --}}
    <section id="faq" class="max-w-3xl mx-auto px-6 py-20">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-gray-900 mb-4">Pertanyaan yang Sering Ditanyakan</h2>
            @if($demoOpen)
                <p class="text-gray-500">Ada pertanyaan lain? <a href="{{ route('demo') }}" class="text-teal-700 font-medium hover:underline">Hubungi kami lewat form demo</a>.</p>
            @endif
        </div>
        <div class="space-y-3">
            @foreach([
                [
                    'Berapa biaya Walisantri?',
                    'Walisantri berlangganan, mulai Rp 150.000/bulan sesuai jumlah santri — rinciannya ada di bagian Harga. Semua modul terbuka di semua paket; yang membedakan hanya kuota santri. Pendaftaran tidak meminta kartu kredit dan akun aktif seketika.'
                ],
                [
                    'Bagaimana cara wali santri mengakses portal?',
                    'Admin pesantren cukup bagikan link unik (link magic) kepada wali santri. Wali klik link tersebut dan langsung masuk ke portal tanpa perlu daftar atau mengingat password. Bisa diakses dari HP manapun.'
                ],
                [
                    'Apakah perlu instalasi atau tenaga IT khusus?',
                    'Tidak sama sekali. Walisantri adalah aplikasi berbasis web — cukup buka browser, login, dan langsung bisa digunakan. Tidak ada software yang perlu diinstall, dan tidak perlu keahlian IT khusus.'
                ],
                [
                    'Apakah data pesantren aman?',
                    'Semua akses lewat HTTPS. Data tiap pesantren dipisahkan dan hanya bisa dijangkau oleh akun pesantren itu sendiri — pengurus satu pesantren tidak pernah bisa melihat data pesantren lain. Backup berjalan otomatis setiap hari dan disalin ke penyimpanan luar dalam keadaan terenkripsi, dan setiap perubahan data penting tercatat di audit log. Data Anda tidak pernah dibagikan ke pihak ketiga.'
                ],
                [
                    'Berapa lama proses setup awal?',
                    'Akun aktif seketika setelah Anda mendaftar — tidak ada proses persetujuan. Yang memakan waktu hanya memasukkan data santri, dan itu pun bisa dipercepat dengan impor dari file Excel. Panduan langkah demi langkah tersedia di dalam aplikasi.'
                ],
                [
                    'Apakah cocok untuk pesantren kecil dengan sedikit santri?',
                    'Sangat cocok. Walisantri dirancang untuk semua skala pesantren — dari yang baru berdiri dengan puluhan santri hingga pesantren besar dengan ribuan santri.'
                ],
                [
                    'Apakah bisa ekspor data ke Excel atau PDF?',
                    'Ya. Laporan akademik, kesehatan, ibadah, dan keuangan bisa diekspor ke PDF maupun Excel. Fitur ini berguna untuk evaluasi bulanan, rapat dewan guru, atau laporan ke wali santri.'
                ],
                [
                    'Bagaimana cara absensi santri?',
                    'Presensi bisa dicatat sekali sehari atau per jam pelajaran — pesantren memilih sendiri. Pengisiannya manual lewat daftar kelas, atau dengan memindai kartu QR santri memakai kamera HP maupun alat scanner. Wali juga bisa mengajukan izin atau sakit dari portal beserta surat keterangannya, dan begitu disetujui pengurus, presensi hari itu terisi otomatis.'
                ],
                [
                    'Apa yang terjadi kalau masa langganan berakhir?',
                    'Ada masa tenggang 7 hari: pengurus diarahkan ke halaman langganan saat masuk, sementara wali santri tetap bisa membuka portal untuk melihat data. Lewat masa itu akun ditangguhkan sampai langganan diperpanjang. Data pesantren Anda tidak dihapus.'
                ],
                [
                    'Bagaimana jika koneksi internet di pesantren tidak stabil?',
                    'Walisantri membutuhkan koneksi internet untuk mengakses data secara real-time. Namun antarmuka dirancang ringan agar tetap bisa digunakan di koneksi yang lambat sekalipun. Kami terus mengoptimalkan performa untuk kondisi ini.'
                ],
            ] as $faq)
                <details class="border border-gray-200 rounded-xl overflow-hidden group">
                    <summary class="flex items-center justify-between px-5 py-4 cursor-pointer hover:bg-gray-50 transition-colors">
                        <span class="font-semibold text-gray-800 text-sm pr-4">{{ $faq[0] }}</span>
                        <span class="text-teal-600 flex-shrink-0">
                            <span class="faq-icon-plus text-xl font-light">+</span>
                            <span class="faq-icon-minus text-xl font-light">−</span>
                        </span>
                    </summary>
                    <div class="px-5 pb-5 pt-1 text-sm text-gray-600 leading-relaxed border-t border-gray-100 bg-gray-50">
                        {{ $faq[1] }}
                    </div>
                </details>
            @endforeach
        </div>
    </section>

    {{-- CTA Demo --}}
    <section class="bg-gray-900 dark:bg-gray-50 py-20">
        <div class="max-w-2xl mx-auto px-6 text-center">
            <h2 class="text-3xl font-bold text-white dark:text-gray-900 mb-4">Ingin Lihat Langsung?</h2>
            @if($registrationOpen)
                <p class="text-gray-400 mb-8 leading-relaxed">
                    Daftarkan pesantren Anda hari ini — akun aktif seketika dengan fitur penuh,
                    supaya wali santri Anda bisa mulai memantau perkembangan anak sejak hari pertama.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="{{ route('register') }}"
                       class="inline-block bg-teal-600 text-white font-semibold px-8 py-3.5 rounded-xl text-base hover:bg-teal-500 transition-colors">
                        Daftar Sekarang →
                    </a>
                </div>
            @elseif($demoOpen)
                <p class="text-gray-400 mb-8 leading-relaxed">
                    Pendaftaran mandiri sedang ditutup sementara. Tinggalkan data pesantren Anda —
                    tim kami akan menghubungi begitu kuota pendaftaran dibuka kembali.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="{{ route('demo') }}"
                       class="inline-block bg-teal-600 text-white font-semibold px-8 py-3.5 rounded-xl text-base hover:bg-teal-500 transition-colors">
                        Ajukan Demo →
                    </a>
                </div>
            @else
                <p class="text-gray-400 leading-relaxed">
                    Pendaftaran pesantren baru sedang ditutup sementara, dan untuk saat ini kami belum
                    membuka antrean demo. Pesantren yang sudah terdaftar tetap bisa masuk seperti biasa.
                </p>
            @endif
        </div>
    </section>

    {{-- Footer --}}
    @include('partials.situs-footer')

</body>
</html>
