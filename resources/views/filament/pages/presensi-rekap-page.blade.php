<x-filament-panels::page>
@php
    $ringkasan = $this->getRingkasan();
    $baris = $this->getBaris();
    $perhatian = $this->getPerluPerhatian();
@endphp

{{-- Filter --}}
<div class="grid grid-cols-1 gap-3 sm:grid-cols-4">
    <div>
        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Tahun Ajaran</label>
        <select wire:model.live="tahun_ajaran" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
            @foreach($this->tahunAjaranOptions() as $nilai => $label)
                <option value="{{ $nilai }}">{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Periode</label>
        <select wire:model.live="periode" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
            @foreach($this->periodeOptions() as $nilai => $label)
                <option value="{{ $nilai }}">{{ $label }}</option>
            @endforeach
        </select>
    </div>

    @if($periode === 'Bulanan')
    <div>
        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Bulan</label>
        <select wire:model.live="bulan" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
            <option value="">— pilih bulan —</option>
            @foreach($this->bulanOptions() as $nilai => $label)
                <option value="{{ $nilai }}">{{ $label }}</option>
            @endforeach
        </select>
    </div>
    @endif

    <div>
        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Kelas</label>
        <select wire:model.live="kelas_id" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
            <option value="">Semua kelas</option>
            @foreach($this->kelasOptions() as $nilai => $label)
                <option value="{{ $nilai }}">{{ $label }}</option>
            @endforeach
        </select>
    </div>
</div>

{{-- Ringkasan --}}
<div class="mt-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
        <p class="mb-1 text-xs font-medium text-gray-500 dark:text-gray-400">Santri</p>
        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $ringkasan->jumlah_santri }}</p>
    </div>
    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
        <p class="mb-1 text-xs font-medium text-gray-500 dark:text-gray-400">Hari Efektif</p>
        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $ringkasan->hari_efektif }}</p>
    </div>
    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
        <p class="mb-1 text-xs font-medium text-gray-500 dark:text-gray-400">Rata-rata Kehadiran</p>
        <p class="text-2xl font-bold {{ $ringkasan->persen_kehadiran >= 80 ? 'text-green-600 dark:text-green-400' : ($ringkasan->persen_kehadiran >= 60 ? 'text-amber-600 dark:text-amber-400' : 'text-red-600 dark:text-red-400') }}">
            {{ $ringkasan->persen_kehadiran }}%
        </p>
    </div>
    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
        <p class="mb-1 text-xs font-medium text-gray-500 dark:text-gray-400">Tanpa Keterangan</p>
        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $ringkasan->tanpa_keterangan }}</p>
        <p class="mt-1 text-xs text-gray-400">hari belum diisi</p>
    </div>
</div>

{{-- Perlu Perhatian --}}
@if($perhatian->isNotEmpty())
<div class="mt-6 rounded-xl border border-amber-300 bg-amber-50 p-4 dark:border-amber-700/60 dark:bg-amber-950/40">
    <p class="mb-2 text-sm font-semibold text-amber-800 dark:text-amber-200">
        Perlu Perhatian — {{ $perhatian->count() }} santri alpa berturut-turut
    </p>
    <div class="space-y-1">
        @foreach($perhatian as $p)
            <div class="flex items-center justify-between text-sm">
                <span class="text-amber-900 dark:text-amber-100">
                    {{ $p->nama_lengkap }}
                    <span class="text-amber-600 dark:text-amber-400">· {{ $p->nama_kelas ?? '—' }}</span>
                </span>
                <span class="font-medium text-amber-800 dark:text-amber-200">
                    {{ $p->beruntun }}× berturut-turut ({{ $p->total_alpa }} total)
                </span>
            </div>
        @endforeach
    </div>
    <p class="mt-2 text-xs text-amber-700 dark:text-amber-300">
        Dihitung atas hari efektif, jadi hari libur tidak memutus rangkaian.
    </p>
</div>
@endif

{{-- Tabel rekap --}}
<div class="mt-6 overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
    @if($baris->isEmpty())
        <div class="bg-white py-12 text-center text-sm text-gray-400 dark:bg-gray-900">
            Belum ada santri aktif pada cakupan ini.
        </div>
    @else
        <table class="w-full min-w-[52rem] text-sm">
            <thead class="bg-gray-50 text-xs uppercase text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                <tr>
                    <th class="px-3 py-2 text-left">Santri</th>
                    <th class="px-3 py-2 text-left">Kelas</th>
                    @foreach($this->statusList() as $status)
                        <th class="px-2 py-2 text-center">{{ $status->value }}</th>
                    @endforeach
                    <th class="px-2 py-2 text-center">Tanpa Ket.</th>
                    <th class="px-2 py-2 text-center">Hari Efektif</th>
                    <th class="px-3 py-2 text-center">% Hadir</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white dark:divide-gray-800 dark:bg-gray-900">
                @foreach($baris as $b)
                    <tr>
                        <td class="px-3 py-2 font-medium text-gray-800 dark:text-gray-100">{{ $b->nama_lengkap }}</td>
                        <td class="px-3 py-2 text-gray-500 dark:text-gray-400">{{ $b->nama_kelas ?? '—' }}</td>
                        @foreach($this->statusList() as $status)
                            <td class="px-2 py-2 text-center text-gray-700 dark:text-gray-300">
                                {{ $b->{$this->kolomStatus($status)} }}
                            </td>
                        @endforeach
                        <td class="px-2 py-2 text-center {{ $b->tanpa_keterangan > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-gray-400' }}">
                            {{ $b->tanpa_keterangan }}
                        </td>
                        <td class="px-2 py-2 text-center text-gray-500 dark:text-gray-400">{{ $b->hari_efektif }}</td>
                        <td class="px-3 py-2 text-center font-semibold {{ $b->persen_kehadiran >= 80 ? 'text-green-600 dark:text-green-400' : ($b->persen_kehadiran >= 60 ? 'text-amber-600 dark:text-amber-400' : 'text-red-600 dark:text-red-400') }}">
                            {{ $b->persen_kehadiran }}%
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>

<p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
    <strong>Tanpa Keterangan</strong> berarti tidak ada yang mencatat apa pun pada hari efektif itu — berbeda dari
    <strong>Alpa</strong>, yang berarti seseorang menyatakan santri tidak hadir. Angka besar di kolom ini biasanya
    menandakan presensinya belum diisi, bukan santrinya bolos.
</p>
</x-filament-panels::page>
