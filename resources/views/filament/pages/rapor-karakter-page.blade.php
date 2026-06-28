<x-filament-panels::page>
    <div class="space-y-6">

        {{-- Filter --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl p-5">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-4">Pilih Santri & Periode</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
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
                            <option value="{{ $value }}" @selected($value === $tahunAjaran)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Periode</label>
                    <select wire:model.live="periode"
                            class="fi-input block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm">
                        @foreach($this->getPeriodeOptions() as $value => $label)
                            <option value="{{ $value }}" @selected($value === $periode)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                @if($periode === 'Bulanan')
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Bulan</label>
                    <select wire:model.live="bulan"
                            class="fi-input block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm">
                        @foreach($this->getBulanOptions() as $value => $label)
                            <option value="{{ $value }}" @selected($value === $bulan)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
            </div>
        </div>

        @php
            $santri = $this->getSantri();
            $rapor  = $santriId ? $this->getRapor() : null;
            $periodeLabel = $this->getPeriodeOptions()[$periode] ?? $periode;
            $bulanLabel   = $periode === 'Bulanan' ? ($this->getBulanOptions()[$bulan] ?? $bulan) : null;

            $nilaiColor = fn($v) => match($v) {
                'A' => 'bg-green-100 dark:bg-green-900/40 text-green-800 dark:text-green-300',
                'B' => 'bg-blue-100 dark:bg-blue-900/40 text-blue-800 dark:text-blue-300',
                'C' => 'bg-yellow-100 dark:bg-yellow-900/40 text-yellow-800 dark:text-yellow-300',
                default => 'bg-red-100 dark:bg-red-900/40 text-red-800 dark:text-red-300',
            };
        @endphp

        @if(! $santri)
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl p-8 text-center text-sm text-gray-400">
                Pilih santri untuk melihat rapor karakter.
            </div>
        @elseif(! $rapor)
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl p-8 text-center text-sm text-gray-400">
                Belum ada rapor karakter untuk <strong>{{ $santri->nama_lengkap }}</strong>
                pada periode <strong>{{ $periodeLabel }}{{ $bulanLabel ? ' ' . $bulanLabel : '' }}</strong>
                tahun ajaran <strong>{{ $tahunAjaran }}</strong>.
            </div>
        @else

            {{-- Info Santri --}}
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl p-5 flex items-center justify-between">
                <div>
                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $santri->nama_lengkap }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        {{ $santri->kelas?->nama_kelas ?? '—' }}
                        · {{ $periodeLabel }}{{ $bulanLabel ? ' ' . $bulanLabel : '' }}
                        · TA {{ $tahunAjaran }}
                    </p>
                </div>
                <div class="text-right">
                    <p class="text-xs text-gray-500 dark:text-gray-400">Tanggal Input</p>
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ $rapor->tanggal_input?->translatedFormat('d M Y') ?? '—' }}
                    </p>
                </div>
            </div>

            {{-- Penilaian Adab --}}
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                <div class="px-5 py-3 border-b border-gray-100 dark:border-gray-700">
                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">🕌 Penilaian Adab</p>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-px bg-gray-100 dark:bg-gray-700">
                    @foreach($this->getAdabFields() as $field => $label)
                    <div class="bg-white dark:bg-gray-900 px-5 py-4 flex items-center justify-between">
                        <span class="text-xs text-gray-600 dark:text-gray-400">{{ $label }}</span>
                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-sm font-bold {{ $nilaiColor($rapor->$field) }}">
                            {{ $rapor->$field }}
                        </span>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Penilaian Kepribadian --}}
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                <div class="px-5 py-3 border-b border-gray-100 dark:border-gray-700">
                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">🌟 Penilaian Kepribadian</p>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-px bg-gray-100 dark:bg-gray-700">
                    @foreach($this->getKepribadianFields() as $field => $label)
                    <div class="bg-white dark:bg-gray-900 px-5 py-4 flex items-center justify-between">
                        <span class="text-xs text-gray-600 dark:text-gray-400">{{ $label }}</span>
                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-sm font-bold {{ $nilaiColor($rapor->$field) }}">
                            {{ $rapor->$field }}
                        </span>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Keterangan Nilai --}}
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl p-5">
                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-3">Keterangan Nilai</p>
                <div class="flex flex-wrap gap-3">
                    <span class="inline-flex items-center gap-2 text-xs">
                        <span class="inline-flex items-center justify-center w-6 h-6 rounded bg-green-100 dark:bg-green-900/40 text-green-800 dark:text-green-300 font-bold text-xs">A</span>
                        <span class="text-gray-600 dark:text-gray-400">Sangat Baik</span>
                    </span>
                    <span class="inline-flex items-center gap-2 text-xs">
                        <span class="inline-flex items-center justify-center w-6 h-6 rounded bg-blue-100 dark:bg-blue-900/40 text-blue-800 dark:text-blue-300 font-bold text-xs">B</span>
                        <span class="text-gray-600 dark:text-gray-400">Baik</span>
                    </span>
                    <span class="inline-flex items-center gap-2 text-xs">
                        <span class="inline-flex items-center justify-center w-6 h-6 rounded bg-yellow-100 dark:bg-yellow-900/40 text-yellow-800 dark:text-yellow-300 font-bold text-xs">C</span>
                        <span class="text-gray-600 dark:text-gray-400">Cukup</span>
                    </span>
                    <span class="inline-flex items-center gap-2 text-xs">
                        <span class="inline-flex items-center justify-center w-6 h-6 rounded bg-red-100 dark:bg-red-900/40 text-red-800 dark:text-red-300 font-bold text-xs">D</span>
                        <span class="text-gray-600 dark:text-gray-400">Perlu Bimbingan</span>
                    </span>
                </div>
            </div>

            {{-- Log Kasus Khusus --}}
            @if($rapor->log_kasus_khusus)
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl p-5">
                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-2">📝 Log Kasus Khusus</p>
                <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ $rapor->log_kasus_khusus }}</p>
            </div>
            @endif

        @endif
    </div>
</x-filament-panels::page>
