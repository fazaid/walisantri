@php
    $rapor = $modul['rapor'];
    $nilaiColor = fn($v) => match($v) {
        'A' => 'bg-green-100 dark:bg-green-900/40 text-green-800 dark:text-green-300',
        'B' => 'bg-blue-100 dark:bg-blue-900/40 text-blue-800 dark:text-blue-300',
        'C' => 'bg-yellow-100 dark:bg-yellow-900/40 text-yellow-800 dark:text-yellow-300',
        default => 'bg-red-100 dark:bg-red-900/40 text-red-800 dark:text-red-300',
    };
@endphp

@if(! $rapor)
    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100 dark:border-gray-700">
            <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">🌟 Rapor Karakter</p>
        </div>
        <p class="p-6 text-center text-sm text-gray-400">Belum ada rapor karakter pada periode ini.</p>
    </div>
@else
    {{-- Penilaian Adab --}}
    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
            <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">🕌 Penilaian Adab</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">
                Diinput {{ $rapor->tanggal_input?->translatedFormat('d M Y') ?? '—' }}
            </p>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-px bg-gray-100 dark:bg-gray-700">
            @foreach($modul['adab_fields'] as $field => $label)
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
            @foreach($modul['kepribadian_fields'] as $field => $label)
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
            @foreach(['A' => 'Sangat Baik', 'B' => 'Baik', 'C' => 'Cukup', 'D' => 'Perlu Bimbingan'] as $huruf => $arti)
            <span class="inline-flex items-center gap-2 text-xs">
                <span class="inline-flex items-center justify-center w-6 h-6 rounded font-bold text-xs {{ $nilaiColor($huruf) }}">{{ $huruf }}</span>
                <span class="text-gray-600 dark:text-gray-400">{{ $arti }}</span>
            </span>
            @endforeach
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
