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
            </div>
        </div>

        {{-- Hasil --}}
        @php
            $santri = $this->getSantri();
            $nilaiList = $this->getNilaiList();
            $rataRata = $this->getRataRata();
            $tahfidzRapor = $this->getTahfidzRapor();
        @endphp

        @if(! $santri)
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl p-8 text-center text-sm text-gray-400">
                Pilih santri untuk melihat rekap rapor.
            </div>
        @else
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl p-5 flex items-center justify-between">
                <div>
                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $santri->nama_lengkap }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        {{ $santri->kelas?->nama_kelas ?? '—' }} · {{ $tahunAjaran }} · {{ str_replace('_', ' ', $periode) }}
                    </p>
                </div>
                @if($rataRata !== null)
                    <div class="text-right">
                        <p class="text-xs text-gray-500 dark:text-gray-400">Rata-rata Akademik</p>
                        <p class="text-lg font-bold text-teal-700 dark:text-teal-400">{{ $rataRata }}</p>
                    </div>
                @endif
            </div>

            {{-- Nilai Akademik --}}
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                <div class="px-5 py-3 border-b border-gray-100 dark:border-gray-700">
                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">📚 Nilai Akademik</p>
                </div>
                @if($nilaiList->isEmpty())
                    <p class="p-6 text-center text-sm text-gray-400">Belum ada nilai akademik pada periode ini.</p>
                @else
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-800 text-xs text-gray-500 dark:text-gray-400 uppercase">
                            <tr>
                                <th class="text-left px-5 py-2">Mata Pelajaran</th>
                                <th class="text-left px-5 py-2">Nilai</th>
                                <th class="text-left px-5 py-2">Catatan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach($nilaiList as $item)
                                <tr>
                                    <td class="px-5 py-2.5 text-gray-800 dark:text-gray-200">{{ $item->mataPelajaran?->nama_mapel ?? '—' }}</td>
                                    <td class="px-5 py-2.5 font-semibold text-gray-900 dark:text-gray-100">{{ $item->nilai }}</td>
                                    <td class="px-5 py-2.5 text-gray-500 dark:text-gray-400">{{ $item->catatan ?: '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

            {{-- Nilai Tahfidz --}}
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                <div class="px-5 py-3 border-b border-gray-100 dark:border-gray-700">
                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">📖 Nilai Tahfidz</p>
                </div>
                @if(! $tahfidzRapor)
                    <p class="p-6 text-center text-sm text-gray-400">Belum ada nilai tahfidz pada periode ini.</p>
                @else
                    <div class="p-5 grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Hafalan</p>
                            <p class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ $tahfidzRapor->nilai_hafalan }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Tilawah</p>
                            <p class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ $tahfidzRapor->nilai_tilawah }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Makhraj</p>
                            <p class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ $tahfidzRapor->nilai_makhraj }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Tajwid</p>
                            <p class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ $tahfidzRapor->nilai_tajwid }}</p>
                        </div>
                    </div>
                    <div class="px-5 pb-5">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Rekomendasi Pembimbing</p>
                        <p class="text-sm text-gray-700 dark:text-gray-300">{{ $tahfidzRapor->rekomendasi_pembimbing }}</p>
                    </div>
                @endif
            </div>
        @endif
    </div>
</x-filament-panels::page>
