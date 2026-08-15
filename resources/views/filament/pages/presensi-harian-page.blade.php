<x-filament-panels::page>
    {{--
        Guard sebelum form dirender. Tanpa ini halaman tampak normal tapi tidak
        melakukan apa-apa — kelas bug yang sama pernah terjadi di modul Mutaba'ah
        (lihat migrasi tenant/2026_08_13_000003), dan diperbaiki di v4.27.
    --}}
    @php
        $peringatan = $this->peringatanKosong();
    @endphp

    @if ($peringatan)
        <div class="rounded-xl border border-amber-300 bg-amber-50 px-4 py-6 text-center dark:border-amber-700/60 dark:bg-amber-950/40">
            <p class="text-sm font-medium text-amber-800 dark:text-amber-200">
                {{ $peringatan['judul'] }}
            </p>
            <p class="mt-1 text-sm text-amber-700 dark:text-amber-300">
                {{ $peringatan['saran'] }}
            </p>
        </div>
    @else
        {{ $this->content }}
    @endif
</x-filament-panels::page>
