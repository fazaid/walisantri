<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Pesantren · Walisantri.com</title>
    <link rel="icon" type="image/svg+xml" href="{{ \App\Models\PlatformBrandingSetting::faviconUrl() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.tema')
    @include('partials.analytics-head')
</head>
<body class="bg-gray-50 min-h-screen py-12 px-4">
@include('partials.analytics-body')

    <div class="max-w-xl mx-auto">
        <div class="text-center mb-8">
            <a href="{{ route('landing') }}" class="text-teal-700 font-bold text-xl">🕌 Walisantri.com</a>
            <h1 class="text-2xl font-bold text-gray-900 mt-4">Daftarkan Pesantren Anda</h1>
            <p class="text-gray-500 text-sm mt-1">Akun aktif seketika dengan fitur penuh — mulai hubungkan pesantren Anda dengan wali santri hari ini</p>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">

            @unless($registrationOpen)
                <div class="text-center py-4">
                    <div class="text-4xl mb-3">🛠️</div>
                    <h2 class="font-bold text-gray-900 mb-2">Pendaftaran Mandiri Sedang Nonaktif Sementara</h2>
                    @if($demoOpen)
                        <p class="text-sm text-gray-500 mb-6">
                            Untuk saat ini, pendaftaran pesantren baru hanya bisa lewat tim kami.
                            Isi form demo singkat dan kami akan bantu proses pendaftarannya.
                        </p>
                        <a href="{{ route('demo') }}"
                           class="inline-block bg-teal-700 hover:bg-teal-800 text-white font-semibold px-6 py-2.5 rounded-xl transition-colors">
                            Isi Form Demo
                        </a>
                    @else
                        <p class="text-sm text-gray-500">
                            Pendaftaran pesantren baru sedang ditutup sementara dan antrean demo juga
                            belum dibuka. Pesantren yang sudah terdaftar tetap bisa masuk seperti biasa.
                        </p>
                    @endif
                </div>
            @else

            @php
                // Galat bisa mendarat di langkah mana pun, dan halaman harus terbuka di
                // langkah yang memuatnya — kalau tidak, pendaftar melihat pesan galat
                // untuk kolom yang sedang tersembunyi.
                //
                // Dihitung di Blade, bukan oleh JS setelah muat: tidak ada kedipan langkah
                // yang salah, dan hasilnya bisa diperiksa langsung dari markup oleh tes.
                // Default 1 sekaligus menangkap galat 'slug' dari penangkap QueryException.
                $kolomLangkah1 = ['nama_pesantren', 'slug', 'wilayah_provinsi', 'wilayah_kota',
                                  'wilayah_kecamatan', 'wilayah_desa', 'alamat_pesantren',
                                  'telepon_pesantren', 'email_pesantren'];
                $langkahAwal = ($errors->any() && ! $errors->hasAny($kolomLangkah1)) ? 2 : 1;

                $kelasKolom = 'w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-teal-500';
                $kelasLabel = 'block text-sm font-medium text-gray-700 mb-1';
            @endphp

            <noscript>
                <style>
                    /* Kaskade wilayah memang mustahil tanpa JS. Yang bisa dijaga: form-nya
                       tidak boleh tampil separuh dan gagal diam-diam. */
                    [data-langkah] { display: block !important; }
                    #tombol-lanjut, #tombol-kembali { display: none !important; }
                    #tombol-daftar { display: block !important; }
                </style>
                <div class="bg-amber-50 border border-amber-200 text-amber-800 text-sm rounded-xl px-4 py-3 mb-5">
                    JavaScript sedang nonaktif, sehingga pilihan Kota/Kabupaten, Kecamatan, dan
                    Desa/Kelurahan tidak bisa dimuat. Aktifkan JavaScript untuk menyelesaikan pendaftaran.
                </div>
            </noscript>

            @if($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl px-4 py-3 mb-5">
                    <ul class="space-y-1">
                        @foreach($errors->all() as $error)
                            <li>• {{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Satu <form> untuk dua langkah: payload POST dan validasi server tetap satu
                 permintaan, persis seperti sebelum wizard ada. --}}
            <form method="POST" action="{{ route('register.submit') }}" id="form-daftar"
                  data-langkah-awal="{{ $langkahAwal }}" class="space-y-5">
                @csrf

                {{-- Indikator langkah --}}
                <ol class="flex items-center gap-3 text-xs font-medium mb-1">
                    <li data-titik="1" class="{{ $langkahAwal === 1 ? 'text-teal-700' : 'text-gray-400' }}">1. Data Pesantren</li>
                    <li class="flex-1 h-px bg-gray-200"></li>
                    <li data-titik="2" class="{{ $langkahAwal === 2 ? 'text-teal-700' : 'text-gray-400' }}">2. Penanggung Jawab</li>
                </ol>

                {{-- ============================ LANGKAH 1 ============================ --}}
                <div id="langkah-1" data-langkah="1" class="space-y-5 {{ $langkahAwal === 1 ? '' : 'hidden' }}">

                    {{-- Nama Pesantren --}}
                    <div>
                        <label class="{{ $kelasLabel }}">Nama Pesantren</label>
                        <input type="text" name="nama_pesantren" id="nama-pesantren" value="{{ old('nama_pesantren') }}"
                               required autofocus
                               class="{{ $kelasKolom }}"
                               placeholder="Pesantren Al-Hidayah">
                    </div>

                    {{-- Slug --}}
                    <div>
                        <label class="{{ $kelasLabel }}">Alamat Subdomain</label>
                        <div class="flex rounded-xl border border-gray-300 overflow-hidden focus-within:ring-2 focus-within:ring-teal-500">
                            <input type="text" name="slug" id="slug" value="{{ old('slug') }}"
                                   required
                                   class="flex-1 px-4 py-2.5 text-sm outline-none bg-white"
                                   placeholder="al-hidayah"
                                   pattern="[a-z0-9][a-z0-9\-]{1,28}[a-z0-9]">
                            <span class="bg-gray-50 px-3 py-2.5 text-sm text-gray-500 border-l border-gray-300">
                                .walisantri.com
                            </span>
                        </div>
                        <p id="slug-status" class="text-xs mt-1 text-gray-400">3–30 karakter, huruf kecil, angka, tanda hubung</p>
                    </div>

                    {{-- Wilayah berjenjang. Provinsi dirender server-side; tiga sisanya diisi
                         JS setelah induknya dipilih. --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="{{ $kelasLabel }}">Provinsi</label>
                            <select name="wilayah_provinsi" id="wilayah-provinsi" required
                                    data-kosong="— Pilih Provinsi —" class="{{ $kelasKolom }}">
                                <option value="">— Pilih Provinsi —</option>
                                @foreach($provinsi as $w)
                                    <option value="{{ $w['kode'] }}" @selected(old('wilayah_provinsi') === $w['kode'])>{{ $w['nama'] }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="{{ $kelasLabel }}">Kota/Kabupaten</label>
                            <select name="wilayah_kota" id="wilayah-kota" required disabled
                                    data-kosong="— Pilih Kota/Kabupaten —" data-terpilih="{{ old('wilayah_kota') }}"
                                    class="{{ $kelasKolom }}">
                                <option value="">— Pilih Kota/Kabupaten —</option>
                            </select>
                        </div>

                        <div>
                            <label class="{{ $kelasLabel }}">Kecamatan</label>
                            <select name="wilayah_kecamatan" id="wilayah-kecamatan" required disabled
                                    data-kosong="— Pilih Kecamatan —" data-terpilih="{{ old('wilayah_kecamatan') }}"
                                    class="{{ $kelasKolom }}">
                                <option value="">— Pilih Kecamatan —</option>
                            </select>
                        </div>

                        <div>
                            <label class="{{ $kelasLabel }}">Desa/Kelurahan</label>
                            <select name="wilayah_desa" id="wilayah-desa" required disabled
                                    data-kosong="— Pilih Desa/Kelurahan —" data-terpilih="{{ old('wilayah_desa') }}"
                                    class="{{ $kelasKolom }}">
                                <option value="">— Pilih Desa/Kelurahan —</option>
                            </select>
                        </div>

                        {{-- Selebar penuh: alamat jalan tidak muat berdampingan, dan ia
                             melengkapi empat kolom wilayah di atasnya, bukan menggantikannya. --}}
                        <div class="sm:col-span-2">
                            <label class="{{ $kelasLabel }}">Alamat Pesantren</label>
                            <textarea name="alamat_pesantren" rows="2" required maxlength="500"
                                      class="{{ $kelasKolom }}"
                                      placeholder="Jl. Raya Contoh No. 12, RT 03/RW 05">{{ old('alamat_pesantren') }}</textarea>
                            <p class="text-xs mt-1 text-gray-400">Nama jalan, nomor, RT/RW, dan patokan. Provinsi sampai desa sudah terisi dari kolom di atas.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="{{ $kelasLabel }}">
                                Nomor Telepon Pesantren <span class="text-gray-400 font-normal">(opsional)</span>
                            </label>
                            <input type="tel" name="telepon_pesantren" value="{{ old('telepon_pesantren') }}"
                                   maxlength="20" pattern="[0-9+\-\s()]{8,20}"
                                   title="Gunakan angka saja, 8–20 karakter"
                                   class="{{ $kelasKolom }}"
                                   placeholder="0251 1234567">
                        </div>

                        <div>
                            <label class="{{ $kelasLabel }}">
                                Email Pesantren <span class="text-gray-400 font-normal">(opsional)</span>
                            </label>
                            <input type="email" name="email_pesantren" value="{{ old('email_pesantren') }}"
                                   maxlength="100"
                                   class="{{ $kelasKolom }}"
                                   placeholder="info@pesantren.com">
                        </div>
                    </div>
                </div>

                {{-- ============================ LANGKAH 2 ============================ --}}
                <div id="langkah-2" data-langkah="2" class="space-y-5 {{ $langkahAwal === 2 ? '' : 'hidden' }}">

                    {{-- Nama Admin --}}
                    <div>
                        <label class="{{ $kelasLabel }}">Nama Anda (Admin)</label>
                        <input type="text" name="admin_name" value="{{ old('admin_name') }}"
                               required
                               class="{{ $kelasKolom }}"
                               placeholder="Nama lengkap">
                    </div>

                    {{-- Email --}}
                    <div>
                        <label class="{{ $kelasLabel }}">Email</label>
                        <input type="email" name="email" value="{{ old('email') }}"
                               required
                               class="{{ $kelasKolom }}"
                               placeholder="admin@pesantren.com">
                    </div>

                    {{-- Nomor WhatsApp --}}
                    <div>
                        <label class="{{ $kelasLabel }}">Nomor WhatsApp</label>
                        <input type="tel" name="admin_whatsapp" value="{{ old('admin_whatsapp') }}"
                               required maxlength="20" pattern="[0-9+\-\s()]{8,20}"
                               title="Gunakan angka saja, 8–20 karakter"
                               class="{{ $kelasKolom }}"
                               placeholder="0812 3456 7890">
                        <p class="text-xs mt-1 text-gray-400">Dipakai untuk pemberitahuan penting dan pemulihan akun.</p>
                    </div>

                    {{-- Password --}}
                    <div>
                        <label class="{{ $kelasLabel }}">Password</label>
                        <input type="password" name="password" required minlength="8"
                               pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}"
                               title="Minimal 8 karakter, kombinasi huruf besar, huruf kecil, dan angka"
                               class="{{ $kelasKolom }}"
                               placeholder="Minimal 8 karakter">
                        <p class="text-xs mt-1 text-gray-400">Minimal 8 karakter, kombinasi huruf besar, huruf kecil, dan angka</p>
                    </div>

                    <div>
                        <label class="{{ $kelasLabel }}">Konfirmasi Password</label>
                        <input type="password" name="password_confirmation" required minlength="8"
                               class="{{ $kelasKolom }}"
                               placeholder="Ulangi password">
                    </div>
                </div>

                <div class="flex gap-3">
                    <button type="button" id="tombol-kembali"
                            class="px-5 py-2.5 rounded-xl font-semibold text-sm border border-gray-300 text-gray-700 hover:bg-gray-50 transition-colors {{ $langkahAwal === 2 ? '' : 'hidden' }}">
                        ← Kembali
                    </button>
                    <button type="button" id="tombol-lanjut"
                            class="flex-1 bg-teal-700 hover:bg-teal-800 text-white font-semibold py-2.5 rounded-xl transition-colors {{ $langkahAwal === 1 ? '' : 'hidden' }}">
                        Lanjut →
                    </button>
                    <button type="submit" id="tombol-daftar"
                            class="flex-1 bg-teal-700 hover:bg-teal-800 text-white font-semibold py-2.5 rounded-xl transition-colors {{ $langkahAwal === 2 ? '' : 'hidden' }}">
                        Daftarkan Pesantren
                    </button>
                </div>

                <p class="text-center text-xs text-gray-400">
                    Dengan mendaftar Anda menyetujui
                    <span class="underline cursor-pointer">syarat & ketentuan</span> Walisantri.com.
                </p>

            </form>
            @endif
        </div>

        <p class="text-center text-sm text-gray-500 mt-5">
            Sudah punya akun?
            <a href="{{ route('login') }}" class="text-teal-700 font-medium hover:underline">Masuk</a>
        </p>
    </div>

    <script>
    const namaInput = document.getElementById('nama-pesantren');
    const slugInput = document.getElementById('slug');
    const statusEl  = document.getElementById('slug-status');
    const PETUNJUK  = '3–30 karakter, huruf kecil, angka, tanda hubung';
    let timer;

    // Selama user belum menyunting subdomain sendiri, kolomnya mengikuti nama
    // pesantren. Kalau halaman dimuat ulang setelah gagal validasi, old('slug')
    // sudah terisi — itu ketikan user, jangan ditimpa.
    let slugDisuntingManual = (slugInput?.value ?? '').trim().length > 0;

    // Cerminan ValidTenantSlug: huruf kecil, angka, tanda hubung tunggal, tidak
    // diawali/diakhiri tanda hubung, maksimal 30 karakter. Awalan generik dibuang
    // supaya "Pondok Pesantren Al-Hidayah" jadi "al-hidayah", bukan
    // "pondok-pesantren-al-hidayah" — hampir semua pendaftar memakainya.
    function jadikanSlug(teks) {
        return teks
            .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
            .toLowerCase()
            .replace(/^(pondok\s+pesantren|pesantren|pondok|ponpes|pp)\s+/, '')
            .replace(/['’`]/g, '')
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/-{2,}/g, '-')
            .replace(/^-+|-+$/g, '')
            .slice(0, 30)
            .replace(/-+$/, '');
    }

    function setStatus(pesan, warna) {
        statusEl.textContent = pesan;
        statusEl.className = `text-xs mt-1 ${warna}`;
    }

    function periksaSlug() {
        clearTimeout(timer);
        const val = slugInput.value;
        if (val.length < 3) { setStatus(PETUNJUK, 'text-gray-400'); return; }
        setStatus('Memeriksa…', 'text-gray-400');
        timer = setTimeout(async () => {
            try {
                const r = await fetch(`/check-slug/${encodeURIComponent(val)}`);
                const d = await r.json();
                setStatus(d.message, d.available ? 'text-green-600' : 'text-red-500');
            } catch { setStatus('Gagal memeriksa slug.', 'text-red-500'); }
        }, 400);
    }

    namaInput?.addEventListener('input', () => {
        if (slugDisuntingManual) return;
        slugInput.value = jadikanSlug(namaInput.value);
        periksaSlug();
    });

    slugInput?.addEventListener('input', () => {
        // Dikosongkan lagi = minta saran otomatis kembali.
        slugDisuntingManual = slugInput.value.trim().length > 0;
        periksaSlug();
    });

    // ---------------------------------------------------------------------
    // Wizard dua langkah
    // ---------------------------------------------------------------------
    const formDaftar = document.getElementById('form-daftar');
    const panel      = { 1: document.getElementById('langkah-1'), 2: document.getElementById('langkah-2') };
    const btnLanjut  = document.getElementById('tombol-lanjut');
    const btnKembali = document.getElementById('tombol-kembali');
    const btnDaftar  = document.getElementById('tombol-daftar');

    function tampilkanLangkah(n) {
        panel[1].classList.toggle('hidden', n !== 1);
        panel[2].classList.toggle('hidden', n !== 2);
        btnLanjut.classList.toggle('hidden', n !== 1);
        btnDaftar.classList.toggle('hidden', n !== 2);
        btnKembali.classList.toggle('hidden', n !== 2);
        document.querySelectorAll('[data-titik]').forEach((t) => {
            const aktif = Number(t.dataset.titik) === n;
            t.classList.toggle('text-teal-700', aktif);
            t.classList.toggle('text-gray-400', !aktif);
        });
        panel[n].querySelector('input, select')?.focus();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    btnLanjut?.addEventListener('click', () => {
        // Gerbangnya memakai constraint HTML5 yang sudah menempel di tiap kolom
        // (required/pattern) — tidak ada aturan duplikat yang bisa menyimpang dari
        // aturan server.
        const cacat = [...panel[1].querySelectorAll('input, select, textarea')].find((el) => !el.checkValidity());
        if (cacat) { cacat.reportValidity(); return; }
        tampilkanLangkah(2);
    });

    btnKembali?.addEventListener('click', () => tampilkanLangkah(1));

    // Jaring pengaman: kolom `required` yang sedang display:none tidak bisa difokuskan
    // browser, sehingga submit diblokir TANPA pesan apa pun — hanya warning di konsol.
    // Kalau itu terjadi, lompat dulu ke langkah pemiliknya supaya pesannya terlihat.
    formDaftar?.addEventListener('invalid', (e) => {
        const pemilik = e.target.closest('[data-langkah]');
        if (pemilik?.classList.contains('hidden')) tampilkanLangkah(Number(pemilik.dataset.langkah));
    }, true);

    // ---------------------------------------------------------------------
    // Kaskade wilayah
    // ---------------------------------------------------------------------
    const rantai = ['wilayah-provinsi', 'wilayah-kota', 'wilayah-kecamatan', 'wilayah-desa']
        .map((id) => document.getElementById(id));

    function opsiKosong(sel) {
        sel.innerHTML = '';
        sel.appendChild(new Option(sel.dataset.kosong, ''));
    }

    async function muatAnak(target, kodeInduk, terpilih = '') {
        target.innerHTML = '';
        target.appendChild(new Option('Memuat…', ''));
        target.disabled = true;
        try {
            const r = await fetch(`/wilayah/${encodeURIComponent(kodeInduk)}`);
            const daftar = await r.json();
            opsiKosong(target);
            // new Option() menaruh nama sebagai teks, bukan HTML — nama wilayah tidak
            // pernah bisa menyuntikkan markup.
            daftar.forEach((w) => target.appendChild(new Option(w.nama, w.kode, false, w.kode === terpilih)));
            target.disabled = false;
        } catch {
            target.innerHTML = '';
            target.appendChild(new Option('Gagal memuat — pilih ulang induknya', ''));
        }
    }

    function kosongkanTurunan(mulai) {
        for (let i = mulai; i < rantai.length; i++) {
            opsiKosong(rantai[i]);
            rantai[i].disabled = true;
        }
    }

    rantai.forEach((sel, i) => {
        if (i === rantai.length - 1) return;
        sel.addEventListener('change', () => {
            kosongkanTurunan(i + 1);
            if (sel.value) muatAnak(rantai[i + 1], sel.value);
        });
    });

    // Pulihkan pilihan setelah validasi server gagal: provinsi sudah terpilih dari
    // server, tiga sisanya dirantai berurutan dari atribut data-terpilih.
    (async () => {
        for (let i = 1; i < rantai.length; i++) {
            const induk = rantai[i - 1].value;
            const terpilih = rantai[i].dataset.terpilih;
            if (!induk || !terpilih) break;
            await muatAnak(rantai[i], induk, terpilih);
        }
    })();
    </script>

</body>
</html>
