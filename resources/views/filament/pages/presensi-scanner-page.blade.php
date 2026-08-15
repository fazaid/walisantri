<x-filament-panels::page>
@php
    $pengaturan = $this->pengaturan();
    $libur = $this->keteranganLibur();
@endphp

{{--
    @vite di sini, BUKAN di AdminPanelProvider: html5-qrcode ~300 KB dan hanya
    halaman ini yang membutuhkannya. Mendaftarkannya sebagai aset panel akan
    membebani setiap halaman admin demi satu layar yang dibuka sekali sehari.
--}}
@vite('resources/js/presensi-scanner.js')

@if($libur)
<div class="mb-4 rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 dark:border-amber-700/60 dark:bg-amber-950/40">
    <p class="text-sm font-medium text-amber-800 dark:text-amber-200">Hari ini libur: {{ $libur }}</p>
    <p class="text-sm text-amber-700 dark:text-amber-300">
        Pemindaian tetap tercatat bila memang ada kegiatan.
    </p>
</div>
@endif

<div x-data="presensiScanner()" x-on:livewire:navigating.window="bersihkan()">

    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
        <p class="mb-1 text-sm font-semibold text-gray-800 dark:text-gray-100">Pindai kartu santri</p>
        <p class="mb-4 text-xs text-gray-500 dark:text-gray-400">
            Arahkan alat pemindai ke QR di kartu, atau ketik kode/NIS lalu tekan Enter.
            Batas hadir: {{ \Illuminate\Support\Str::of($pengaturan->jam_masuk)->substr(0, 5) }}
            + {{ $pengaturan->toleransi_terlambat_menit }} menit.
        </p>

        {{--
            Jalur utama: alat pemindai USB/Bluetooth berperilaku sebagai papan
            ketik (mengetik kode lalu menekan Enter).

            ⚠️ SENGAJA TANPA wire:model. Kolom ini `autofocus` dan tetap fokus
            sepanjang sesi, sedangkan morph Livewire dengan sengaja TIDAK menimpa
            nilai input yang sedang fokus — supaya ketikan pengguna tidak terhapus
            di tengah jalan. Akibatnya `$this->kode = ''` di sisi server tidak
            pernah sampai ke DOM, kolomnya tidak pernah bersih, dan tiap pindaian
            berikutnya menempel di belakang yang lama:
            "WSP1.AAAWSP1.BBBWSP1.CCC" — lalu semuanya ditolak sebagai satu kode
            ngawur. Karena itu nilainya diambil dan dibersihkan di sisi klien,
            lalu dikirim sebagai argumen; jalur yang sama persis dengan kamera.
        --}}
        <input
            type="text"
            x-ref="kolomKode"
            x-on:keydown.enter.prevent="kirimManual()"
            autofocus
            autocomplete="off"
            placeholder="Menunggu pemindaian…"
            class="w-full rounded-lg border border-gray-300 bg-white px-4 py-3 font-mono text-lg tracking-widest text-gray-800 placeholder-gray-400 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
        >

        {{-- Lapis kedua: kamera, untuk pesantren tanpa alat pemindai. --}}
        <div class="mt-4 flex flex-wrap items-center gap-3">
            <button
                type="button"
                x-show="! tampil"
                x-on:click="nyalakan()"
                x-bind:disabled="memuat"
                class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 disabled:opacity-60"
            >
                <x-filament::icon icon="heroicon-o-camera" class="h-4 w-4" />
                <span x-text="memuat ? 'Menyiapkan kamera…' : 'Pindai dengan Kamera'"></span>
            </button>

            <button
                type="button"
                x-show="tampil"
                x-cloak
                x-on:click="matikan()"
                class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800"
            >
                <x-filament::icon icon="heroicon-o-stop-circle" class="h-4 w-4" />
                Matikan Kamera
            </button>

            <p x-show="aktif" x-cloak class="text-xs text-gray-500 dark:text-gray-400">
                Arahkan QR kartu ke kamera. Tiap kartu dicatat sekali — jauhkan kartunya, lalu dekatkan kartu berikutnya.
            </p>
        </div>

        <p x-show="galat" x-cloak x-text="galat"
           class="mt-3 rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-xs text-amber-800 dark:border-amber-700/60 dark:bg-amber-950/40 dark:text-amber-200"></p>

        {{--
            Wadah video. x-show terikat ke `tampil`, BUKAN `aktif` — dan itu
            memperbaiki bug nyata: html5-qrcode mengukur lebar elemen ini untuk
            menentukan ukuran video dan kotak pindai, jadi ia harus sudah terlihat
            SEBELUM start() dipanggil. Versi sebelumnya menampilkannya baru setelah
            kamera berhasil menyala, sehingga elemennya masih display:none saat
            diukur, clientWidth terbaca 0, dan videonya tidak pernah muncul.

            min-height menahan pergeseran layout saat video belum terpasang;
            [&_video] memastikan elemen video yang disisipkan pustaka mengisi
            wadahnya alih-alih memakai ukuran intrinsik kamera.
        --}}
        <div x-show="tampil" x-cloak class="mt-4">
            {{--
                wire:ignore WAJIB. Elemen <video> di dalam sini disisipkan
                html5-qrcode lewat JavaScript, jadi ia TIDAK ADA di HTML yang
                dirender server. Tiap pemindaian berhasil mengubah $riwayat →
                Livewire me-render ulang → morph membandingkan DOM dengan HTML
                server, menganggap video itu simpanan liar, dan menghapusnya.
                Gejalanya: wadahnya tetap terlihat (Alpine masih memegang
                `tampil`) tapi isinya hitam kosong, persis setelah santri
                PERTAMA berhasil dipindai.
            --}}
            <div
                wire:ignore
                id="pemindai-kamera"
                class="mx-auto w-full max-w-sm overflow-hidden rounded-xl border border-gray-200 bg-black dark:border-gray-700 [&_video]:h-auto [&_video]:w-full"
                style="min-height: 16rem;"
            ></div>

            <p x-show="memuat" x-cloak class="mt-2 text-center text-xs text-gray-500 dark:text-gray-400">
                Menyiapkan kamera…
            </p>
        </div>
    </div>

    <div class="mt-6">
        <p class="mb-2 text-sm font-semibold text-gray-800 dark:text-gray-100">Riwayat Pemindaian</p>

        @if(empty($riwayat))
            <div class="rounded-xl border border-gray-200 bg-white py-10 text-center text-sm text-gray-400 dark:border-gray-700 dark:bg-gray-900">
                Belum ada pemindaian pada sesi ini.
            </div>
        @else
            <div class="space-y-2">
                @foreach($riwayat as $item)
                    @php
                        $warna = match($item['nada']) {
                            'success' => 'border-green-200 bg-green-50 dark:border-green-800/60 dark:bg-green-950/30',
                            'warning' => 'border-amber-200 bg-amber-50 dark:border-amber-800/60 dark:bg-amber-950/30',
                            default => 'border-red-200 bg-red-50 dark:border-red-800/60 dark:bg-red-950/30',
                        };
                    @endphp
                    <div class="flex items-center justify-between rounded-xl border px-4 py-2.5 {{ $warna }}">
                        <div>
                            <p class="text-sm font-medium text-gray-800 dark:text-gray-100">{{ $item['nama'] }}</p>
                            <p class="text-xs text-gray-600 dark:text-gray-400">{{ $item['pesan'] }}</p>
                        </div>
                        <span class="font-mono text-xs text-gray-500 dark:text-gray-400">{{ $item['waktu'] }}</span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

</div>
</x-filament-panels::page>
