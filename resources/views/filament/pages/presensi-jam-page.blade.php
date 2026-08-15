<x-filament-panels::page>
    {{--
        Guard sebelum form dirender — pola yang sama dengan presensi harian.
        Bedanya di sini ada satu sebab tambahan: fiturnya memang bisa dimatikan,
        dan yang membaca pesan itu (admin) adalah orang yang bisa menyalakannya.
    --}}
    @php
        $peringatan = $this->peringatanKosong();
        $hariLibur = $peringatan ? null : $this->peringatanHariLibur();
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
        @if ($hariLibur)
            <div class="mb-4 flex items-start gap-3 rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 dark:border-amber-700/60 dark:bg-amber-950/40">
                <x-filament::icon icon="heroicon-o-calendar-days" class="mt-0.5 h-5 w-5 flex-shrink-0 text-amber-600 dark:text-amber-400" />
                <div>
                    <p class="text-sm font-medium text-amber-800 dark:text-amber-200">
                        Tanggal ini hari libur: {{ $hariLibur }}
                    </p>
                    <p class="text-sm text-amber-700 dark:text-amber-300">
                        Anda tetap bisa mengisi presensi bila memang ada kegiatan. Periksa kembali tanggalnya bila tidak.
                    </p>
                </div>
            </div>
        @endif

        {{ $this->content }}
    @endif
</x-filament-panels::page>
