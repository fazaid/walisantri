<x-filament-panels::page>
    <div class="space-y-6">

        {{-- Filter --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl p-5">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-4">Pilih Santri & Periode</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Santri</label>
                    <select wire:model.live="santriId"
                            class="fi-input block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm">
                        <option value="">— pilih santri —</option>
                        @foreach($this->getSantriOptions() as $id => $nama)
                            <option value="{{ $id }}">{{ $nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Tahun</label>
                    <select wire:model.live="tahun"
                            class="fi-input block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm">
                        @foreach($this->getTahunOptions() as $value => $label)
                            <option value="{{ $value }}" @selected($value === $tahun)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Bulan</label>
                    <select wire:model.live="bulan"
                            class="fi-input block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm">
                        @foreach($this->getBulanOptions() as $value => $label)
                            <option value="{{ $value }}" @selected((int)$value === $bulan)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        @php
            $santri   = $this->getSantri();
            $ringkasan = $santriId ? $this->getRingkasan() : [];
            $bulanNama = $this->getBulanOptions()[$bulan] ?? $bulan;
        @endphp

        @if(! $santri)
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl p-8 text-center text-sm text-gray-400">
                Pilih santri untuk melihat rapor mutabaah.
            </div>
        @elseif(empty($ringkasan))
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl p-8 text-center text-sm text-gray-400">
                Tidak ada catatan mutabaah untuk <strong>{{ $santri->nama_lengkap }}</strong>
                pada <strong>{{ $bulanNama }} {{ $tahun }}</strong>.
            </div>
        @else
            {{-- Info Santri --}}
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl p-5 flex items-center justify-between">
                <div>
                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $santri->nama_lengkap }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        {{ $santri->kelas?->nama_kelas ?? '—' }} · {{ $bulanNama }} {{ $tahun }}
                    </p>
                </div>
                <div class="text-right">
                    <p class="text-xs text-gray-500 dark:text-gray-400">Rata-rata Kepatuhan</p>
                    <p class="text-lg font-bold {{ $ringkasan['rata_rata'] >= 80 ? 'text-green-600 dark:text-green-400' : ($ringkasan['rata_rata'] >= 60 ? 'text-yellow-600 dark:text-yellow-400' : 'text-red-600 dark:text-red-400') }}">
                        {{ $ringkasan['rata_rata'] }}%
                    </p>
                </div>
            </div>

            {{-- Statistik Hari --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl p-4 text-center">
                    <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $ringkasan['total_hari'] }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Hari Tercatat</p>
                </div>
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl p-4 text-center">
                    <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $ringkasan['total_hari'] - $ringkasan['total_udzur'] }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Hari Aktif</p>
                </div>
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl p-4 text-center">
                    <p class="text-2xl font-bold text-orange-500">{{ $ringkasan['total_udzur'] }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Hari Udzur</p>
                </div>
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl p-4 text-center">
                    <p class="text-2xl font-bold {{ $ringkasan['rata_rata'] >= 80 ? 'text-green-600' : ($ringkasan['rata_rata'] >= 60 ? 'text-yellow-600' : 'text-red-600') }}">
                        {{ $ringkasan['rata_rata'] }}%
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Rata-rata</p>
                </div>
            </div>

            {{-- Ringkasan Amalan --}}
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                <div class="px-5 py-3 border-b border-gray-100 dark:border-gray-700">
                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">📋 Ringkasan Amalan Harian</p>
                </div>
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800 text-xs text-gray-500 dark:text-gray-400 uppercase">
                        <tr>
                            <th class="text-left px-5 py-2">Amalan</th>
                            <th class="text-center px-5 py-2">Terpenuhi</th>
                            <th class="text-center px-5 py-2">Target</th>
                            <th class="text-center px-5 py-2">Persentase</th>
                            <th class="px-5 py-2 w-40">Progress</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach($ringkasan['amalan'] as $item)
                        @php
                            $persen = $item['persen'];
                            $warna = $persen >= 80 ? 'bg-green-500' : ($persen >= 60 ? 'bg-yellow-500' : 'bg-red-500');
                            $teksWarna = $persen >= 80 ? 'text-green-700 dark:text-green-400 bg-green-50 dark:bg-green-900/30' : ($persen >= 60 ? 'text-yellow-700 dark:text-yellow-400 bg-yellow-50 dark:bg-yellow-900/30' : 'text-red-700 dark:text-red-400 bg-red-50 dark:bg-red-900/30');
                        @endphp
                        <tr>
                            <td class="px-5 py-3 text-gray-800 dark:text-gray-200 font-medium">{{ $item['label'] }}</td>
                            <td class="px-5 py-3 text-center text-gray-700 dark:text-gray-300">{{ $item['total_capai'] }}</td>
                            <td class="px-5 py-3 text-center text-gray-500 dark:text-gray-400">{{ $item['total_maks'] }}</td>
                            <td class="px-5 py-3 text-center">
                                <span class="inline-block px-2 py-0.5 rounded text-xs font-semibold {{ $teksWarna }}">
                                    {{ $persen }}%
                                </span>
                            </td>
                            <td class="px-5 py-3">
                                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                    <div class="{{ $warna }} h-2 rounded-full transition-all" style="width: {{ $persen }}%"></div>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Rincian Udzur --}}
            @if(! empty($ringkasan['udzur_detail']))
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                <div class="px-5 py-3 border-b border-gray-100 dark:border-gray-700">
                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">🏥 Rincian Udzur</p>
                </div>
                <div class="px-5 py-4 flex flex-wrap gap-3">
                    @foreach($ringkasan['udzur_detail'] as $jenis => $jumlah)
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-orange-50 dark:bg-orange-900/30 text-orange-700 dark:text-orange-300 text-xs font-medium">
                        {{ str_replace('_', ' ', $jenis) }}: <strong>{{ $jumlah }} hari</strong>
                    </span>
                    @endforeach
                </div>
            </div>
            @endif

        @endif
    </div>
</x-filament-panels::page>
