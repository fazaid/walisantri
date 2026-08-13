{{-- Nilai Akademik --}}
<div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
    <div class="px-5 py-3 border-b border-gray-100 dark:border-gray-700">
        <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">📚 Nilai Akademik</p>
    </div>
    @if($modul['nilai']->isEmpty())
        <p class="p-6 text-center text-sm text-gray-400">Belum ada nilai akademik pada periode ini.</p>
    @else
        <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800 text-xs text-gray-500 dark:text-gray-400 uppercase">
                <tr>
                    <th class="text-left px-5 py-2">Mata Pelajaran</th>
                    <th class="text-left px-5 py-2">Nilai</th>
                    <th class="text-left px-5 py-2">Catatan</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @foreach($modul['nilai'] as $item)
                    <tr>
                        <td class="px-5 py-2.5 text-gray-800 dark:text-gray-200">{{ $item->mataPelajaran?->nama_mapel ?? '—' }}</td>
                        <td class="px-5 py-2.5 font-semibold text-gray-900 dark:text-gray-100">{{ $item->nilai }}</td>
                        <td class="px-5 py-2.5 text-gray-500 dark:text-gray-400">{{ $item->catatan ?: '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        </div>
    @endif
</div>

{{-- Ekskul Aktif --}}
<div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
    <div class="px-5 py-3 border-b border-gray-100 dark:border-gray-700">
        <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">🎯 Ekstrakurikuler Aktif</p>
    </div>
    @if($modul['ekskul']->isEmpty())
        <p class="p-6 text-center text-sm text-gray-400">Belum ada ekskul yang diikuti.</p>
    @else
        <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800 text-xs text-gray-500 dark:text-gray-400 uppercase">
                <tr>
                    <th class="text-left px-5 py-2">Ekskul</th>
                    <th class="text-left px-5 py-2">Level</th>
                    <th class="text-left px-5 py-2">Mulai</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @foreach($modul['ekskul'] as $item)
                    <tr>
                        <td class="px-5 py-2.5 text-gray-800 dark:text-gray-200">{{ $item->ekskulMaster?->nama ?? '—' }}</td>
                        <td class="px-5 py-2.5 text-gray-700 dark:text-gray-300">{{ $item->labelLevel() }}</td>
                        <td class="px-5 py-2.5 text-gray-500 dark:text-gray-400">{{ $item->tanggal_mulai?->format('d M Y') ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        </div>
    @endif
</div>
