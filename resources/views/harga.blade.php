<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Paket harga Walisantri.com — berlangganan sesuai jumlah santri, semua modul terbuka di setiap paket. Pilih siklus bulanan atau tahunan, lengkap dengan add-on kuota untuk pesantren besar.">
    <title>Paket Harga — Walisantri.com</title>
    <link rel="icon" type="image/svg+xml" href="{{ \App\Models\PlatformBrandingSetting::faviconUrl() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.tema')
    @include('partials.analytics-head')
    <style>
        /* Pemilih siklus harga: radio tersembunyi + selector sibling, supaya
           halaman ini tetap bebas JavaScript seperti akordeon FAQ di bawahnya.
           Gaya <details> sendiri tinggal di resources/css/app.css — dipakai
           halaman ini DAN landing. */
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
        <div class="max-w-3xl mx-auto px-6 py-16 text-center">
            <h1 class="text-4xl font-bold text-gray-900 mb-4">Harga Transparan, Sesuai Jumlah Santri</h1>
            <p class="text-gray-500 leading-relaxed">
                Semua modul terbuka di semua paket; yang membedakan hanya kuota santri.
                Pendaftaran tidak meminta kartu kredit.
            </p>
        </div>
    </section>

    {{-- Kartu paket --}}
    <section class="max-w-6xl mx-auto px-6 pb-20 pt-4">
        {{-- Pemilih siklus. Radio-nya sengaja jadi saudara langsung isi <section>
             agar CSS di <head> bisa menyembunyikan harga yang tidak dipilih tanpa
             JS. Jangan membungkus salah satu sisi dengan elemen baru. --}}
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

    {{-- Perbandingan paket --}}
    <section id="perbandingan" class="bg-gray-50 py-20">
        <div class="max-w-6xl mx-auto px-6">
            <div class="text-center mb-10">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">Yang Didapat Setiap Paket</h2>
                <p class="text-gray-500 max-w-2xl mx-auto leading-relaxed">
                    Tidak ada modul yang dikunci di paket manapun — pesantren kecil memakai
                    aplikasi yang sama utuhnya dengan pesantren besar. Satu-satunya pembeda
                    adalah kuota santri di baris pertama.
                </p>
            </div>

            {{-- Tabelnya lebih lebar dari layar HP; ia menggulir di dalam wadahnya
                 sendiri supaya badan halaman tidak jadi bisa digeser ke samping. --}}
            <div class="overflow-x-auto rounded-2xl border border-gray-200 bg-white">
                <table class="w-full min-w-[40rem] text-sm">
                    <caption class="sr-only">Perbandingan kuota dan modul antar paket langganan</caption>
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th scope="col" class="text-left font-semibold text-gray-900 px-5 py-4">Fitur</th>
                            @foreach($paketList as $paket)
                                <th scope="col" class="px-4 py-4 text-center font-semibold whitespace-nowrap
                                    {{ $paket['populer'] ? 'text-teal-700' : 'text-gray-900' }}">
                                    {{ $paket['nama'] }}
                                    @if($paket['populer'])
                                        <span class="block text-[11px] font-medium text-teal-600">Paling Populer</span>
                                    @endif
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="border-b border-gray-100 bg-gray-50">
                            <th scope="row" class="text-left font-semibold text-gray-900 px-5 py-3.5">Kuota santri</th>
                            @foreach($paketList as $paket)
                                <td class="px-4 py-3.5 text-center font-semibold text-gray-900 whitespace-nowrap">
                                    Sampai {{ number_format($paket['kuota'], 0, ',', '.') }}
                                    @if($paket['hubungiKami'])
                                        <span class="block text-[11px] font-normal text-gray-500">+ add-on per 100 santri</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                        <tr class="border-b border-gray-100">
                            <th scope="row" class="text-left font-semibold text-gray-900 px-5 py-3.5">Harga per bulan</th>
                            @foreach($paketList as $paket)
                                <td class="px-4 py-3.5 text-center text-gray-700 whitespace-nowrap">{{ $paket['harga'] }}</td>
                            @endforeach
                        </tr>
                        @foreach([
                            'Portal wali & Magic Link',
                            'Website profil pesantren',
                            'Presensi harian, per jam & kartu QR',
                            'Akademik, Tahfidz & Rapor',
                            "Mutaba'ah harian & Karakter",
                            'Kesehatan & rekam medis',
                            'SPP, Uang Saku & tarif',
                            'Inventaris & Prestasi',
                            'Ekstrakurikuler',
                            'Pengumuman ke wali',
                            'Ekspor PDF & Excel',
                            'Audit log',
                        ] as $fitur)
                            <tr class="border-b border-gray-100 last:border-0">
                                <th scope="row" class="text-left font-normal text-gray-600 px-5 py-3">{{ $fitur }}</th>
                                @foreach($paketList as $paket)
                                    <td class="px-4 py-3 text-center text-teal-500">
                                        <span aria-hidden="true">✓</span>
                                        <span class="sr-only">Termasuk di paket {{ $paket['nama'] }}</span>
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    {{-- Add-on kuota paket Maju --}}
    <section id="add-on" class="max-w-4xl mx-auto px-6 py-20">
        <div class="text-center mb-10">
            <h2 class="text-3xl font-bold text-gray-900 mb-4">Lebih dari {{ number_format($addOn['kuotaBase'], 0, ',', '.') }} Santri?</h2>
            <p class="text-gray-500 leading-relaxed">
                Paket Maju tidak berhenti di kuota bawaannya. Kuota bisa ditambah bertahap,
                jadi pesantren dengan ribuan santri tidak perlu paket khusus yang dinegosiasikan dari nol.
            </p>
        </div>

        <div class="border border-gray-200 rounded-2xl p-6 sm:p-8 grid grid-cols-1 sm:grid-cols-3 gap-6 items-center">
            <div class="sm:col-span-1 text-center sm:text-left">
                <div class="text-3xl font-bold text-gray-900">+{{ $addOn['hargaPer100'] }}</div>
                <div class="text-sm text-gray-500 mt-1">per tambahan 100 santri</div>
            </div>
            <div class="sm:col-span-2 text-sm text-gray-600 leading-relaxed">
                <p>
                    Di atas {{ number_format($addOn['kuotaBase'], 0, ',', '.') }} santri, setiap blok 100 santri
                    menambah <span class="font-semibold text-gray-900">{{ $addOn['hargaPer100'] }}</span> pada tagihan bulanan.
                    Blok dihitung ke atas, jadi 1 santri lebih pun sudah membuka satu blok penuh.
                </p>
                <p class="mt-3 rounded-xl bg-teal-50 px-4 py-3 text-teal-800">
                    <span class="font-semibold">Contoh:</span>
                    {{ number_format($addOn['contohSantri'], 0, ',', '.') }} santri → kuota
                    {{ number_format($addOn['contohKuota'], 0, ',', '.') }} santri →
                    <span class="font-semibold">{{ $addOn['contohHarga'] }}</span>/bulan.
                </p>
            </div>
        </div>

        @if($demoOpen)
            <p class="text-center text-sm text-gray-500 mt-6">
                Butuh angka pastinya untuk jumlah santri Anda?
                <a href="{{ route('demo') }}" class="text-teal-700 font-medium hover:underline">Hubungi kami</a>.
            </p>
        @endif
    </section>

    {{-- FAQ khusus biaya --}}
    <section id="faq-harga" class="bg-gray-50 py-20">
        <div class="max-w-3xl mx-auto px-6">
            <div class="text-center mb-10">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">Pertanyaan Seputar Biaya</h2>
                <p class="text-gray-500">
                    Pertanyaan umum lainnya ada di <a href="{{ $anchorBase }}#faq" class="text-teal-700 font-medium hover:underline">FAQ di beranda</a>.
                </p>
            </div>

            @php
                // Bonus bisa dinolkan dari BillingSettingsPage. Kalimatnya karena itu
                // dirakit di sini, bukan diinterpolasi begitu saja: "0 bulan gratis"
                // adalah klaim yang salah, bukan sekadar angka yang kebetulan nol.
                $kalimatDurasi = 'Ada empat: 1 bulan, 3 bulan, 6 bulan, dan 12 bulan.';

                if ($bonusEnam > 0) {
                    $kalimatDurasi .= ' Durasi 6 bulan mendapat '.$bonusEnam.' bulan gratis.';
                }

                if ($bonusTahunan > 0) {
                    $kalimatDurasi .= ' Durasi 12 bulan mendapat '.$bonusTahunan.' bulan gratis — Anda membayar '
                        .$bulanBayarTahunan.' bulan dan langganan aktif '.$totalBulanTahunan.' bulan.';
                }

                $kalimatDurasi .= ($bonusEnam > 0 || $bonusTahunan > 0)
                    ? ' Bonusnya diberikan sebagai bulan tambahan, bukan potongan harga, sehingga harga per bulannya tetap sama.'
                    : ' Harga per bulannya sama untuk semua durasi; yang berbeda hanya berapa lama sekali Anda membayar.';
            @endphp

            <div class="space-y-3">
                @foreach([
                    [
                        'Bagaimana cara membayarnya?',
                        'Lewat transfer bank. Setelah memilih paket dan durasi di halaman Langganan, sistem menerbitkan tagihan berisi nomor rekening tujuan; Anda tinggal transfer dan mengunggah bukti transfernya. Tim kami memverifikasi lalu masa aktif langganan langsung diperpanjang. Tidak ada penagihan otomatis dan tidak ada kartu kredit yang perlu didaftarkan.',
                    ],
                    [
                        'Durasi apa saja yang bisa dipilih?',
                        $kalimatDurasi,
                    ],
                    [
                        'Apakah harga bisa berubah setelah saya berlangganan?',
                        'Harga dapat berubah sewaktu-waktu, dan yang tercantum di halaman ini adalah harga yang berlaku hari ini. Perubahan harga tidak menyentuh masa aktif yang sudah Anda bayar — tagihan yang sudah terbit tetap memakai angka saat itu.',
                    ],
                    [
                        'Apa yang terjadi kalau masa langganan berakhir?',
                        'Ada masa tenggang 7 hari: pengurus diarahkan ke halaman Langganan saat masuk, sementara wali santri tetap bisa membuka portal untuk melihat data. Lewat masa itu akun ditangguhkan sampai langganan diperpanjang. Data pesantren Anda tidak dihapus.',
                    ],
                    [
                        'Bisakah naik paket di tengah masa aktif?',
                        'Bisa, dan sisa masa aktif paket lama tidak hangus — ia ditambahkan ke masa aktif paket baru. Yang perlu diperhatikan hanya minimum durasinya: bila sisa masa aktif lebih dari 6 bulan, durasi yang bisa dipilih minimal 6 bulan; bila lebih dari 9 bulan, hanya durasi 12 bulan.',
                    ],
                    [
                        'Kuota santri saya lewat sedikit dari batas paket. Bagaimana?',
                        'Sistem menahan penambahan santri begitu kuota paket penuh, jadi tidak ada tagihan kejutan di akhir bulan. Pilihannya naik ke paket berikutnya, atau — untuk paket Maju — menambah kuota per 100 santri seperti dijelaskan di bagian add-on di atas.',
                    ],
                    [
                        'Apakah ada biaya pemasangan atau biaya tambahan lain?',
                        'Tidak ada. Harga paket sudah mencakup seluruh modul, website profil pesantren di subdomain sendiri, portal wali, dan ekspor laporan. Tidak ada biaya per pengguna untuk pengurus maupun wali santri.',
                    ],
                ] as $faq)
                    <details class="border border-gray-200 rounded-xl overflow-hidden group bg-white">
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
        </div>
    </section>

    {{-- CTA penutup --}}
    <section class="bg-gray-900 dark:bg-gray-50 py-20">
        <div class="max-w-2xl mx-auto px-6 text-center">
            <h2 class="text-3xl font-bold text-white dark:text-gray-900 mb-4">Siap Memulai?</h2>
            @if($registrationOpen)
                <p class="text-gray-400 mb-8 leading-relaxed">
                    Daftarkan pesantren Anda hari ini — akun aktif seketika dengan fitur penuh,
                    supaya wali santri Anda bisa mulai memantau perkembangan anak sejak hari pertama.
                </p>
                <a href="{{ route('register') }}"
                   class="inline-block bg-teal-600 text-white font-semibold px-8 py-3.5 rounded-xl text-base hover:bg-teal-500 transition-colors">
                    Daftar Sekarang →
                </a>
            @elseif($demoOpen)
                <p class="text-gray-400 mb-8 leading-relaxed">
                    Pendaftaran mandiri sedang ditutup sementara. Tinggalkan data pesantren Anda —
                    tim kami akan menghubungi begitu kuota pendaftaran dibuka kembali.
                </p>
                <a href="{{ route('demo') }}"
                   class="inline-block bg-teal-600 text-white font-semibold px-8 py-3.5 rounded-xl text-base hover:bg-teal-500 transition-colors">
                    Ajukan Demo →
                </a>
            @else
                <p class="text-gray-400 leading-relaxed">
                    Pendaftaran pesantren baru sedang ditutup sementara, dan untuk saat ini kami belum
                    membuka antrean demo. Pesantren yang sudah terdaftar tetap bisa masuk seperti biasa.
                </p>
            @endif
        </div>
    </section>

    @include('partials.situs-footer')

</body>
</html>
