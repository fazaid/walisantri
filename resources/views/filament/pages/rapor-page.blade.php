<x-filament-panels::page>
    <div class="space-y-6">

        {{-- Filter --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl p-5 space-y-4">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Pilih Santri & Periode</h2>

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

            {{-- Pilihan modul --}}
            <div class="pt-4 border-t border-gray-100 dark:border-gray-700">
                <div class="flex items-center justify-between mb-2">
                    <label class="text-xs font-medium text-gray-600 dark:text-gray-400">Rapor yang ditampilkan</label>
                    {{-- Tanpa satu pun modul, kedua tombol ini tidak punya apa pun untuk dipilih. --}}
                    @if(! empty($this->getModulOptions()))
                        <div class="flex items-center gap-3 text-xs">
                            <button type="button" wire:click="pilihSemuaModul" class="text-teal-600 dark:text-teal-400 hover:underline">Pilih semua</button>
                            <button type="button" wire:click="kosongkanModul" class="text-gray-500 dark:text-gray-400 hover:underline">Kosongkan</button>
                        </div>
                    @endif
                </div>
                <div class="flex flex-wrap gap-2">
                    @foreach($this->getModulOptions() as $key => $label)
                        <label class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 text-sm text-gray-700 dark:text-gray-300 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800">
                            <input type="checkbox" value="{{ $key }}" wire:model.live="modul"
                                   class="rounded border-gray-300 dark:border-gray-600 text-teal-600 focus:ring-teal-500">
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
            </div>
        </div>

        @php
            $santri = $this->getSantri();
            $data   = $this->getData();
        @endphp

        {{-- Cabang ini WAJIB paling atas. Saat seluruh modul rapor dimatikan, tidak
             ada santri yang bisa dipilih untuk memperbaikinya dan tidak ada centang
             yang bisa dicentang — dua pesan di bawah sama-sama menyuruh admin
             melakukan sesuatu yang tidak akan menolong. --}}
        @if(empty($this->getModulOptions()))
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl p-8 text-center text-sm text-gray-400">
                Semua modul rapor sedang dimatikan untuk pesantren ini. Nyalakan lewat menu Manajemen → Modul.
            </div>
        @elseif(! $santri)
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl p-8 text-center text-sm text-gray-400">
                Pilih santri untuk melihat rapor.
            </div>
        @elseif(empty($this->modul))
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl p-8 text-center text-sm text-gray-400">
                Centang minimal satu rapor untuk ditampilkan.
            </div>
        @else
            {{-- Identitas santri --}}
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl p-5 flex items-center justify-between">
                <div>
                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $santri->nama_lengkap }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        {{ $santri->kelas?->nama_kelas ?? '—' }} · TA {{ $tahunAjaran }} · {{ $this->getPeriodeLabel() }}
                    </p>
                </div>
                <div class="flex items-center gap-6 text-right">
                    @if(($data['akademik']['rata_rata'] ?? null) !== null)
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Rata-rata Akademik</p>
                            <p class="text-lg font-bold text-teal-700 dark:text-teal-400">{{ $data['akademik']['rata_rata'] }}</p>
                        </div>
                    @endif
                    @if(isset($data['tahfidz']))
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Capaian Juz (Lulus)</p>
                            <p class="text-lg font-bold text-teal-700 dark:text-teal-400">{{ $data['tahfidz']['total_juz_lulus'] }} Juz</p>
                        </div>
                    @endif
                </div>
            </div>

            @foreach($this->getModulOptions() as $key => $label)
                @if(isset($data[$key]))
                    @include('filament.pages.partials.rapor.' . $key, ['modul' => $data[$key]])
                @endif
            @endforeach
        @endif
    </div>
</x-filament-panels::page>
