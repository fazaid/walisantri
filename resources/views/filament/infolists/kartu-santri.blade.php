@php
    use App\Support\GambarQr;
    use App\Support\KodePresensi;

    $kode = $record->kode_presensi;
@endphp

<div class="flex flex-col items-start gap-4 px-1 sm:flex-row sm:items-center">
    @if($kode)
        <div class="shrink-0 rounded-lg border border-gray-200 bg-white p-2 dark:border-gray-700 dark:bg-gray-900">
            {{-- Skala dikecilkan dari bawaan cetak: ini pratinjau layar, dan PNG
                 skala 6 membengkakkan HTML halaman detail tanpa terlihat lebih baik. --}}
            <img
                src="{{ GambarQr::dataUri(KodePresensi::payload($kode), 4) }}"
                alt="QR kartu presensi {{ $record->nama_lengkap }}"
                class="h-32 w-32"
            >
        </div>

        <div class="min-w-0 text-sm">
            <p class="text-gray-500 dark:text-gray-400">Kode kartu</p>
            <p class="font-mono text-base font-medium tracking-wider text-gray-900 dark:text-gray-100">{{ $kode }}</p>
            <p class="mt-2 max-w-prose text-xs text-gray-500 dark:text-gray-400">
                QR ini yang dipindai di menu <strong>Presensi &rarr; Scan QR</strong>. Kodenya juga
                tercetak sebagai teks di kartu, supaya petugas bisa mengetiknya bila QR-nya rusak.
                Kalau kartu hilang atau bocor, ganti kodenya lewat <strong>Ganti Kode Kartu Presensi</strong> —
                kartu lama langsung tidak berlaku.
            </p>
        </div>
    @else
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Santri ini belum punya kode kartu. Buat dulu lewat aksi
            <strong>Ganti Kode Kartu Presensi</strong> di header halaman ini.
        </p>
    @endif
</div>
