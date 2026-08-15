<x-filament-panels::page>
@php
    $pengaturan = $this->pengaturan();
    $libur = $this->keteranganLibur();
@endphp

@if($libur)
<div class="mb-4 rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 dark:border-amber-700/60 dark:bg-amber-950/40">
    <p class="text-sm font-medium text-amber-800 dark:text-amber-200">Hari ini libur: {{ $libur }}</p>
    <p class="text-sm text-amber-700 dark:text-amber-300">
        Pemindaian tetap tercatat bila memang ada kegiatan.
    </p>
</div>
@endif

<div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
    <p class="mb-1 text-sm font-semibold text-gray-800 dark:text-gray-100">Pindai kartu santri</p>
    <p class="mb-4 text-xs text-gray-500 dark:text-gray-400">
        Arahkan alat pemindai ke QR di kartu, atau ketik kode/NIS lalu tekan Enter.
        Batas hadir: {{ \Illuminate\Support\Str::of($pengaturan->jam_masuk)->substr(0, 5) }}
        + {{ $pengaturan->toleransi_terlambat_menit }} menit.
    </p>

    {{-- Autofocus + wire:keydown.enter: alat pemindai USB/Bluetooth berperilaku
         sebagai papan ketik (mengetik kode lalu menekan Enter), jadi tidak ada
         dependensi JS sama sekali dan petugas tidak perlu menyentuh layar. --}}
    <input
        type="text"
        wire:model="kode"
        wire:keydown.enter="scan"
        autofocus
        autocomplete="off"
        placeholder="Menunggu pemindaian…"
        class="w-full rounded-lg border border-gray-300 bg-white px-4 py-3 font-mono text-lg tracking-widest text-gray-800 placeholder-gray-400 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
    >
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
</x-filament-panels::page>
