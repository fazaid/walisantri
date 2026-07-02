<x-filament-panels::page>
    <div class="space-y-6">

        {{-- Filter --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl p-5">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-4">Pilih Santri & Periode</h2>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
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
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Tahun Ajaran</label>
                    <select wire:model.live="tahunAjaran"
                            class="fi-input block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm">
                        @foreach($this->getTahunAjaranOptions() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Periode</label>
                    <select wire:model.live="periode"
                            class="fi-input block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm">
                        @foreach($this->getPeriodeOptions() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div @class(['invisible' => $periode !== 'Bulanan'])>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Bulan</label>
                    <select wire:model.live="bulan"
                            class="fi-input block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm">
                        @foreach($this->getBulanOptions() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- Hasil --}}
        @php
            $santri = $this->getSantri();
            $ujianList = $this->getUjianList();
            $setoranStats = $this->getSetoranStats();
            $totalJuzLulus = $this->getTotalJuzLulus();
        @endphp

        @if(! $santri)
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl p-8 text-center text-sm text-gray-400">
                Pilih santri untuk melihat rekap rapor tahfidz.
            </div>
        @else
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl p-5 flex items-center justify-between">
                <div>
                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $santri->nama_lengkap }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        {{ $santri->kelas?->nama_kelas ?? '—' }} · {{ $tahunAjaran }} ·
                        @if($periode === 'Bulanan' && $bulan)
                            {{ $this->getBulanOptions()[$bulan] ?? $bulan }}
                        @else
                            {{ str_replace('_', ' ', $periode) }}
                        @endif
                    </p>
                </div>
                <div class="text-right">
                    <p class="text-xs text-gray-500 dark:text-gray-400">Capaian Juz (Lulus)</p>
                    <p class="text-lg font-bold text-teal-700 dark:text-teal-400">{{ $totalJuzLulus }} Juz</p>
                </div>
            </div>

            {{-- Ringkasan Setoran --}}
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                <div class="px-5 py-3 border-b border-gray-100 dark:border-gray-700">
                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">📈 Ringkasan Setoran Periode Ini</p>
                </div>
                @if($setoranStats['total_setoran'] === 0)
                    <p class="p-6 text-center text-sm text-gray-400">Belum ada setoran pada periode ini.</p>
                @else
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 p-5">
                        <div class="text-center">
                            <p class="text-xs text-gray-400 mb-1">Total Setoran</p>
                            <p class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ $setoranStats['total_setoran'] }}</p>
                        </div>
                        <div class="text-center">
                            <p class="text-xs text-gray-400 mb-1">Total Halaman</p>
                            <p class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ $setoranStats['total_halaman'] }}</p>
                        </div>
                        <div class="text-center">
                            <p class="text-xs text-gray-400 mb-1">Hari Aktif</p>
                            <p class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ $setoranStats['hari_aktif'] }}</p>
                        </div>
                        @foreach($setoranStats['per_tipe'] as $tipe => $data)
                        <div class="text-center">
                            <p class="text-xs text-gray-400 mb-1">{{ $tipe }}</p>
                            <p class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ $data['jumlah'] }}</p>
                            <p class="text-[11px] text-gray-400">{{ $data['halaman'] }} hal.</p>
                        </div>
                        @endforeach
                    </div>

                    @if($setoranStats['nilai_distribusi']->isNotEmpty())
                    <div class="px-5 pb-4 space-y-2">
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400">Distribusi Nilai Kelancaran</p>
                        @foreach(['Mumtaz', 'Jayyid Jiddan', 'Jayyid', 'Maqbul'] as $label)
                        @php
                            $cnt = $setoranStats['nilai_distribusi'][$label] ?? 0;
                            $pct = $cnt > 0 ? round($cnt / $setoranStats['total_setoran'] * 100) : 0;
                        @endphp
                        @if($cnt > 0)
                        <div class="flex items-center gap-3">
                            <span class="text-xs text-gray-600 dark:text-gray-300 w-28 flex-shrink-0">{{ $label }}</span>
                            <div class="flex-1 h-1.5 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden">
                                <div class="h-full bg-teal-500 rounded-full" style="width: {{ $pct }}%"></div>
                            </div>
                            <span class="text-xs font-medium text-gray-700 dark:text-gray-200 w-20 text-right flex-shrink-0">{{ $cnt }} ({{ $pct }}%)</span>
                        </div>
                        @endif
                        @endforeach
                    </div>
                    @endif

                    <div class="px-5 pb-5 flex flex-wrap gap-2">
                        @foreach($setoranStats['surah_list'] as $surah)
                            <span class="text-xs px-2.5 py-1 rounded-full bg-teal-50 text-teal-700 dark:bg-teal-900/30 dark:text-teal-300">{{ $surah }}</span>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Hasil Ujian Tahfidz --}}
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                <div class="px-5 py-3 border-b border-gray-100 dark:border-gray-700">
                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">📖 Hasil Ujian Tahfidz</p>
                </div>
                @if($ujianList->isEmpty())
                    <p class="p-6 text-center text-sm text-gray-400">Belum ada ujian tahfidz pada periode ini.</p>
                @else
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-800 text-xs text-gray-500 dark:text-gray-400 uppercase">
                            <tr>
                                <th class="text-left px-5 py-2">Tanggal</th>
                                <th class="text-left px-5 py-2">Target Juz</th>
                                <th class="text-left px-5 py-2">Status</th>
                                <th class="text-left px-5 py-2">Hafalan</th>
                                <th class="text-left px-5 py-2">Tilawah</th>
                                <th class="text-left px-5 py-2">Makhraj</th>
                                <th class="text-left px-5 py-2">Tajwid</th>
                                <th class="text-left px-5 py-2">Penguji</th>
                                <th class="text-left px-5 py-2">Rekomendasi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach($ujianList as $ujian)
                                <tr>
                                    <td class="px-5 py-2.5 text-gray-800 dark:text-gray-200">{{ $ujian->tanggal_ujian?->translatedFormat('d M Y') ?? '—' }}</td>
                                    <td class="px-5 py-2.5 text-gray-800 dark:text-gray-200">{{ $ujian->target_juz ? "{$ujian->target_juz} Juz" : '—' }}</td>
                                    <td class="px-5 py-2.5">
                                        <span class="{{ $ujian->status_kelulusan === 'Lulus' ? 'text-green-700 dark:text-green-400' : 'text-amber-700 dark:text-amber-400' }} font-semibold">
                                            {{ $ujian->status_kelulusan ?? '—' }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-2.5 font-semibold text-gray-900 dark:text-gray-100">{{ $ujian->nilai_hafalan }}</td>
                                    <td class="px-5 py-2.5 text-gray-800 dark:text-gray-200">{{ $ujian->nilai_tilawah }}</td>
                                    <td class="px-5 py-2.5 text-gray-800 dark:text-gray-200">{{ $ujian->nilai_makhraj }}</td>
                                    <td class="px-5 py-2.5 text-gray-800 dark:text-gray-200">{{ $ujian->nilai_tajwid }}</td>
                                    <td class="px-5 py-2.5 text-gray-600 dark:text-gray-300">{{ $ujian->penguji?->name ?? '—' }}</td>
                                    <td class="px-5 py-2.5 text-gray-500 dark:text-gray-400">{{ $ujian->rekomendasi_pembimbing ?: '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        @endif
    </div>
</x-filament-panels::page>
