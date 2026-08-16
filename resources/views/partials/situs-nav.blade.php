{{--
    Nav situs publik — dipakai landing dan /panduan.

    $anchorBase kosong di landing supaya tautan seksi tetap menggulir di halaman
    yang sama; halaman lain mengisinya dengan URL landing, kalau tidak tautan
    "#harga" menggantung di halaman itu sendiri dan tidak ke mana-mana.

    $registrationOpen & $demoOpen wajib dikirim pemanggil (lihat LandingController
    dan PanduanController) — gerbangnya sama persis di kedua halaman, jadi menutup
    pendaftaran tidak menyisakan pintu yang masih terbuka di /panduan.

    JANGAN membungkus @include partial ini dengan <div> apa pun: nav-nya sticky, dan
    elemen sticky terkurung di blok induknya. Pembungkus setinggi nav = nav tidak
    punya ruang untuk menempel dan langsung tergulir hilang (bug /panduan v4.46).
--}}
@php
    $anchorBase = $anchorBase ?? '';

    // Satu daftar untuk dua tempat (bar desktop & panel HP) supaya keduanya tidak
    // bisa berbeda isi — termasuk saat gerbang demo ditutup.
    $tautanMenu = [
        ['label' => 'Fitur', 'url' => $anchorBase.'#fitur'],
        ['label' => 'Harga', 'url' => $anchorBase.'#harga'],
        ['label' => 'Cara Kerja', 'url' => $anchorBase.'#cara-kerja'],
        ['label' => 'FAQ', 'url' => $anchorBase.'#faq'],
    ];

    if ($demoOpen) {
        $tautanMenu[] = ['label' => 'Demo', 'url' => route('demo')];
    }

    $tautanMenu[] = ['label' => 'Panduan', 'url' => '/panduan'];
@endphp

<nav class="group bg-white border-b border-gray-100 sticky top-0 z-50">
    {{-- Kendali menu HP. Wajib anak PERTAMA <nav>: varian peer-* hanya menjangkau
         saudara SETELAHNYA, jadi panel di bawah harus berada sesudah input ini. --}}
    <input type="checkbox" id="menu-situs" class="sr-only peer">

    <div class="max-w-6xl mx-auto px-4 sm:px-6 py-3 sm:py-4 flex items-center justify-between gap-2">
        {{-- Isi bar butuh 340px. Di bawah 360px tulisan mereknya disembunyikan
             (logonya sendiri sudah membawa alt="Walisantri.com") supaya tombol mode
             gelap tetap bisa tinggal di bar tanpa membuat halaman meluap ke samping.
             min-w-0 + truncate jadi jaring terakhir: kalau toh masih sempit, teksnya
             terpotong rapi, bukan halamannya yang jadi bisa digeser ke kanan. --}}
        <a href="{{ $anchorBase ?: '/' }}" class="flex items-center gap-2 text-teal-700 font-bold text-base sm:text-xl tracking-tight min-w-0">
            <img src="{{ \App\Models\PlatformBrandingSetting::logoUrl() }}" alt="Walisantri.com" class="h-8 sm:h-9 w-auto shrink-0">
            <span class="hidden min-[360px]:inline truncate">Walisantri.com</span>
        </a>

        <div class="hidden md:flex items-center gap-6">
            @foreach($tautanMenu as $tautan)
                <a href="{{ $tautan['url'] }}" class="text-sm text-gray-500 hover:text-teal-700 font-medium">{{ $tautan['label'] }}</a>
            @endforeach
        </div>

        {{-- Di HP bar hanya memuat logo, tombol mode, Daftar, dan tombol menu —
             "Masuk" pindah ke dalam panel karena keempatnya sudah mepet di layar
             sempit. Lihat juga tulisan merek yang disembunyikan di bawah 380px. --}}
        <div class="flex items-center gap-1 sm:gap-2 shrink-0">
            @include('partials.tema-tombol')

            <a href="{{ route('login') }}"
               class="hidden md:inline-block text-sm text-gray-600 hover:text-teal-700 font-medium px-3 py-1.5 sm:px-4 sm:py-2 rounded-lg hover:bg-gray-50 transition-colors whitespace-nowrap">
                Masuk
            </a>

            @if($registrationOpen)
                <a href="{{ route('register') }}" id="nav-daftar"
                   class="text-sm bg-teal-700 text-white font-medium px-3 py-1.5 sm:px-4 sm:py-2 rounded-lg hover:bg-teal-800 transition-colors whitespace-nowrap">
                    Daftar
                </a>
            @endif

            {{-- Label, bukan tombol, karena yang mengendalikan panel adalah checkbox
                 di atas (tanpa JavaScript). Ia bersarang di dalam bar sehingga tidak
                 bisa memakai peer-*; keadaan checkbox dibaca lewat group-has-* di <nav>.
                 Satu-satunya <input> di nav ini memang checkbox itu. --}}
            <label for="menu-situs" aria-label="Buka menu"
                   class="md:hidden text-gray-600 hover:text-teal-700 p-2 rounded-lg hover:bg-gray-50 transition-colors cursor-pointer
                          group-has-[input:focus-visible]:ring-2 group-has-[input:focus-visible]:ring-teal-600">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"
                     class="w-6 h-6 group-has-[input:checked]:hidden" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
                </svg>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"
                     class="w-6 h-6 hidden group-has-[input:checked]:block" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                </svg>
            </label>
        </div>
    </div>

    {{-- Panel menu HP. Sengaja absolute, bukan dalam alur: kalau ia menambah tinggi
         nav saat dibuka, --nav-h di /panduan (offset sidebar & anchor) jadi meleset.
         max-md: dipakai supaya di lebar md ke atas ia tetap tersembunyi walau
         checkbox-nya terlanjur tercentang saat layar diperkecil lalu diperbesar. --}}
    <div id="menu-situs-panel"
         class="hidden max-md:peer-checked:block absolute inset-x-0 top-full bg-white border-b border-gray-100 shadow-lg">
        <div class="px-4 py-2">
            @foreach($tautanMenu as $tautan)
                <a href="{{ $tautan['url'] }}" class="block py-3 text-sm text-gray-600 hover:text-teal-700 font-medium border-b border-gray-100">
                    {{ $tautan['label'] }}
                </a>
            @endforeach

            {{-- Tombol mode gelap TIDAK diulang di sini: ia sudah tetap tampil di bar,
                 termasuk di layar HP. --}}
            <a href="{{ route('login') }}" class="block py-3 text-sm text-gray-600 hover:text-teal-700 font-medium">Masuk</a>
        </div>
    </div>

    {{-- Panel menutup sendiri setelah tautan diketuk — kalau tidak, ia tetap menutupi
         seksi yang baru dituju. Pola yang sama dengan penutup drawer daftar isi di
         panduan.blade.php. --}}
    <script>
        document.querySelectorAll('#menu-situs-panel a').forEach(function (tautan) {
            tautan.addEventListener('click', function () {
                document.getElementById('menu-situs').checked = false;
            });
        });
    </script>
</nav>
