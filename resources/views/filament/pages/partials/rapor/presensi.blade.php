<div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
    <div class="px-5 py-3 border-b border-gray-100 dark:border-gray-700">
        <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">🗓️ Presensi</p>
    </div>

    @if(! $modul['ada_data'])
        <p class="p-6 text-center text-sm text-gray-400">Tidak ada catatan presensi pada periode ini.</p>
    @else
        @php
            $persen = $modul['persen_kehadiran'];
            $warnaPersen = $persen >= 90 ? 'text-green-600' : ($persen >= 75 ? 'text-yellow-600' : 'text-red-600');
        @endphp

        <div class="grid grid-cols-2 md:grid-cols-4 gap-px bg-gray-100 dark:bg-gray-700">
            <div class="bg-white dark:bg-gray-900 p-4 text-center">
                <p class="text-2xl font-bold {{ $warnaPersen }}">{{ $persen }}%</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Kehadiran</p>
            </div>
            <div class="bg-white dark:bg-gray-900 p-4 text-center">
                <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $modul['hadir_efektif'] }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Hari Hadir</p>
            </div>
            <div class="bg-white dark:bg-gray-900 p-4 text-center">
                <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $modul['hari_efektif'] }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Hari Efektif</p>
            </div>
            <div class="bg-white dark:bg-gray-900 p-4 text-center">
                <p class="text-2xl font-bold text-orange-500">{{ $modul['tanpa_keterangan'] }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Tanpa Keterangan</p>
            </div>
        </div>

        <div class="overflow-x-auto border-t border-gray-100 dark:border-gray-700">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800 text-xs text-gray-500 dark:text-gray-400 uppercase">
                    <tr>
                        <th class="text-left px-5 py-2">Status</th>
                        <th class="text-center px-5 py-2">Jumlah Hari</th>
                        <th class="text-center px-5 py-2">Porsi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($modul['status'] as $item)
                    <tr>
                        <td class="px-5 py-2 text-gray-700 dark:text-gray-300">{{ $item['label'] }}</td>
                        <td class="px-5 py-2 text-center text-gray-900 dark:text-gray-100">{{ $item['jumlah'] }}</td>
                        <td class="px-5 py-2 text-center text-gray-500 dark:text-gray-400">
                            {{ $modul['hari_efektif'] > 0 ? (int) round($item['jumlah'] / $modul['hari_efektif'] * 100) : 0 }}%
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <p class="px-5 py-3 text-xs text-gray-500 dark:text-gray-400 border-t border-gray-100 dark:border-gray-700 leading-relaxed">
            Periode dihitung {{ \Illuminate\Support\Carbon::parse($modul['awal'])->translatedFormat('d M Y') }}
            – {{ \Illuminate\Support\Carbon::parse($modul['akhir'])->translatedFormat('d M Y') }};
            batas atasnya dipotong ke hari ini supaya sisa periode yang belum berjalan tidak masuk penyebut.
            <strong>Tanpa Keterangan</strong> adalah hari efektif yang presensinya belum tercatat — bukan
            ketidakhadiran yang dinyatakan.
        </p>
    @endif
</div>
